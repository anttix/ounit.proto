#!/bin/sh

# Path points to 
PATH=`echo /usr/lib/firefox-*`:$PATH
SSJAR=/home/users/anttix/qe.anttix.org/selenium/selenium-server.jar
JAVA=/home/users/anttix/qe.anttix.org/jdk/bin/java
DISPLAY=:666
HOME=/home/users/anttix/qe.anttix.org/selenium
export PATH DISPLAY HOME

# Calculate random port (must be unprivileged > 1024)
rport=0
while [ $rport -le 1024 ]; do rport=$RANDOM; done

$JAVA -jar $SSJAR -timeout 50 -port $rport \
-htmlSuite "*chrome" "$1" "`pwd`/$2" "$3" >> ~sylekoer/logs/selenium.log 2>&1
