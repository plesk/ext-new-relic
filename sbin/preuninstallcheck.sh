#!/bin/bash -e

if [ -f /usr/local/psa/var/modules/new-relic/removepackageinfrastructure ]
then
    REMOVEINFRASTRUCTURE=`cat /usr/local/psa/var/modules/new-relic/removepackageinfrastructure`

    if [ "$REMOVEINFRASTRUCTURE" = "1" ];
    then
        if [ -f /etc/redhat-release ];
        then
            if [ $(rpm -q newrelic-infra 2>/dev/null | grep -c "not installed") -eq 0 ];
            then
                yum -y -q remove newrelic-infra

                if [ -f /etc/yum.repos.d/newrelic-infra.repo ]
                then
                    rm -f /etc/yum.repos.d/newrelic-infra.repo
                fi

                if [ -f /etc/newrelic-infra.yml ]
                then
                    rm -f /etc/newrelic-infra.yml
                fi
            fi
        else
            if [ $(dpkg-query -W -f='${Status}' newrelic-infra 2>/dev/null | grep -c "ok installed") -eq 1 ];
            then
                apt-get -qq -y --purge autoremove newrelic-infra

                if [ -f /etc/apt/sources.list.d/newrelic-infra.list ]
                then
                    rm -f /etc/apt/sources.list.d/newrelic-infra.list
                fi
            fi
        fi
    fi

    # Add sleep of 6 seconds to avoid process lock issues
    sleep 6
fi

if [ -f /usr/local/psa/var/modules/new-relic/removepackageservers ]
then
    REMOVESERVERS=`cat /usr/local/psa/var/modules/new-relic/removepackageservers`

    if [ "$REMOVESERVERS" = "1" ];
    then
        if [ -f /etc/init.d/newrelic-sysmond ];
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
    fi

    # Add sleep of 6 seconds to avoid process lock issues
    sleep 6
fi

if [ -f /usr/local/psa/var/modules/new-relic/removepackageapm ]
then
    REMOVEAPM=`cat /usr/local/psa/var/modules/new-relic/removepackageapm`

    if [ "$REMOVEAPM" = "1" ];
    then
        if [ -f /etc/redhat-release ];
        then
            if [ $(rpm -q newrelic-php5 2>/dev/null | grep -c "not installed") -eq 0 ];
            then
                NR_INSTALLED_PHPLIST=`cat /usr/local/psa/var/modules/new-relic/removephpversions`
                export NR_INSTALL_PHPLIST="$NR_INSTALLED_PHPLIST"
                export NR_INSTALL_SILENT=1
                newrelic-install purge

                yum -y -q remove newrelic-php5
                yum -y -q remove newrelic-php5-common
                yum -y -q remove newrelic-repo

                if [ -f /etc/yum.repos.d/newrelic.repo ]
                then
                    rm /etc/yum.repos.d/newrelic.repo
                fi

                service httpd restart
                plesk bin php_handler --reread
            fi
        else
            if [ $(dpkg-query -W -f='${Status}' newrelic-php5 2>/dev/null | grep -c "ok installed") -eq 1 ];
            then
                NR_INSTALLED_PHPLIST=`cat /usr/local/psa/var/modules/new-relic/removephpversions`
                export NR_INSTALL_PHPLIST="$NR_INSTALLED_PHPLIST"
                export NR_INSTALL_SILENT=1
                newrelic-install purge

                apt-get -qq -y --purge autoremove newrelic-php5

                if [ -f /etc/apt/sources.list.d/newrelic.list ]
                then
                    rm /etc/apt/sources.list.d/newrelic.list
                fi

                service apache2 restart
                plesk bin php_handler --reread
            fi
        fi

        # Kill all daemon processes that are currently running after uninstall
        if [ $(ps -C newrelic-daemon | grep -c newrelic-daemon) -gt 0 ];
        then
            killall newrelic-daemon &> /dev/null
        fi
    fi
fi

exit 0