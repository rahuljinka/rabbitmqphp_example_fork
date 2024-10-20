<?php

require_once('get_host_info.inc');

class rabbitMQServer
{
    private $machine = "";
    public $BROKER_HOST;
    private $BROKER_PORT;
    private $USER;
    private $PASSWORD;
    private $VHOST;
    private $exchange;
    private $queue;
    private $routing_key = '*';
    private $exchange_type = "topic";
    private $auto_delete = false;

    public function __construct($machine, $server = "rabbitMQ")
    {
        $this->machine = getHostInfo(array($machine));
        $this->BROKER_HOST = $this->machine[$server]["BROKER_HOST"];
        $this->BROKER_PORT = $this->machine[$server]["BROKER_PORT"];
        $this->USER = $this->machine[$server]["USER"];
        $this->PASSWORD = $this->machine[$server]["PASSWORD"];
        $this->VHOST = $this->machine[$server]["VHOST"];

        if (isset($this->machine[$server]["EXCHANGE_TYPE"])) {
            $this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
        }
        if (isset($this->machine[$server]["AUTO_DELETE"])) {
            $this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
        }

        $this->exchange = $this->machine[$server]["EXCHANGE"];
        $this->queue = $this->machine[$server]["QUEUE"];
    }

    public function process_message($msg)
    {
        $this->conn_queue->ack($msg->getDeliveryTag());
        try {
            if ($msg->getReplyTo()) {
                $payload = json_decode($msg->getBody(), true);
                $response = call_user_func($this->callback, $payload);

                $this->send_response($response, $msg->getReplyTo(), $msg->getCorrelationId());
                return;
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL;
        }
    }

    private function send_response($response, $reply_to, $correlation_id)
    {
        $channel = $this->setup_channel();
        $exchange = new AMQPExchange($channel);
        $exchange->setName($this->exchange);
        $exchange->setType($this->exchange_type);

        $exchange->publish(
            json_encode($response),
            $this->routing_key . ".response",
            AMQP_NOPARAM,
            ['correlation_id' => $correlation_id]
        );
    }

    private function setup_channel()
    {
        $params = [
            'host' => $this->BROKER_HOST,
            'port' => $this->BROKER_PORT,
            'login' => $this->USER,
            'password' => $this->PASSWORD,
            'vhost' => $this->VHOST,
        ];

        $conn = new AMQPConnection($params);
        $conn->connect();
        return new AMQPChannel($conn);
    }

    public function process_requests($callback)
    {
        try {
            $this->callback = $callback;
            $channel = $this->setup_channel();
            $exchange = new AMQPExchange($channel);
            $exchange->setName($this->exchange);
            $exchange->setType($this->exchange_type);

            $this->conn_queue = new AMQPQueue($channel);
            $this->conn_queue->setName($this->queue);
            $this->conn_queue->bind($exchange->getName(), $this->routing_key);

            $this->conn_queue->consume([$this, 'process_message']);
        } catch (Exception $e) {
            die("Failed to process requests: " . $e->getMessage() . "\n");
        }
    }
}

class rabbitMQClient
{
    // Add the missing properties
    private $conn_queue;
    private $machine = "";
    public $BROKER_HOST;
    private $BROKER_PORT;
    private $USER;
    private $PASSWORD;
    private $VHOST;
    private $exchange;
    private $queue;
    private $routing_key = '*';
    private $response_queue = array();
    private $exchange_type = "topic";

    function __construct($machine, $server = "rabbitMQ")
    {
        $this->machine = getHostInfo(array($machine));
        $this->BROKER_HOST = $this->machine[$server]["BROKER_HOST"];
        $this->BROKER_PORT = $this->machine[$server]["BROKER_PORT"];
        $this->USER = $this->machine[$server]["USER"];
        $this->PASSWORD = $this->machine[$server]["PASSWORD"];
        $this->VHOST = $this->machine[$server]["VHOST"];
        if (isset($this->machine[$server]["EXCHANGE_TYPE"])) {
            $this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
        }
        $this->exchange = $this->machine[$server]["EXCHANGE"];
        $this->queue = $this->machine[$server]["QUEUE"];
    }

    // Add the process_response callback function
    function process_response($response)
    {
        $uid = $response->getCorrelationId();
        if (!isset($this->response_queue[$uid])) {
            echo "Unknown response ID\n";
            return true;
        }

        // Acknowledge the response
        $this->conn_queue->ack($response->getDeliveryTag());

        $body = $response->getBody();
        $payload = json_decode($body, true);

        if (!isset($payload)) {
            $payload = "[Empty response]";
        }
        $this->response_queue[$uid] = $payload;
        return false;
    }

    function send_request($message)
    {
        $uid = uniqid();
        $json_message = json_encode($message);

        try {
            $params = array(
                'host' => $this->BROKER_HOST,
                'port' => $this->BROKER_PORT,
                'login' => $this->USER,
                'password' => $this->PASSWORD,
                'vhost' => $this->VHOST,
            );

            $conn = new AMQPConnection($params);
            $conn->connect();
            $channel = new AMQPChannel($conn);

            $exchange = new AMQPExchange($channel);
            $exchange->setName($this->exchange);
            $exchange->setType($this->exchange_type);

            // Setup callback queue
            $callback_queue = new AMQPQueue($channel);
            $callback_queue->setName($this->queue . "_response");
            $callback_queue->declareQueue();
            $callback_queue->bind($exchange->getName(), $this->routing_key . ".response");

            // Setup the main queue
            $this->conn_queue = new AMQPQueue($channel);
            $this->conn_queue->setName($this->queue);
            $this->conn_queue->bind($exchange->getName(), $this->routing_key);

            // Publish the message
            $exchange->publish($json_message, $this->routing_key, AMQP_NOPARAM, array(
                'reply_to' => $callback_queue->getName(),
                'correlation_id' => $uid,
            ));

            $this->response_queue[$uid] = "waiting";
            $callback_queue->consume(array($this, 'process_response'));

            $response = $this->response_queue[$uid];
            unset($this->response_queue[$uid]);
            return $response;
        } catch (Exception $e) {
            die("Failed to send message to exchange: " . $e->getMessage() . "\n");
        }
    }
}

?>

