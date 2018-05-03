#!/bin/bash -e

### Function to update the newrelic.ini file
function writeIniFile() {
    sed -i -e "s/;newrelic.daemon.port = \"\/tmp\/.newrelic.sock\"/newrelic.daemon.port = \"\/run\/@.newrelic.sock\"/g" $1
    sed -i -e "s/newrelic.appname = \"PHP Application\"/newrelic.appname = \"PLESK PHP $2\"/g" $1
}

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
if [ -f /etc/redhat-release ]
then
    ### Fix CentOS/RHEL 7 issue - daemon port must be changed due to security restrictions
    if [ -f /etc/php.d/newrelic.ini ]
    then
        writeIniFile '/etc/php.d/newrelic.ini' 'OS version'
    fi

    if [ $(rpm -q --qf "%{VERSION}" $(rpm -q --whatprovides redhat-release)) -eq 7 ]
    then
        IFS=':' read -r -a array <<< "$3"

        for element in "${array[@]}"
        do
            phpPath=${element/\/bin/}
            iniPath=$phpPath/etc/php.d/newrelic.ini

            if [ -f $iniPath ]
            then
                phpVersion=${phpPath/\/opt\/plesk\/php\//}
                writeIniFile $iniPath $phpVersion
            fi
        done
    fi
else
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

### Remove all running daemon processes and reread PHP configuration
killall newrelic-daemon
plesk bin php_handler --reread

exit 0