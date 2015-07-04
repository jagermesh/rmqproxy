## Client-Server proxy for RabbitMQ using NODE.JS

### Installation

~~~
npm install
~~~

### Configuration

Rename `/config.js.example` to `/config.js` and change settings

### Usage

#### JavaScript

You need to link some scripts

~~~
<script src="/js/socket.io.js"></script>
<script src="/js/br.rabbitMQ.js"></script>
~~~

Then you can use proxy as easy as in following example

~~~
$(document).ready(function() {

  var rmq = br.rabbitMQ({port: 8080});
  rmq.subscribe('exchange', '*.task', function(data) {
    console.log(data);
  });
  rmq.subscribe('exchange', '*.person', function(data) {
    console.log(data);
  });

});
~~~

#### Methods

- `subscribe(exchangeName, topic, callback)`

### PHP

You need to install PHP AMPQ library (https://github.com/videlalvaro/php-amqplib). For example via Composer

~~~
{
   "require": {
      "videlalvaro/php-amqplib": "2.5.*"
   }
}
~~~

Example

~~~
require_once('/vendor/autoload.php');
require_once('/php/br.rabbitMQ.php');

$rmq = new BrRabbitMQ(array('host' => 'localhost', 'port' => 5672));
$rmq->createExchange('exchange', 'topic');
$rmq->sendMessage('exchange', 'test', 'insert.task');
~~~

#### Methods

- `constructor` accept array with following settings: host, port, login, password, vhost
- `createExchange(exchangeName, routingType)`
- `sendMessage(exchangeName, data, topic)`
