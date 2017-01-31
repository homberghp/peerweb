create or replace function swap_email12(snuma integer) returns integer
language plpgsql
as $swapemail$
declare email_dummy alt_email%rowtype;
begin
	select * from alt_email
      	       where snummer=snuma
       	       for update into email_dummy;
	if not found then
	     raise exception 'cannot find alt email';
	end if;
	update alt_email a set email2 = (select email1 from student where snummer =snuma) where snummer=snuma;
	update student set email1= email_dummy.email2
	       where snummer=snuma and exists (select 1 from alt_email where snummer=snuma);
	return snuma;
end;
$swapemail$;


create or replace function swap_email_project(prjm_ida integer) returns integer
language plpgsql
as $swapemailp$
declare sr alt_email%rowtype;
declare i integer;
begin
	i := 0;
	for sr in select snummer,email2,email3 from alt_email join prj_grp using(snummer) join prj_tutor using (prjtg_id)   where prjm_id =prjm_ida loop
	    execute format('select swap_email12(%s::integer)', sr.snummer);
	    i:= i+1;
	end loop;
	return i;
end;
$swapemailp$
