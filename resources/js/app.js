import './bootstrap';

import { io } from 'socket.io-client';

const socketHost = import.meta.env.VITE_SOCKET_IO_HOST || 'localhost';
const socketPort = import.meta.env.VITE_SOCKET_IO_PORT || 6001;

window.socketIO = io(`http://${socketHost}:${socketPort}`, {
    transports: ['websocket', 'polling'],
    reconnection: true,
    reconnectionAttempts: 10,
    reconnectionDelay: 1000,
    reconnectionDelayMax: 5000,
});

window.socketIO.on('connect', function () {
    console.log('Connected:', window.socketIO.id);
});

window.socketIO.on('disconnect', function (reason) {
    console.log('Disconnected:', reason);
});

window.socketIO.on('connect_error', function (err) {
    console.warn('Connection error:', err.message);
});
