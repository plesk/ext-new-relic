#!/bin/bash -e

NR_INSTALL_PHPLIST="$3"

echo $NR_INSTALL_PHPLIST > "/usr/local/psa/var/modules/new-relic/removephpversions"

exit 0