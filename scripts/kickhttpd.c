#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
int
main(int argc, char * argv[]){

  setegid(0);
  seteuid(0);
  setgid(0);
  setuid(0);
  execl("/usr/sbin/service"
	,"/usr/sbin/service"
	,"apache2"
	,"reload"
	, (char*) NULL);
}
