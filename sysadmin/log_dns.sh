#!/bin/bash

# Get the current year and month
CURYEAR=`date +%Y`
CURMONTH=`date +%m`

for i in /var/log/lighttpd/$CURYEAR/$CURMONTH/*-access_log; do
  webazolver -N 20 -D /var/lib/webalizer/dns_cache.db $i
done
