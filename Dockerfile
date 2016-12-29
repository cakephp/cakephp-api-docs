FROM ubuntu:16.04

ENV DEBIAN_FRONTEND noninteractive

LABEL Description="This image is used to create an environment to contribute to the cakephp/cakephp-api-docs"

RUN apt-get update && apt-get install -y \
    build-essential \
    nginx git curl \
    php \
    php-bz2 \
    php-json \
    php-mbstring \
    php-zip \
    php-xml \
    php-fpm \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

RUN mkdir /website /root/.ssh

RUN ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts

ARG GIT_COMMIT=master

ENV GIT_COMMIT ${GIT_COMMIT}

# Clone and fetch tags not linked to current branch heads
RUN git clone https://github.com/cakephp/cakephp.git /cakephp \
  && cd /cakephp \
  && git fetch origin --tags

RUN git clone https://github.com/cakephp/chronos.git /chronos

WORKDIR /data

COPY . /data

RUN cd /cakephp && git fetch origin

RUN cd /chronos && git fetch origin

RUN cd /data \
  && ls -lah \
  && make clean build-all SOURCE_DIR=/cakephp CHRONOS_SOURCE_DIR=/chronos \
  && make deploy DEPLOY_DIR=/var/www/html

RUN rm /var/www/html/index.nginx-debian.html \
  && mv /data/nginx.conf /etc/nginx/sites-enabled/default \
  && ls /etc/nginx/sites-enabled/ /etc/nginx/

# forward request and error logs to docker log collector
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
  && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
