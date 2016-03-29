FROM ubuntu

ENV PASSWD 1q2w3e4r

RUN sed -i -e"s/archive/br\.archive/" /etc/apt/sources.list

RUN apt-get update && apt-get install -y git apache2 php5 mysql-server php5-mysql

RUN /usr/sbin/mysqld & \
    sleep 10s &&\
    mysql -uroot -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD('${PASSWD}'); GRANT ALL ON *.* TO root@'%' IDENTIFIED BY '${PASSWD}' WITH GRANT OPTION; FLUSH PRIVILEGES; create database sharif"

RUN rm -rf /var/www/html

RUN git clone http://github.com/maurostorch/Sharif-Judge /var/www/html
RUN chown -R www-data:www-data /var/www
RUN sed -i -e"s/'username' => '',/'username' => 'root',/" /var/www/html/application/config/database.php
RUN sed -i -e"s/'password' => '',/'password' => '${PASSWD}',/" /var/www/html/application/config/database.php
RUN sed -i -e"s/'database' => '',/'database' => 'sharif',/" /var/www/html/application/config/database.php
RUN chmod 755 /var/www/html/application/cache/Twig

EXPOSE 80
CMD ['service', 'mysql', 'start']

ENV APACHE_RUN_USER    www-data
ENV APACHE_RUN_GROUP   www-data
ENV APACHE_PID_FILE    /var/run/apache2.pid
ENV APACHE_RUN_DIR     /var/run/apache2
ENV APACHE_LOCK_DIR    /var/lock/apache2
ENV APACHE_LOG_DIR     /var/log/apache2
ENV LANG               C

CMD ["apache2", "-D", "FOREGROUND"]
