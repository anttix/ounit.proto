#include <stdlib.h>
#include <unistd.h>

#define SCRIPT "/home/users/anttix/qe.anttix.org/ounit/selenium-htmlsuite.sh"
#define UID 504

int main(int argc, char *argv[], char *envp[]) {
	if(setuid(UID) != 0) {
		perror("setuid");
		exit(2);
	}
	execve(SCRIPT, argv, envp);
	perror(SCRIPT);
}
