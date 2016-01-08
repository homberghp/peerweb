begin work;
drop function if exists try_close(integer,integer);

CREATE FUNCTION try_close(gid integer,stid integer) RETURNS boolean as $try_close$
   DECLARE allwritten BOOLEAN;
   DECLARE any_open BOOLEAN;
   DECLARE prj_tutor_open BOOLEAN;
   DECLARE assessment_complete BOOLEAN;
BEGIN
   -- close for this judge
   UPDATE prj_grp SET written=true,prj_grp_open=false WHERE prjtg_id=gid AND snummer=stid;
   SELECT bool_and(written) AS allwritten,
          bool_or(prj_grp_open) AS any_open, 
          prj_tutor.prj_tutor_open, prj_tutor.assessment_complete
   INTO allwritten,any_open,prj_tutor_open,assessment_complete
   FROM public.prj_grp 
   JOIN prj_tutor USING(prjtg_id)
   WHERE prjtg_id=gid 
   GROUP by prjtg_id,prj_tutor.prj_tutor_open,prj_tutor.assessment_complete;
   
   -- if nothing to be done, return false
   IF (NOT allwritten OR any_open) 
      AND NOT prj_tutor_open 
      OR assessment_complete THEN
   -- nothing to do
      RETURN FALSE; 
   ELSE 
   -- else, lose
      UPDATE prj_tutor SET prj_tutor_open=FALSE, assessment_complete=TRUE 
      WHERE prjtg_id=gid;
      RETURN TRUE;
   END IF;


end $try_close$ language plpgsql;
comment on function try_close(integer,integer) is 'Close prj_grp assessment when all other 
judges in group have written and closed';

commit;
