FROM ubuntu:16.04

ENV DEBIAN_FRONTEND noninteractive

LABEL Description="This image is used to create an environment to contribute to the cakephp/docs"

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

RUN git clone https://github.com/cakephp/cakephp.git /cakephp

RUN git clone https://github.com/cakephp/chronos.git /chronos

WORKDIR /data

COPY . /data

RUN git clone https://github.com/cakephp/cakephp-api-docs.git /apigen

RUN cd /cakephp && git fetch origin

RUN cd /chronos && git fetch origin

RUN cd /apigen \
  && ls -lah \
  && git reset --hard \
  && git pull origin master \
  && sed -i.bak 's#git:#https:#g' .gitmodules && rm .gitmodules.bak \
  && sed -i.bak 's#origin/1.3#1.3.21#g' Makefile && rm Makefile.bak \
  && sed -i.bak 's#origin/2.7#2.7.11#g' Makefile && rm Makefile.bak \
  && sed -i.bak 's#origin/2.8#2.8.4#g' Makefile && rm Makefile.bak \
  && git submodule init \
  && git submodule update \
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
