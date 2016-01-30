<?php
class GroupPhoto {
    private $dbConn;
    private $pictHeight = 192;
    private $pictWidth = 128;
    private $MAXROW = 4;
    private $MAXCOL = 6;
    private $prjtg_id = 1;
    private $whereConstraint = ' true';

    function setPictSize($w, $h) {
        $this->pictWidth = $w;
        $this->pictHeight = $h;
        return $this;
    }

    function setWhereConstraint($c) {
        $this->whereConstraint = $c;
        return $this;
    }

    function setMaxCol($c) {
        $this->MAXCOL = $c;
        return $this;
    }

    function __construct($conn, $prjtg_id) {
        $this->dbConn = $conn;
	if ($prjtg_id) {
	  $this->prjtg_id = $prjtg_id;
	}
    }

    function getGroupPhotos() {
        $result = "<!-- get group photos -->\n";
        $sql = "select afko,year,description,pt.grp_num,p.prj_id," 
	  ." pm.prjm_id,ga.alias as grp_alias,pm.milestone,pt.prjtg_id,tut.tutor\n" 
          ." from project p join prj_milestone pm using(prj_id) " 
          ."join prj_tutor pt using(prjm_id) join prj_grp pg using(prjtg_id) join tutor tut on(pt.tutor_id=tut.userid) " 
          ."left join grp_alias ga using(prjtg_id)\n"
	  . "where prjtg_id=" . $this->prjtg_id;
        $resultSet = $this->dbConn->Execute($sql);
        if ($resultSet === false) {
            die("<br>Cannot get student data with \"" . $sql . '", cause ' .
                    $this->dbConn->ErrorMsg() . "<br>");
        }
        extract($resultSet->fields);
        $sql = "select snummer,\n" .
                "roepnaam||' '||coalesce(voorvoegsel||' ','')||achternaam as name,\n" .
                "gebdat as birthday, roepnaam,achternaam,voorvoegsel,pcn,role,\n" .
                "sclass,'fotos/'||image as image\n" .
                "from prj_grp pg join prj_tutor pt using(prjtg_id)\n" .
                " join prj_milestone pm using(prjm_id)\n" .
                "join student_email sem using(snummer)\n" .
                "join student_class c using (class_id) \n" .
                " natural left join " .
                "student_role natural left join project_roles\n" .
                "where prjtg_id="
                . $this->prjtg_id
                . "\n and $this->whereConstraint \n"
                . " order by achternaam,roepnaam";

        $resultSet = $this->dbConn->Execute($sql);
        if ($resultSet === false) {
            die("<br>Cannot data with \"" . $sql . '", cause <pre>' . $this->dbConn->ErrorMsg() . "</pre><br>");
        }

        $colcount = 0;
        $rowcount = 0;
        $tablehead = "<table align='center' width='100%' border='0' style='border-collapse:collapse'>\n"
                . "<thead>\n\t<caption style='font-weight:bold;font-size:120%'>Group photos for project $afko: $description $year-" . ($year + 1)
                . " prj_id={$prj_id}  milestone {$milestone} (prjm_id={$prjm_id}) grp {$grp_num}"
                . " (prjtg_id={$prjtg_id}) {$grp_alias} tutor {$tutor}"
                . "</caption>\n</thead>\n";
        while (!$resultSet->EOF) {
            if ($rowcount == 0 && $colcount == 0) {
                $result .= $tablehead;
            }
            if ($colcount == 0)
                $result .= "<tr>\n";
            extract($resultSet->fields);
            $result .= "\t<th valign='top' width='$this->pictWidth' align='middle'>"
                    . "<img src='$image' alt='$image' border='0' "
                    . "style='width:".$this->pictWidth."px; height=auto;box-shadow:3px 3px 3px #008"
                    .";border-radius:".($this->pictWidth/8)."px'/>"
                    . "\n\t\t<div>\n"
                    . "\t\t\t<b>$name</b><br/>$snummer"
                    . "\n\t\t</div>\n\t</th>\n";
            $colcount++;
            if ($colcount >= $this->MAXCOL) {
                $result .= "</tr>\n";
                $colcount = 0;
                $rowcount++;
                if ($rowcount >= $this->MAXROW) {
                    $result .="</table>\n";
                    $rowcount = 0;
                }
            }
            $resultSet->moveNext();
        }
        if ($colcount != 0)
            $result .= "</tr>\n";
        if ($rowcount != 0)
            $result .= "</table>\n";
        $result .="<!-- end getgroupphotos -->";
        return $result;
    }

    /* end of class GroupPhoto; */
}

?>