#!/bin/bash -e

### Add the New Relic repository and install PHP service
if [ -f /etc/redhat-release ]
then
    rpm -Uvh http://yum.newrelic.com/pub/newrelic/el5/i386/newrelic-repo-5-3.noarch.rpm
    yum -y -q install newrelic-php5
else
	if [ ! -f /etc/apt/sources.list.d/newrelic.list ]
	then
	        echo deb http://apt.newrelic.com/debian/ newrelic non-free >> /etc/apt/sources.list.d/newrelic.list
	fi

	set -e
	export DEBIAN_FRONTEND=noninteractive

	wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
	apt-get -qq update
	apt-get -qq -y install newrelic-php5
fi

### Install the PHP service
export NR_INSTALL_SILENT=1
export NR_INSTALL_KEY="$1"
newrelic-install install

### Restart web server
/etc/init.d/apache2 restart

exit 0