#include <string.h>
#include <stdlib.h>
#include <sys/types.h>
#include <unistd.h>

int main (int argc, char *argv[])
{
    if (argc != 2)
    {
        return EXIT_FAILURE;
    }

    if (strcmp(argv[1], "start") != 0 && strcmp(argv[1], "stop"))
    {
        return EXIT_FAILURE;
    }

    char command[80];
    strcpy(command, "service lfw_server ");
    strcat(command, argv[1]);
    setuid (0);

    /* WARNING: Only use an absolute path to the script to execute,
     *          a malicious user might fool the binary and execute
     *          arbitary commands if not.
     * */
    system(command);

    return EXIT_SUCCESS;
}
