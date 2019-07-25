#!/bin/bash

step=2 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    $(php "/www/wwwroot/tonglingok.com/SubstituteDriving/crontab/tolog.php")
    sleep $step
done

exit 0