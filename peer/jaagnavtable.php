<?php
$tabInterestCount=array();
$tabInterestCount['all']= 1;
$tabInterestCount['tutor'] = hasCap( CAP_TUTOR )?1:0;
$tabInterestCount['mkproject']= hasCap( CAP_MKPROJECT )?1:0;
$tabInterestCount['adminstudent_class']= hasCap( CAP_MKCLASSES )?1:0;
$tabInterestCount['system']= hasCap( CAP_SYSTEM )?1:0;
$tabInterestCount['none']= 0;
$snummer=$_SESSION['snummer'];
$sql = "select count(*) as project_scribe from all_project_scribe where scribe=$peer_id"; 
$resultSet=$dbConn->Execute($sql);
if ($resultSet=== false) {
    die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
} 
$tabInterestCount['project_scribe']=$resultSet->fields['project_scribe'];
// $sql = "select count(prj_id) as nav_deliverable_count from prj_grp join project_deliverables using(prj_id,milestone)\n".
//     " where snummer=$snummer";
// $resultSet=$dbConn->Execute($sql);
// if ($resultSet=== false) {
//     die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
// }
$tabInterestCount['deliverable_count'] = 1;//$resultSet->fields['nav_deliverable_count'];
// $sql = "select count(prj_id) as assessment_count from assessment where contestant=$snummer";
// $resultSet=$dbConn->Execute($sql);
// if ($resultSet=== false) {
//     die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
// }
$tabInterestCount['assessment_count']= 1;//$resultSet->fields['assessment_count'];
// $sql = "select count(prj_id) as project_count from prj_grp where snummer=$snummer and prj_id > 1";
// $resultSet=$dbConn->Execute($sql);
// if ($resultSet=== false) {
//     die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
//  }
$tabInterestCount['project_count'] = 1;//$resultSet->fields['project_count'];
$tabInterestCount['read_result'] = 1;//($resultSet->EOF)?0:1;
$navtable=
  array( // index, defproject, citeria1,2, milestones
	array(
	      'interest' => 'tutor',
	      'toplinktext' => 'Start page/home and class lists',
	      'tooltip' => 'Here you find a home page, class/student photos and class lists',
	      'menu_name' =>'tuthome',
	      'image'=> 'home.png',
	      'subitems' =>
	      array ( // tutor home
		     array( 'target'=> 'tutorhome.php',
			    'tooltip' => 'Home page for tutors',
			    'menu_name' =>'tuthome',
			    'linktext' => 'Tutor Home',
			    'image'=> 'home.png',
			    'interest' =>'all',
			    ),
		     array( 'target' => 'classphoto.php',
			    'tooltip' => 'Class photos',
			    'linktext' =>'Class Photos',
			    'menu_name' =>'classphoto',
			    'image'=> 'kontact_contacts.png',
			    'interest' =>'all',
			    ),
		     array( 'target' => 'classlist.php',
			    'tooltip' => 'Class lists',
			    'linktext' =>'Class lists',
			    'menu_name' =>'classlist',
			    'image'=> 'public.png',
			    'interest' =>'all',
			    ),
		     array( 'target'=> 'buit.php',
			    'tooltip' => 'Formulier jaag actie',
			    'linktext' => 'Jaag scherm',
			    'menu_name' =>'buit',
			    'image'=> 'bowandarrow.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'jaag_buit.php',
			    'tooltip' => 'Resultaat jaag actie',
			    'linktext' => 'Jaag resultaat',
			    'menu_name' =>'jaagbuit',
			    'image'=> 'Boogschutter_thumb.png',
			    'interest' =>'adminstudent_class',
			    ),
		      ), // tutor home
	      ),
	array(
	      'interest' => 'adminstudent_class',
	      'toplinktext' => 'Admin pages',
	      'tooltip' => 'Database administration',
	      'menu_name' =>'studentadmin',
	      'image'=> 'ooffice-extension.png',
	      'subitems' =>
	      array ( // various admin
		     array( 'target'=> 'student_admin.php',
			    'tooltip' => 'Add or edit student data.',
			    'linktext' => 'Student admin',
			    'menu_name' =>'studentadmin',
			    'image'=> 'cervisia.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'buit.php',
			    'tooltip' => 'Formulier jaag actie',
			    'linktext' => 'Jaag scherm',
			    'menu_name' =>'buit',
			    'image'=> 'bowandarrow.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'jaag_buit.php',
			    'tooltip' => 'Resultaat jaag actie',
			    'linktext' => 'Jaag resultaat',
			    'menu_name' =>'jaagbuit',
			    'image'=> 'Boogschutter_thumb.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'tutor.php',
			    'tooltip' => 'Set data on a tutor.',
			    'linktext' => 'Tutor editor',
			    'menu_name' =>'tutoreditor',
			    'image'=> 'cervisia.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'rubberreports.php',
			    'tooltip' => 'Rubber Reports.',
			    'linktext' => 'Rubber Reports',
			    'menu_name' =>'Rubber',
			    'image'=> 'Rubber_small.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'classmaker.php',
			    'tooltip' => 'Update classlists. Move students into student_class.',
			    'linktext' => 'Update student_class',
			    'menu_name' =>'classmaker',
			    'image'=> 'cervisia.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'naw.php',
			    'tooltip' => 'Update Name,address data',
			    'linktext' => 'NA data',
			    'menu_name' =>'naw',
			    'image'=> 'cervisia.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'defstudent_class.php',
			    'tooltip' => 'Update student_class. Add and change class definitions.',
			    'linktext' => 'Class definition',
			    'menu_name' =>'defstudent_class',
			    'image'=> 'cervisia.png',
			    'interest' =>'adminstudent_class',
			    ),
		     array( 'target'=> 'known_courses2.php',
			    'tooltip' => 'List of known courses.',
			    'linktext' => 'Known courses',
			    'menu_name' =>'knowncourses',
			    'image'=> 'cervisia.png',
			    'interest' =>'all',
			    ),
		     array( 'target'=> 'peer_settings.php',
			    'tooltip' => 'Peerweb settings',
			    'linktext' => 'Peerweb settings',
			    'menu_name' =>'Peerweb settings',
			    'image'=> 'cervisia.png',
			    'interest' =>'all',
			    ),
		      ),
	      ),
	array(
	      'interest' => 'system',
	      'toplinktext' => 'Menu Table management',
	      'tooltip' => 'Database administration for the gui menus',
	      'menu_name' =>'menuphp',
	      'image'=> 'cervisia.png',
	      'subitems' =>
	      array ( // table management
		     array( 'target'=> 'menu.php',
			    'tooltip' => 'Table management.',
			    'linktext' => 'Update menu',
			    'menu_name' =>'menuphp',
			    'image'=> 'cervisia.png',
			    'interest' =>'system',
			    ),
		     array( 'target'=> 'menu_items.php',
			    'tooltip' => 'Menu Items.',
			    'linktext' => 'Update menu_item',
			    'menu_name' =>'menuitems',
			    'image'=> 'cervisia.png',
			    'interest' =>'system',
			    ),
		     array( 'target'=> 'menu_option_queries.php',
			    'tooltip' => 'Menus select options.',
			    'linktext' => 'Update menu_option_queries',
			    'menu_name' =>'menuoptionqueries',
			    'image'=> 'cervisia.png',
			    'interest' =>'system',
			    ),
		     array( 'target'=> 'genform.php',
			    'tooltip' => 'Generate simple menu form.',
			    'linktext' => 'Generate Form',
			    'menu_name' =>'formgenerator',
			    'image'=> 'design.png',
			    'interest' =>'system',
			    ),
		     array( 'target'=> 'regextester.php',
			    'tooltip' => 'Test regexes.',
			    'linktext' => 'Rgex tester',
			    'menu_name' =>'regextester',
			    'image'=> 'configure.png',
			    'interest' =>'system',
			    ),
		     array( 'target'=> 'validator_regex.php',
			    'tooltip' => 'Edit regexes.',
			    'linktext' => 'Regex Editor',
			    'menu_name' =>'validator regex editor',
			    'image'=> 'configure.png',
			    'interest' =>'system',
			    ),
		     array( 'target'=> 'validator_map.php',
			    'tooltip' => 'Connect regexes to input fields.',
			    'linktext' => 'Regex Mapper',
			    'menu_name' =>'validator map editor',
			    'image'=> 'configure.png',
			    'interest' =>'system',
			    ),
		      ),
	      ),
	 );
$tutor_navtable=$navtable;
?>