#!/usr/bin/perl -w
# creates lines for pdfpages to put a6 landscape onto a4, double sided
use Getopt::Long;
my ($pagecount,$infilename,$i,
    $p00,$p01,$p02,$p03,$p04,$p05,$p06,$p07,$p08,$p09,
    $p10,$p11,$p12,$p13,$p14,$p15,$p16,$p17,$p18,$p19
    );
my %args;
my $sheetCount;
GetOptions(\%args,
           "file=s") or die "enter valid pdf file name!";
die "Missing -file!" unless $args{file};
$infilename=$args{file};
$pagecount=10;
my $cmd =qq(pdfinfo $infilename);
my $output=`$cmd| grep Pages`;
chomp $output;
$output=~m/^Pages:\s+(\d+)/;
$pagecount=$1;
$sheetCount=int($pagecount/2);
$sheetCount=int(($sheetCount+6)/10);
print STDERR qq($infilename produces $sheetCount sheets\n);
print qq(\\documentclass{article}
\\usepackage[english]{babel}%
\\usepackage[a4paper,noheadfoot,nomarginpar,top=8mm,bottom=8mm]{geometry}% http://ctan.org/pkg/geometry
\\usepackage{pdfpages}% http://ctan.org/pkg/pdfpages
\\begin{document}
);
for ($i=1; $i <= $pagecount; $i+=20){
    $p00=$i;
    $p01 =$i+1;     $p01='{}' unless ($p01 <=$pagecount);
    $p02 =$i+2;     $p02='{}' unless ($p02 <=$pagecount);
    $p03 =$i+3;     $p03='{}' unless ($p03 <=$pagecount);
    $p04 =$i+4;     $p04='{}' unless ($p04 <=$pagecount);
    $p05 =$i+5;     $p05='{}' unless ($p05 <=$pagecount);
    $p06 =$i+6;     $p06='{}' unless ($p06 <=$pagecount);
    $p07 =$i+7;     $p07='{}' unless ($p07 <=$pagecount);
    $p08 =$i+8;     $p08='{}' unless ($p08 <=$pagecount);
    $p09 =$i+9;     $p09='{}' unless ($p09 <=$pagecount);
    $p10 =$i+10;     $p10='{}' unless ($p10 <=$pagecount);
    $p11 =$i+11;     $p11='{}' unless ($p11 <=$pagecount);
    $p12 =$i+12;     $p12='{}' unless ($p12 <=$pagecount);
    $p13 =$i+13;     $p13='{}' unless ($p13 <=$pagecount);
    $p14 =$i+14;     $p14='{}' unless ($p14 <=$pagecount);
    $p15 =$i+15;     $p15='{}' unless ($p15 <=$pagecount);
    $p16 =$i+16;     $p16='{}' unless ($p16 <=$pagecount);
    $p17 =$i+17;     $p17='{}' unless ($p17 <=$pagecount);
    $p18 =$i+18;     $p18='{}' unless ($p18 <=$pagecount);
    $p19 =$i+19;     $p19='{}' unless ($p19 <=$pagecount);
    print qq(\\includepdfmerge[pages=-,nup=2x5,noautoscale,frame]{$infilename,$p00,$p02,$p04,$p06,$p08,$p10,$p12,$p14,$p16,$p18,%
    $infilename,$p03,$p01,$p07,$p05,$p11,$p09,$p15,$p13,$p19,$p17}%
\n);
}
print qq(\\end{document}
);
