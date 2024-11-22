FROM ubuntu:20.04
MAINTAINER Kooky Whiz <kookywhiz@gmail.com>

ARG CRAN_MIRROR=https://cloud.r-project.org

ENV LEAP_PLATFORM_URL=/
ENV LEAP_PASSWORD=admin
ENV LEAP_API_ENABLED=true
ENV LEAP_API_ENABLED_OVERRIDABLE=true
ENV LEAP_DATA_API_ENABLED=true
ENV LEAP_SESSION_LIMIT=0
ENV LEAP_SESSION_LIMIT_OVERRIDABLE=true
ENV LEAP_CONTENT_URL=.
ENV LEAP_CONTENT_URL_OVERRIDABLE=true
ENV LEAP_CONTENT_TRANSFER_OPTIONS='[]'
ENV LEAP_CONTENT_TRANSFER_OPTIONS_OVERRIDABLE=true
ENV LEAP_SESSION_RUNNER_SERVICE=SerializedSessionRunnerService
ENV LEAP_SESSION_RUNNER_SERVICE_OVERRIDABLE=true
ENV LEAP_GIT_ENABLED=0
ENV LEAP_GIT_ENABLED_OVERRIDABLE=true
ENV LEAP_GIT_URL=''
ENV LEAP_GIT_URL_OVERRIDABLE=true
ENV LEAP_GIT_BRANCH=master
ENV LEAP_GIT_BRANCH_OVERRIDABLE=true
ENV LEAP_GIT_LOGIN=''
ENV LEAP_GIT_LOGIN_OVERRIDABLE=true
ENV LEAP_GIT_PASSWORD=''
ENV LEAP_GIT_PASSWORD_OVERRIDABLE=true
ENV LEAP_GIT_REPOSITORY_PATH=''
ENV LEAP_BEHIND_PROXY=false
ENV LEAP_CONTENT_IMPORT_AT_START=true
ENV LEAP_FAILED_AUTH_LOCK_TIME=300
ENV LEAP_FAILED_AUTH_LOCK_STREAK=3
ENV LEAP_SESSION_FILES_EXPIRATION=7
ENV LEAP_SESSION_LOG_LEVEL=1
ENV LEAP_SESSION_STORAGE=filesystem
ENV LEAP_COOKIES_SAME_SITE=strict
ENV LEAP_COOKIES_SECURE=false
ENV LEAP_COOKIES_LIFETIME=0
ENV LEAP_KEEP_ALIVE_INTERVAL_TIME=0
ENV LEAP_KEEP_ALIVE_TOLERANCE_TIME=0
ENV LEAP_SESSION_TOKEN_EXPIRY_TIME=7200
ENV LEAP_SESSION_FORKING=true
ENV LEAP_R_FORCED_GC_INTERVAL=30
ENV LEAP_JWT_SECRET=changeme
ENV LEAP_PHP_SESSION_SAVE_PATH=/app/leap/var/sessions
ENV REDIS_HOST=redis
ENV REDIS_PORT=6379
ENV REDIS_PASS=''
ENV DB_HOST=localhost
ENV DB_PORT=3306
ENV DB_NAME=leap
ENV DB_USER=leap
ENV DB_PASSWORD=changeme
ENV DB_VERSION=5.7
ENV NGINX_PORT=80
ENV NGINX_SERVER_CONF="add_header X-Frame-Options sameorigin always;\nadd_header X-Content-Type-Options nosniff always;"
ENV PHP_FPM_PM=dynamic
ENV PHP_FPM_PM_MAX_CHILDREN=30
ENV PHP_FPM_PM_START_SERVERS=10
ENV PHP_FPM_PM_MIN_SPARE_SERVERS=5
ENV PHP_FPM_PM_MAX_SPARE_SERVERS=15
ENV PHP_FPM_PM_PROCESS_IDLE_TIMEOUT=10s
ENV PHP_FPM_PM_MAX_REQUESTS=300
ENV WEB_USER=www-data
ENV TZ=UTC

COPY . /app/leap/
ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
 && apt-get update -y \
 && apt-get -y install \
    ca-certificates \
    gnupg \
 && echo "deb $CRAN_MIRROR/bin/linux/ubuntu bionic-cran35/" | tee -a /etc/apt/sources.list \
 && apt-key adv --no-tty --keyserver keyserver.ubuntu.com --recv-keys E298A3A825C0D65DFD57CBB651716619E084DAB9 \
 && apt-get update -y \
 && apt-get -y install \
    cron \
    curl \
    gettext \
    git \
    libcurl4-openssl-dev \
    libhiredis-dev \
    libmariadbclient-dev \
    libnginx-mod-http-headers-more-filter \
    libxml2-dev \
    libssl-dev \
    locales \
    nginx \
    php7.4-curl \
    php7.4-mbstring \
    php7.4-mysql \
    php7.4-xml \
    php7.4-zip \
    php-fpm \
    procps \
    r-base \
    r-base-dev \
 && rm -rf /var/lib/apt/lists/* \
 && sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
 && locale-gen "en_US.UTF-8" \
 && Rscript -e "install.packages('https://cran.r-project.org/src/contrib/Archive/rjson/rjson_0.2.20.tar.gz')" \
 && Rscript -e "install.packages(c('digest','filelock','httr','jsonlite','redux','RMySQL','xml2'), repos='$CRAN_MIRROR')" \
 && Rscript -e "install.packages('https://cran.r-project.org/src/contrib/Archive/catR/catR_3.16.tar.gz')" \
 && R CMD INSTALL /app/leap/src/Leap/TestBundle/Resources/R/leap5 \
 && chmod +x /wait-for-it.sh \
 && php /app/leap/bin/console leap:r:cache \
 && crontab -l | { cat; echo "* * * * * . /app/leap/cron/leap.schedule.tick.sh >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "* * * * * . /app/leap/cron/leap.forker.guard.sh >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "0 0 * * * . /app/leap/cron/leap.session.clear.sh >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "*/5 * * * * . /app/leap/cron/leap.session.log.sh >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "* * * * * . /app/leap/cron/leap.r.tick.sh > /dev/null 2>&1"; } | crontab - \
 && rm -f /etc/nginx/sites-available/default \
 && rm -f /etc/nginx/sites-enabled/default \
 && ln -fs /etc/nginx/sites-available/leap.conf /etc/nginx/sites-enabled/leap.conf

COPY build/docker/php/php.ini /etc/php/7.4/fpm/php.ini
COPY build/docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY build/docker/php-fpm/php-fpm.conf /etc/php/7.4/fpm/php-fpm.conf
COPY build/docker/php-fpm/www.conf /etc/php/7.4/fpm/pool.d/www.conf

RUN rm -rf /app/leap/src/Leap/PanelBundle/Resources/public/files \
 && rm -rf /app/leap/src/Leap/TestBundle/Resources/sessions \
 && rm -rf /app/leap/src/Leap/PanelBundle/Resources/import

EXPOSE 80 9000
WORKDIR /app/leap
HEALTHCHECK --interval=1m --start-period=1m CMD curl -f http://localhost/api/check/health || exit 1

CMD if [ "$LEAP_COOKIES_SECURE" = "true" ]; \
    then export LEAP_COOKIES_SECURE_PHP=1; \
    else export LEAP_COOKIES_SECURE_PHP=0; \
    fi \
 && printenv | sed 's/"/\\"/g' | sed 's/^\([a-zA-Z0-9_]*\)=\(.*\)$/export \1="\2"/g' > /root/env.sh \
 && mkdir -p /data/files \
 && mkdir -p /data/sessions \
 && mkdir -p /data/git \
 && mkdir -p /data/import \
 && mkdir -p /data/php/sessions \
 && ln -sf /data/files /app/leap/src/Leap/PanelBundle/Resources/public \
 && ln -sf /data/sessions /app/leap/src/Leap/TestBundle/Resources \
 && ln -sf /app/leap/src/Leap/PanelBundle/Resources/public/files /app/leap/web \
 && ln -sf /data/import /app/leap/src/Leap/PanelBundle/Resources \
 && chown $WEB_USER /data/sessions \
 && chown $WEB_USER /data/import \
 && chown $WEB_USER /data/php/sessions \
 && /wait-for-it.sh $DB_HOST:$DB_PORT -t 300 \
 && php bin/console leap:setup --env=prod --admin-pass=$LEAP_PASSWORD \
 && if [ "$LEAP_CONTENT_IMPORT_AT_START" = "true" ]; \
    then php bin/console leap:content:import --env=prod --sc; \
    fi \
 && chown -R $WEB_USER /data/files \
 && rm -rf var/cache/* \
 && php bin/console cache:warmup --env=prod \
 && chown $WEB_USER src/Leap/TestBundle/Resources/R/fifo \
 && chown -R $WEB_USER var/cache \
 && chown -R $WEB_USER var/logs \
 && chown -R $WEB_USER var/sessions \
 && chown -R $WEB_USER var/git \
 && chown -R $WEB_USER src/Leap/PanelBundle/Resources/export \
 && chown -R $WEB_USER /data/git \
 && BASE_DIR=$(bash /app/leap/scripts/basedir.sh $LEAP_PLATFORM_URL) \
 && cat /app/leap/build/docker/nginx/leap.conf.tpl | sed "s/{{nginx_port}}/$NGINX_PORT/g" | sed "s|{{nginx_server_conf}}|$NGINX_SERVER_CONF|g" | sed "s|{{base_dir}}|$BASE_DIR|g" > /etc/nginx/sites-available/leap.conf \
 && service nginx start \
 && . /app/leap/cron/leap.forker.guard.sh  \
 && /etc/init.d/php7.4-fpm start \
 && cron \
 && tail -F -n 0 var/logs/prod.log var/logs/forker.log