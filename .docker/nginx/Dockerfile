FROM nginx:stable-alpine

# environment arguments
ARG UID
ARG GID
ARG USER
ARG TZ

ENV UID=${UID}
ENV GID=${GID}
ENV USER=${USER}
ENV TZ=${TZ}

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN delgroup dialout

# USER로 지정된 사용자가 GID로 지정된 그룹에 속하고, UID로 지정된 ID를 갖도록 설정
RUN \
    # GID로 그룹이 이미 존재하는지 확인하고, 없다면 생성 또는 ${USER} 이름으로 GID 수정
    if getent group ${USER} > /dev/null; then \
        if [ "$(getent group ${USER} | cut -d: -f3)" != "${GID}" ]; then \
            echo "Group ${USER} exists, modifying GID to ${GID}."; \
            # 기존 그룹 삭제 후 재생성 (Alpine에서는 groupmod 보다 안정적일 수 있음)
            delgroup ${USER} && addgroup -g ${GID} -S ${USER}; \
        fi; \
    else \
        # 그룹 ${USER}가 없다면 GID로 생성 시도
        # 만약 GID가 이미 다른 그룹명으로 사용 중이라면, addgroup이 실패할 수 있으므로 주의
        echo "Group ${USER} does not exist, creating with GID ${GID}."; \
        addgroup -g ${GID} -S ${USER}; \
    fi; \
    \
    # UID로 사용자가 이미 존재하는지 확인하고, 없다면 생성 또는 ${USER} 이름으로 UID/GID 수정
    if getent passwd ${USER} > /dev/null; then \
        EXISTING_UID=$(getent passwd ${USER} | cut -d: -f3); \
        EXISTING_GID_OF_USER=$(getent passwd ${USER} | cut -d: -f4); \
        # UID 또는 GID가 다르면 사용자 삭제 후 재생성
        if [ "${EXISTING_UID}" != "${UID}" ] || [ "${EXISTING_GID_OF_USER}" != "${GID}" ]; then \
            echo "User ${USER} exists, modifying UID to ${UID} and GID to ${GID}."; \
            deluser ${USER} && adduser -u ${UID} -S ${USER} -G ${USER}; \
        fi; \
    else \
        echo "User ${USER} does not exist, creating with UID ${UID} and GID ${GID}."; \
        adduser -u ${UID} -S ${USER} -G ${USER}; \
    fi

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