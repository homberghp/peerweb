begin work;
CREATE EXTENSION if not exists citext;
CREATE DOMAIN email AS citext
  CHECK ( value ~ '^[a-zA-Z0-9.!#$%&''*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$' );
commit;

-- test
SELECT 'foobar@bar.com'::email;
SELECT CAST('foobar@bar.com' AS email);
-- should fail
select 'pit'::email;
