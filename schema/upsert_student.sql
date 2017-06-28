begin work;

prepare upsert_student(
 integer, -- 1 snummer
 character varying(40), -- 2 achternaam
 character varying(10), -- 3 voorvoegsel
 character varying(10), -- 4 roepnaam
 character varying(20), -- 5 voorletters
 character varying(40), -- 6 straat
 character(4), -- 7 huisnt
 character(7), -- 8  pcode
 character varying(40), -- 9 plaats
 character varying(50), -- 10 email1
 character(2), -- 11 nationaliteit
 character(10), -- 12 hoofdgrp 
 boolean, -- 13  active
 smallint, -- 14 cohort
 date, -- 15 gebdat
 character(1), -- 16 sex
 character(2), -- 17 lang
 integer, -- 18 pcn
 bigint, -- 19 opl
 character varying(40), -- 20 phone home
 character varying(40), -- 21 phone_gsm
 character varying(40), -- 22 phone_post
 smallint, 		-- 23 facul
 character varying(50), -- 24 email2
 integer, -- 25 slb
 text, -- 26 image
 integer, -- 27 class_id
 integer, -- 28 studiepan
 character varying(40), -- 29 geboorteplaats
 character(3), -- 30 geboorteland 
 character varying(40) -- 31 voornamen
  )
 as 
 insert into student_email values($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14,
 $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29, $30)
 on conflict (pcn) do nothing ;

execute upsert_student(879416, 'Hombergh', 'van den', 'PJFJ', 'Pieter', 'Kerboschstraat', '12  ', '5913WH ', 'Venlo', 'p.vandenhombergh@fontys.nl', 'NL', 'TUTORINF  ',
true, 1955, '1955-03-18', 'M', 'NL', 879416, 112, '+31 77 3200075',
'+31 653 759 245', '+31 8850 79417', 47, NULL, 877516, '879417.jpg', 411, 864, 'Venlo', 'NLD', 'Petrus Joseph Franciscus Johannes');

