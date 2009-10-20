#!/bin/bash

# Load config
. /srv/selenium/config.sh

# Calculate random port (must be unprivileged > 1024)
rport=0
while [ $rport -le 1024 ]; do rport=$RANDOM; done

$JAVA -jar $SSJAR -timeout 50 -port $rport \
-htmlSuite "*chrome" "$1" "`pwd`/$2" "$3" >> $LOGDIR/selenium.log 2>&1
