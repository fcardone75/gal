#!/bin/bash
cd /var/www/html
bin/console doctrine:migrations:migrate --env=prod --no-debug --no-interaction
