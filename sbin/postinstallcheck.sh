#!/bin/bash -e

if [ ! -f /etc/init.d/newrelic-sysmond ];
then
    echo "1" > "/usr/local/psa/var/modules/new-relic/removepackageservers"
fi

if [ -f /etc/redhat-release ];
then
    if [ $(rpm -q newrelic-php5 2>/dev/null | grep -c "not installed") -eq 1 ];
    then
        echo "1" > "/usr/local/psa/var/modules/new-relic/removepackageapm"
    fi
else
    if [ $(dpkg-query -W -f='${Status}' newrelic-php5 2>/dev/null | grep -c "ok installed") -eq 0 ];
    then
        echo "1" > "/usr/local/psa/var/modules/new-relic/removepackageapm"
    fi
fi

exit 0