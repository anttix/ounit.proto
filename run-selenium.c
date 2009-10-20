#include <stdlib.h>
#include <unistd.h>

#define SCRIPT "/srv/ounit/selenium-htmlsuite.sh"
#define UID 501 // selsrv user

int main(int argc, char *argv[], char *envp[]) {
	if(setuid(UID) != 0) {
		perror("setuid");
		exit(2);
	}
	execve(SCRIPT, argv, envp);
	perror(SCRIPT);
}
