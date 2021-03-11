#
# Usage example:
#
# docker build --tag=jms_queue_bundle_docs_gen .
# docker run --rm -u `id -u`:`id -g` -v `pwd`:/build jms_queue_bundle_docs_gen:latest update  .sami.config.php
#

FROM php:7.3-cli-alpine AS sami

RUN apk add --no-cache git make openssh-client unzip zip curl nano htop

COPY sami.phar /usr/local/bin/sami

RUN chmod +x /usr/local/bin/sami

COPY docker/sami/php.ini /usr/local/etc/php/

ENTRYPOINT [ "sami" ]
WORKDIR /build
