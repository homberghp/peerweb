#!/usr/bin/perl -w
print "$ARGV[0] ";
my $em = $ARGV[0];
if ($em =~ m/^\w+(\w|\-|\.)*\@[a-zA-Z0-9][a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/) {
	print "matches";
}
print "\n";
