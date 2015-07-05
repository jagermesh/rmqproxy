/* global process */

var config = require('./config.js');
var io = require('socket.io');
var rabbitjs = require('rabbit.js');

var context = rabbitjs.createContext('amqp://' + config.rmq.host + ':' + config.rmq.port);
context.on('ready', function() {

  var socketServer = io.listen(config.port, { log: false });

  console.log('Ready');

  socketServer.on('connection', function(socket) {

    var subs = [];

    socket.on('RMQ/Subscribe', function(data) {

      var uid = data.uid;

      var sub = context.socket('SUB', { routing: 'topic' });
      sub.connect(data.exchange, data.topic, function() {
        // console.log('Subscribe (' + uid + '): ' + JSON.stringify(data));
        socket.emit('RMQ/Subscribed', { uid: uid });
        sub.setEncoding('utf8');
        subs.push(sub);
        sub.on('data', function(data) {
          // console.log('Data (' + uid + '): ' + data);
          socket.emit('RMQ/Message', { uid: uid, data: JSON.parse(data) });
        });

      });

    });

    socket.on('RMQ/SendMessage', function(data) {

      // console.log('SendMessage: ' + JSON.stringify(data));
      var pub = context.socket('PUB', { routing: 'topic' });
      pub.connect(data.exchange, function() {
        pub.publish(data.topic, JSON.stringify(data.data));
      });

    });

    socket.on('error', function(error) {
      console.log(error);
    });

    socket.on('disconnect', function() {

      // console.log('disconnect');
      for(var i = 0; i < subs.length; i++) {
        subs[i].close();
      }
      subs = [];

    });

  });

});

