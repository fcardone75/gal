#!/bin/bash
cd /var/www/html
curl -sS https://getcomposer.org/installer | php
export COMPOSER_ALLOW_SUPERUSER=1
php composer.phar install --no-ansi --no-interaction
