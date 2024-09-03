FROM  sirfaenor/dev-php:7.4-apache

ARG APACHE_RUN_USER
ARG APACHE_RUN_GROUP

RUN docker-php-ext-install mysqli

RUN useradd --uid $APACHE_RUN_USER --gid $APACHE_RUN_GROUP --shell /bin/bash --create-home appuser
RUN usermod -aG root appuser

RUN chown -R "$APACHE_RUN_USER":"$APACHE_RUN_GROUP" ./
RUN chmod -R 755 ./

