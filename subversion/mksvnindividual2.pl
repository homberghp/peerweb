#!/usr/bin/perl -w
# read group and user from database
# write authz records for groups
use strict;
use DBI;
use Getopt::Long;
use File::Temp qw/ tempfile tempdir /;
use File::Path qw/make_path/;
use File::Basename;
use POSIX qw(locale_h);

my %attr = (
    PrintError => 0,
    RaiseError => 0,
);
setlocale(LC_ALL,'en_US.UTF-8');
my $dirtemplate ='mksvniscriptXXXXXX';
my $tempdir = tempdir( $dirtemplate, CLEANUP => 0, TMPDIR => 1 );
my $script=$tempdir.'/mkdir.sh';
my $initdir=$tempdir.'/init';
my $confdir=$initdir.'/conf';
mkdir $initdir;
mkdir $confdir;
my $authz=$confdir.'/authz';

my ($username,$groupname,%ghash,%uhash,$ogname,$project,$year);
my ($snummer,$achternaam,$roepnaam,$voorvoegsel,$prjm_id);
my ($gpath,$path,$group);
my ($con,$gline,$youngest);
my $dbuser='wwwrun';
my $dbpasswd='apache4ever';
my $row;
my ($result,$url_tail);
my $url_base='svn';
my ($svnrootpath,$grprepospath);
my $templates='/etc/repos-templates';
my $db='peer2';
my $prj_id='1';
my $milestone='1';
my $repos_parent='/tmp'; # The parent dir of the repos
my $repos_name='new_repos'; # The repos name within the parent.
my $trunk_twigs='';
my $optResult = GetOptions("db=s" => \$db,# database
			  "projectmilestone=i" => \$prjm_id, # project id
			  "parent=s" => \$repos_parent,
			  "url_base=s" => \$url_base,
			  "name=s" => \$repos_name,
			  "twigs=s" =>\$trunk_twigs 
    );
if ( ($prjm_id == 1)  || ($repos_parent eq '/tmp') ) {
     print <<EOF;
      usage: $0 --db db_name --projectmilestone prjm_id --parent reposparent --name reposname --url_base url_base [--twigs trunk_twigs]
EOF
    exit(1);
}
my $dbh;
my $query;
my $sth;
$dbh= DBI->connect("dbi:Pg:dbname=$db","$dbuser" ,"$dbpasswd",\%attr)
    or die "Cannot execute ",$DBI::errstr,"\n";

$query="select rtrim(lower(afko)) as afko,year,prj_id,milestone from project join prj_milestone using(prj_id)  where prjm_id=$prjm_id\n";
$sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute();
while ($row = $sth->fetchrow_arrayref) {
  ($project,$year,$prj_id,$milestone) = @$row;
}
my $repostail='/'.$year.'/'.$repos_name;
my $repospath=$repos_parent.$repostail;

my $apache_conf_file='/etc/apache2/svnrepos'.$repostail.'.conf';

if (-d $repospath){
    print "dir $repospath already exists, bailing out\n";
    exit(1);
} 

if (-f $apache_conf_file ) {
    print "apache config file '$apache_conf_file' already exists, bailing out\n";
    exit(2);
}

print "\trepos will live at $repospath\n\tThe conf file is $apache_conf_file\n";

if (!open(AUTHZ,">$authz")){
    print  "Cannot create authz $authz file to create and configure repos\n";
    exit(3);
}

$svnrootpath=$repospath;

if (!open(MKDIRSCRIPT,">$script")){
    print "cannot open script $script for writing\n";
    exit(4);
}


print MKDIRSCRIPT <<EOF;
#!/bin/bash
# called with args $db $prj_id $milestone $repos_parent $repos_name
LANG=en_US.UTF-8
LC_CTYPE=en_US.UTF-8
LC_ALL=en_US.UTF-8
export LC_CTYPE LANG LC_ALL
set -x
echo creating repository in $repospath
echo script name = $script
echo authz name = $authz
umask 007
mkdir -p $svnrootpath
/usr/bin/svnadmin create $svnrootpath
# create temp for conf and hooks
mkdir -p $svnrootpath/tmp/{conf,hooks}
mkdir -p $tempdir/init/{conf,hooks} 
cp $templates/hooks/* $tempdir/init/hooks
cp $templates/conf/* $tempdir/init/conf
echo repos after svnadmin create
#ls -lsR $svnrootpath
EOF


# insert head in output
my $file='/etc/repos-templates/authz.head';
if (!open(HEAD,"<$file")) { 
    print "cannot open $file for read\n";
    exit(4);
}	 
print "writing authz file $authz\n";
while (<HEAD>) {
  print AUTHZ $_;
}
close(HEAD);
# get tutors
$query="select username,tutor from svn_tutor \n".
  "where prj_id=$prj_id and milestone=$milestone";
$sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute();

my $admins='';
$con='';
while ($row = $sth->fetchrow_arrayref) {
  ($username) = @$row;
  $admins .= $con.$username;
  $con=',';
}

print AUTHZ "[groups]\n",
  "tutor = $admins\n";
print MKDIRSCRIPT "cd $initdir\n";
$query="select snummer,replace(replace(replace(replace(replace(achternaam,'ä','ae'),'ö','oe'),'ü','ue'),'ß','ss'),'é','e') as achternaam ,"
    ."replace(replace(replace(replace(replace(roepnaam,'ä','ae'),'ö','oe'),'ü','ue'),'ß','ss'),'é','e') as roepnaam,voorvoegsel\n"
    ." from prj_grp natural join student join all_prj_tutor using(prjtg_id) \n"
    ."where prj_id=$prj_id and milestone=$milestone order by achternaam,roepnaam,snummer";
# $query="select snummer,achternaam ,"
#     ."roepnaam,voorvoegsel\n"
#     ." from prj_grp natural join student join all_prj_tutor using(prjtg_id) \n"
#     ."where prj_id=$prj_id and milestone=$milestone order by achternaam,roepnaam,snummer";
$sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute();
$ogname='';
$con=' = ';
$trunk_twigs =~ s/\s+//g; # remove all whitespace in twigs argument to prevent wrong dir names.
my $itrunk_twigs='';
while ($row = $sth->fetchrow_arrayref) {
    ($snummer,$achternaam,$roepnaam,$voorvoegsel) = @$row;
    if (defined $voorvoegsel) {
      $roepnaam .= '_'.$voorvoegsel
    } 
    $gpath = join('_',$achternaam,$roepnaam,$snummer);
    $gpath =~ s/\s+/_/g; # remove white space from path
    $group='g'.$snummer;
    if ($trunk_twigs) {
	$itrunk_twigs="$gpath/trunk/".join(" $gpath/trunk/",split(/\s+/,$trunk_twigs)); 
    } else {
	$itrunk_twigs='';
    }
    $ghash{$gpath} = $group;
    print MKDIRSCRIPT "mkdir -p $gpath/{trunk,tags,branches} $itrunk_twigs\n";
    print AUTHZ "$group = $snummer\n";
}

print AUTHZ "\n".
  "[/]\n".
  "\@tutor = rw\n".
  "* = r\n".
  "\n".
  "[/conf]\n".
  "\@tutor = rw\n".
  "* =\n".
  "\n";


foreach $gpath (sort keys %ghash) {
  print AUTHZ "[/$gpath]\n".
    "\@$ghash{$gpath} = rw\n".
      "\@tutor = r\n".
	"* = \n\n";
}

print AUTHZ "\n";
close(AUTHZ);

$file=$svnrootpath.'/tmp/conf/authz';

print MKDIRSCRIPT <<EOF;

cat $authz >$file
chmod -R g+w $svnrootpath
echo repos before import 
ls -ls $svnrootpath
/bin/sync
echo start mksvnindividual \$(date) >> $tempdir/mk.log
locale >> $tempdir/mk.log
export import_result=\$(/usr/bin/svn import --force -m 'initial struct, conf and hooks' $initdir file://$svnrootpath 2>> $tempdir/mk.log)
export youngest=\$(/usr/bin/svnlook youngest $svnrootpath)
#echo import result is \$import_result

youngest=\$(/usr/bin/svnlook youngest $svnrootpath)

if [ \$youngest -gt 0 ] ; then
    echo initial import successfull
    /usr/bin/svn ls file://$svnrootpath
    echo repos after import, non recursive
    ls -ls $svnrootpath
    cd $svnrootpath
    /usr/bin/svn co file://$svnrootpath/conf _conf
    /usr/bin/svn co file://$svnrootpath/hooks _hooks
    rm -fr conf hooks; mv _hooks hooks; mv _conf conf
    echo repos creation script $script done
else
    echo import failed
    exit 4;
fi
echo finished mksvnindividual \$(date) >> $tempdir/mk.log

EOF

close(MKDIRSCRIPT);
# and execute the script
$result .= `LC_CTYPE=en_US.UTF-8 bash $script`;
print "Command executed with output<pre style='color:#800'>\n$result"."</pre>\n";

if (!open(APACHECONF,">$apache_conf_file")) {
    print "Cannot open config file $apache_conf_file\n";
    exit(7);
}
$url_tail='/'.$url_base.$repostail;
print APACHECONF <<EOF;
Use SVNRepo ${year} ${repos_name}
EOF
close(APACHECONF);
# # insert repos into peerweb
$query = "begin work;\n"
    ."insert into repositories (milestone,repospath, description, isroot, url_tail,owner,prjm_id)\n"
    ."\t select milestone,'$svnrootpath', '$repos_name', true,'$url_tail',t.userid,prjm_id \n"
    ." from prj_milestone natural join project p join tutor t on(p.owner_id=t.userid) where prj_id=$prj_id and milestone=$milestone;\n"
    ."commit;";
# print $query;
$sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute();
$dbh->disconnect;

# notify apache deamon on new repos
open(PINGS,'>>',"/etc/apache2/svnrepos/pings");
print PINGS "$repostail\n";
close(PINGS);
# sleep 2;
# open(PIPE,'>>',"/etc/apache2/svnrepos/makeloopfifo");
# print PIPE "$repostail\n";
# close(PIPE);
`/usr/sbin/kickhttpd`;
print "Repos creation complete\n";

#unlink $authz,$script;
exit(0);
