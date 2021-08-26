FROM alpine:3.13

LABEL Description="CakePHP API Docs"

RUN apk add --no-cache \
    bash \
    curl \
    git \
    make \
    nginx \
    openssh-client \
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
  && mv /data/nginx.conf /etc/nginx/conf.d/default.conf

# forward request and error logs to docker log collector
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
  && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
