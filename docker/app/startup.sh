#!/usr/bin/env bash

# This script is executed when the container is brought up.

## Fix Cron issues
touch /etc/crontab /etc/cron.*/*
# Place the environment varialbes in a script for CRON jobs to be able to access them.
declare -p | grep -Ev 'BASHOPTS|BASH_VERSINFO|EUID|PPID|SHELLOPTS|UID' > /container.env

# Run DB Migrations
php /var/www/html/vendor/robmorgan/phinx/bin/phinx migrate -c /var/www/html/phinx.php &

# Temporary migration for the webhook log mongo migrations
php /var/www/html/cli/migrate-mongo.php

# Tail the application log
tail -f /var/www/html/logs/app.log &

# Supervisord runs Nginx & PHP-FPM Services.
/usr/bin/supervisord