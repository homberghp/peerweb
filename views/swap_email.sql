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


