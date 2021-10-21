FROM alpine:3.14

LABEL Description="CakePHP API Docs"

RUN apk add --no-cache \
    bash \
    curl \
    git \
    make \
    nginx \
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

RUN ln -sf /usr/bin/php /usr/bin/php8

RUN mkdir /website /root/.ssh

RUN ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts

ARG GIT_COMMIT=master

ENV GIT_COMMIT ${GIT_COMMIT}
WORKDIR /data
COPY . /data

RUN git clone https://github.com/cakephp/cakephp.git /cakephp \
  && git clone https://github.com/cakephp/chronos.git /chronos \
  && git clone https://github.com/cakephp/elastic-search.git /elastic \
  && git clone https://github.com/cakephp/queue.git /queue

RUN ls -lah \
  && make clean build-all CAKEPHP_SOURCE_DIR=/cakephp CHRONOS_SOURCE_DIR=/chronos ELASTIC_SOURCE_DIR=/elastic QUEUE_SOURCE_DIR=/queue \
  && make deploy DEPLOY_DIR=/var/www/html

RUN mkdir -p /run/nginx \
  && mv /data/nginx.conf /etc/nginx/http.d/default.conf

# forward request and error logs to docker log collector
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
  && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
