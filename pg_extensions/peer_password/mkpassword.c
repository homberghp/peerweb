#include "generator.h"
void usage(int argc, char * argv[]);
int getURandomSeed();

int 
main(int argc, char * argv[])
{
  int pwlen;
  int i;
  int rcount;
  int pwcount = 1;
  char template[MAXPWLEN+1];
  char newpasswd[MAXPWLEN+1];
  
  strncpy(template,base_template,sizeof(template)-1);
  if (argc > 1) {
    if (strchr("aA@&bB1~.x",argv[1][0]) == NULL){
      usage(argc,argv);
      exit(1);
    }
    strncpy(template,argv[1],sizeof(template)-1);
    template[sizeof(template)-1]='\0';
    if ( argc > 2 ) {
	pwcount = atoi(argv[2]);
    }
  }
  
  pwlen=strlen(template);
  while (pwcount) {
    mkPasswd(pwlen,template,newpasswd);
      printf("%s%s",newpasswd,(pwcount>1)?" ":"");
      pwcount--;
  }
  exit(0);
}

void usage(int argc, char * argv[]) {
  fprintf(stderr,"Usage: %s [passwordtemplate]\n",argv[0]);
  fprintf(stderr,"\twhere passwordtemplate = any combination of the chars \"%s\" \n",base_template);
  fprintf(stderr,"\twith a total length not exceeding %d chars\n",MAXPWLEN);
  fprintf(stderr,"\ta => any of \"%s\"\n",vowels);
  fprintf(stderr,"\tA => any of \"%s\"\n",vowelsu);
  fprintf(stderr,"\t@ => any of \"%s\"\n",vowelsb);
  fprintf(stderr,"\t%% => any of \"%s\"\n",consonants);
  fprintf(stderr,"\tb => any of \"%s\"\n",cons2);
  fprintf(stderr,"\tB => any of \"%s\"\n",cons3);
  fprintf(stderr,"\t~ => any of \"%s\"\n",anyletter);
  fprintf(stderr,"\t1 => any of \"%s\"\n",digits);
  fprintf(stderr,"\t. => any of \"%s\"\n",punct);
}

