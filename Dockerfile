# Build api docs with php 8.1 requirements
FROM alpine:3.16

RUN apk add --no-cache \
    bash \
    curl \
    git \
    make \
    openssh-client \
    php81 \
    php81-bz2 \
    php81-curl \
    php81-dom \
    php81-intl \
    php81-json \
    php81-mbstring \
    php81-openssl \
    php81-phar \
    php81-simplexml \
    php81-tokenizer \
    php81-xml \
    php81-xmlwriter \
    php81-zip

RUN ln -sf /usr/bin/php81 /usr/bin/php

RUN mkdir /root/.ssh \
    && ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts

WORKDIR /data
COPY . /data

RUN git clone https://github.com/cakephp/cakephp.git /cakephp

RUN ls -lah \
  && make build-cakephp5-all CAKEPHP_SOURCE_DIR=/cakephp CHRONOS_SOURCE_DIR=/chronos ELASTIC_SOURCE_DIR=/elastic QUEUE_SOURCE_DIR=/queue

# Build api docs with php7 requirements
FROM alpine:3.15

RUN apk add --no-cache \
    bash \
    curl \
    git \
    make \
    openssh-client \
    php8 \
    php8-bz2 \
    php8-curl \
    php8-dom \
    php8-intl \
    php8-json \
    php8-mbstring \
    php8-openssl \
    php8-phar \
    php8-simplexml \
    php8-tokenizer \
    php8-xml \
    php8-xmlwriter \
    php8-zip \
    php7 \
    php7-bz2 \
    php7-curl \
    php7-dom \
    php7-intl \
    php7-json \
    php7-mbstring \
    php7-openssl \
    php7-phar \
    php7-simplexml \
    php7-tokenizer \
    php7-xml \
    php7-xmlwriter \
    php7-zip

RUN ln -sf /usr/bin/php8 /usr/bin/php

RUN mkdir /root/.ssh \
    && ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts

WORKDIR /data
COPY . /data

RUN git clone https://github.com/cakephp/cakephp.git /cakephp \
  && git clone https://github.com/cakephp/chronos.git /chronos \
  && git clone https://github.com/cakephp/elastic-search.git /elastic \
  && git clone https://github.com/cakephp/queue.git /queue

RUN ls -lah \
  && make build-cakephp3-all PHP_COMPOSER=php7 CAKEPHP_SOURCE_DIR=/cakephp \
  && make build-cakephp4-all PHP_COMPOSER=php7 CAKEPHP_SOURCE_DIR=/cakephp \
  && make build-chronos1-all PHP_COMPOSER=php7 CHRONOS_SOURCE_DIR=/chronos \
  && make build-chronos2-all PHP_COMPOSER=php7 CHRONOS_SOURCE_DIR=/chronos \
  && make build-elastic2-all PHP_COMPOSER=php7 ELASTIC_SOURCE_DIR=/elastic \
  && make build-elastic3-all PHP_COMPOSER=php7 ELASTIC_SOURCE_DIR=/elastic \
  && make build-queue1-all PHP_COMPOSER=php7 QUEUE_SOURCE_DIR=/queue


# nginx server
FROM alpine:3.16

LABEL Description="CakePHP API Docs"

RUN apk add --no-cache \
    nginx \
    openssh-client

# forward request and error logs to docker log collector
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
  && ln -sf /dev/stderr /var/log/nginx/error.log

COPY nginx.conf /etc/nginx/http.d/default.conf

WORKDIR /var/www/html
COPY --from=0 /data/build/api ./
COPY --from=1 /data/build/api ./

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
