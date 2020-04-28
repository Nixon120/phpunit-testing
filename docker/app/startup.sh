#!/usr/bin/env bash

# This script is executed when the container is brought up.

# Place the environment variables in a script for CRON jobs to be able to access them.
declare -p | grep -Ev 'BASHOPTS|BASH_VERSINFO|EUID|PPID|SHELLOPTS|UID' > /container.env

while ! nc -w 1 -z $MYSQL_HOST 3306; do sleep 0.1; done;

# Run DB Migrations
php /app/vendor/robmorgan/phinx/bin/phinx migrate -c /app/phinx.php

/usr/sbin/crond -l 8
/usr/bin/supervisord -c /etc/supervisord.conf
