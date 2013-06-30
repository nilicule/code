#!/bin/bash

# Get the current year and month
CURYEAR=`date +%Y`
CURMONTH=`date +%m`

for hostname in brink.st eugroup.nl dannyfine.nl fractalgee.com hayka.nl isitweekendyet.com mostsexymen.com nilicule.com rc.vc rc6.org studio5-0.com theweekendhaslanded.net tiramteatret.org mixes.nilicule.com arwinravenstone.com
do
	# bash check if directory exists
	if [ ! -d "/var/www/htdocs/domains/rc.vc/subdomains/stats.rc.vc/$hostname" ]; then
		mkdir "/var/www/htdocs/domains/rc.vc/subdomains/stats.rc.vc/$hostname"
	fi
	
	# Analyze logfiles
   	/usr/bin/sudo /usr/bin/webalizer -c /etc/webalizer.conf -w -j -D /var/lib/webalizer/dns_cache.db -n $hostname -o /var/www/htdocs/domains/rc.vc/subdomains/stats.rc.vc/$hostname /var/log/lighttpd/$CURYEAR/$CURMONTH/$hostname-access_log
done

