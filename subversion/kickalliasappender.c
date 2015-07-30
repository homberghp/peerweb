#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
int
main(int argc, char * argv[]){

  execl(
	"/bin/bash",
	"/bin/bash",
	"/home/maillists/aliasappender.sh", (char*) NULL);
}
