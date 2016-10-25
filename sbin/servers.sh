#!/bin/bash -e

### Add the New Relic repository and install the daemon
if [ -f /etc/redhat-release ]
then
	rpm -Uvh https://download.newrelic.com/pub/newrelic/el5/i386/newrelic-repo-5-3.noarch.rpm
	yum -y -q install newrelic-sysmond
else
	if [ ! -f /etc/apt/sources.list.d/newrelic.list ]
	then
	        echo deb http://apt.newrelic.com/debian/ newrelic non-free >> /etc/apt/sources.list.d/newrelic.list
	fi

	wget -O- https://download.newrelic.com/548C16BF.gpg | apt-key add -
	apt-get update
	apt-get install newrelic-sysmond
fi

### Configure the Server Monitor daemon
nrsysmond-config --set license_key="$1"
sed -i -r "s/#?hostname=(\")?.*(\")?/hostname=\"$2\"/g" /etc/newrelic/nrsysmond.cfg

### Start New Relic service - User "restart" to update config if already running
/etc/init.d/newrelic-sysmond restart

exit 0