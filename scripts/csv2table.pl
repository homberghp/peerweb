#!/usr/bin/perl -w
use strict;
use warnings;
use Getopt::Long;
use POSIX;
use Text::CSV::Encoded;
use POSIX;
use bytes;
#use Encode qw/encode decode/;
my $ac = scalar @ARGV;
my ($filename,$tablename);
if ( $ac == 0) {
  die "$#ARGV Usage <filename> [<tablename>] \n";
} elsif ($ac ==1) {
  $filename = $tablename = $ARGV[0];
  $tablename =~ s/.csv$//;
} else {
  $filename = $ARGV[0];
  $tablename = $ARGV[1];
}
my (%typeHash,%altHash);
my (@data,$col,%lenHash,%maxStringHash,%changer,%maxHash,$dType,$value);
my (@lines, $len,$line,$sep);
my (@names, $name);
my $csv = Text::CSV::Encoded->new();
my $schemaOnly = 0;
if ( -f $filename && open(IN,"<",$filename)) {
    
  my $line = (<IN>);
  if ($csv->parse($line)) {
    @data = $csv->fields();
    # print STDERR "@data\n";
  }
  for (my $col=0; $col < @data; $col++) {
    $name = $data[$col];
    next unless length($name);
    $name =~ s/(\.|\s|-|\/|\(|\))+/_/g;
    $name =~ s/[()]+/_/g;
    $name =~ s/_+/_/g;
    $name =~ s/_$//;
    $name =~ s/^_//;
    $name = lc($name);
    $names[$col]= $name;
    $typeHash{$name}= 'unkn';
    $lenHash{$name}=0;
    $maxStringHash{$name}='';
    $maxHash{$name} = -999999;
  }
} else {
  print STDERR qq(Cannot open $filename for read\n);
  exit 1;
}

# read rest of file
my $count=0;

while (<IN>) {
  if ($csv->parse($_)) {
    @data = $csv->fields();
    $line='';
    $sep='';
    for (my $col =0; $col< @data; $col++) {
      $name=$names[$col];
      $value = $data[$col];
      $value =~ s/^\s+//;
      $value =~ s/\s+$//;
#      $value =~s/^-  -$//g; # throw out progress volg null dates.
      $len = length($value);
      if ( $lenHash{$name} < $len) {
	$maxStringHash{$name} = $value;
	$lenHash{$name} = $len;
	$changer{$name} .= $value.';';
      }
      if ( ($value =~ m/^\d{4}-\d{2}-\d{2}$/)
	   || ($value =~ m/^\d{2}\/\d{2}\/\d{2,4}$/)
	  || ($value=~ m/^(19|20)\d{2}[01]\d[0123]\d$/)
	   || ($value =~ m/^-  -$/)) {
	$dType ='date';
	$value =~s/^-  -$//g; # throw out "progress-volg" null dates.
	$typeHash{$name} = $dType;
	if ( length($maxStringHash{$name}) < length($value)) {
	  $maxStringHash{$name} = $value;
	}
	$changer{$name} .= $value.';';
      } elsif ( $value =~ m/^\d{2}:\d{2}(:\d{2})?$/ ) {
	$dType ='time';
	$typeHash{$name} = $dType;
	if ( length($maxStringHash{$name}) < length($value)) {
	  $maxStringHash{$name} = $value;
	}
	$changer{$name} .= $value.';';
      } elsif ( $value =~ m/^\d+$/ && $typeHash{$name} ne 'alpha' ) {
	$dType= 'num';
	if ($typeHash{$name}  ne $dType) {
	  $typeHash{$name} = $dType;
	  $changer{$name} .= $value.';';
	}
	if ( $maxHash{$name} < $value) {
	  $maxHash{$name} = $value;
	  $maxStringHash{$name}=$value;
	}
      } elsif ($value ne '') {
	$dType= 'alpha';
	$typeHash{$name} = $dType;
	$changer{$name} .= $value.';';
      } else {
	  ## cannot guess type from nothing. Ignore this value.
      }
      if ($value eq '') {
	  $line .=$sep.'NULL';
      }  elsif($dType eq 'alpha' || $dType eq 'date' ) {
	$line .=$sep.'"'.$value.'"';
      } else {
	$line .=$sep.$value;
      }
      $sep=',';
    }
    push @lines,$line."\n";
  }
  $count++;
}

print STDERR "read $count records\n";
# write schema
print "set datestyle='ISO,DMY';\nbegin work;\ndrop table if exists $tablename cascade;\n".
  "create table $tablename (${tablename}_id serial primary key,\n";
my $c='';
foreach my $name (@names) {
  print "$c\t$name ";
  $len = $lenHash{$name};
  if ($len == 0) {
    $len = 1;
  } elsif ($len > 5 && $len < 10 ) {
      # between 1 and 10 round up to 5 multiples 
      $len = (floor($len/5)+1)*5;
  } elsif ($len >=10) {
      # round up to 10 multiples
      $len = (floor($len/10)+1)*10;
  
  }
  if ($typeHash{$name} eq 'num') {
    if ($maxHash{$name} > 2147483647) {
      print "\tbigint";
    } else {
      print "\tinteger";
    }
  } elsif ($typeHash{$name} eq 'date') {
    print"\tdate";
  } else { 
    print "\tvarchar($len)";
  }
  $c=",\n";
}
print ");\n";

if (!$schemaOnly) {
  print "set session datestyle='DMY';\n";
  print "copy $tablename (";
  $c='';
  foreach my $name (@names) {
    print "$c\t$name";
    $c =",\n";
  }
  print ")\n from STDIN CSV HEADER NULL 'NULL';\n";
  #seek(IN,0,0);
   foreach my $line (@lines){
    print $line;
  }
  print "\\.\n";
  close(IN);
}
print "commit;\n";

# foreach $name (sort keys %typeHash) {
#   print STDERR "name $name $typeHash{$name} len $lenHash{$name} max '$maxStringHash{$name}' changer $changer{$name}\n";
# }
