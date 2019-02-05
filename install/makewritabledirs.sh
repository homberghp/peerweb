#!/bin/bash
wd=$(pwd)
apache_user=www-data

mkdir -p ../log ../tex/{attendancelist_out,makeauthentication_out,photolist_out,tablecard_out}
sudo chown ${apache_user} ../log ../tex/{attendancelist_out,makeauthentication_out,photolist_out,tablecard_out}
