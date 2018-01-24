<?php

//$Id: documentfolders2.php 1723 2014-01-03 08:34:59Z hom $
/**
 * shows documents in a folder
 * assumes a lot about the columns
 */
if ( !isSet( $_SESSION['openTreeSet'] ) ) {
  $_SESSION['openTreeSet'] = array( );
}
// get my requestors
if ( isSet( $_REQUEST['opentree'] ) ) {
  array_push( $_SESSION['openTreeSet'], $_REQUEST['opentree'] );
}
if ( isSet( $_REQUEST['closetree'] ) ) {
  unset( $_SESSION['openTreeSet'][array_search( $_REQUEST['closetree'], $_SESSION['openTreeSet'] )] );
  //    echo "closetree". $_REQUEST['closetree']."<br/>";
}
$openallfolders = isSet( $_SESSION['openallfolders'] ) ? $_SESSION['openallfolders'] : false;
if ( isSet( $_REQUEST['openallfolders'] ) ) {
  $openallfolders = $_SESSION['openallfolders'] = true;
}

if ( isSet( $_REQUEST['closeallfolders'] ) ) {
  $openallfolders = $_SESSION['openallfolders'] = false;
}

/**
 * executes query and invokes documentFoldersPreExecuted
 */
function documentFolders( $dbConn, $sql ) {
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( 'Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . '</pre>' );
  } else {
    documentFoldersPreExecuted( $resultSet );
  }
}

/**
 * shows documents in a folder
 * assumes a lot about the columns
 */
function documentFoldersPreExecuted( $resultSet, $urlTail = '' ) {
  global $PHP_SELF;
  //global $fileimages;
  global $upload_path_prefix;
  global $root_url;
  $today = date( 'Y-m-d' );
  $openclosealllink = (isSet( $_SESSION['openallfolders'] ) && $_SESSION['openallfolders'] == true) ? 'closeallfolders=x' : 'openallfolders=x';
  echo "<p style='color:#080;font-weight:bold;'>" .
  "Click on the folder icon <a href='$PHP_SELF?$openclosealllink&$urlTail'>(<img src='" . IMAGEROOT . "/folder.png' alt='folder' border='0'/>" .
  " or <img src='" . IMAGEROOT . "/folder_green.png' alt='folder green' border='0'/>)</a>\n" .
  " to open the various project related folders.
            </p>\n";
  $startColor = '#eeb';
  $startColor = 'rgba(240,240,176,0.6)';

  $oldafko = $afko = '';
  $authorgrp = $oldgrp = '';
  $tdStyle = "style='background:$startColor;padding:3pt;' valign='top'";
  $contin = '';
  $rowcounter = 1;

  echo "<table border='0' style='border-collapse:collapse;' width='100%' align='left'"
  . "summary='Overview of documents accessable by student'>\n"
  . "<colgroup><col width='40px'/></colgroup>";
  if ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
    echo "<thead><tr style='background:rgba(255,255,255,0.4)'><td colspan='9'><h2>$afko, $description, document $dtdescr</h2></td></tr></thead>\n";
  }
  while ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
    $opener = $prj_id . ':' . $authorgrp;
    $openTree = in_array( $opener, $_SESSION['openTreeSet'] );
    $folder_name = 'folder';
    if ( $viewergrp == $authorgrp )
      $folder_name .='_green';
    if ( $openTree )
      $folder_name .='_open';
    $folder_name .= '.png';
    if ( $openTree || $_SESSION['openallfolders'] ) {
      $link = $PHP_SELF . '?closetree=' . $opener . (($urlTail == '') ? '' : '&amp;' . $urlTail);
      $openerlink = "<a href='$link' style='text-decoration:none;'>" .
              "-<img src='" . IMAGEROOT . "/$folder_name' alt='open folder' border='0' align='middle'/></a>";
    } else {
      $link = $PHP_SELF . '?opentree=' . $opener . (($urlTail == '') ? '' : '&amp;' . $urlTail);
      $openerlink = "<a href='$link' style='text-decoration:none;'>" .
              "+<img src='" . IMAGEROOT . "/$folder_name' alt='folder' border='0' align='middle'/></a>";
    }

    $title = stripslashes( $title );
    if ( !isSet( $long_name ) )
      $long_name = '';
    if ( ($oldafko != $afko) || ( $oldgrp != $authorgrp) ) {
      //	$startColor =$rainbow->getNext();
      $tdStyle = "style='background:$startColor;padding:3pt;' valign='top'";
      $member = ($viewergrp == $authorgrp) ? '<span style=\'color:#080; font-weight:bold;\'>is member</span>,' : '';
      echo $contin . "\t<thead><tr style='background:$startColor'><th>$openerlink</th><th colspan='8' align='left'>" .
      "group $grp_num \"$alias: $long_name\"" .
      " ({$prj_id} Grp {$authorgrp} M{$milestone} {$year}) " .
      "$member $doc_count document" . (($doc_count > 1) ? 's' : '') . "</th></tr>\n";
      if ( $openTree || $_SESSION['openallfolders'] ) {
        echo "\t<tr  style='background:$startColor'><th align='right'>Nr</th><th>Remark?</th>"
        . "<th >RT</th><th>Doc Type</td><th>Title/direct link</th><th>Vs</th>"
        . "<th>file size</th><th>Uploaded by</th><th title='late=red'>Upload date/time</th></tr></thead>\n";
      } else {
        echo "\t</thead>\n";
      }
      $rowcounter = 1;
    }
    if ( $openTree || $_SESSION['openallfolders'] ) {
      $fileimage = 'mimetypes' . '/' . str_replace( '/', '-', $mime_type ) . '.png';
      $tdStyle = "style='padding:3pt;' valign='top'";
      $dueStyle = (($viewergrp == $authorgrp) || ($today > $due)) ? "style='color:#080; ;padding:3pt;' valign='top'" : "style='color:#800; ;padding:3pt;' valign='top'";
      if ( $late_or_early == 'late' ) {
        $uploadStyle = "style='padding:3pt;color:#c00;font-weight:bold;' valign='top'";
      } else {
        $uploadStyle = "style='padding:3pt;color:#060;font-weight:bold;' valign='top'";
      }
      $rights = explode( ',', substr( $rights, 1, strlen( $rights ) - 2 ) );
      $rights = (($rights[0] == 't') ? 'r' : '-') . (($rights[1] == 't') ? 'r' : '-');
      $critimg = ($crits > 0) ? "<img src='" . IMAGEROOT . "/attention.png' alt='attention'/>" : '';
      $ahref = "<a href='$root_url/upload_critique.php?doc_id=$upload_id' target='_blank'>";
      $bhref = "<a href='downloader/$upload_id/$rel_file_path' target='_blank'>";
      if ( strlen( $doc_desc ) > 20 ) {
        $doc_desc_t = substr( $doc_desc, 0, 17 ) . '... ';
      } else {
        $doc_desc_t = $doc_desc;
      }
      if ( ('image/jpeg' == $mime_type) || ('image/png' == $mime_type) || ('image/svg+xml' == $mime_type) ) {
        $a_size = getimagesize( $upload_path_prefix . '/' . $rel_file_path );
        $file_size = '' . $a_size[0] . 'x' . $a_size[1];
        $bhref .="<img src=\"downloader/$upload_id/$rel_file_path\" border='0' style='width:100px;vertical-align:middle;'/>";
      } else {
        $file_size = number_format( $filesize, 0, '.', ',' );
      }
      echo "<tr style='background:rgba(255,255,255,0.4)'>\n"
      . "\t<td align='right' style='color:#888'>$rowcounter</td>"
      . "\t<td align='center'>$critimg</td>\n"
      . "\t<td align='center'>$rights</td>\n"
      . "\t<td title='{$doc_desc} Meta data'>{$ahref}{$doc_desc_t}</a></td>\n"
      . "\t<td title='{$doc_desc}'>{$bhref}<img src='" . IMAGEROOT . "/{$fileimage}' alt='{$mime_type}' align='middle' border='0'/>&nbsp;{$title}</a></td>\n"
      . "\t<td align='right'>$vers</td>\n"
      . "\t<td align='right'>$file_size</td>\n"
      . "\t<td >$roepnaam $tussenvoegsel $achternaam ($snummer)</td>\n"
      . "\t<td $uploadStyle align='center' title='due:$due'>$uploadts</td></tr>\n";
    }
    $oldafko = $afko;
    $oldgrp = $authorgrp;
    $resultSet->moveNext();
    $rowcounter++;
    //echo $contin;
  }
  echo "</table>\n</div>\n<br/>\n";
}

// end of documentFolders
?>
