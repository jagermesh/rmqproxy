<?php

/**
 * Project:     Bright framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Bright Core
 */

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BrRabbitMQ {

  private $connection;
  private $channel;
  private $params;

  function __construct($params = array()) {

    $this->params = $params;

  }

  function connect() {

    if (!$this->connection) {
      $this->connection = new AMQPConnection( @$this->params['host'] ? @$this->params['host'] : 'localhost'
                                            , @$this->params['port'] ? @$this->params['port'] : 5672
                                            , @$this->params['login'] ? @$this->params['login'] : 'guest'
                                            , @$this->params['password'] ? @$this->params['password'] : 'guest'
                                            , @$this->params['vhost'] ? @$this->params['vhost'] : '/'
                                            );
    }
    if (!$this->channel) {
      $this->channel = $this->connection->channel();
    }

    return $this;

  }

  function createExchange($exchangeName, $type = 'direct', $passive = false, $durable = false, $auto_delete = false) {

    $this->connect();
    $this->channel->exchange_declare($exchangeName, $type, $passive, $durable, $auto_delete);

    return $this;

  }

  function createQueue($queueName, $a = false, $b = false, $c = true, $d = false) {

    $this->connect();
    $this->channel->queue_declare($queueName, $a, $b, $c, $d);

    return $this;

  }

  function sendMessage($exchangeName, $message, $routingKey = null) {

    $this->connect();
    $message = json_encode($message);
    $msg = new AMQPMessage( $message
                          , array( 'content_type' => 'application/json'
                                 , 'delivery_mode' => 2
                                 ));
    $this->channel->basic_publish($msg, $exchangeName, $routingKey);

    return $this;

  }

  function receiveOneMessage($exchangeName, $bindingKey, $callback, $params = array()) {

    if (is_callable($bindingKey)) {
      $params = $callback;
      $callback = $bindingKey;
      $bindingKey = null;
    }

    $this->connect();
    $this->channel->queue_bind($queueName, $exchange);
    if ($queueName = @$params['queueName']) {

    } else {
      list($queueName, ,) = $this->channel->queue_declare('', false, false, true, false);
    }
    $consumerTag = @$params['consumerTag'];
    $this->channel->queue_bind($queueName, $exchangeName, $bindingKey);

    $received = false;

    $this->channel->basic_consume($queueName, $consumerTag, false, false, false, false, function($msg) use ($callback, &$received) {
      $callback($msg->body);
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    });

    while (!$received) {
      $this->channel->wait();
    }

    return $this;

  }

  function receiveMessages($exchangeName, $bindingKey, $callback, $params = array()) {

    if (is_callable($bindingKey)) {
      $params = $callback;
      $callback = $bindingKey;
      $bindingKey = null;
    }

    $this->connect();
    if ($queueName = @$params['queueName']) {

    } else {
      list($queueName, ,) = $this->channel->queue_declare('', false, false, true, false);
    }
    $consumerTag = @$params['consumerTag'];
    $this->channel->queue_bind($queueName, $exchangeName, $bindingKey);

    $aborted = false;

    $this->channel->basic_consume($queueName, $consumerTag, false, false, false, false, function($msg) use ($callback, &$aborted) {
      $callback($msg->body, $aborted);
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    });

    while (!$aborted) {
      $this->channel->wait();
    }

    return $this;

  }

  function close() {

    if ($this->channel) {
      $this->channel->close();
      $this->connection->close();
      $this->channel = null;
      $this->connection = null;
    }

    return $this;

  }

}
