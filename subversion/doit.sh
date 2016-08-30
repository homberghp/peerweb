#!/bin/bash
sudo -u www-data rm -fr /etc/apache2/svnrepos/2016/java1.conf
sudo -u www-data rm -fr /home/svn/2016/java1
psql peer <<EOF
delete  from repositories where prjm_id=777;
commit;
EOF

sudo -u www-data /home/f/fontysvenlo.org/peerweb/subversion/mksvnindividual2.pl --db peer --projectmilestone 777 --parent /home/svn --name java1 --url_base svn --twigs='ch{00..14}'
