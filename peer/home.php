<?php
requireCap(CAP_DEFAULT);
include_once('tutorhelper.php');
require_once'navigation2.php';
/**
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: home.php 1761 2014-05-24 13:17:31Z hom $
 * Redirect to real main page
 */
$page = new PageContainer();
$page->setTitle('Welcome to peerweb');
$page->addHeadComponent(new Component("
<style type='text/css'>
    p {text-align: justify;}
    p:first-letter {font-size:180%; font-family: script;font-weight:bold; color:#800;}
 </style>"));

if (file_exists('fotos/' . $judge . '.jpg')) {
    $foto = 'fotos/' . $judge . '.jpg';
} else {
    $foto = 'fotos/0.jpg';
}

$lang = strtolower($lang);
$page_opening = "Hello $roepnaam $tussenvoegsel $achternaam <a href='myface.php'><img src='$foto' alt='you' style='width:32px;height:auto;'/></a><span style='font-size:60%;'>($snummer)</span>, this is <i>Peerweb</i>: the place where you share with your fellow students<br/> Click on your face to see yourselves a bit better.";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
ob_start();
?>
<table class='layout' style='layout:fixed;border-collapse:collapse;' border='0' summary='layout'>
    <colgroup>
        <col width='80%'/>
        <col width='20%'/>
    </colgroup>
    <tr>
        <td valign='top' width='80%' style='padding:0px'>
                <h1 class='normal' style='width:100%;top:0px;margin:0;'>Peerweb?</h1>
            <div style='padding: 0 1em 0 1em'>
                <?php if ($lang == 'nl') { ?>
                    <p>Peerweb is een plaats waar je bestanden en beoordelingen kunt
                        delen (en uitdelen!) met medestudenten. 	Je kunt elkaar
                        beoordelen op verschillende criteria, van belang als je samen in
                        een project zit. Je kunt bestanden delen door ze hier op te
                        slaan. Je kunt ook bestanden bekijken die je groepsleden hebben
                        opgeslagen.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>Peerweb is a place where you can share (and give) files and assessments 
                        with your fellow students. You can grade each other on several
                        criteria, of importance	if you are in the same project. You
                        can share files by storing them here. You can also watch files
                        uploaded by teammembers, or after the due date the module participants.
                    </p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>Peerweb ist ein Platz, an dem man Dateien teilen (und verteilen!) kann
                        mit anderen Mitstudenten. Es ist m&ouml;glich sich gegenseitig zu bewerten 
                        anhand von verschiedenen Kriterien mit unterschiedlicher Gewichtung, sofern
                        man sich im selben Projekt befindet. Ebenso ist es m&ouml;glich Dateien zu 
                        betrachten, welche von Team Mitgliedern upgeloaded wurden. Nach einer 
                        abgelaufenen Deadline ist es m&ouml;glich die Dateien der anderen Modulteilnehmer (also anderer Gruppen)
                        anzuschauen.
                    </p>
                <?php } ?>
                <?php if ($lang == 'nl') { ?>
                    <h2 class='normal'>Werkwijze</h2>
                    <p>Met peerweb kun je op verschillende manieren werken:
                        <strong>1</strong> beoordelen en beoordeeld worden,
                        <strong>2</strong> projecten bestanden delen en
                        <strong>3</strong> feedback geven op de producten van de
                        anderen.</p> 
                    <p>Of je aan een of meerdere delen kunt meedoen, hangt af van wat
                        de docent voor het project instelt.</p>  
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <h2 class='normal'>Way of working</h2>
                    <p>With peerweb you can work in several ways:
                        <strong>1</strong> grade and be graded
                        <strong>2</strong> share project files
                        <strong>3</strong> give feedback on the products of
                        others.</p>
                    <p>Wether you can participate in one or more parts depends on
                        what the teacher set for the your project(s)</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <h2 class='normal'>Arbeitsweise</h2>
                    <p>Mit Peerweb kann man auf verschiedene Arten arbeiten:
                        <strong>1</strong> bewerten und bewertet werden
                        <strong>2</strong> Projekt Dateien verteilen
                        <strong>3</strong> Feedback geben f&uuml;r fertige Produkte von anderen.</p>
                    <p>Ob man an verschiedenen Teilen des Systems teilnehmen kann, ist abh&auml;ngig
                        von den Voreinstellungen die der Dozent f&uuml;r das/die Projekt(e) getroffen hat.
                    </p>
                <?php } ?>
                <?php if ($lang == 'nl') { ?>
                    <h2 class='normal'>Elkaar beoordelen op een aantal criteria</h2>
                    <div class='notice' style='width:15%;' onclick='new Effect.Puff(this)'>Ben jij het meeliften van anderen ook zo moe?</div>
                    <p>Als de docent de mogelijkheid gebruikt om de studenten elkaar
                        te laten beoordelen bij het projectwerk, dan kiest de docent een
                        aantal beoordelingcriteria. Aan de hand van deze criteria zullen
                        de studenten in een groep elkaar beoordelen. De criteria gaan in
                        de meeste gevallen over kwaliteit en kwantiteit van je bijdrage
                        aan het groepswerk. Het beoordelen kan meerdere keren per
                        project gebeuren bij zogenaamde mijlpalen (milestones). Per
                        mijlpaal kan de groepssamenstelling gewijzigd worden.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <h2 class='normal'>Assess each other on a number of criteria</h2>
                    <div class='notice' style='width:15%' onclick='new Effect.Puff(this)'>Are you bored of free riders too?</div>
                    <p>If a teacher makes use of the possibility to let fellow students 
                        assess each other, then theacher will select the assessment criteria.
                        Guided by these criteria, the students within a group can assess each other.
                        In most cases the criteria are about quality an quantity of you contribution to 
                        the group work. The assessment can take place several times during a project, 
                        on so called <i>milestones</i>. Per milestone the groups can be changed.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <h2 class='normal'>Gegenseitige Beurteilung anhand einer Anzahl von verschiedenen Kriterien</h2>
                    <div class='notice' style='width:15%' onclick='new Effect.Puff(this)'>Bist du auch genervt von Leuten, die Trittbrett fahren und die Arbeit scheuen?</div>
                    <p>Wenn ein Dozent Gebrauch von der M&ouml;glichkeit der gegenseitigen Bewertung 
                        der Studenten macht legt er bestimmte Bewertungskriterien zu Grunde.
                        Anhand dieser Kriterien sollen die Studenten einer Gruppe sich gegenseitig beurteilen.
                        In den meisten F&auml;llen beziehen die Kriterien sich auf Qualit&auml;t und Quantit&auml;t 
                        deiner Teilnahme / Einbringung in die Gruppenarbeit. Die Beurteilung kann 
                        mehrmals w&auml;hrend eines Projektes stattfinden, immer bei Abschluss eines sogenannten 
                        <i>Milestones</i>. Pro Milestone ist es m&ouml;glich die Gruppe zu wechseln.</p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <p>Tijdens het invullen maak je gebruik van je
                        eigen invul pagina <a
                            href='ipeer.php'><?= $langmap['beoordelen'][$lang] ?></a>. Daar
                        geef je de beoordeling van je 
                        medegroepsleden in.</p> 
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>While filling out the assessment, you use a private form <a
                            href='ipeer.php'><?= $langmap['beoordelen'][$lang] ?></a>. There you enter 
                        your appreciation of your fellow groupmembers.</p> 
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>F&uuml;r das Assessment wird ein privates Formular benutzt <a
                            href='ipeer.php'><?= $langmap['beoordelen'][$lang] ?></a>. Dort gibst du die 
                        Beurteilung deiner Gruppenmitglieder ein.</p> 
                <?php } ?>
                <?php if ($lang == 'nl') { ?>
                    <p>Hoe de medestudenten jou beoordelen, met andere woorden je resultaat, is
                        zichtbaar op de pagina <a
                            href='iresult.php'><?= $langmap['resultaat'][$lang] ?></a>. Daar
                        kun je beoordelingen zien zodra iedereen van je groep klaar
                        is. Vanaf dat moment kun je de beoordelingen ook niet meer
                        wijzigen. De student die het laatste invult trekt als het ware
                        de deur achter zich dicht. Alleen de docent kan die deur weer
                        tijdelijk open zetten.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>How other students appreciate you, in other words your result, 
                        can be found on page <a href='iresult.php'><?= $langmap['resultaat'][$lang] ?></a>. 
                        There you can see your appraisal once everyone in your group is ready filling out their form.
                        From that moment on, you cannot alter your assessment anymore. It is as if the last one
                        filling out the form closes the door. Only the teacher can reopen the door.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>Wie andere Studenten dich beurteilen, mit anderen Worten dein Ergebnis kann auf dieser Seite
                        eingesehen werden. <a href='iresult.php'><?= $langmap['resultaat'][$lang] ?></a>. 
                        Dort kannst du deine Einsch&auml;tzung durch die Gruppe sehen, wenn alle Mitglieder fertig sind
                        mit dem Ausf&uuml;llen des Bewertungsformulars.
                        Von diesem Momement an ist es nicht mehr m&ouml;glich Bewertungen zu &auml;ndern. Es ist als ob der letzte die
                        T&uuml;r abgeschlossen hat. Lediglich dem Dozenten ist es m&ouml;glich diese T&uuml;r zu &ouml;ffnen.</p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <p>Op de resultaat pagina kun je alleen zien wat je
                        medestudenten je gemiddeld per criterium gegeven hebbben. Dit
                        wordt daarnaast ook gerelateerd aan het gemiddelde cijfer dat in
                        die groep voor een bepaald criterium is uitgedeeld.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>On the result page you can only see what your fellow students gave 
                        you as an average grade per criterium.
                        This average itself is also related to the average grade of the whole group
                        on a certian criterium.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>Auf der Resultat/Ergebnis Seite kannst du nur sehen wie deine Mitstudenten dich 
                        durchschnittlich pro Kriterium bewertet haben.
                        Dieser Durchschnitt steht in Relation zu der durchschnittlichen Note der Gruppe 
                        f&uuml;r ein bestimmtes Kriterium.
                    </p>
                <?php } ?>
                <?php if ($lang == 'nl') { ?>
                    <p>De docent kan de resultaten inzien en ze gebruiken om
                        naar de individuele inzet van studenten te differenti&euml;ren.
                        Hij gebruikt daarvoor het gemiddeld cijfer dat de student
                        voor een criterium gekregen heeft gerelateerd aan het groepsgemiddelde
                        voor dat criterium. Deze verhouding heet op deze site de
                        <i>mulitiplier</i>. 
                        Het systeem geeft daarbij een suggestie voor
                        het individuele cijfer, door bijvoorbeeld het cijfer voor de
                        groep te vermenigvuldigen met de multiplier. Een multiplier van
                        <i>1</i> betekent dat je precies gelijk aan het groepsgemiddelde
                        gescoord hebt. Lager als 1 betekent dat je je zou moeten
                        verbeteren, hoger dan een betekent dat je goed zit. Je zou dit
                        systeem kunnen vergelijken met het krijgen van een cijfer budget dat je
                        over de groepsleden mag verdelen.
                    </p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>The teacher can view all results and may use them to differentiate group grades
                        to the individual effort of the student. Therefor he uses
                        the average grade of that student divided by the average of the group on that criterium.
                        This ratio is called <i>mulitiplier</i> on this site. 
                        The system makes a suggestion for the individual grade by multiplying the group grade with this multiplier.
                        A multiplier of <i>1</i> means that you scored exaclty equal to the group average. Below 1 says that you should improve, above
                        average says that your group is content with your effort. This system is comparable to having a limited budget
                        of grades that you may share with your fellow students.
                    </p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>Der Dozent kann s&auml;mtliche Resultate einsehen und kann diese nutzen um die Noten in der Gruppe zu 
                        differenzieren und somit den Fortschritt eines jeden Studenten zu ermitteln.
                        Daf&uuml;r benutzt er die Durchschnittsnote des Studenten dividiert durch den Gruppendurchschnitt f&uuml;r dieses Kriterium.
                        Dieses Verh&auml;ltnis auf dieser Seite ist der <i>Multiplikator</i>. 
                        Das System macht einen Vorschlag f&uuml;r die individuelle Note, indem es die Note der Gruppe mit dem Multiplikator multipliziert. 
                        Ein Multiplikator von <i>1</i> bedeutet, dass du genau im Gruppendurchschnitt bist. Niedriger als 1 bedeutet, dass
                        du dich verbessern solltest, &uuml;berdurchschnittlich bedeutet, dass die Gruppe mit dir und deinem Engagement sehr zufrieden ist. 
                        Dieses System ist vergleichbar mit einem limitierten Budget an Noten das man mit seinen Mitstudenten teilt.
                    </p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <h2 class='normal'>Bestanden delen</h2>
                    <p>Tijdens projectwerk bestaan veel van de producten die je
                        maakt uit bestanden, zoals verslagen, onderzoeksresultaten,
                        broncode  of configuratie bestanden. Deze bestanden zul je
                        moeten delen met je groepsgenoten zodat zij ook kennis kunnen
                        nemen van jou bijdrage. Dat is nodig om het totale project te
                        kunnen overzien, maar ook om te kunnen leren van jou bijdrage.
                        Door hier de bestanden op te slaan, kan elk groepslid ze
                        zien. Dat groepslid kan ook feedback geven, maar daar komen we zo meteen
                        op terug.
                    </p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <h2 class='normal'>Sharing files</h2>
                    <p>During project work, many artifacts are in fact files, such as reports, 
                        research results, source code or configuration files. These files will 
                        have to be shared with your group members, so they may take notice (and learn) from
                        your contribution. This is necessary, to be able to keep an overview of the whole project.
                        By storing your files here, any group member can get at them. 
                        That group member can also give feedback, but that we will address shortly.
                    </p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <h2 class='normal'>Sharing files</h2>
                    <p>W&auml;hrend der Projektarbeit, sind sehr viele Materialien Dateien, wie zum Beispiel Berichte, 
                        Recherche Ergebnisse, Quellcode oder Konfigurationsdateien. 
                        Diese Dateien m&uuml;ssen den anderen Gruppenmitgliedern zug&auml;nglich sein, so dass diese von 
                        deiner Leistung wissen und lernen k&ouml;nnen.
                        Dies ist notwendig, um sich einen &Uuml;berblick &uuml;ber das gesamte Projekt verschaffen zu k&ouml;nnen.
                        Dadurch das die Dateien hier gespeichert werden, erh&auml;lt jedes Gruppenmitglied Zugriff auf diese.
                        Am Rande sei erw&auml;hnt, dass Gruppenmitglieder Feedback geben k&ouml;nnen.
                    </p>
                <?php } ?>
                <?php if ($lang == 'nl') { ?>
                    <div class='notice' style='width:15%;float:right'>Deze files zijn openbaar voor de hele groep.</div>
                    <p>
                        Bij het opslaan (uploaden) kun je vaak kiezen tussen meerdere
                        documenttypen. De mogelijk typen wordt door de docent
                        bepaald. Hij legt ook vast <i>hoeveel versies</i> van dat type jij mag
                        uploaden. De naam van het bestand (max 127 characters) alsmede
                        de titel (max 80 characters) mag je zelf kiezen. Bij voorkeur
                        bestanden uploaden van bestandstype <strong>.pdf</strong>. Dat kan op elke
                        computer gelezen worden. De docent zal ervoor kiezen om alleen
                        bestanden te beoordelen die hier ge-upload zijn.
                    </p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <div class='notice' style='width:15%;float:right'>The files are (group) public.</div>
                    <p>Before uploading you can choose between several document types. These types are determined by your teacher.
                        He or she also fixes <i>how many versions</i> of that type you may upload.
                        The name of the file (max 127 characters) as well as the title
                        (max 80 characters) can (and must) be set by you. Preferably upload files
                        of the file type <strong>.pdf</strong>. That can be viewed on any computer.
                        The teacher may choose to only assess files that are uploaded on this system.
                    </p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <div class='notice' style='width:15%;float:right'>Die Dateien sind &ouml;ffentlich f&uuml;r die ganze Gruppe.</div>
                    <p>	Bevor eine Datei hochgeladen wird kann man deren Typ festlegen.
                        Die Dateitypen k&ouml;nnen von dem Dozent festgelegt werden.
                        Er oder sie kann ebenso festlegen <i>wievele Versionen</i> eines Typs man hochladen kann.
                        Der Dateiname (max 127 Zeichen) und ebenso der Titel (max 80 Zeichen) k&ouml;nnen (und m&uuml;ssen)
                        von der festgelegt werden. Vorzugsweise sollten Dateien (Dokumente) vom Typ <strong>.pdf</strong> hochgeladen werden,
                        da diese auf jedem Computer betrachtet werden k&ouml;nnen.
                        Der Dozent kann sich daf&uuml;r entscheiden nur Dateien zu evaluieren, welche hier in dieses System hochgeladen wurden.
                    </p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <p><strong>Dit is geen versie beheerssysteem!</strong> Studenten
                        die documenten en of broncode bestanden onder versie beheer
                        (CVS) willen zetten moeten daarvoor de <a
                            href='http://www.fontysvenlo.org'>cvs server</a> 
                        gebruiken. Pas als er een versie is die gedeeld moet worden,
                        dan wordt een copie daarvan hier neergezet.</p>

                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p><strong>This is not a version control system!</strong> Students
                        that want to use version control on documentes and/or sourcecode
                        (e.g. CVS) should use the cvs server as described at <a
                            href='http://www.fontysvenlo.org'>cvs server</a>. 
                        Only if there is a version that has to be shared with fellows and the teacher, you should place a copy here.
                    </p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p><strong>Dies ist kein Versionsverwaltungs System!</strong> Studenten
                        die eine Versionsverwaltung f&uuml;r Dokumente und/oder Quellcode nutzen m&ouml;chten
                        (z.B. CVS) sollten den CVS Server wie beschrieben auf <a
                            href='http://www.fontysvenlo.org'>cvs server</a> benutzen. 
                        Nur wenn eine bestimmte Version f&uuml;r Gruppenmitglieder und Dozent ben&ouml;tigt wird, sollte hier eine Kopie hinterlegt werden. 
                    </p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <h2 class='normal'>Elkaar feedback geven op de gedeelde producten</h2>
                    <p>Een van de moderne didactische inzichten is dat het bestuderen van
                        elkaars bijdrage en daar dan feedback op geven een aantal doelen
                        dient. Het geven van deze feedback geeft juist de winst, wat
                        betekent dat niet alleen je geuploade documenten maar ook je
                        feedback beoordeeld wordt. Maak je daar dus niet al te
                        gemakkelijk van af. In onderzoekingen is aangetoond dat degene
                        die de meest relevante feedback geeft, de materie het beste
                        beheerst. Hij of zij zal dan ook het beste cijfer verdienen.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <h2 class='normal'>Giving feedback on shared files</h2>
                    <p>A modern didactical viewpoint is, that studying each other&rsquo;s contribution and giving feedback on it, serves
                        a number of goals. The gain is realised by giving feedback. This means that not only your uploaded documents are
                        evaluated, but also your feedback! So do not take this giving feedback lightly.
                        Research has shown that the one giving the most relevant feedback, is in best control of the subject. 
                        He or she will therefor be entitled to the better grades.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <h2 class='normal'>Feedback geben f&uuml;r geteiltete Dateien</h2>
                    <p>Ein modernes didaktisches Konzept ist, da&szlig; das studieren der Beitr&auml;ge von Anderen und darauf Feedback geben mehrere Ziele dient. 
                        Das geben von Feedback erbringt diesen Gewin. Das hei&szlig;t das nicht nur die hochgeladene Dokumente evaluiert werden aber auch das Feeback das Du gibst!
                        Nimm das geben von Feedback also nicht zu leicht. Untersuchungen haben gezeigt das derjenige der der Relevanteste Feedback gibt, sich mit das Thema am besten auskennt.
                        Er oder sie sollte dann auch die bessere Noten bekommen.</p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <p>Het geven van feedback kan op elk document dat door je groep
                        is aangedragen. Dit kun je doen via de pagina <a
                            href="uploadviewer.php" title="view uploaded documents of
                            projects" class="navtop">
                            <?= $langmap['uploadview'][$lang] ?></a></p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>You can give feedback on any document that is supplied by your group.
                        You can give feedback via the page 
                        <a href="uploadviewer.php" title="view uploaded documents of projects" class="navtop"><?= $langmap['uploadview'][$lang] ?></a></p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>Du kannst Feedback geben f&uuml;r jedes Dokument das von deiner Gruppe bereitgestellt wird.
                        Feedback kann anhand dieser Seite gegeben werden
                        <a href="uploadviewer.php" title="view uploaded documents of projects" class="navtop"><?= $langmap['uploadview'][$lang] ?></a></p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <p>Je kunt op die pagina de voor jou zichtbare documenten
                        kiezen, ze lezen (eventueel online) en daarbij er feedback op
                        geven. Je gegevens, datum en natuurlijk je feedback worden
                        opgeslagen en zijn voor iedereen zichtbaar.</p>
                    <p>Een slimme auteur zorgt ervoor dat er pagina en
                        regelnummers in het document voorkomen, zodat degene die feeback
                        geeft via pagina en regelnummer precies kan aangeven waar een
                        kritiekpunt zit.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>You can select the documents visible to you, read them (online if you want) and give
                        feedback on them. Your data, date and of course your feedback will be stored and be visible to
                        all who can access the document.</p>
                    <p>A clever writer takes care that the document hase page and <strong>line</strong> numbers, so that
                        the critiquer can give precise coordinates in his feedback.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>Du kannst die auf der Seite f&uuml;r dich sichtbaren Dokumente ausw&auml;hlen und sie (online wenn du m&ouml;chtest) lesen und Feedback geben.
                        Deine Daten, Datum und nat&uuml;rlich auch dein Feedback werden gespeichert und sind sichtbar f&uuml;r 
                        alle die Zugriff auf das Dokument haben.</p>
                    <p>Ein schlauer Schreiber achtet darauf, dass das Dokument mit Seiten- und <strong>Zeilen</strong>nummern versehen ist, um es dem
                        Kritiker in seinem Feedback zu erm&ouml;glichen bestimmte Stellen zu nennen.</p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <p>De kritieken die je krijgt op je werk dien je te verwerken in
                        een verbeterde versie van het document. Dat kun je zolang doen
                        totdat je de door de docent ingestelde versie limiet (typisch 3)
                        bereikt hebt.</p>

                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <p>The critiques you receive should be used to improve your document, so you can upload an improved version.
                        You can improve your document as often as allowed by the version limit set by the teacher (typically 3).
                    </p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <p>Kritiken die du erh&auml;lst sollten genutzt werden, um dein Dokument zu verbessern, so dass du eine 
                        verbesserte Version in das System stellen kannst. Es ist m&ouml;glich das Dokument zu verbessern, so oft wie das vom 
                        Dozenten eingestellte Versionslimit es zul&auml;sst (normalerweise 3).
                    </p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <h2 class='normal'>Email</h2>
                    <p>Dit systeem stuurt emailtjes naar de deelnemers. Bijvoorbeeld
                        als een beoordeling compleet is, of als er een bestand wordt
                        geupload. Dan gaat er een emailtje naar de groepsleden en de
                        tutor. Hetzelfde geldt bij het geven van kritiek. De auteur van het document ontvangt daarvan ook een emailtje. 
                        Omdat het
                        systeem van alle studenten een offci&euml;el student email adres
                        heeft, wordt daar de mail naar toegestuurd. Je kunt hier ook een
                        mailtje sturen naar al je groepsleden, bijvoorbeeld als je ziek
                        bent. Daarvan gaat dan ook een kopie naar de docent. Omdat veel
                        studenten meerdere email adressen hebben, kun je ook een email
                        adres toevoegen. Naar dat extra email adres wordt dan telkens een mailtje
                        gestuurd met de strekking 'you have mail'.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <h2 class='normal'>Email</h2>
                    <p>This system sends email to the participants; students and teachers. For instance
                        if an assessment is complete (by the last student in the group) or when a file is uploaded.
                        In those cases an email is sent to the group members and the teacher.
                        The same applies when giving feedback. The author of the document will receive an email when a critique hase been given.
                        The system has all official university email addresses of the students, and that will be used to send the email to.
                        You can also send emails to your group members, for instance to let them know you are ill.
                        Of these email, a copy will be sent to the teacher. Because many students have several email addresses,
                        you can also add an email address. This additional email address will be used to send a notification as in
                        'you have mail'.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <h2 class='normal'>Email</h2>
                    <p>Dieses System sendet Emails an die Teilnehmer; Studenten und Dozenten. 
                        Zum Beispiel wenn eine Bewertung beendet wurde (durch den letzten Studenten der Gruppe) oder
                        wenn eine Datei hochgeladen wurde.
                        In diesen F&auml;llen wird eine Email an die Gruppenmitglieder und den Dozenten versandt.
                        Das selbe gilt f&uuml;r gegebenes Feedback. Der Autor des Dokuments erh&auml;lt eine Email, wenn eine Anmerkung/Kritik gegeben wurde.
                        Das System hat Zugriff auf alle offiziellen Universit&auml;ts Email Adressen von Studenten und diese werden f&uuml;r das Versenden 
                        der Emails genutzt. Solltest du zum Beispiel einmal krank sein, so kannst du deine Gruppenmitglieder &uuml;ber dieses System informieren.
                        Von dieser Email erh&auml;lt der Dozent dann automatisch eine Kopie.
                        Weil Studenten f&uuml;r gew&ouml;hnlich mehrere Email Adressen haben, ist es m&ouml;glich eine Adresse hinzuzuf&uuml;gen.
                        Diese zus&auml;tzliche Email Adresse wird genutzt f&uuml;r eine Benachrichtigung mit dem Betreff 'you have mail'.</p>
                <?php } ?>

                <?php if ($lang == 'nl') { ?>
                    <h2 class='normal'>Tijd schrijven</h2>
                    <p>In sommige gevallen is het nodig om tijd te schrijven bij projecten. Dit systeem voorziet daarin. 
                        De geschreven tijd op een bepaald project is ook zichtbaar vor deelnemers in dat project. Zie de <a href='settings.php'>settings pagina</a> voor je instellingen 
                        en eigen tijd record.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <h2 class='normal'>Keeping a time record</h2>
                    <p>In some cases it is necessary to keep time on a project. That service is also provided by this system.
                        The records on behalf of a certain project can also be seen by the other team members. See the <a href='settings.php'>settings page</a> for your settings on your own time records.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <h2 class='normal'>Eine Zeit&uuml;bersicht f&uuml;hren</h2>
                    <p>In manchen F&auml;llen ist es notwendig eine Zeit&uuml;bersicht f&uuml;r ein Projekt zu f&uuml;hren.
                        Dieser Service wird ebenso von dem System bereitgestellt.
                        Die Zeit&uuml;bersicht f&uuml;r ein bestimmtes Projekt kann auch von anderen Gruppenmitgliedern eingesehen werden
                        Siehe <a href='settings.php'>Einstellungs Seite</a> f&uuml;r deine eigenen Einstellungen von Zeit&uuml;bersichten.</p>
                <?php } ?>
                <?php if ($lang == 'nl') { ?>
                    <h2 class='normal'>Uitloggen</h2>
                    <p>Door uit te loggen met de rode knop rechts boven of helemaal onderaan, kun
                        je er voor zorgen dat niemand anders op dezelfde werkplek onder
                        jouw naam aan de slag gaat.</p>
                <?php } ?>
                <?php if ($lang == 'en') { ?>
                    <h2 class='normal'>Log off</h2>
                    <p>By logging off with the red button top right or at the bottom, you can prevent that someone else uses your account.</p>
                <?php } ?>
                <?php if ($lang == 'de') { ?>
                    <h2 class='normal'>Abmelden</h2>
                    <p>Indem du dich mit dem roten Button oben rechts oder unten abmeldest, kannst du verhindern das dein Account missbraucht werden kann.</p>
                <?php } ?>
            </div>
        </td>
    </tr></table>	
<!-- db_name=<?= $db_name ?> -->
<!-- $Id: home.php 1761 2014-05-24 13:17:31Z hom $-->
<?php
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addHeadText('
<script src="' . $root_url . '/js/scriptaculous/prototype.js" type="text/javascript"></script>
<script src="' . $root_url . '/js/scriptaculous/scriptaculous.js" type="text/javascript"></script>
');
$page->show();
?>
