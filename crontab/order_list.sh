#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

if [ -f ~/.bash_profile ];
then
  . ~/.bash_profile
fi

step=1 #间隔的秒数，不能大于60
for (( i = 0; i < 60; i=(i+step) ));
do
  curl -dump  https://www.tonglingok.com/api/v1/order/list/handel
  sleep $step
done
exit 0
