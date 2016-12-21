#!/bin/bash -e

### Add the New Relic repository and install PHP service
if [ -f /etc/redhat-release ]
then
    if [ ! -f /etc/yum.repos.d/newrelic.repo ]
    then
        rpm -Uvh http://yum.newrelic.com/pub/newrelic/el5/i386/newrelic-repo-5-3.noarch.rpm
    fi

    yum -y -q install newrelic-php5
else
    if [ ! -f /etc/apt/sources.list.d/newrelic.list ]
    then
            echo deb http://apt.newrelic.com/debian/ newrelic non-free >> /etc/apt/sources.list.d/newrelic.list
    fi

    set -e
    export DEBIAN_FRONTEND=noninteractive

    wget -O - https://download.newrelic.com/548C16BF.gpg | apt-key add -
    apt-get -qq update
    apt-get -qq -y install newrelic-php5
fi

### Install the PHP service
export NR_INSTALL_SILENT=1
export NR_INSTALL_KEY="$1"
export NR_INSTALL_PHPLIST="$3"
newrelic-install purge
newrelic-install install

### Fix for New Relic installer script
if [ ! -f /etc/redhat-release ]
then
    if [ -f /etc/php/7.0/cli/conf.d/20-newrelic.ini ]
    then
        rm /etc/php/7.0/cli/conf.d/20-newrelic.ini
    fi

    if [ -f /etc/php/7.0/fpm/conf.d/20-newrelic.ini ]
    then
        rm /etc/php/7.0/fpm/conf.d/20-newrelic.ini
    fi

    if [ -f /etc/php/7.0/cgi/conf.d/20-newrelic.ini ]
    then
        rm /etc/php/7.0/cgi/conf.d/20-newrelic.ini
    fi

    if [ -f /etc/php5/cli/conf.d/20-newrelic.ini ]
    then
        rm /etc/php5/cli/conf.d/20-newrelic.ini
    fi

    if [ -f /etc/php5/fpm/conf.d/20-newrelic.ini ]
    then
        rm /etc/php5/fpm/conf.d/20-newrelic.ini
    fi

    if [ -f /etc/php5/cgi/conf.d/20-newrelic.ini ]
    then
        rm /etc/php5/cgi/conf.d/20-newrelic.ini
    fi
fi

### Restart web server
if [ -f /etc/redhat-release ]
then
    service httpd restart
else
    service apache2 restart
fi

plesk bin php_handler --reread

exit 0