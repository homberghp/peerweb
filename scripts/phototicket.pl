#!/usr/bin/perl -w
use strict;
use DBI;
use utf8;
use Encode qw(encode decode);
use Cwd qw(abs_path realpath);
use File::Basename;
my $scriptPath=dirname(abs_path($0));
my $cpath=realpath( $scriptPath);
my $preamble = realpath($cpath.'/../tex/photocarddef.tex');
my ($pwfile,$dbname,$username,$password,$key,$val,$faculty);
$pwfile=$cpath.'/../etc/jmerge.credentials';
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
my $ubuntu=`lsb_release -r`; 
chomp $ubuntu;
# print STDERR qq('running on $ubuntu'\n);
if ( $ubuntu =~ m/14.04$/) {
#    print STDERR qq(default binmode on $ubuntu\n);
} else {
#    print STDERR qq(alternate binmode on $ubuntu\n);
    binmode STDOUT, ':utf8';
}
my $course_grp='SEBI';
if ($#ARGV == 0) {
    $course_grp=$ARGV[0];
}
my ($snummer,$achternaam,$roepnaam,$voorvoegsels,$course,$lang,$land_phone,$gsm,$email);
my ($postcode_plaats,$volledig_adres,$land,$aanmeldingstatus,$aanmelddatum,$peildatum,$pcn,$sex,$gebdat,$gebplaats);
my ($gebland,$nat);
my ($name);
my $query =qq(select studentnummer as snummer,trim(achternaam) as achternaam,
   trim(roepnaam) as roepnaam,trim(voorvoegsels) as voorvoegsels,
   trim(course_grp) as course_grp,lang,
   coalesce('+'||land_nummer_vast||' '||vast_nummer,'-') as land_phone,
   coalesce('+'||land_nummer_mobiel||' '||mobiel_nummer,'-') as gsm,
    e_mail_privé as email_prive,
  --email_prive,
   postcode_en_plaats,
   volledig_adres,
   land,peildatum,aanmeldingstatus,aanmelddatum,pcn_nummer,geslacht as sex,
   geboortedatum,coalesce(geboorteplaats,'unknown'),
   geboorteland,leidende_nationaliteit
   from importer.sv05_aanmelders where instituutcode =$faculty -- where course_grp like 'SEBIEN%'
   order by course_grp,achternaam,roepnaam);
my $dbh= DBI->connect("dbi:Pg:dbname=$dbname",$username,$password,{pg_utf8_strings =>1});

my $sth = $dbh->prepare($query);
$sth->execute( );
print qq(\\input{$preamble}
%\\begin{document}
);
my $oldcourse='';
while (my $row = $sth->fetchrow_arrayref) {
  ($snummer,$achternaam,$roepnaam,$voorvoegsels,$course,$lang,$land_phone,$gsm,$email,$postcode_plaats,$volledig_adres,$land,$peildatum,$aanmeldingstatus,$aanmelddatum,$pcn,$sex,$gebdat,$gebplaats,$gebland,$nat) =  @$row;
  $name = $roepnaam;
  if (defined $voorvoegsels) {
    $name .=' '.$voorvoegsels;
  }
  $name .=' '.$achternaam;
  if (!defined $pcn) {
      $pcn='';
  } else {
      $pcn .=qq(\@student.fontys.nl);
  }
  print qq(\\def\\Lang{$lang}
\\def\\Course{$course}
\\SaveVerb{Email}:$email:
\\def\\Land{$land}
\\def\\PP{$postcode_plaats}
\\def\\Aanmeldingstatus{$aanmeldingstatus}
\\SaveVerb{VolledigAdres}*$volledig_adres*
\\def\\Aanmelddatum{$aanmelddatum}
\\def\\Aanmeldingstatus{$aanmeldingstatus}
\\def\\Peildatum{$peildatum}
\\def\\GeboorteDatum{$gebdat}
\\def\\GeboortePlaats{$gebplaats}
\\def\\GeboorteLand{$gebland}
\\def\\Nationaliteit{$nat}
\\SaveVerb{PCN}:$pcn:
);
  if ($oldcourse ne $course){
      print qq(\\addcontentsline{toc}{section}{\\Course}
);
      $oldcourse=$course;
  }
  print qq(\\photocard{$name ($sex)}{$course}{$snummer}{$lang}{$land_phone}{$gsm}\n);
}
$dbh->disconnect;
# print qq(
# \\makeatletter
# \\renewcommand\\tableofcontents{\\\@starttoc{toc}}
# \\makeatother
# \\def\\Course{Contents}
# \\def\\Lang{End}
# \\setlength{\\columnsep}{2em}
# \\setlength{\\columnseprule}{0.2pt}

# \\begin{multicols}{2}
# \\tableofcontents
# \\end{multicols}
# );
print qq(\\end{document}
);
exit(0);

