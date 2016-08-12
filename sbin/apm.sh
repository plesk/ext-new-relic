#!/usr/bin/env bash

### Add the New Relic apt repository and install the daemon
### TODO - Add CentOs / Red Hat
if [ ! -f /etc/redhat-release ]
then
	wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -

	if [ ! -f /etc/apt/sources.list.d/newrelic.list ]
	then
	        echo deb http://apt.newrelic.com/debian/ newrelic non-free >> /etc/apt/sources.list.d/newrelic.list
	fi

	set -e
	export DEBIAN_FRONTEND=noninteractive

	apt-get -qq update
	apt-get -qq -y install newrelic-php5

	export NR_INSTALL_SILENT=1
    export NR_INSTALL_KEY="$1"

	newrelic-install install
fi

### Restart PHP services
### TODO - Check for all possibilities
/etc/init.d/apache2 restart
/etc/init.d/php5-fpm restart