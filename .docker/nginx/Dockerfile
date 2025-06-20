FROM nginx:stable-alpine

# environment arguments
ARG UID
ARG GID
ARG TZ

ENV UID=${UID}
ENV GID=${GID}
ENV TZ=${TZ}

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN delgroup dialout

#RUN addgroup -g ${GID} --system ${USER}
#RUN adduser -G ${USER} --system -D -s /bin/sh -u ${UID} ${USER}

# Install logrotate and openssl
RUN apk update &&\
    apk upgrade &&\
    apk --update add logrotate &&\
    apk add --no-cache openssl &&\
    apk add --no-cache curl

# Modify nginx configuration to use the new user's priviledges for starting it.
ADD ./.docker/nginx/conf/nginx.conf /etc/nginx/nginx.conf
#RUN sed -i "s/user nginx/user ${USER}/g" /etc/nginx/nginx.conf
#RUN sed -i "s/user nginx/nginx '${USER}'/g" /etc/nginx/nginx.conf

# Copies nginx configurations to override the default.
ADD ./.docker/nginx/sites/dev.default.conf /etc/nginx/conf.d/default.conf

COPY ./.docker/nginx/logrotate/nginx_log.conf /etc/logrotate.d/nginx
COPY ./.docker/nginx/ssl /etc/nginx/ssl

COPY ./.docker/nginx/nginx-service.sh /usr/local/bin/nginx-service.sh
RUN chmod +x /usr/local/bin/nginx-service.sh

ENTRYPOINT ["/usr/local/bin/nginx-service.sh", "/etc/nginx/ssl"]