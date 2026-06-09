FROM nginx:stable-bookworm

ENV TZ ${TIMEZONE}

WORKDIR /

COPY docker/nginx/default.conf /etc/nginx/conf.d/
COPY public /workdir/public
