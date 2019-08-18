step=1 #间隔的秒数，不能大于60
for (( i = 0; i < 60; i=(i+step) ));
do
  curl -dump  https://www.tonglingok.com/api/v1/order/push/no/handel
  sleep $step
done
exit 0