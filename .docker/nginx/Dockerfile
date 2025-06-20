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
RUN set -e; \
    TARGET_USER_NAME="${USER}"; \
    TARGET_GROUP_NAME="${USER}"; \
    TARGET_GID="${GID}"; \
    NEW_USER_DEFAULT_UID="1000"; \
    \
    echo ">>> 그룹 설정 시작: 그룹 '${TARGET_GROUP_NAME}'가 GID ${TARGET_GID}를 갖도록 합니다."; \
    \
    # 1. 목표 GID(${TARGET_GID})가 다른 그룹명으로 사용 중인지 확인하고, 그렇다면 해당 그룹 삭제
    #    getent는 그룹/사용자가 없으면 non-zero exit code를 반환할 수 있으므로, `|| true`로 오류 무시
    EXISTING_GROUP_NAME_FOR_TARGET_GID=$(getent group "${TARGET_GID}" 2>/dev/null | cut -d: -f1 || true); \
    if [ -n "${EXISTING_GROUP_NAME_FOR_TARGET_GID}" ] && [ "${EXISTING_GROUP_NAME_FOR_TARGET_GID}" != "${TARGET_GROUP_NAME}" ]; then \
        echo "GID ${TARGET_GID}는 이미 그룹 '${EXISTING_GROUP_NAME_FOR_TARGET_GID}'에 의해 사용 중입니다. 해당 그룹을 삭제합니다."; \
        delgroup "${EXISTING_GROUP_NAME_FOR_TARGET_GID}"; \
    fi; \
    \
    # 2. 그룹 ${TARGET_GROUP_NAME}이 존재하고 GID가 ${TARGET_GID}인지 확인 및 처리
    if getent group "${TARGET_GROUP_NAME}" > /dev/null 2>&1; then \
        CURRENT_GROUP_GID=$(getent group "${TARGET_GROUP_NAME}" | cut -d: -f3); \
        if [ "${CURRENT_GROUP_GID}" != "${TARGET_GID}" ]; then \
            echo "그룹 '${TARGET_GROUP_NAME}'가 존재하지만 GID가 ${CURRENT_GROUP_GID}입니다. GID ${TARGET_GID}로 수정합니다."; \
            delgroup "${TARGET_GROUP_NAME}"; \
            addgroup -g "${TARGET_GID}" -S "${TARGET_GROUP_NAME}"; \
        else \
            echo "그룹 '${TARGET_GROUP_NAME}'는 이미 올바른 GID ${TARGET_GID}를 가지고 있습니다."; \
        fi; \
    else \
        echo "그룹 '${TARGET_GROUP_NAME}'가 존재하지 않습니다. GID ${TARGET_GID}로 생성합니다."; \
        addgroup -g "${TARGET_GID}" -S "${TARGET_GROUP_NAME}"; \
    fi; \
    echo ">>> 그룹 설정 완료: 그룹 '${TARGET_GROUP_NAME}' (GID $(getent group ${TARGET_GROUP_NAME} | cut -d: -f3))"; \
    \
    echo ">>> 사용자 설정 시작: 사용자 '${TARGET_USER_NAME}' 설정"; \
    # 3. 사용자 ${TARGET_USER_NAME} 존재 여부 확인 및 처리
    if getent passwd "${TARGET_USER_NAME}" > /dev/null 2>&1; then \
        EXISTING_USER_ACTUAL_UID=$(getent passwd "${TARGET_USER_NAME}" | cut -d: -f3); \
        EXISTING_USER_PRIMARY_GID_NUM=$(getent passwd "${TARGET_USER_NAME}" | cut -d: -f4); \
        echo "사용자 '${TARGET_USER_NAME}'가 UID ${EXISTING_USER_ACTUAL_UID}, 주 그룹 GID ${EXISTING_USER_PRIMARY_GID_NUM}(으)로 존재합니다."; \
        \
        # 사용자의 주 그룹 GID가 ${TARGET_GID} (그룹 ${TARGET_GROUP_NAME}의 GID)와 다른 경우, 사용자 재설정 (기존 UID 유지)
        if [ "${EXISTING_USER_PRIMARY_GID_NUM}" != "${TARGET_GID}" ]; then \
            echo "사용자 '${TARGET_USER_NAME}'의 주 그룹 GID(${EXISTING_USER_PRIMARY_GID_NUM})가 그룹 '${TARGET_GROUP_NAME}'의 GID(${TARGET_GID})와 다릅니다. 사용자를 UID ${EXISTING_USER_ACTUAL_UID} 및 주 그룹 '${TARGET_GROUP_NAME}'(GID ${TARGET_GID})로 재설정합니다."; \
            deluser "${TARGET_USER_NAME}"; \
            adduser -u "${EXISTING_USER_ACTUAL_UID}" -S "${TARGET_USER_NAME}" -G "${TARGET_GROUP_NAME}"; \
        else \
            echo "사용자 '${TARGET_USER_NAME}'는 이미 올바른 주 그룹 '${TARGET_GROUP_NAME}'(GID ${TARGET_GID})을 가지고 있습니다."; \
        fi; \
    else \
        echo "사용자 '${TARGET_USER_NAME}'가 존재하지 않습니다. UID ${NEW_USER_DEFAULT_UID}, 주 그룹 '${TARGET_GROUP_NAME}'(GID ${TARGET_GID})로 생성합니다."; \
        adduser -u "${NEW_USER_DEFAULT_UID}" -S "${TARGET_USER_NAME}" -G "${TARGET_GROUP_NAME}"; \
    fi; \
    echo ">>> 사용자 설정 완료: 사용자 '${TARGET_USER_NAME}' (UID $(getent passwd ${TARGET_USER_NAME} | cut -d: -f3), GID $(getent passwd ${TARGET_USER_NAME} | cut -d: -f4))";


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