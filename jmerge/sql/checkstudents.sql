select * from worksheet w where not exists (select 1 from public.student where w.snummer=snummer);

