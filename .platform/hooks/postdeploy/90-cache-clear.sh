#!/bin/bash
cd /var/www/html
bin/console c:c -e prod
chown -R webapp:webapp .
