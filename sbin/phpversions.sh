#!/bin/bash -e

for file in /opt/plesk/php/*; do
  echo ${file##*/}
done