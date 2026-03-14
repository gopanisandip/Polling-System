import 'dotenv/config';
import { createServer } from 'http';
import { Server } from 'socket.io';
import Redis from 'ioredis';


const PORT = parseInt(process.env.SOCKET_IO_PORT || '6001', 10);
const REDIS_HOST = process.env.REDIS_HOST || '127.0.0.1';
const REDIS_PORT = parseInt(process.env.REDIS_PORT || '6379', 10);
const REDIS_PASSWORD = process.env.REDIS_PASSWORD === 'null' ? undefined : process.env.REDIS_PASSWORD;
const REDIS_DB = parseInt(process.env.REDIS_DB || '0', 10);

const CLUSTER_ENABLED = true;
const CLUSTER_NODES_RAW = '';

const APP_NAME = (process.env.APP_NAME || 'laravel').toLowerCase().replace(/[^a-z0-9]+/g, '-');
const REDIS_PREFIX = process.env.REDIS_PREFIX || `${APP_NAME}-database-`;


function createRedisClient(label) {
    let client;

    if (CLUSTER_ENABLED && CLUSTER_NODES_RAW) {
        const nodes = CLUSTER_NODES_RAW.split(',').map(node => {
            const parts = node.trim().split(':');
            return { host: parts[0], port: parseInt(parts[1] || '6379', 10) };
        });

        client = new Redis.Cluster(nodes, {
            redisOptions: {
                password: REDIS_PASSWORD,
                db: REDIS_DB,
            },
            clusterRetryStrategy(times) {
                const delay = Math.min(times * 100, 3000);
                return delay;
            },
            scaleReads: 'slave',
            enableReadyCheck: true,
            maxRedirections: 16,
            retryDelayOnFailover: 300,
            retryDelayOnClusterDown: 1000,
            retryDelayOnTryAgain: 300,
        });

        console.log(`[${label}] Connecting to Redis Cluster: ${nodes.map(n => n.host + ':' + n.port).join(', ')}`);

    } else {
        client = new Redis({
            host: REDIS_HOST,
            port: REDIS_PORT,
            password: REDIS_PASSWORD,
            db: REDIS_DB,
            retryStrategy(times) {
                const delay = Math.min(times * 50, 2000);
                return delay;
            },
            maxRetriesPerRequest: 3,
        });

        console.log(`[${label}] Connecting to Redis standalone: ${REDIS_HOST}:${REDIS_PORT}`);
    }

    client.on('connect', () => console.log(`[${label}] Redis connected`));
    client.on('error', (err) => console.error(`[${label}] Redis error:`, err.message));
    client.on('close', () => console.log(`[${label}] Redis connection closed`));

    return client;
}

/**
 * Socket max connection limit added to make sure - server not overload 
 */
const MAX_CONNECTIONS = parseInt(process.env.SOCKET_MAX_CONNECTIONS || '15000', 10);

const httpServer = createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'ok',
            connections: io.engine.clientsCount,
            max_connections: MAX_CONNECTIONS,
            uptime: process.uptime(),
            memory_mb: Math.round(process.memoryUsage().heapUsed / 1024 / 1024),
        }));
        return;
    }
    res.writeHead(404);
    res.end();
});

const io = new Server(httpServer, {
    cors: {
        origin: process.env.APP_URL || 'http://localhost:8000',
        methods: ['GET', 'POST'],
        credentials: true,
    },
    pingTimeout: 60000,
    pingInterval: 25000,
    transports: ['websocket', 'polling'],
    maxHttpBufferSize: 1000000, // 1mb
});


io.on('connection', (socket) => {

    /**
     * If connection is more than max allowed than disconnect 
     */
    if (io.engine.clientsCount > MAX_CONNECTIONS) {
        socket.disconnect(true);
        return;
    }

    socket.on('subscribe', (channel) => {
        // Added validation for limited length of size and only string allowe
        if (typeof channel === 'string' && channel.length < 200) {
            socket.join(channel);
        }
        
    });

    socket.on('unsubscribe', (channel) => {
        socket.leave(channel);
    });

});

const subscriber = createRedisClient('Subscriber');

subscriber.psubscribe(`${REDIS_PREFIX}*`, (err, count) => {

    if (err) {
        console.error('Failed to psubscribe:', err.message);
        return;
    }

});

subscriber.on('pmessage', (pattern, redisChannel, message) => {
    try {
        
        const channel = redisChannel.replace(REDIS_PREFIX, '');
        const payload = JSON.parse(message);

        const eventName = payload.event;
        const eventData = payload.data;

        io.to(channel).emit(eventName, eventData);
    } catch (err) {
        console.error('Failed to parse message:', err.message);
    }
});

httpServer.listen(PORT, () => {
    console.log(`Socket.IO Server running on port ${PORT}`);
});

function shutdown() {
    console.log('\nShutting down...');
    subscriber.disconnect();
    io.close(() => {
        httpServer.close(() => {
            console.log('Closed');
            process.exit(0);
        });
    });
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
