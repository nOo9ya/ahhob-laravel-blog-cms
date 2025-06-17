#!/bin/bash

# SSL 인증서 생성 스크립트
# 개발환경용 자체 서명 인증서 생성

SSL_DIR="./.docker/nginx/ssl"
DOMAIN="localhost"
COUNTRY="KR"
STATE="Seoul"
CITY="Seoul"
ORGANIZATION="Development"
ORG_UNIT="IT Department"
EMAIL="dev@localhost"

echo "🔐 개발환경용 SSL 인증서 생성 중..."

# SSL 디렉토리 생성
mkdir -p "$SSL_DIR"

# 개인키 생성 (2048비트 RSA)
echo "📋 개인키 생성 중..."
openssl genrsa -out "$SSL_DIR/key.pem" 2048

# SAN (Subject Alternative Names) 확장을 위한 설정 파일 생성
cat > "$SSL_DIR/cert.conf" << EOF
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
req_extensions = v3_req

[dn]
CN=$DOMAIN

[v3_req]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @alt_names

[alt_names]
DNS.1 = localhost
DNS.2 = *.localhost
IP.1 = 127.0.0.1
IP.2 = ::1
EOF

# SAN 확장이 포함된 인증서 생성
echo "📋 SSL 인증서 생성 중..."
openssl req -new -key "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.csr" -config "$SSL_DIR/cert.conf"
openssl x509 -req -days 365 -in "$SSL_DIR/cert.csr" -signkey "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.pem" -extensions v3_req -extfile "$SSL_DIR/cert.conf"

# 임시 파일 정리
rm "$SSL_DIR/cert.csr" "$SSL_DIR/cert.conf"

# 권한 설정
chmod 600 "$SSL_DIR/key.pem"
chmod 644 "$SSL_DIR/cert.pem"

echo "✅ SSL 인증서 생성 완료!"
echo "📁 인증서 위치: $SSL_DIR/"
echo "🔑 개인키: $SSL_DIR/key.pem"
echo "📜 인증서: $SSL_DIR/cert.pem"
echo ""
echo "⚠️  자체 서명 인증서이므로 브라우저에서 보안 경고가 표시됩니다."
echo "💡 Chrome에서 'thisisunsafe'를 입력하거나 '고급' > '안전하지 않음으로 이동'을 클릭하세요."
echo ""
echo "🚀 이제 다음 URL로 접속할 수 있습니다:"
echo "   HTTP:  http://localhost"
echo "   HTTPS: https://localhost"