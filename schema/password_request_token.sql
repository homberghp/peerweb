begin work;
drop table if exists password_request_token ;
create table password_request_token (
       userid integer references passwd(userid) primary key,
       token text,
       expires timestamp default now()+ '24:00:00'::time
);
commit;


