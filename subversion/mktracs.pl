#!/usr/bin/perl -w
# read group and user from database
# write authz records for groups
use strict;
use DBI;
use File::Temp qw/ tempfile tempdir /;
my ($con,$gline);
my $db='peer';
my $dbuser='wwwrun';
my $dbpasswd='apache4ever';
my  ($prjm_id,$prj_id,$milestone,$alias,$project_name,$dbname,$trac_path,$grp_num,$url_tail,$repospath,$scriptname,$username,$role);
my  ($achternaam,$roepnaam,$tussenvoegsel,$grp,$descriptive_name);
my ($sql2, $sth2, $row2,$query2);
my $row;
if ($#ARGV < 0) {
     print <<EOF;
      useage: $#ARGV $0 prjm_id
EOF
	exit(1);
}
$prjm_id=$ARGV[0];

my $dbh= DBI->connect("dbi:Pg:dbname=$db","$dbuser" ,"$dbpasswd");
my $query= "select trim(p.afko)||p.year||'_'||pm.milestone as scriptname from project p join prj_milestone pm using(prj_id) where  prjm_id=$prjm_id ";
my $sth=$dbh->prepare($query);
$sth->execute();
$row =$sth->fetchrow_arrayref;
($scriptname) = @$row;
print STDERR "$scriptname\n";

open(APACHE_CONF ,">$scriptname.conf") or die "sorry, cannot write file\n";
open(TRAC_INIT, ">$scriptname.sh") or die "sorry, cannot write file\n";
open(SQL_INIT, ">$scriptname.sql") or die  "sorry, cannot write file\n";
print APACHE_CONF qq(AliasMatch ^/trac(/.*)?\$	"/home/trac\$1"\n);
print TRAC_INIT qq(#!/bin/bash\n);
print TRAC_INIT qq(umask 007\n);
print TRAC_INIT qq(cat $scriptname.sql | psql -X peer2\n);


$query="select prj_id,milestone,coalesce(rtrim(alias),'g'||grp_num) as alias,project_name,dbname,trac_path,repospath,grp_num from trac_init_data tid where prjm_id=$prjm_id\n";
$sth=$dbh->prepare($query);
#print STDERR "$query\n";

$sth->execute();
while ($row = $sth->fetchrow_arrayref) {
  ($prj_id,$milestone,$alias,$project_name,$dbname,$trac_path,$repospath,$grp_num) = @$row;
#  print qq($prj_id,$milestone,$alias,$project_name,$trac_path\n);
  $url_tail = $trac_path;
  $url_tail =~ s/^\/home//;
  print APACHE_CONF qq(
<Directory "$trac_path">
    PythonOption TracEnv $trac_path
    Include /etc/apache2/trac/trac.auth
    Auth_PG_pwd_whereclause  " and prj_id=$prj_id and milestone=$milestone and gid in ('0','$grp_num') limit 1"
    ErrorDocument 403 "https://www.fontysvenlo.org$url_tail/wiki" 
</Directory>
);
  $sql2=$scriptname.'_'.$alias.'.sql';
  open(SQL_INIT_P2, ">$sql2") or die ;
  $query2="select distinct username,case when gid=0 then 'tutor' else 'student' end as  role "
      ." from project_group pg where prj_id=$prj_id and milestone=$milestone and gid in (0,$grp_num)\n";
  $sth2=$dbh->prepare($query2);
  $sth2->execute();
  print SQL_INIT_P2 qq(insert into permission (username,action) values('tutor','TRAC_ADMIN');
insert into permission (username,action) values('student','TRAC_ADMIN');\n);
  while ($row2 = $sth2->fetchrow_arrayref) {
      ($username,$role) = @$row2;
      print SQL_INIT_P2 qq(insert into permission (username,action) values('$username','$role');\n);
  }
#  print SQL_INIT qq(create user "$dbname" with password '$dbname';\n);
  print SQL_INIT qq(create database  "$dbname" owner "trackerd";\n);
  print TRAC_INIT qq(sudo -u www-data mkdir -p $trac_path\n);
  print TRAC_INIT qq(sudo -u www-data trac-admin $trac_path initenv $project_name postgres://trackerd:ticktricktrack\@localhost/$dbname svn $repospath\n);
  #print TRAC_INIT qq(cat /home/svn/adminrepos/admin/user_map.sql | psql -X $dbname\n);
#  $sql2=$scriptname.'_g'.$grp_num.'.sql';
  # $query2="select username,achternaam,roepnaam,tussenvoegsel,trim(grp),descriptive_name from trac_user_map  where prjm_id=$prjm_id and grp in ('$alias','g0') ";
  # $sth2=$dbh->prepare($query2);
  # $sth2->execute();
  # while ($row2 = $sth2->fetchrow_arrayref) {
  #     ($username,$achternaam,$roepnaam,$tussenvoegsel,$grp,$descriptive_name) = @$row2;
  #     if (defined $tussenvoegsel) { $tussenvoegsel=qq('$tussenvoegsel');}
  #     else {$tussenvoegsel='null';}
  #     print SQL_INIT_P2 qq(insert into user_map (username,achternaam,roepnaam,tussenvoegsel,grp,descriptive_name) 
  #          values ('$username','$achternaam','$roepnaam',$tussenvoegsel,'$grp','$descriptive_name');\n);
  # }

  print TRAC_INIT qq(cat $sql2 | psql -X $dbname\n);
  close(SQL_INIT_P2);
}
close(SQL_INIT);
close(TRAC_INIT);
close(APACHE_CONF);
