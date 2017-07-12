<?php

//$Id: documentfolders.php 1723 2014-01-03 08:34:59Z hom $
/**
 * shows documents in a folder
 * assumes a lot about the columns
 */
require_once 'fileiconmap.php';
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

/**
 * executes query and invokes documentFoldersPreExecuted
 */
function documentFolders( $dbConn, $sql ) {
  echo getDocumentFolders( $dbConn, $sql );
}

function getDocumentFolders( $dbConn, $sql ) {
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( 'Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . '</pre>' );
  } else {
    return getDocumentFoldersPreExecuted( $resultSet );
  }
}

function documentFoldersPreExecuted( $resultSet ) {
  echo getDocumentFoldersPreExecuted( $resultSet );
}

/**
 * Shows documents in a folder.
 * assumes a lot about the columns.
 */
function getDocumentFoldersPreExecuted( $resultSet ) {
  global $PHP_SELF;
  global $fileimages;
  global $root_url;
  global $upload_path_prefix;
  $today = date( 'Y-m-d' );
  $result =
          "<p style='color:#080;font-weight:bold;'>" .
          "Click on the folder icon (<img src='" . IMAGEROOT . "/folder.png' alt='folder' border='0'/>" .
          " or <img src='" . IMAGEROOT . "/folder_green.png' alt='folder green' border='0'/>) to open the various project related folders.</p>\n";
  $result .= "<table border='0' style='border-collapse:collapse; padding:2pt;' width='100%' align='left'\n"
          . "summary='Overview of documents accessable by student'>\n"
          . "<colgroup><col width='40px' style='border-width:0'/></colgroup>";
  $startColor = '#eeb';
  $startColor = 'rgba(240,240,176,0.4)';
  $oldafko = $afko = '';
  $authorgrp = $oldgrp = '';
  $tdStyle = "style='background:$startColor;padding:3pt;' valign='top'";
  $contin = '';
  $rowcounter = 1;
  while ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
    $opener = $prj_id . ':' . $authorgrp;
    $openTree = in_array( $opener, $_SESSION['openTreeSet'] );
    $folder_name = 'folder';
    if ( !isSet( $long_name ) || !$long_name ) {
      $long_name = 'g' . $author_grp_num;
    }
    if ( $viewergrp == $authorgrp ) {
      $folder_name .='_green';
    }
    if ( $openTree ) {
      $folder_name .='_open';
    }
    $folder_name .= '.png';
    if ( $openTree ) {
      $openerlink = "<a href='$PHP_SELF?closetree=$opener' style='text-decoration:none;'>" .
              "<sup>-</sup><img src='" . IMAGEROOT . "/$folder_name' alt='open folder' border='0'/></a>";
    } else {
      $openerlink = "<a href='$PHP_SELF?opentree=$opener' style='text-decoration:none;'>" .
              "<sup>+</sup><img src='" . IMAGEROOT . "/$folder_name' alt='folder' border='0'/></a>";
    }

    $title = stripslashes( $title );
    if ( !isSet( $long_name ) )
      $long_name = '';
    if ( ($oldafko != $afko) || ( $oldgrp != $authorgrp) ) {
      //	$startColor =$rainbow->getNext();
      $tdStyle = "style='background:$startColor;padding:3pt;' valign='top'";
      $member = ($viewergrp == $authorgrp) ? '<span style=\'color:#080; font-weight:bold;\'>is member</span>' : '';
      $result.= $contin . "\t<thead><tr><th $tdStyle>$openerlink</th><th colspan='7' $tdStyle align='left'>" .
              "$afko, $description, group $author_grp_num \"$long_name\"," .
              " ({$prj_id} Grp {$authorgrp} M{$milestone} {$year}) $member, $doc_count document" . (($doc_count > 1) ? 's' : '') . " </th></tr>\n";
      if ( $openTree ) {
        $result .= "\t<tr><th $tdStyle>Nr</th><th $tdStyle>Remarks?</th><th $tdStyle><span title='Access rights'>RT</span></th>" .
                "<th $tdStyle>Document type</th><th $tdStyle>V#</th><th $tdStyle>Title/direct link</th>" .
                "<th $tdStyle align='right'>file size</th><th $tdStyle>Uploaded by</th><th $tdStyle title='late=red'>Upload date/time</th></tr></thead>\n";
      } else {
        $result .= "\t</thead>\n";
      }
      //	$contin='<tfoot><tr><td $tdStyle></td><td colspan=\'7\''. $tdStyle.'>&nbsp;</td></tr></tfoot>'."\n";
      $rowcounter = 1;
    }
    if ( $openTree ) {
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
      if ( $link == 't' ) {
        $ahref = "<a href='$root_url/upload_critique.php?doc_id=$upload_id' target='_blank'>";
        $bhref = "<a href='downloader/$upload_id/$rel_file_path' target='_blank'>";
        $refEnd = "</a>";
      } else {
        $ahref = '';
        $bhref = '';
        $refEnd = '';
      }
      if ( ('image/jpeg' == $mime_type) || ('image/png' == $mime_type) || ('image/svg+xml' == $mime_type) ) {
        $a_size = getimagesize( $upload_path_prefix . '/' . $rel_file_path );
        $file_size = '' . $a_size[0] . 'x' . $a_size[1];
      } else {
        $file_size = number_format( $filesize, 0, '.', ',' );
      }
      if ( ('image/jpeg' == $mime_type) || ('image/png' == $mime_type) || ('image/svg+xml' == $mime_type) ) {
        $a_size = getimagesize( $upload_path_prefix . '/' . $rel_file_path );
        $file_size = '' . $a_size[0] . 'x' . $a_size[1];
        if ( $link == 't' ) {
          $bhref .="<img src=\"downloader/$upload_id/$rel_file_path\" border='0' style='width:100px;vertical-align:middle;'/>";
        }
      } else {
        $file_size = number_format( $filesize, 0, '.', ',' );
      }

      $result .= "<tr style='background:rgba(255,255,255,0.4)'>"
              . "<td align='right' style='color:#888'>$rowcounter</td>\n"
              . "\t<td align='center'>$critimg</td>\n"
              . "\t<td align='center'>$rights</td>\n"
              . "\t<td >$ahref<img src='" . IMAGEROOT
              . "/$fileimage' alt='$mime_type' border='0' align='left'/>&nbsp;$dtdescr$refEnd</td>\n"
              . "\t<td align='right'>$vers</td><td $tdStyle>$bhref $title $refEnd</td>\n"
              . "\t<td align='right'>$file_size</td>\n"
              . "\t<td align='left'>$roepnaam $tussenvoegsel $achternaam ($snummer)</td>\n"
              . "\t<td $uploadStyle align='center' title='due $due'>$uploadts</td></tr>\n";
    }
    $oldafko = $afko;
    $oldgrp = $authorgrp;
    $resultSet->moveNext();
    $rowcounter++;
    //$result .= $contin;
  }
  $result .= "</table>\n<br/>\n";
  return $result;
}

// end of documentFolders
?>