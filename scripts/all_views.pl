#!/usr/bin/perl -w

use strict;
use DBI;
use Text::Template;
use Sys::Hostname;
use File::Basename;
use Cwd;
use POSIX qw/strftime/;
my $dbname='peer2';
my $dbuser = 'hom';
my $query= qq (SELECT   c.relname
FROM pg_catalog.pg_class c
     LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
WHERE c.relkind IN ('v','')
      AND n.nspname <> 'pg_catalog'
      AND n.nspname <> 'information_schema'
      AND n.nspname !~ '^pg_toast'
  AND pg_catalog.pg_table_is_visible(c.oid)
ORDER BY 1;);

my $dbh= DBI->connect("dbi:Pg:dbname=$dbname");
my ($key,$row,$relname);
my $sth = $dbh->prepare($query);
$sth->execute( );
while (my $row = $sth->fetchrow_arrayref) {
    ($relname) = @$row;
    print qq(pg_dump -s -t ${relname} ${dbname} > ${relname}.sql\n);
}
