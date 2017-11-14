begin work;
drop function if exists peer_password(text);

CREATE FUNCTION peer_password(text) RETURNS text
    AS 'peer_password', 'peer_password'
    LANGUAGE C STRICT;
commit;
