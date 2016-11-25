#!/bin/bash -e

REMOVESERVERS=`cat /opt/psa/var/modules/new-relic/removepackageservers`

if [ "$REMOVESERVERS" = "1" ];
then
    if [ -f /etc/redhat-release ];
    then
        nohup rpm -e newrelic-sysmond > /dev/null 2>&1 &
    else
        nohup dpkg -r newrelic-sysmond > /dev/null 2>&1 &
        apt-get -qq -y autoremove
    fi
fi

# Add sleep of 3 seconds to avoid process lock issues
sleep 3

REMOVEAPM=`cat /opt/psa/var/modules/new-relic/removepackageapm`

if [ "$REMOVEAPM" = "1" ];
then
    if [ -f /etc/redhat-release ];
    then
        nohup rpm -e newrelic-php5 > /dev/null 2>&1 &
    else
        nohup dpkg -r newrelic-php5 > /dev/null 2>&1 &
        apt-get -qq -y autoremove
    fi
fi