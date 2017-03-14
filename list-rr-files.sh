#!/bin/sh

find ./../portal/www/roadrunner -path "./../portal/www/roadrunner/apps/libraries" -prune -o -path "./../portal/www/roadrunner/vendor" -prune -o -path "./../portal/www/roadrunner/core/libraries" -prune -o -type f -name '*.php' -print
