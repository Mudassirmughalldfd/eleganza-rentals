#!/usr/bin/env sh
cd "$(dirname "$0")"
printf 'Open http://localhost:8000\n'
php -S localhost:8000 router.php
