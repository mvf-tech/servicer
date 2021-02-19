FROM php:7.4-cli-alpine

WORKDIR /code

RUN apk update \
    && apk add php7-phar \
    && apk add php7-mbstring \
    && apk add php7-curl \
    && apk add php7-tokenizer \
    && apk add php7-json \
    && apk add php7-phpdbg \
    && rm -rf /var/cache/apk/* /var/tmp/*/tmp/*

RUN wget https://github.com/DataDog/dd-trace-php/releases/download/0.28.1/datadog-php-tracer_0.28.1_noarch.apk \
    && apk add --no-cache --allow-untrusted datadog-php-tracer_0.28.1_noarch.apk \
    && rm datadog-php-tracer_0.28.1_noarch.apk
