#!/usr/bin/perl -w
use warnings;
use strict;
use vars;
use File::Find;
use File::Basename;
use File::MimeInfo::Magic;
use PDF::API2;
use Getopt::Long;
use Cwd 'abs_path';

my $verbose=0;
my $dir ='./';
my ($mtype,$file,$offense,$count,$line,$comment);
my %allowedTypes;
my $allowedTypesFile=dirname(__FILE__).'/allowedmimetypes.txt';
my $optResult = GetOptions("v=i" => \$verbose, # verbosity
			   "dir=s" => \$dir,   # dir to walk
			   "mimetypes=s" => \$allowedTypesFile
			  );

our @offenders;
print "$allowedTypesFile\n" if ($verbose);
open(MIMETYPES,"<$allowedTypesFile")
  or die "cannot open allowed mimetypes file $allowedTypesFile\n";
while (<MIMETYPES>) {
  chomp();
  ($mtype,$comment) = split/#/;
  if (defined $mtype && $mtype ne '') {
    $count=0;
    if ($mtype =~ m/=/) {
      ($mtype,$count) = split/\s*=\s*/,$mtype;
      print "$mtype max count $count\n" if ($verbose);
    }
    $allowedTypes{$mtype} = (defined $count)?$count:0;
  }
}

sub search;

find (\&search, $dir);

sub search {
  if (-f $_) {
    my $file =$File::Find::name;
    my $mime_type = mimetype($file);
    my $valid = 'ILLEGAL';
    if (!defined $mime_type) {
      $mime_type= qx(/usr/bin/mimetype -b $file);
    }
    if (defined $allowedTypes{$mime_type}) {
      if (($allowedTypes{$mime_type} > 0) && $mime_type eq 'application/pdf') {
	my $pdf = PDF::API2->open($file);
	if ($pdf->pages > $allowedTypes{$mime_type}) {
	  $valid ='ILLEGAL pdf file with '.$pdf->pages.' pages, allowed '.$allowedTypes{$mime_type};
	} else {
	  $valid = 'legal';
	}
      } else {
	$valid = 'legal';
      }
    }

    my $result="$file type $mime_type $valid";
    #    our @offenders;
    if ($valid =~ m/^ILLEGAL.*/) {
      push(@offenders,$result);
    }
    print "$result\n" if ($verbose);
  }
}
if (scalar (@offenders) > 0) {
  print STDERR "Illegal file types  in $dir:\n";
  foreach $offense (@offenders) {
    print STDERR "\t$offense\n";
  }
  exit(1);
}
exit(0);
