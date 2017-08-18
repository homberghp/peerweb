#!/bin/bash
wdir=$(dirname $(ls -td /tmp/php*.d/sv05* | head -1))
echo $wdir
sudo -u www-data ../scripts/jmerge -w ${wdir} -c $(pwd) -p $(pwd)/sv05_import.properties
