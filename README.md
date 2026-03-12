# Laravel Real-Time Polling System

A real-time polling application built with Laravel, Socket.IO, and Redis.

## Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18
- Redis server
- MySQL

## Setup

```bash
git clone <repo-url> && cd laravel-polling-sys
```

then runs below command 
- composer install, 
- cp .env.example .env, (generates an app key and set other .env variables  and run migrations, installs npm packages, and builds frontend assets.)
- pgp artisan serve
- node socket-server.js
