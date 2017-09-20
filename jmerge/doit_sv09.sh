#!/bin/bash
wdir=$(dirname $(ls -td /tmp/php*.d/sv09* | head -1))
sudo -u www-data ../scripts/jmerge -w ${wdir} -c $(pwd) -p $(pwd)/sv09_syncprogress.properties
