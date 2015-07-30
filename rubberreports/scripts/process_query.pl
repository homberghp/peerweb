#!/usr/bin/perl -w
# @Author: Pieter van den Hombergh (879417)
# This script processes a query file and some latex files 
# into a new latex file
# and processes it into a pdf file.
# This pdf file is linked into the report page.
use strict;
use DBI;
use Text::Template;
use Sys::Hostname;
use File::Basename;
use Cwd;
use POSIX qw/strftime/;

my $dbname='peer2';
my $dbuser = 'hom';
my $dbpasswd = 'for2morrow';
my $preamble ='report_preamble.tex';
my $template = 'template.tex';
my $conffile= 'config.txt';
my $queryFile = 'query.sql';
my $ts=strftime('%Y%m%d%H%M%S',localtime);
my $appname=basename(Cwd::cwd());
my $csvfile = "out/$appname-${ts}.csv";
my $dropCount = 0;
my $translateCount = 0;
my $texTemplate = Text::Template->new(SOURCE =>$template,
				      DELIMITERS => ['@-','-@']
    ) or die "Could not construct template $Text::Template::ERROR";

#
my $preambleFile='report_preamble.tex';
my $host=hostname;
print "\\newcommand\\hostname{$host}\n";

if ( -e $preambleFile &&  open(PREAMBLE,"<$preambleFile") ){
    while (<PREAMBLE>){
	print;
    }
    close(PREAMBLE);
}


# Try to find the the translation map file and read the map from that.

my %itemMap;
my ($line,$dummy,$recordCounter);
my ($key,$value);
# fill the itemmap if any.
if ( -e $conffile &&  open(ITEMMAPFILE,"<$conffile") ){
    while(<ITEMMAPFILE>) {
	chomp;
	# chop off cr as well, it might have been added by html editor
	s/\r$//g;
	(my $line,$dummy) = split '#';
	if ($line) {
	    ($key,$value) = split /\s*=\s*/, $line;
	    if ($key && $value) {
		$itemMap{$key} = $value;
	    }
	}
    }
    close(ITEMMAPFILE);
}
foreach $key (sort keys %itemMap) {
    print STDERR "$key => $itemMap{$key}\n";
}

my $query='';
if ( -e $queryFile &&  open(QUERYFILE,"<$queryFile") ){
    while(<QUERYFILE>) {
	$query .= $_;
    }
    close(QUERYFILE);
}
print STDERR "query=$query\n";

my $dbh= DBI->connect("dbi:Pg:dbname=$dbname",$dbuser,$dbpasswd);

my $sth = $dbh->prepare($query);
$sth->execute( );
my %params;
my %itemizeItems;
# Build hash for easy itemizeables lookup.
if ( $itemMap{'itemize'} ) {
    (my @itemNames) = split /\s*,\s*/, $itemMap{'itemize'};
    foreach my $item (@itemNames) {
	$itemizeItems{$item} = $item;
    }
}

# read preamble
my $document_language = 'default';
# 
open(CSVOUT,">$csvfile") or die "cannot open csv file";
my $csvline ="";
my $concat='';
my $CSVheaderWritten=0;
my $CSVheaderLine="";
my $col=0;
while (my @rowarr = $sth->fetchrow_array) {
    for ($col=0; $col < $#rowarr; $col++) {
	$CSVheaderLine .= $concat.$sth->{NAME}->[$col];
	$csvline .= $concat.$rowarr[$col];
	$concat=';';
    }
    if (!$CSVheaderWritten){
	print CSVOUT $CSVheaderLine."\n";
	$CSVheaderWritten=1;
    }
    print CSVOUT $csvline."\n";
    $CSVheaderLine="";
    $csvline="";
    $concat='';
}
close(CSVOUT);

$concat='';
#execute again
$sth->execute( );

while (my $row = $sth->fetchrow_hashref) {
    # first pick out lang if defined.
    if ($$row{'document_language'}) {
	$document_language = $$row{'document_language'};
    } else {
	$document_language = 'default';
    }
    my $texItemize= "\\begin{itemize}\n";
    foreach $key (sort keys %$row) {
	$value = $$row{$key};
	$concat=';';
	my $translation_or_action = $value;
	# trim the value (remove left hand space)
	$value =~ s/\s+$//;
	# lookup if this should be itemized
	if ($itemizeItems{$key}) {
	    # if so, lookup an action (_drop_) or a translation or use the default.
	    # first look at the default.
	    my $default_key="$key.$value.default";
	    # The default translation doubles as (_drop_) action.
	    if ( $itemMap{$default_key} ){
		my $default_action = $itemMap{$default_key};
		if ($default_action ne '_drop_' ) {
		    ++$translateCount;
		    # lookup if there is a translation to the document_language
		    my $lookup_key = "$key.$value.$document_language";
		    if ($itemMap{$lookup_key}) {
			$translation_or_action = $itemMap{$lookup_key};
		    } else {
			$translation_or_action = $default_action;
		    }
		    $texItemize .= "\t\\item  $translation_or_action\n"; 
		    
		} else {
		    ++$dropCount;
		    $translation_or_action = '_drop_';
		}
	    } else {
		# this should be itemized untranslated.
		$texItemize .= "\t\\item  $translation_or_action\n"; 
	    }
	    print STDERR "item $key => '$value' => $translation_or_action\n";
	} else {
	    # simple translation
	    $params{$key} = $value; 
	    print STDERR "$key => $params{$key}\n";
	}
	
    }
    $csvline="";
    $concat='';
    $texItemize .="\\end{itemize}\n";
    $params{'itemlist'}= $texItemize;
    my $template_out= $texTemplate->fill_in(HASH => \%params);
    if ($document_language) {
	if ($document_language eq 'NL') {
	    print "\\renewcommand\\DE[1]{}\n".
		"\\renewcommand\\NL[1]{\#1}\n".
		"\\selectlanguage{dutch}\n";
	} elsif ($document_language eq 'DE') {
	    print "\\renewcommand\\DE[1]{\#1}\n".
		"\\renewcommand\\NL[1]{}\n".
		"\\selectlanguage{german}\n";
	} else { 
	    # dutch as we are in The Netherlands
	    print "\\renewcommand\\DE[1]{}\n".
		"\\renewcommand\\NL[1]{\#1}\n".
		"\\selectlanguage{dutch}\n";
	    
	}
    }
    print $template_out;
    ++$recordCounter;
} # each fetch row
$dbh->disconnect;
print <<EOF;
\\cleardoublepage
RubberReport Summary\\
\\today\\ \\printtime\\
on \\texttt{$host}\\

This is the end of this RubberReport.

    \\textbf{Closing summary}:\\\\
    \\begin{tabbing}
Translated item count \\= 56789\\kill
    Records read \\> $recordCounter\\\\
    Dropped item count \\> $dropCount\\\\
    Translated item count \\> $translateCount\\\\
    \\end{tabbing}

The translation file \\texttt{$conffile}\\ had the following settings:\\\\
    \\lstinputlisting[language=sh]{$conffile}
\\clearpage
%# add report summary to latex output.

    This report is produced by the service \\textsc{RubberReports} on
    \\today, \\printtime\\ at \\texttt{\\hostname}.\\\\
    The following query was used:\\\\
    \\lstinputlisting[language=sql]{query.sql}

The number pages produced is \\pageref{LastPage}\\footnote{To get this
							       number right you must process {\\LaTeX} the file twice} including
    this report.
    \\clearpage
EOF

    my $postambleFile='report_postamble.tex';
if ( -e $postambleFile &&  open(POSTAMBLE,"<$postambleFile") ){
    while (<POSTAMBLE>) {
	print;
    }
    close(POSTAMBLE);
}

exit 0;

