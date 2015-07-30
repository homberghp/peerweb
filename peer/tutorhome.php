<?php
include_once('./peerlib/peerutils.inc');
include_once('navigation2.inc');
require_once('component.inc');
requireCap(CAP_TUTOR);
$page = new PageContainer();
$page->setTitle('Peer assessment for tutors');
$page->addHeadComponent( new Component("<style type='text/css'>
 p {text-align: justify;}
 p:first-letter {font-size:180%; font-family: script;font-weight:bold; color:#800;}
 
 </style>"));
$page_opening="Peer assessment, what&#39;s in it for me, the tutor?";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$newsfile='news.html';
$news_text= file_get_contents($newsfile, true);
if ($news_text !== false ) {
    $nav->addLeftNavText($news_text);
 }
//ob_start();
include 'birthdays.php';
$bd= new BirthDaysToDay();
$rightColumn=$bd;
//ob_clean();
$maintext= new Component("<div style='padding:1em'>
<table><tr><td width='60%'>
   <h2 class='normal' style='text-align:center;'>The why for this site.</h2>
    <h4>Peer assessment</h4>
    <p>This site started as a help to lessen the burden of doing the administrative work 
      when executing a peer assessment, where students value each other on several criteria.
      The intend is that the criteria are not fixed but can be defined per project or course.</p>
    <h4>Upload repository for files to be shared.</h4>
    <p>At a certain moment the need arose to have a central upload facility for documents that are to
      be handed in by the students as products produced in their project activities. 
      The documents can be handed in per category, determined by the teaching staff 
      (and entered in the database by the/a tutor). The system support versions of those deliverables.
      To prevent disk overflow and denial of service a maximum number
      of versions can be set. A due date is also supported 
      (but currently not actively checked upon). Diffs etc (like in
      CVS) are not supported. This is a showcase, not a versioning
      system. 
      Until the due date the documents that are uploaded are only
      visible to the group members. After the due date they are
      visible to other groups that participate in this (year's)
      version of the same module or course. This allows a intra group
      review cycle between hand in and due date and  
      a inter group review once the due date has passed.</p>
    <h4>Peer critiques and feedback.</h4>
    <p>Once the first draft of the upload and version support was in
      place, we implemented functionality to let students (and
      tutors) give feedback to the documents that are uploaded. This
      is more or less modeled after the VAL or peer review
      concept. The students (within a group) can give improvement
      suggestions (critiques) to the uploaded documents.</p>
   <p>If you have any bugs or improvement suggestions, visit our trac <a href='https://www.fontysvenlo.org/trac/generic/peerweb/wiki/'>bug tracking system</a>.</p>

      </td><td>$rightColumn</td>
      </tr></table></div>
    ");
$page->addBodyComponent( $maintext);
$page->show();
?>