# Minecraft Server List

A simple Minecraft server list built with Laravel 13, React, Inertia, and shadcn/ui-style components.

## Stack

- Laravel 13
- Inertia.js
- React
- Tailwind CSS
- shadcn/ui-style local components

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

The server data is currently static in `routes/web.php`, so no database is required.
