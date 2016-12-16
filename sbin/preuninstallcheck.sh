#!/bin/bash -e

REMOVESERVERS=`cat /usr/local/psa/var/modules/new-relic/removepackageservers`

if [ "$REMOVESERVERS" = "1" ];
then
    if [ -f /etc/redhat-release ];
    then
        yum -y -q remove newrelic-sysmond
        yum -y -q remove newrelic-repo

        if [ -f /etc/yum.repos.d/newrelic.repo ]
        then
            rm /etc/yum.repos.d/newrelic.repo
        fi
    else
        apt-get -qq -y --purge autoremove newrelic-sysmond

        if [ -f /etc/apt/sources.list.d/newrelic.list ]
        then
            rm /etc/apt/sources.list.d/newrelic.list
        fi
    fi
fi

# Add sleep of 6 seconds to avoid process lock issues
sleep 6

REMOVEAPM=`cat /usr/local/psa/var/modules/new-relic/removepackageapm`

if [ "$REMOVEAPM" = "1" ];
then
    if [ -f /etc/redhat-release ];
    then
        yum -y -q remove newrelic-php5
        yum -y -q remove newrelic-php5-common
        yum -y -q remove newrelic-repo

        if [ -f /etc/yum.repos.d/newrelic.repo ]
        then
            rm /etc/yum.repos.d/newrelic.repo
        fi
    else
        apt-get -qq -y --purge autoremove newrelic-php5

        if [ -f /etc/apt/sources.list.d/newrelic.list ]
        then
            rm /etc/apt/sources.list.d/newrelic.list
        fi
    fi
fi