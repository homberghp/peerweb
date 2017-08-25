#!/usr/bin/perl -w
use strict;
use DBI;
use utf8;
use Encode qw(encode decode);
use Cwd qw(abs_path realpath);
use File::Basename;
my $scriptPath=dirname(abs_path($0));
binmode STDOUT, ':utf8';
my ($pwfile,$dbname,$username,$password,$key,$val,$faculty);
my $cpath=realpath( $scriptPath);
$pwfile=realpath($cpath.'/../etc/jmerge.credentials');
my $preamble = realpath($cpath.'/../tex/busscard.tex');
$faculty=47;
if ($#ARGV >= 0) {
    $faculty=$ARGV[0];
}

open(PWFILE, "<$pwfile" ) or die qq(cannot open credentials file $pwfile\n);
while(<PWFILE>){
    chomp;
    ($key,$val)=split/\s*=\s*/;
    if ($key eq 'db') {
	$dbname=$val;
    } elsif ($key eq 'username') {
	$username=$val;
    }  elsif ($key eq 'password') {
	$password=$val;
    }
}
close(PWFILE);
my ($snummer,$achternaam,$roepnaam,$voorvoegsels,$course,$lang);
my ($name);
my $query =qq(select snummer,trim(achternaam) as achternaam,
   trim(roepnaam) as roepnaam,trim(tussenvoegsel) as tussenvoegsel,
   trim(hoofdgrp) as course_grp,lang
   from prospects 
   where faculty_id=$faculty
   and hoofdgrp in ('WTBDE2017','WTBNL2017','WTBEN2017','IPODE2017','IPONL2017','IPOEN2017')
   order by hoofdgrp,achternaam,roepnaam);
my $dbh= DBI->connect("dbi:Pg:dbname=$dbname",$username,$password,{pg_utf8_strings =>1});
# --   where (course_grp like 'IPO%' or course_grp like 'WTB%')
my $sth = $dbh->prepare($query);
$sth->execute( );
print qq(\\input{$preamble}
);
while (my $row = $sth->fetchrow_arrayref) {
  ($snummer,$achternaam,$roepnaam,$voorvoegsels,$course,$lang) =  @$row;
  $name = $roepnaam;
  if (defined $voorvoegsels) {
    $name .=' '.$voorvoegsels;
  }
  $name .=' '.$achternaam;
  print qq(\\businesscard{$name}{$course}{$snummer}{$lang}\n);
}
$dbh->disconnect;
print qq(\\end{document}
);
exit(0);

