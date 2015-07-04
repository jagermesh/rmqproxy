/* global process */

var config = require('./config.js');
var io = require('socket.io');
var rabbitjs = require('rabbit.js');

var context = rabbitjs.createContext('amqp://' + config.rmq.host + ':' + config.rmq.port);
context.on('ready', function() {

  console.log('Ready');

  var socketServer = io.listen(config.port, { log: false });

  socketServer.on('connection', function(socket) {

    var subs = [];

    socket.on('RMQ/Subscribe', function(data) {

      var sub = context.socket('SUB', { routing: 'topic' });
      var uid = data.uid;

      sub.connect(data.exchange, data.topic, function() {
        socket.emit('RMQ/Subscribed', { uid: uid });
        subs.push(sub);
        sub.setEncoding('utf8');
        sub.on('data', function(data) {
          data = JSON.parse(data);
          socket.emit('RMQ/Message', { uid: uid, data: data });
        });
      });

    });

    socket.on('disconnect', function() {

      for(var i = 0; i < subs.length; i++) {
        subs[i].close();
      }
      subs = [];

    });

  });

});

