#!/bin/bash -e

### Add the New Relic repository and install the agent
if [ -f /etc/redhat-release ]
then
    if [ ! -f /etc/yum.repos.d/newrelic-infra.repo ]
    then
        curl -o /etc/yum.repos.d/newrelic-infra.repo https://download.newrelic.com/infrastructure_agent/linux/yum/el/$(rpm -q --qf "%{VERSION}" $(rpm -q --whatprovides redhat-release))/x86_64/newrelic-infra.repo
    fi

    yum -q makecache -y --disablerepo='*' --enablerepo='newrelic-infra'
    yum -y -q install newrelic-infra
else
    if [ ! -f /etc/apt/sources.list.d/newrelic-infra.list ]
    then
        printf "deb [arch=amd64] http://download.newrelic.com/infrastructure_agent/linux/apt $(lsb_release -sc) main" | tee -a /etc/apt/sources.list.d/newrelic-infra.list
    fi

    curl https://download.newrelic.com/infrastructure_agent/gpg/newrelic-infra.gpg | apt-key add -
    apt-get -qq update
    apt-get -qq -y install newrelic-infra
fi

### Add the license key
echo "license_key: $1" | tee -a /etc/newrelic-infra.yml

### Start New Relic Infrastructure service
service newrelic-infra restart

# Add sleep of 2 seconds to avoid process lock issues
sleep 2

exit 0