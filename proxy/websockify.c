/*
 * A WebSocket to TCP socket proxy with support for "wss://" encryption.
 * Copyright 2010 Joel Martin
 * Licensed under LGPL version 3 (see docs/LICENSE.LGPL-3)
 *
 * You can make a cert/key with openssl using:
 * openssl req -new -x509 -days 365 -nodes -out self.pem -keyout self.pem
 * as taken from http://docs.python.org/dev/library/ssl.html#certificates
 */
#include <stdio.h>
#include <stdarg.h>
#include <errno.h>
#include <limits.h>
#include <getopt.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <netdb.h>
#include <sys/select.h>
#include <fcntl.h>
#include <sys/stat.h>
#include "websocket.h"
#include <mw/msg.h>
#include <mw/shm.h>

uint8_t proxy_debug = 0;

void pdbg(char *format, ...)
{
    if (!proxy_debug) return;

    va_list args;

    va_start(args, format);
    vprintf(format, args);
    va_end(args);
}

char traffic_legend[] = "\n\
Traffic Legend:\n\
    }  - Client receive\n\
    }. - Client receive partial\n\
    {  - Target receive\n\
\n\
    >  - Target send\n\
    >. - Target send partial\n\
    <  - Client send\n\
    <. - Client send partial\n\
";

char USAGE[] = "Usage: [options] " \
               "[source_addr:]source_port \n\n" \
               "  --verbose|-v       verbose messages and per frame traffic\n" \
               "  --debug|-d         debug proxy messages\n" \
               "  --daemon|-D        become a daemon (background process)\n" \
               "  --cert CERT        SSL certificate file\n" \
               "  --key KEY          SSL key file (if separate from cert)\n" \
               "  --ssl-only         disallow non-encrypted connections";

#define usage(fmt, args...) \
    fprintf(stderr, "%s\n\n", USAGE); \
    fprintf(stderr, fmt , ## args); \
    exit(1);

extern pipe_error;
extern settings_t settings;

//this faction is executed for all outgoing data (messages)
//format: data_length (uint8_t) + message_id (uin8_t) + data (uint8_t*)
int ws_msg_serialize(char *target, const struct S_MSG *msg) {
    uint8_t i;

    target[0] = msg->size;
    target[1] = msg->message_id;

    for (i=0;i<msg->size;i++)
        target[i+2] = msg->data[i];

    pdbg("Constructed message: %.*s\n",target,i+2);
    return i+2;
}


//each transmission starts with:
//  1) filter length (uint8_t)
//  2) filter array (uint8_t*)
// and follows by stream of data (repetitive):
//  3) data_length (uint8_t)
//  4) message_id (uint8_t)
//  5) data (uint8_t*)

//this function will be executed for all incoming data
int ws_msg_parse(struct S_MSG *target, const char *buf, int buf_len) {
   // pdbg("Parsing message: %.*s\n",buf,buf_len);
    uint8_t state = 0;
    int i=0;
    uint8_t j=0;

    if (buf_len<2) return 0;
    if (buf[0]+1>buf_len) return 0;

    for (i=0;i<buf_len && state<3;i++) {
        switch (state) {
            case 0: 
                target->size = buf[i];
                state++;
                break;
            case 1:
                target->message_id = buf[i];
                state++;
                break;
            case 2:
                if (j<target->size) {
                    target->data[j++] = buf[i];
                }
                if (j==target->size) state++;
                break;
        }
    }


    if (state==3) //all fine
        return i;

    target->message_id = 0;
    return 0;
}

int8_t ws_msg_parse_filter(uint8_t **shm_filter, uint8_t *shm_filter_length, uint8_t *shm_filter_received, char *buf, int buf_len) {
    int i;
    uint8_t *ptr = NULL;
    if (buf_len<1) return 0; //filter_lenght can be 0
    if (buf[0]+1>buf_len) return 0;
    *shm_filter_length = buf[0];
    pdbg("Got %u filters: ",*shm_filter_length);
    ptr = (uint8_t*)malloc(*shm_filter_length); 
    if(ptr== NULL) 
    {
        printf("\nERROR: Memory allocation did not complete successfully!"); 
        return -1;
    }     
    for (i=0;i<*shm_filter_length;i++) {
       ptr[i] = (uint8_t)buf[i+1];
	   pdbg("%u ",ptr[i]);
    }
    pdbg("\n");
    *shm_filter = ptr;
    *shm_filter_received = 1;
    return i+1;
}


char *xx(const uint8_t *s, int len) {
    static char buf[0xFF];
        int i;

    if (len>(0xFF/3)) {  //3 bytes for every character in s
        sprintf(buf,"[STRING TOO LONG]\0");
        return buf;
    }

        for (i = 0; i < len; ++i) sprintf(buf+(3*i),"%02x ", s[i]);
    buf[3*i] = '\0';

    return buf;
}

void do_proxy(ws_ctx_t *ws_ctx) {
    fd_set rlist, wlist, elist;
    struct timeval tv;
    int maxfd, client = ws_ctx->sockfd;
    unsigned int opcode, left, ret;
    unsigned int tout_start, tout_end, cout_start, cout_end;
    unsigned int tin_start, tin_end;
    ssize_t len, bytes;

    struct S_MSG msg;

    uint8_t shm_filter_received = 0;
    uint8_t shm_filter_length = 0;
    uint8_t *shm_filter;
    uint8_t stop = 0;

    tout_start = tout_end = cout_start = cout_end = 0;
    tin_start = tin_end = 0;
    maxfd = client+1;


    shm_client_init();
    pdbg("Starting proxy loop\n");
    while (!stop) {
        tv.tv_sec = 0;
        tv.tv_usec = 2000000; //every 50ms

        FD_ZERO(&rlist);
        FD_ZERO(&wlist);
        FD_ZERO(&elist);

        FD_SET(client, &elist);


        //monitor client always for reading
        FD_SET(client, &rlist);

        if (cout_end != cout_start) //monitor client for writing only if we have data to write
            FD_SET(client, &wlist);

        ret = select(maxfd, &rlist, &wlist, &elist, &tv);
        if (pipe_error) { stop=1; break; }

        if (FD_ISSET(client, &elist)) {
            handler_emsg("client exception\n");
            break;
        }

        if (ret == -1) {
            handler_emsg("select(): %s\n", strerror(errno));
            stop = 1;
            break;
        } else if ((ret == 0) && (cout_start==cout_end)) { //select timeout - try reading from target only if we have sent out everything
            pdbg("Trying to read from target...\n");
            bytes = 0;
            //cout_start = 0; //this is done when client write is complete
            while (shm_scan_incoming(&msg) && bytes<256) { //we got a message //dont retrieve more than 256 bytes at ones (per proxy loop)
                pdbg("From target: writing into cin_buf+%zd\n",bytes);
                bytes += ws_msg_serialize(ws_ctx->cin_buf+bytes,&msg);
            }
            if (pipe_error) { stop=1; break; }
            pdbg("From target: encoding %zd bytes from cin_buf into cout_buf\n",bytes);
            if (ws_ctx->hybi) {
                cout_end = encode_hybi(ws_ctx->cin_buf, bytes,
                                ws_ctx->cout_buf, BUFSIZE, 1);
            } else {
                cout_end = encode_hixie(ws_ctx->cin_buf, bytes,
                                ws_ctx->cout_buf, BUFSIZE);
                }
            if (bytes < 0) {
                handler_emsg("encoding error\n");
                stop = 1;
                break;
            } else {
                pdbg("From target: done; encoded %u bytes.\n",cout_end);
                traffic("{");                          
            }         
            continue;
        }

        if (FD_ISSET(client, &wlist)) {
            len = cout_end-cout_start;
            pdbg("Trying to write to client %zd bytes from cout_buf+%u\n",len,cout_start);
            bytes = ws_send(ws_ctx, ws_ctx->cout_buf + cout_start, len);
            pdbg("To client: sent %zd bytes.\n",bytes);
            if (pipe_error) { stop=1; break; }
            if (len < 3) {
                handler_emsg("len: %d, bytes: %d: %d\n",
                             (int) len, (int) bytes,
                             (int) *(ws_ctx->cout_buf + cout_start));
            }
            cout_start += bytes;
            if (cout_start >= cout_end) {
                cout_start = cout_end = 0;
                traffic("<");
            } else {                
                traffic("<.");
            }
        }

        if (FD_ISSET(client, &rlist)) {
            pdbg("Trying to read from client into tin_buf+%u ...\n",tin_end);
            bytes = ws_recv(ws_ctx, ws_ctx->tin_buf + tin_end, BUFSIZE-tin_end-1);
            pdbg("From client read %zd bytes\n",bytes);
            pdbg("%s\n",xx(ws_ctx->tin_buf + tin_end,bytes));
            if (pipe_error) { stop=1; break; }
            if (bytes <= 0) {
                handler_emsg("client closed connection\n");
                stop = 1;
                break;
            }
            tin_end += bytes;
 
            pdbg("From client: decoding %u bytes from tin_buf+%u into tout_buf+%u\n",tin_end-tin_start,tin_start,tout_end);
            if (ws_ctx->hybi) {
                len = decode_hybi(ws_ctx->tin_buf + tin_start,
                                  tin_end-tin_start,
                                  ws_ctx->tout_buf+tout_end, BUFSIZE-tout_end-1,
                                  &opcode, &left);
            } else {
                len = decode_hixie(ws_ctx->tin_buf + tin_start,
                                   tin_end-tin_start,
                                   ws_ctx->tout_buf+tout_end, BUFSIZE-tout_end-1,
                                   &opcode, &left);
            }
            pdbg("From client: decoded %zd bytes; left=%u\n",len,left);
            pdbg("%s\n",xx(ws_ctx->tout_buf+tout_end,len));
            if (opcode == 8) {
                handler_emsg("client sent orderly close frame\n");
                stop = 1;
                break;
            }

            if (len < 0) {
                handler_emsg("decoding error\n");
                stop = 1;
                break;
            }
            if (left) {
                tin_start = tin_end - left;
                //printf("partial frame from client");
            } else {
                tin_start = 0;
                tin_end = 0;
            }

            traffic("}");
            tout_end += len;

            if (pipe_error) { stop=1; break; }
            //write immediately to target
            do {
                len = tout_end-tout_start;
                pdbg("To target: sending %zd bytes from tout_buf+%u\n",len,tout_start);
                if (!shm_filter_received) 
                    bytes = ws_msg_parse_filter(&shm_filter,&shm_filter_length,&shm_filter_received, ws_ctx->tout_buf + tout_start, len);
                else bytes = ws_msg_parse(&msg,ws_ctx->tout_buf + tout_start, len);
                pdbg("To target: parsed %u bytes\n",bytes);
                if (msg.message_id)
                    shm_put_outgoing(&msg);

                tout_start += bytes;

                if (bytes) {
                    if (tout_start < tout_end) traffic(">.");
                    else if (tout_start == tout_end) traffic(">");                    
                }

            } while (bytes>0);

            if (bytes<0) { //something terribly wrong happend
                stop = 1; break; 
            }

            pdbg("To target: moving %u bytes of tout_buf+%u to front\n",tout_end-tout_start,tout_start);   
            memmove(ws_ctx->tout_buf,ws_ctx->tout_buf+tout_start,tout_end-tout_start);
            tout_end -= tout_start;
            tout_start = 0;
        }
    }

    if ((shm_filter_received) && (shm_filter_length>0))
        free(shm_filter);


    shm_client_end();
}

void proxy_handler(ws_ctx_t *ws_ctx) {

    handler_msg("Received connection\n");

    if ((settings.verbose) && (! settings.daemon)) {
        printf("%s", traffic_legend);
    }

    do_proxy(ws_ctx);
}

int main(int argc, char *argv[])
{
    int c, option_index = 0;
    static int ssl_only = 0, daemon = 0, run_once = 0, verbose = 0;
    char *found;
    static struct option long_options[] = {
        {"verbose",    no_argument,       &verbose,    'v'},
        {"ssl-only",   no_argument,       &ssl_only,    1 },
        {"daemon",     no_argument,       &daemon,     'D'},
        {"proxy-debug",     no_argument,       &proxy_debug,     'd'},
        /* ---- */
        {"run-once",   no_argument,       0,           'r'},
        {"cert",       required_argument, 0,           'c'},
        {"key",        required_argument, 0,           'k'},
        {0, 0, 0, 0}
    };

    settings.cert = realpath("self.pem", NULL);
    if (!settings.cert) {
        /* Make sure it's always set to something */
        settings.cert = "self.pem";
    }
    settings.key = "";

    while (1) {
        c = getopt_long (argc, argv, "vDdrc:k:",
                         long_options, &option_index);

        /* Detect the end */
        if (c == -1) { break; }

        switch (c) {
            case 0:
                break; // ignore
            case 1:
                break; // ignore
            case 'v':
                verbose = 1;    
                break;
            case 'd':
                dbg_init(0b11111111);            
                proxy_debug = 1;
                break;
            case 'D':
                daemon = 1;
                break;
            case 'r':
                run_once = 1;
                break;
            case 'c':
                settings.cert = realpath(optarg, NULL);
                if (! settings.cert) {
                    usage("No cert file at %s\n", optarg);
                }
                break;
            case 'k':
                settings.key = realpath(optarg, NULL);
                if (! settings.key) {
                    usage("No key file at %s\n", optarg);
                }
                break;
            default:
                usage("");
        }
    }
    settings.verbose      = verbose;
    settings.ssl_only     = ssl_only;
    settings.daemon       = daemon;
    settings.run_once     = run_once;

    if ((argc-optind) != 1) {
        usage("Invalid number of arguments\n");
    }

    found = strstr(argv[optind], ":");
    if (found) {
        memcpy(settings.listen_host, argv[optind], found-argv[optind]);
        settings.listen_port = strtol(found+1, NULL, 10);
    } else {
        settings.listen_host[0] = '\0';
        settings.listen_port = strtol(argv[optind], NULL, 10);
    }
    optind++;
    if (settings.listen_port == 0) {
        usage("Could not parse listen_port\n");
    }

    if (ssl_only) {
        if (access(settings.cert, R_OK) != 0) {
            usage("SSL only and cert file '%s' not found\n", settings.cert);
        }
    } else if (access(settings.cert, R_OK) != 0) {
        fprintf(stderr, "Warning: '%s' not found\n", settings.cert);
    }

    //printf("  verbose: %d\n",   settings.verbose);
    //printf("  ssl_only: %d\n",  settings.ssl_only);
    //printf("  daemon: %d\n",    settings.daemon);
    //printf("  run_once: %d\n",  settings.run_once);
    //printf("  cert: %s\n",      settings.cert);
    //printf("  key: %s\n",       settings.key);

    settings.handler = proxy_handler; 


    start_server();

    return 0;

}
