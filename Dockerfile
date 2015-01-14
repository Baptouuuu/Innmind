FROM ubuntu:14.04.1

RUN apt-get update -y
RUN apt-get install -y apache2
RUN apt-get install -y php5
RUN apt-get install -y php5-mysqlnd
RUN apt-get install -y curl
RUN apt-get install -y mysql-client

ADD conf/apache.conf /etc/apache2/sites-available/default.conf

RUN rm /etc/apache2/sites-enabled/000-default.conf
RUN a2ensite default

EXPOSE 80

VOLUME /var/www
WORKDIR /var/www

CMD ["/usr/sbin/apache2ctl -D FOREGROUND"]
