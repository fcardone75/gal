#!/usr/bin/env bash

php bin/console app:check:vpn true prod
php bin/console app:nsia:get
php bin/console app:nsia:parse
php bin/console app:nsia:send

echo "ROUTINE ENDED"
