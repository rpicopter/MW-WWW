bin_PROGRAMS = mw-ws 
mw_ws_SOURCES = websockify.c websocket.c b64_ntop.c b64_pton.c 
mw_ws_CFLAGS = -Wall
mw_ws_LDFLAGS = 
mw_ws_LDADD = -lmw_core -lrt -lpthread -lssl -lcrypto -lresolv

mwconfdir=$(sysconfdir)/mw 

install-exec-hook:
	mkdir -p $(mwconfdir)
	cp mw-ws.start $(mwconfdir)/
	chmod 666 $(mwconfdir)/mw-ws.start
