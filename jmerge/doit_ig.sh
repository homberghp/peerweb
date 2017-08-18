#!/bin/bash
wdir=$(dirname $(ls -td /tmp/php*.d/worksheet.xlsx | head -1))
echo $wdir
sudo -u www-data ../scripts/jmerge -w ${wdir} -c $(pwd) -p $(pwd)/uploadgroup.properties
