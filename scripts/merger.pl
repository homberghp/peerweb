#!/usr/bin/perl -w
# creates lines for pdfpages to pu a6 landscape onto a4, double sided
use Getopt::Long;
my ($pagecount,$infilename,$i,$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8);
my %args;
my $sheetCount;
GetOptions(\%args,
           "file=s") or die "enter valid pdf file name!";
die "Missing -file!" unless $args{file};
$infilename=$args{file};
my $cmd =qq(pdfinfo $infilename);
my $output=`$cmd| grep Pages`;
chomp $output;
$output=~m/^Pages:\s+(\d+)/;
$pageCount=$1;
$sheetCount=int($pageCount/2);
$sheetCount=int(($sheetCount+3)/4);
my $pp = $sheetCount*2;
print STDERR qq($infilename produces $sheetCount sheets\n);
print qq(\\documentclass[landscape,twoside]{article}
\\usepackage[english]{babel}%
\\usepackage[a4paper,scale={0.85,0.8},inner=10mm,outer=10mm]{geometry}% http://ctan.org/pkg/geometry
\\usepackage{graphicx}
\\usepackage[dvipsnames]{xcolor}
\\usepackage{pdfpages}% http://ctan.org/pkg/pdfpages
\\usepackage{fancyhdr}
\\renewcommand{\\headrulewidth}{0pt}
\\renewcommand{\\footrulewidth}{0pt}
\\fancyhead{}
\\cfoot{}
\\fancyhead[ER,OL]{{\\sffamily\\bfseries\\color{Gray}\\Large{}A}}
\\fancyhead[EL,OR]{\\sffamily\\bfseries\\color{Gray}\\Large{}B}
\\fancyfoot[ER,OL]{{\\sffamily\\bfseries\\color{Gray}\\Large{}C}}
\\fancyfoot[EL,OR]{\\sffamily\\bfseries\\color{Gray}\\Large{}D}
\\begin{document}
\\pagestyle{fancy}
\\includepdfset{pagecommand={\\thispagestyle{fancy}}}
);
for ($i=1; $i <= $pp; $i+=2){
    $p1=$i;
    $p2=$i+$pp; $p2='{}' unless ($p2 <=$pageCount);
    $p3=$i+2*$pp; $p3='{}' unless ($p3 <=$pageCount);
    $p4=$i+3*$pp; $p4='{}' unless ($p4 <=$pageCount);
    $p5=$i+1;$p5='{}' unless ($p5 <=$pageCount);
    $p6=$i+$pp+1; $p6='{}' unless ($p6 <=$pageCount);
    $p7=$i+2*$pp+1; $p7='{}' unless ($p7 <=$pageCount);
    $p8=$i+3*$pp+1; $p8='{}' unless ($p8 <=$pageCount);
    print qq(\\includepdfmerge[pages=-,nup=2x2,noautoscale]{$infilename,$p1,$p2,$p3,$p4,$infilename,$p6,$p5,$p8,$p7}%\n);
}
print qq(\\end{document}
);
