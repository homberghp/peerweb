#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
int
main(int argc, char * argv[]){

  execl("/usr/bin/killall","/usr/bin/killall","-USR1","/usr/sbin/apache2", (char*) NULL);
}
