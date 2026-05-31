# Minecraft.How Server List

A clean Minecraft server list starter built with Laravel 13, React, Inertia, Tailwind CSS, and shadcn/ui-style components.

The project lists public Minecraft servers, checks their live Java Edition status, and renders colored MOTD text from the server response. It is intentionally simple, easy to customize, and ready to use as a base for a larger server directory.

## Features

- Live Minecraft Java server ping
- Colored MOTD rendering from JSON and legacy color codes
- Online/offline status, latency, version, and player count
- Search and mode filters
- Responsive React interface
- Static server configuration in `routes/web.php`
- No database required for the starter version
- Footer credit for [Minecraft.How](https://minecraft.how)

## Stack

- Laravel 13
- Inertia.js
- React
- Tailwind CSS
- shadcn/ui-style local components
- Vite

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run build
php artisan serve
```

For development, run Vite in a second terminal:

```bash
npm run dev
```

## Server Data

Servers are currently defined in `routes/web.php`. Each server supports:

- name
- host
- port
- version
- mode
- country
- fallback description

Live values are loaded from:

```text
GET /api/servers/status
```

The status endpoint caches ping results for 60 seconds to keep the page fast and avoid excessive requests to Minecraft servers.

## Credits

Made by [Minecraft.How](https://minecraft.how).
