#!/bin/bash -e

### Add the New Relic repository and install the daemon
if [ -f /etc/redhat-release ]
then
    if [ ! -f /etc/yum.repos.d/newrelic.repo ]
    then
        rpm -Uvh http://yum.newrelic.com/pub/newrelic/el5/i386/newrelic-repo-5-3.noarch.rpm
    fi

    yum -y -q install newrelic-sysmond
else
    if [ ! -f /etc/apt/sources.list.d/newrelic.list ]
    then
        echo deb http://apt.newrelic.com/debian/ newrelic non-free >> /etc/apt/sources.list.d/newrelic.list
    fi

    wget -O- https://download.newrelic.com/548C16BF.gpg | apt-key add -
    apt-get -qq update
    apt-get -qq -y install newrelic-sysmond
fi

### Configure the Server Monitor daemon
nrsysmond-config --set license_key="$1"
sed -i -r "s/#?hostname=(\")?.*(\")?/hostname=\"$2\"/g" /etc/newrelic/nrsysmond.cfg

### Run monitor as root user to avoid re-installation problems
if [ -f /etc/redhat-release ]
then
    if [ -f /etc/sysconfig/newrelic-sysmond ]
    then
        sed -i -r "s/#?RUNAS=newrelic/#RUNAS=newrelic/g" /etc/sysconfig/newrelic-sysmond
    fi
else
    if [ -f /etc/default/newrelic-sysmond ]
    then
        sed -i -r "s/#?RUNAS=newrelic/#RUNAS=newrelic/g" /etc/default/newrelic-sysmond
    fi
fi

### Start New Relic service - User "restart" to update config if already running
/etc/init.d/newrelic-sysmond restart

# Add sleep of 2 seconds to avoid process lock issues
sleep 2

exit 0