#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "postgres.h"
#include "fmgr.h"
#include "generator.h"

PG_MODULE_MAGIC;
 
/* 	$Id$	 */
/*
 *
 */

PG_FUNCTION_INFO_V1(peer_password);

Datum
peer_password(PG_FUNCTION_ARGS)
{
  text     *t = PG_GETARG_TEXT_P(0);
  char     *template=(char *)VARDATA(t);
  int       pwlen   = strlen(template);

  text     *result = (text *) palloc(VARSIZE(t));
  SET_VARSIZE( result, VARSIZE(t) );

  char     *newpasswd=(char *)VARDATA(result);
  mkPasswd(pwlen,template,newpasswd);
  PG_RETURN_TEXT_P(result);
}
