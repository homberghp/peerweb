#!/usr/bin/perl -w
use File::Grep qw( fgrep );
open(NAV,"<navtable.php") or die"cannot open nav table.php\n";
my %fronthash;
while(<NAV>){
    if (m/'(\w+\.php)',$/){
	$fronthash{$1}=$1;
# 	print "in nav $1\n";
    }
}

opendir(CURDIR,".") or die "cannot read dir\n";
while(readdir CURDIR){
    if (!defined($fronthash{$_}) && -f $_) { 
	print "$_ not in  nav\n";
	# print "file $_ used in ", 
	# (fgrep { /$_/ }  glob "./.*"), " used in curdir\n";
    }
}

