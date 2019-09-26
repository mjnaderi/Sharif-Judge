FROM ubuntu

ENV PASSWD 1q2w3e4r

RUN sed -i -e"s/archive/br\.archive/" /etc/apt/sources.list

RUN apt-get update && apt-get install -y git apache2 php5 mysql-server php5-mysql gcc g++-4.8 openjdk-7-jdk python2.7 python3-all

RUN /usr/sbin/mysqld & \
    sleep 10s &&\
    mysql -uroot -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD('${PASSWD}'); GRANT ALL ON *.* TO root@'%' IDENTIFIED BY '${PASSWD}' WITH GRANT OPTION; FLUSH PRIVILEGES; create database sharif"

RUN rm -rf /var/www/html

RUN git clone http://github.com/maurostorch/Sharif-Judge /var/www/html
RUN chown -R www-data:www-data /var/www
RUN sed -i -e"s/'username' => '',/'username' => 'root',/" /var/www/html/application/config/database.php
RUN sed -i -e"s/'password' => '',/'password' => '${PASSWD}',/" /var/www/html/application/config/database.php
RUN sed -i -e"s/'database' => '',/'database' => 'sharif',/" /var/www/html/application/config/database.php
RUN sed -i -e"s/'shj_value' => '\/home\/shj\/tester'/'shj_value' => '\/var\/www\/html\/tester'/" /var/www/html/application/controllers/Install.php
RUN sed -i -e"s/'shj_value' => '\/home\/shj\/assignments'/'shj_value' => '\/var\/www\/html\/assignments'/" /var/www/html/application/controllers/Install.php
RUN chmod 755 /var/www/html/application/cache/Twig
RUN echo "#!/bin/bash" > /start.sh
RUN echo "mysqld &" >> /start.sh 
RUN echo "apache2 -D FOREGROUND" >> /start.sh
RUN chmod +x /start.sh
EXPOSE 80

ENV APACHE_RUN_USER    www-data
ENV APACHE_RUN_GROUP   www-data
ENV APACHE_PID_FILE    /var/run/apache2.pid
ENV APACHE_RUN_DIR     /var/run/apache2
ENV APACHE_LOCK_DIR    /var/lock/apache2
ENV APACHE_LOG_DIR     /var/log/apache2
ENV LANG               C

CMD ["/start.sh"]
