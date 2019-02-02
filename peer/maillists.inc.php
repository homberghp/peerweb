<?php
requireCap(CAP_TUTOR);
/**
 * fill a mail list with the apropriate name. 
 * a cronjob will pick up this list into the email aliases every two minutes.
 * @author hom
 */
function createMaillists($dbConn, $prjm_id) {
    $sql1 = "select lower(trim(afko)) as afko,lower(trim(course_short)) as opl,year \n" .
            " from project join prj_milestone using(prj_id) natural join fontys_course where prjm_id=$prjm_id";
    $resultSet = $dbConn->Execute($sql1);
    if ($resultSet === false) {
        return;
    } else {
        extract($resultSet->fields);
        $mailalias = $opl . '.' . $afko . '.' . $year;
        $maillist_filename = '/home/maillists/' . $mailalias . '.maillist';
        $sql = "select distinct rtrim(email1) email,achternaam\n" .
                " from project_grp_stakeholders join prj_tutor using(prjtg_id) "
                . "join student_email using(snummer) where prjm_id=$prjm_id\n" .
                " order by achternaam\n";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            return;
        } else {

            $handle = fopen("$maillist_filename", "w");
            while (!$resultSet->EOF) {
                fwrite($handle, $resultSet->fields['email'] . "\n");
                $resultSet->moveNext();
            }
            fclose($handle);
            //      @system('/bin/kickaliasappender');
            $dbConn->log("created maillist with address $mailalias@fontysvenlo.org\n");
        }
    }
}

function createGroupMaillists($dbConn, $prjm_id) {
    $sql1 = "select maillist, email1,achternaam,roepnaam from prj_grp_email where prjm_id=$prjm_id \n"
            . "union \n"
            . "select maillist, email1,achternaam,roepnaam from prj_tutor_email where prjm_id=$prjm_id order by maillist,achternaam,roepnaam";
    ;
    $resultSet = $dbConn->Execute($sql1);
    $currentMaillist = '';
    $isOpen = false;
    $handle = 0;

    $lists = '';
    if ($resultSet === false) {
        return;
    } else {
        while (!$resultSet->EOF) {

            if ($currentMaillist != $resultSet->fields['maillist']) {
                if ($isOpen) {
                    fclose($handle);
                }
                $currentMaillist = $resultSet->fields['maillist'];
                $lists .= $currentMaillist . "@fontysvenlo.org<br/>";
                $maillist_filename = '/home/maillists/' . $currentMaillist . '.maillist';
                $handle = fopen("$maillist_filename", "w");
                $isOpen = true;
            }
            fwrite($handle, $resultSet->fields['email1'] . "\n");
            $resultSet->moveNext();
        }
        if ($isOpen) {
            fclose($handle);
        }
        //@system('/bin/kickaliasappender');
        $dbConn->log("created maillists with addresses $lists\n");
    }
}

/**
 * Create a postfix mail list and trigger mailer to update its aliases.
 * @param type $dbConn
 * @param type $prefix
 * @param type $query
 * @return type
 */
function createGenericMaillist($dbConn, $prefix, $query) {
    $resultSet = $dbConn->Execute($query);
    $maillist_filename = '/home/maillists/' . $prefix . '.maillist';
    $count=0;
    if ($resultSet === false) {
        return;
    } else {
        $handle = fopen("$maillist_filename", "w");
        while (!$resultSet->EOF) {
            fwrite($handle, $resultSet->fields['email'] . "\n");
            $count++;
            $resultSet->moveNext();
        }
        fclose($handle);
        //@system('/bin/kickaliasappender');
        if ($count > 0) {
            chmod($maillist_filename, 0664);
            $dbConn->log("created maillist with address $prefix@fontysvenlo.org\n");
        } else {
            unlink($maillist_filename);
            $dbConn->log("removed empty maillist with address $prefix@fontysvenlo.org\n");        
        }
    }
}

/**
 * Generate maillist for class;
 * @param type $dbConn to db
 * @param type $prefix in front of @fontysvenlo.org
 * @param type $class_id class
 */
function createGenericMaillistByClassid($dbConn, $class_id) {
    $prefix ='noprefix';
    $sql ="select lower(rtrim(faculty_short)||'.'||rtrim(sclass)) as prefix from student_class join faculty using(faculty_id) where class_id={$class_id}";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo "$sql";
        return;
    } else {
        $prefix=$resultSet->fields['prefix'];
    }
    createGenericMaillist($dbConn,$prefix,"select email1 as email from student where class_id=$class_id");
}
