<?php

# $Id: project_selector.php 1846 2015-03-19 14:05:01Z hom $
# show uniform selector in all group def php pages

function getProjectSelector($dbConn, $peer_id, $prj_id,$selname='prj_id', $whereclause = '') {
    // global $dbConn;
    $project_id_sql = "select afko||': '||description||'('||year||')'||' ['||t.tutor||'] ends on '||valid_until as name,\n" .
            " prj_id as value, \n" .
            "year||' ['||t.tutor||']' as namegrp \n" .
            ", case when owner_id=$peer_id then 1 else 0 end as ismine,\n" .
            " case when now()::date > valid_until then 'color:#888' else 'color:0;font-weight:bold' end as style\n" .
            "from project join tutor t on(owner_id=userid)\n" .
            "where prj_id >0 \n";
    if ($whereclause != '') {
        $project_id_sql .=" and {$whereclause} ";
    }

    $project_id_sql .="order by year desc,ismine desc,tutor,valid_until desc,afko";
    //    $dbConn->log( $project_id_sql);
    // $dbConn->log( "\n prj_id=$prj_id\n ");
    $project_selector = "\t<select name='{$selname}' onchange='submit()'>\n"
            . getOptionListGrouped($dbConn, $project_id_sql, $prj_id) . "\n\t</select>\n";
    return $project_selector;
}

?>