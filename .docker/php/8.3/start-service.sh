#!/bin/sh

set -e

# 1. 권한 및 소유자 변경 (컨테이너 런타임에 한번 더 보장)
chown -R ${USER}:${USER} ${WORK_DIR}/storage/framework/cache/data || true
chown -R ${USER}:${USER} ${WORK_DIR}/storage/logs || true
chown -R ${USER}:${USER} ${WORK_DIR}/bootstrap/cache || true

chmod 755 -R ${WORK_DIR}/storage/* || true
chmod 755 -R ${WORK_DIR}/bootstrap/cache || true

# 2. crond 시작 (백그라운드)
crond

# 3. Laravel/Node 관련 작업 (artisan이 있을 때만)
if [ -f "${WORK_DIR}/artisan" ]; then

  # storage/public 링크 재생성
  if [ -f "${WORK_DIR}/public/storage" ]; then
    unlink ${WORK_DIR}/public/storage
    ln -s ${WORK_DIR}/storage/app/public/ ${WORK_DIR}/public/storage
  fi

#  if [ ! -d "${WORK_DIR}/public/storage" ]; then
#    php artisan storage:link
#  fi

  # Composer install 및 Autoload
  if [ "prod" = "$APP_ENV" ]; then
    echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>"
    echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> production php service container start"
    echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>"
    composer install --optimize-autoloader --no-dev
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan event:clear

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
  else
    echo "@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@"
    echo "@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ develop php service container start"
    echo "@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@"
    composer install && composer dump-autoload
  #    php artisan cache:clear
  #    php artisan optimize:clear # cache 도 clear 해버림
  fi

  #  composer cc
  composer clear-cache

  #  -------------- migrate start
  #  php artisan migrate

  # ----------------------- Node.js 서비스 자동 실행 추가 -----------------------
  # node_modules가 없으면 설치 (프론트엔드 빌드/실행 전 필요)
  if [ -f "${WORK_DIR}/package.json" ]; then
    if [ ! -d "${WORK_DIR}/node_modules" ]; then
      echo "node_modules not found, running npm install..."
      npm install --prefix "${WORK_DIR}"
    fi

    # 개발/프로덕션 환경에 따라 Node 서비스 실행 방식 구분
    if [ "prod" = "$APP_ENV" ]; then
      echo "Starting Node.js production build..."
      npm run build --prefix "${WORK_DIR}"
      # 필요하다면 pm2 등으로 node 앱 구동: pm2 start dist/main.js
    else
      echo "Starting Node.js development server..."
      # 5173 포트(vite), 3000/4000 등은 프론트 dev 서버 포트에 맞게 조정
      # npm run dev --prefix "${WORK_DIR}" -- --port 3000 --host 0.0.0.0 &
      npm run dev -- --port 4000 --host &
    fi
  else
    echo "package.json not found, skipping Node.js setup."
  fi

  #  ------------- Crontab Update
  echo "*       *       *       *       *       /usr/local/bin/php ${WORK_DIR}/artisan schedule:run >> /var/log/cron.log 2>&1" >> /var/spool/cron/crontabs/root;
  echo "30      4       *       *       6       rm /var/log/cron.log" >> /var/spool/cron/crontabs/root;
  echo "30      4       *       *       *       find ${WORK_DIR}/storage/logs/ -name '*.log' -mtime +30 -delete" >> /var/spool/cron/crontabs/root;
  echo "30      4       *       *       *       find ${WORK_DIR}/storage/debugbar/ -name '*.json' -mtime +30 -delete" >> /var/spool/cron/crontabs/root;
  echo "30      4       *       *       *       find /var/log/laravel/ -name '*.log' -mtime +30 -delete" >> /var/spool/cron/crontabs/root;
  echo "30      4       *       *       *       find /var/log/supervisor/ -name '*.log' -mtime +30 -delete" >> /var/spool/cron/crontabs/root;
  echo "=============================================================================================="
  echo "============================= already server php service container ============================="
  echo "=============================================================================================="
else
  echo "not found artisan file!!!!!!!"
  echo "You must have Laravel installed"
  # php-fpm을 실행하지 않고 종료되도록 하거나, 에러 상태로 종료되도록 할 수 있습니다.
    # 여기서는 php-fpm을 실행하지 않고 tail -f /dev/null로 넘어가지 않도록 exit 1을 추가하는 것을 고려할 수 있습니다.
    # exit 1
    tail -f /dev/null # artisan 파일이 없을 경우에도 컨테이너가 유지되도록 하려면 이 줄을 유지
fi

# PHP-FPM 실행
echo "Starting PHP-FPM..."
exec php-fpm

# tail -f /dev/null로 컨테이너가 바로 종료되지 않도록 유지 (php-fpm이 foreground로 실행되지 않는 경우 필요)
# php-fpm이 foreground로 실행된다면 이 tail -f는 필요 없을 수 있습니다.
# 대부분의 php-fpm Docker 이미지는 php-fpm을 foreground로 실행하므로,
# 아래 tail -f는 주석 처리하거나 제거해도 될 가능성이 높습니다.
# tail -f /dev/null