#include <stdio.h>
#include <sys/inotify.h>

#define NOB_IMPLEMENTATION
#include "nob.h"

// =================== [Settings] =================== //

const char* excludes[] = {
    "./App/Templates",
    "./App/Models/Instances",
};

#define APP_DIR "./App"
#define PORT "6969"

#define EVENT_BUF_LEN 1024

// =================== [Types] =================== //

typedef struct {
    const char** items;
    size_t count;
    size_t capacity;
} DA_String;

typedef struct {
    int fd;
    int wd;
} Watch;

typedef struct {
    Watch* items;
    size_t count;
    size_t capacity;
} DA_Watch;

// =================== [Functions] =================== //

// bool filter_folders_and_chop(String_View* sv)
// {
//     if (!sv_ends_with_cstr(*sv, ".php")) return false;
//     for (size_t i = 0; i < ARRAY_LEN(excludes); i++) {
//         if (sv_starts_with(*sv, sv_from_cstr(excludes[i]))) return false;
//     }
//     sv_chop_prefix(sv, sv_from_cstr("./"));
//     sv_chop_suffix(sv, sv_from_cstr(".php"));
//     return true;
// }

bool on_file_print(Walk_Entry entry)
{
    if (entry.type != NOB_FILE_REGULAR) return true;
    printf("%s\n", entry.path);
    return true;
}

bool on_file_watch(Walk_Entry entry)
{
    if (entry.type != NOB_FILE_DIRECTORY) return true;
    DA_Watch* w = entry.data;
    int fd = inotify_init1(O_NONBLOCK);
    if (fd == -1) {
        nob_log(NOB_ERROR, "Unable to init inotify for %s", entry.path);
        return false;
    }
    int wd = inotify_add_watch(fd, entry.path, IN_MODIFY | IN_CREATE | IN_DELETE | IN_ONLYDIR);
    if (wd == -1) {
        nob_log(NOB_ERROR, "Unable to add watch %s directory", entry.path);
        close(fd);
        return false;
    }
    da_append(w, ((Watch) {
        .fd = fd,
        .wd = wd,
    }));
    return true;
}

Cmd cmd = {0};
Procs procs = {0};

bool start_debug_php_server()
{
    cmd_append(&cmd, "php");
    cmd_append(&cmd, "-S", "localhost:" PORT);
    if (!cmd_run(&cmd, .async = &procs)) {
        nob_log(NOB_ERROR, "Unable to start php server");
        return false;
    }
    nob_log(NOB_INFO, "PHP server started");
    return true;
}

bool open_localhost()
{
    cmd_append(&cmd, "xdg-open");
    cmd_append(&cmd, "http://localhost:" PORT);
    if (!cmd_run(&cmd)) return false;
    return true;
}

bool stop_php_server()
{
    if (procs.count == 0) return true;
    int p = da_first(&procs);
    if (kill(p, SIGTERM) == -1) {
        nob_log(NOB_ERROR, "Unable to stop php proccess");
        kill(p, SIGKILL);
        return false;
    }
    int status;
    waitpid(p, &status, 0);
    procs.count = 0;
    nob_log(NOB_INFO, "PHP server stopped");
    return true;
}

// =================== [Main] =================== //

int main(int argc, char** argv)
{
    NOB_GO_REBUILD_URSELF(argc, argv);

    int result = 0;

    if (argc < 2) {
        nob_log(NOB_INFO, "Usage: ./nob [test|watch]");
        return_defer(1);
    }

    const char* command = argv[1];

    if (strcmp(command, "print") == 0) {
        if (!walk_dir("./", on_file_print)) {
            nob_log(NOB_ERROR, "Unable to triverse %s directory for print", APP_DIR);
            return_defer(1);
        }
    } else if (strcmp(command, "test") == 0) {
        // DA_String paths = {0};
        // if (!walk_dir(APP_DIR, on_file_test, .data = &paths)) {
        //     nob_log(NOB_ERROR, "Unable to triverse %s directory for tests", APP_DIR);
        //     return_defer(1);
        // }
        //
        // cmd_append(&cmd, "./tests.php");
        // da_foreach(const char*, path, &paths) {
        //     cmd_append(&cmd, *path);
        // }

        cmd_append(&cmd, "php");
        cmd_append(&cmd, "./tests.php");
        if (!cmd_run(&cmd)) return 1;
    } else if (strcmp(command, "watch") == 0) {
        DA_Watch w = {0};
        if (!walk_dir(APP_DIR, on_file_watch, .data = &w)) {
            nob_log(NOB_ERROR, "Unable to walk directory %s", APP_DIR);
            return_defer(1);
        }

        {
            const char* index_file = "./index.php";
            int fd = inotify_init1(O_NONBLOCK);
            if (fd == -1) {
                nob_log(NOB_ERROR, "Unable to init inotify for %s", index_file);
                return false;
            }
            int wd = inotify_add_watch(fd, index_file, IN_MODIFY | IN_CREATE | IN_DELETE);
            if (wd == -1) {
                nob_log(NOB_ERROR, "Unable to add watch on %s", index_file);
                close(fd);
                return false;
            }
            da_append(&w, ((Watch) {
                .fd = fd,
                .wd = wd,
            }));
            const char* init_file = "./init.php";
            fd = inotify_init1(O_NONBLOCK);
            if (fd == -1) {
                nob_log(NOB_ERROR, "Unable to init inotify for %s", init_file);
                return false;
            }
            wd = inotify_add_watch(fd, init_file, IN_MODIFY | IN_CREATE | IN_DELETE);
            if (wd == -1) {
                nob_log(NOB_ERROR, "Unable to add watch on %s", init_file);
                close(fd);
                return false;
            }
            da_append(&w, ((Watch) {
                .fd = fd,
                .wd = wd,
            }));
        }

        if (!start_debug_php_server()) {
            return_defer(1);
        }
        if (!open_localhost()) return_defer(1);

        nob_log(NOB_INFO, "Watching %s", APP_DIR);

        char buf[EVENT_BUF_LEN];
        while (1) {
            bool modified = false;
            da_foreach(Watch, it, &w) {
                int fd = it->fd;
                ssize_t len = read(fd, buf, EVENT_BUF_LEN);
                if (len <= 0 && errno != EAGAIN) {
                    nob_log(NOB_ERROR, "Unable to read event");
                    return_defer(1);
                }
                if (len <= 0) {
                    continue;
                }

                ssize_t i = 0;
                while (i < len) {
                    struct inotify_event *event = (struct inotify_event *)&buf[i];
                    if (event->mask & IN_MODIFY) {
                        nob_log(NOB_INFO, "File modified: %s", event->name);
                    } else if (event->mask & IN_CREATE) {
                        nob_log(NOB_INFO, "File created: %s", event->name);
                    } else if (event->mask & IN_DELETE) {
                        nob_log(NOB_INFO, "File deleted: %s", event->name);
                    } else {
                        nob_log(NOB_INFO, "Something happens: %s", event->name);
                    }
                    i += sizeof(struct inotify_event) + event->len;
                }
                modified = true;
            }
            if (modified) {
                if (!stop_php_server())  return_defer(1);
                if (!start_debug_php_server()) return_defer(1);
            }
            sleep(1);
        }

    defer:
        stop_php_server();
        da_foreach(Watch, it, &w) {
            inotify_rm_watch(it->fd, it->wd);
            close(it->fd);
        }
    }
    return result;
}
