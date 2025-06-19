#!/bin/sh

SSL_DIR=${1}

# SSL 디렉토리가 없으면 생성
echo ">>>>>>>>> SSL 디렉토리 및 파일 확인 시작!!"
if [ ! -d "$SSL_DIR" ]; then
    echo "$SSL_DIR 디렉토리가 존재하지 않아 생성합니다."
    mkdir -p "$SSL_DIR"
fi

# SSL 인증서 파일이 없으면 생성
if [ ! -f "$SSL_DIR/default.crt" ]; then
    echo "$SSL_DIR/default.crt 파일이 존재하지 않아 생성합니다."

    openssl genrsa -out "$SSL_DIR/default.key" 2048
    # -subj 에 필수 필드 추가 (예: 국가 코드 C)
    openssl req -new -key "$SSL_DIR/default.key" -out "$SSL_DIR/default.csr" -subj "/C=KR/ST=Seoul/L=Seoul/O=default/OU=default/CN=default"
    openssl x509 -req -days 365 -in "$SSL_DIR/default.csr" -signkey "$SSL_DIR/default.key" -out "$SSL_DIR/default.crt"
    # 개인 키 권한은 더 제한적으로 설정하는 것이 좋습니다.
    chmod 600 "$SSL_DIR/default.key"
    chmod 644 "$SSL_DIR/default.crt" # 인증서는 644 괜찮음

    echo "SSL 인증서 파일 생성이 완료되었습니다."
else
    echo "$SSL_DIR/default.crt 파일이 이미 존재합니다."
fi

echo ">>>>>>>>> SSL 디렉토리 및 파일 확인 완료!!"

# cron job to restart nginx every 6 hour
(crontab -l ; echo "0 0 */4 * * nginx -s reload") | crontab -

# Start crond in background
# crond -l 2 -b
#* * * * * root nginx -s reload >> /var/log/cron.log


sed -i "s/0       2       */0       4       */g" /var/spool/cron/crontabs/root
# cron job to restart nginx every 04:20
echo "20      4       *       *       *       nginx -s reload" >> /var/spool/cron/crontabs/root;
# cron job to nginx log delete
echo "30      4       *       *       *       find /var/log/nginx/ -mtime +30 -delete" >> /var/spool/cron/crontabs/root;

# logrotate 실행
# echo ">>>>>>>>> Logrotate 실행!!"
# logrotate /etc/logrotate.d/nginx

# crond 시작 (모든 설정이 끝난 후)
echo ">>>>>>>>> Crond 시작!!"
crond -l 2 -b

# Nginx 시작
echo ">>>>>>>>> Nginx 시작!!"
nginx -g 'daemon off;'