#!/bin/sh

a=`dirname "$0"`
cd "$a"
dir=`pwd`

useradd -M -s /bin/nologin -d $dir/tmp ouphp
useradd -M -s /bin/nologin -d $dir/selenium selsrv

mkdir /var/www/lollus
cp php.fcgi /var/www/lollus/php
chown -R ouphp:ouphp /var/www/lollus
