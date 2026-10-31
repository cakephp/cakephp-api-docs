# Build api docs with php 8.5 requirements
FROM alpine:3.24 AS builder

RUN apk add --no-cache \
    bash \
    curl \
    git \
    make \
    openssh-client \
    php85 \
    php85-bz2 \
    php85-curl \
    php85-dom \
    php85-intl \
    php85-iconv \
    php85-mbstring \
    php85-openssl \
    php85-phar \
    php85-simplexml \
    php85-tokenizer \
    php85-xml \
    php85-xmlwriter \
    php85-zip

RUN ln -sf /usr/bin/php85 /usr/bin/php

RUN mkdir /root/.ssh \
    && ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts

WORKDIR /data
COPY . /data

RUN git clone https://github.com/cakephp/cakephp.git /cakephp \
  && git clone https://github.com/cakephp/authentication.git /authentication \
  && git clone https://github.com/cakephp/authorization.git /authorization \
  && git clone https://github.com/cakephp/chronos.git /chronos \
  && git clone https://github.com/cakephp/elastic-search.git /elastic \
  && git clone https://github.com/cakephp/queue.git /queue

RUN ls -lah \
  # && make build-cakephp3-all CAKEPHP_SOURCE_DIR=/cakephp \
  # && make build-cakephp4-all CAKEPHP_SOURCE_DIR=/cakephp \
  && make build-cakephp5-all CAKEPHP_SOURCE_DIR=/cakephp \
  && make build-authentication-all AUTHENTICATION_SOURCE_DIR=/authentication \
  && make build-authorization-all AUTHORIZATION_SOURCE_DIR=/authorization \
  && make build-chronos-all CHRONOS_SOURCE_DIR=/chronos \
  && make build-elastic-all ELASTIC_SOURCE_DIR=/elastic \
  && make build-queue-all QUEUE_SOURCE_DIR=/queue

# nginx server
FROM alpine:3.24

LABEL Description="CakePHP API Docs"

RUN apk add --no-cache \
    nginx \
    openssh-client

# forward request and error logs to docker log collector
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
  && ln -sf /dev/stderr /var/log/nginx/error.log

COPY nginx.conf /etc/nginx/http.d/default.conf

WORKDIR /var/www/html
COPY --from=builder /data/build/api ./

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
