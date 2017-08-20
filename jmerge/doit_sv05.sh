#!/bin/bash
wdir=$(dirname $(ls -td /tmp/php*.d/sv05* | head -1))
echo $wdir
scriptdir=$(dirname $(pwd))/scripts
sudo -u www-data ${scriptdir}/jmergeAndTicket -w ${wdir} 
