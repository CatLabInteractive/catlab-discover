#!/usr/bin/env bash
var=${PHP_BIN:-php}
var=${COMPOSER_BIN:-composer}

$COMPOSER_BIN update --no-dev
$PHP_BIN artisan migrate --force


echo
${PHP_BIN} artisan env

echo
echo "Clearing cache"
$PHP_BIN artisan config:cache
$PHP_BIN artisan cache:clear

### Run upgrade scripts
echo
echo "Running upgrade scripts"
$PHP_BIN artisan migrate --force

### Optimize
echo
echo "Caching config & routes"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
