FROM njkookywhiz/leap-platform:test
MAINTAINER Kooky Whiz <kookywhiz@gmail.com>

RUN apt-get update -y \
 && apt-get install composer -y

CMD if [ "$LEAP_COOKIES_SECURE" = "true" ]; \
    then export LEAP_COOKIES_SECURE_PHP=1; \
    else export LEAP_COOKIES_SECURE_PHP=0; \
    fi \
 && /wait-for-it.sh $DB_TEST_HOST:$DB_TEST_PORT -t 300 \
 && php bin/console leap:setup --env=test \
 && cat /etc/nginx/sites-available/leap.conf.tpl | sed "s/{{nginx_port}}/$NGINX_PORT/g" | sed "s/{{nginx_server_conf}}/$NGINX_SERVER_CONF/g" > /etc/nginx/sites-available/leap.conf \
 && service nginx start \
 && php bin/console leap:forker:start --env=test \
 && /etc/init.d/php7.4-fpm start \
 && vendor/bin/simple-phpunit