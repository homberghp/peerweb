#!/bin/bash
wdir=$(ls -td /tmp/php*.d | head -1)
sudo -u www-data ../scripts/jmerge -w ${wdir} -c $(pwd) -p $(pwd)/sv09_syncprogress.properties
