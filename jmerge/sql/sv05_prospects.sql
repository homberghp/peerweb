select * from prospects p
where not exists (select snummer from student where snummer=p.snummer) order by hoofdgrp,achternaam,roepnaam;
