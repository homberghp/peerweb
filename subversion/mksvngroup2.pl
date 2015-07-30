#!/usr/bin/perl -w
# read group and user from database
# write authz records for groups
use strict;
use DBI;
use Getopt::Long;
use File::Temp qw/ tempfile tempdir /;
use File::Path qw/make_path/;
use File::Basename;
my %attr = (
    PrintError => 0,
    RaiseError => 0,
);
my ($username,$groupname,%ghash,%uhash,$ogname,$project,$projectmilstone,$year,$prj_id,$prm_id,$milestone);
my ($con,$gline,$url_tail,%repoHash,$repoName);
#my $db='peer';
my $dbuser='wwwrun';
my $dbpasswd='apache4ever';
my $row;
my ($svnrootpath,$grprepospath);
my $templates='/etc/repos-templates';
my $url_base='svnt';
my $db = 'peer2';
my $prjm_id='1';
my $repos_parent='/tmp'; # The parent dir of the repos
my $repos_name='new_repos'; # The repos name within the parent.
my $trunk_twigs='';
my $optResult = GetOptions("db=s" => \$db,# database
			  "projectmilestone=i" => \$prjm_id, # projectmilestone id
			  "parentdir=s" => \$repos_parent,
			  "name=s" => \$repos_name,
			  "url_base=s" => \$url_base,
			  "twigs=s" =>\$trunk_twigs
    );
if ( $repos_parent eq '/tmp' ) {
     print <<EOF;
      useage: $0 --db db_name --projectmilestone prjm_id --parent reposparent --name reposname --url_base url_base [--twigs trunktwigs]
EOF
    exit(1);
}
my $sth;
my $dbh;
my $query;
$dbh= DBI->connect("dbi:Pg:dbname=$db","$dbuser" ,"$dbpasswd",\%attr)
    or die "Cannot execute ",$DBI::errstr,"\n";
$query="select rtrim(lower(afko)) as afko,year,milestone,prj_id,prjm_id from project join prj_milestone using(prj_id) where prjm_id=$prjm_id\n";
$sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute() 
    or die "Cannot execute ",$sth->errstr(),"\n";
while ($row = $sth->fetchrow_arrayref) {
  ($project,$year,$milestone,$prj_id) = @$row;
}
my $repostail='/'.$year.'/'.$repos_name;
my $repospath=$repos_parent.$repostail;

my $apache_conf_file='/etc/apache2/svnrepos'.$repostail.'.conf';
my ($script,$scriptfh);
if (-d $repospath){
    print "dir $repospath already exists, bailing out\n";
    exit(1);
} elsif (-f $apache_conf_file ) {
    print "apache config file '$apache_conf_file' already exists, bailing out\n";
    exit(2);
} else {
    print "\trepos will live at $repospath\n\tThe conf file is $apache_conf_file\n";
    make_path('/tmp/peer_subversion');
    ($scriptfh, $script) = tempfile('mksvnscriptXXXXXX', DIR=> '/tmp/peer_subversion', OPEN => 1);
    if (! defined $scriptfh ) { 
	print  "Cannot create script $script file to create and configure repos\n";
	exit(3);
    }
    chmod 0750, $scriptfh;
    $svnrootpath=$repospath.'/svnroot';
    print $scriptfh <<EOF;
#!/bin/bash
echo creating repository in $repospath
umask 007
mkdir -p $svnrootpath
/usr/bin/svnadmin create $svnrootpath
mkdir -p $svnrootpath/tmp/{conf,hooks}
cp $templates/hooks/* $svnrootpath/tmp/hooks
cp $templates/conf/* $svnrootpath/tmp/conf
EOF
    close($scriptfh);
    `bash $script`;
}
# get all groups in the project by prj_tutor+alias
$query = "select trim(coalesce(grp_name,'g'||grp_num)) as groupname from prj_tutor pt \n"
    ." where pt.prjm_id=$prjm_id ";
 $sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";

$sth->execute()
    or die "Cannot execute ",$sth->errstr(),"\n";

while ($row = $sth->fetchrow_arrayref) {
  ($groupname) = @$row;
  $repoHash{$groupname} = $groupname;
}

$query="select groupname,username from svn_group \n".
  "where prjm_id=$prjm_id order by groupname,username";
 $sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute()
    or die "Cannot execute ",$sth->errstr(),"\n";

$ogname='';
$con='';
while ($row = $sth->fetchrow_arrayref) {
  ($groupname,$username) = @$row;
  if ($ogname ne $groupname) {
    if ($ogname ne '') {
      $ghash{$ogname} = $gline;
    }
    $ogname=$groupname;
    $con='';
    $gline=$groupname.' = ';
  }
  if (defined $username) {
      $gline .= $con.$username;
      $con=',';
  }
}
if ($ogname ne '') {
  $ghash{$ogname} = $gline;
}
print "group hash ";

# insert head in output
my $file='/etc/repos-templates/authz.head';
if (!open(HEAD,"<$file")) { 
    print "cannot open $file for read\n";
    exit(4);
}	 
$file=$svnrootpath.'/tmp/conf/authz';
# create the path to file
make_path(dirname($file));
print "writing file $file\n";
if (!open(AUTHZ,">$file")) {
    print "cannot open $file for write\n";
    exit(5);
}
while (<HEAD>) {
  print AUTHZ $_;
}
close(HEAD);
# get tutors
$query="select username,tutor from svn_tutor \n".
  "where prjm_id=$prjm_id";
$sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute()
    or die "Cannot execute ",$sth->errstr(),"\n";


my $admins='';
$con='';
while ($row = $sth->fetchrow_arrayref) {
  ($username) = @$row;
  $admins .= $con.$username;
  $con=',';
}

$query="select auditor from svn_auditor \n".
  "where prjm_id=$prjm_id";
$sth=$dbh->prepare($query)
    or die "Cannot execute ",$sth->errstr(),"\n";
$sth->execute()
    or die "Cannot execute ",$sth->errstr(),"\n";
my $auditors='';
$con='';
while ($row = $sth->fetchrow_arrayref) {
  ($username) = @$row;
  $auditors .= $con.$username;
  $con=',';
}

print AUTHZ "[groups]\n",
  "tutor = $admins\n",
    "auditor = $auditors\n";
$script=$repospath.'/mkdir.sh';
if (!open(MKDIRSCRIPT,">$script")){
    print "cannot open script $script for writing";
    exit(6);
} else {
 print "Succesfully created creation scrip $script\n";

}
# prepend trunk_twigs parts with trunk/
if ($trunk_twigs) {
    $trunk_twigs='trunk/'.join(' trunk/',split(/\s+/,$trunk_twigs)); 
}

foreach $groupname (sort keys %ghash) {
  print "$groupname => $ghash{$groupname}\n";
  print AUTHZ "$ghash{$groupname}\n";
}
 
for $repoName (sort keys %repoHash) {
 $grprepospath=$repospath.'/'.$repoName;
 print "Group repos path = $grprepospath\n";
  print MKDIRSCRIPT <<EOF 
#!/bin/bash\n
/usr/bin/svnadmin create $grprepospath
mkdir -p $grprepospath/tmp
cd $grprepospath/tmp
mkdir -p {branches,trunk,tags} $trunk_twigs
/usr/bin/svn import -m'initial group dirs' file://$grprepospath
EOF
}

print AUTHZ "\n[/]\n".
  "* = r\n\n".
  "[svnroot:/conf]\n".
  "\@tutor = rw\n".
  "* =\n";

foreach $groupname (sort keys %ghash) {
  print AUTHZ "\n[$groupname:/]\n".
    "\@$groupname = rw\n".
      "\@tutor = r\n".
      "\@auditor = r\n".
	"* =\n";
}
print AUTHZ "\n";
close(AUTHZ);

print MKDIRSCRIPT <<EOF;

cd $svnrootpath/tmp
svn import -m'initial conf and hook' file://$svnrootpath
cd $svnrootpath

/bin/rm -fr conf hooks
/usr/bin/svn co file://$svnrootpath/conf conf
/usr/bin/svn co file://$svnrootpath/hooks hooks
EOF

close(MKDIRSCRIPT);

# execute the script
`bash $script`;
make_path(dirname($apache_conf_file));

if (!open(APACHECONF,">$apache_conf_file")) {
    print "Cannot open config file $apache_conf_file\n";
    exit(7);
}
$url_tail='/'.$url_base.$repostail;
print APACHECONF <<EOF;
Use SVNParentRepo ${year} ${repos_name}
EOF
close(APACHECONF);
# insert repos into peerweb
$query = "begin work;\n"
    ."insert into repositories (milestone,repospath, description, isroot, url_tail,owner,grp_num,prjm_id,prjtg_id) \n"
    ."\t select milestone,'$repospath/'||group_name, '$repos_name '||group_name, \n"
    ."false,'$url_tail/'||group_name,owner,grp_num,prjm_id,prjtg_id\n"
    ." from repos_group_name where prjm_id=$prjm_id ;\n"
    ."insert into repositories (milestone,repospath, description, isroot,url_tail,owner,grp_num,prjm_id)\n"
    ."\t select $milestone,'$repospath/svnroot', '$repos_name svnroot', true, '$url_tail'||'/svnroot',owner,0,prjm_id "
    ."from repos_group_name where prjm_id=$prjm_id and grp_num=1;\n"
    ."commit;";
print qq($query);
$sth=$dbh->prepare($query)
    or die "Cannot prepare ",$sth->errstr(),"\n";
$sth->execute()
    or die "Cannot execute ",$sth->errstr(),"\n";
$dbh->disconnect;

# notify apache deamon on new repos
open(PINGS,'>>',"/etc/apache2/svnrepos/pings");
print PINGS "$repostail\n";
close(PINGS);
# sleep 2;
# open(PIPE,'>>',"/etc/apache2/svnrepos/makeloopfifo");
# print PIPE "$repostail\n";
# close(PIPE);
# reload apache2 config
`/usr/sbin/kickhttpd`;
print "Repos creation complete\n";
#unlink $script;
exit(0);
