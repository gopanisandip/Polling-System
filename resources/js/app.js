import './bootstrap';

import { io } from 'socket.io-client';

// Development: connect directly to localhost:6001
const socketUrl = import.meta.env.VITE_SOCKET_IO_URL
    || (window.location.protocol === 'https:'
        ? window.location.origin
        : `http://${import.meta.env.VITE_SOCKET_IO_HOST || 'localhost'}:${import.meta.env.VITE_SOCKET_IO_PORT || 6001}`);

window.socketIO = io(socketUrl, {
    path: '/socket.io',
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
