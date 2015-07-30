--
-- get student data from database only if relevant columns have these values

select snummer,initcap(trim(s.achternaam))as achternaam,
       initcap(trim(s.roepnaam)) as roepnaam,
     trim(coalesce(s.voorvoegsel,'')) as tussenvoegsel,
     trim(course_description) as omschrijving_studieprog,
     lang as document_language,
     hoofdgrp,
     sex,
     case when sex ='M' and lang = 'DE' then 'Lieber'
     	  when sex ='F' and lang = 'DE' then 'Liebe' 
	  else 'Beste'
     end as anrede,
     to_char(gebdat,'YYYY/MM/DD') as gebdat_tex
     from student s join fontys_course on(opl=course) 
     WHERE snummer in (879417,879305,870521,877516)
     order by hoofdgrp,achternaam;
