#! /bin/bash
RET=1

while [[ RET -ne 0 ]]; do
    sleep 1;
    mysql -h mysql -uroot -proot -e 'exit' > /dev/null 2>&1; RET=$?
done

sudo -u www-data sh -c "DOCKER=true /var/www/app/console doctrine:database:create"
sudo -u www-data sh -c "DOCKER=true /var/www/app/console doctrine:schema:update --force"
sudo -u www-data sh -c "DOCKER=true /var/www/app/console innmind:server:add self localhost worker.crawler"

/usr/sbin/apache2ctl -D FOREGROUND
