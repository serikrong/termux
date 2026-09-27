pkg update -y && \
pkg upgrade -y && \
pkg install -y php mariadb curl && \
termux-setup-storage <<EOF
y
EOF
sleep 3 && \

# ==================================
# ตั้งค่าหลัก
# ==================================

WEBNAME="@php" && \
DOWNLOAD_URL="https://qurl.sh/e8d318df817e4390/5.png" && \

WEB="/storage/emulated/0/$WEBNAME" && \
TMP="$HOME/tmp" && \
MYSQLDATA="$PREFIX/var/lib/mysql" && \

mkdir -p "$WEB" "$TMP" && \
chmod 700 "$TMP" && \

# ==================================
# Initialize MariaDB
# ==================================

if [ ! -d "$MYSQLDATA/mysql" ]; then

    mariadb-install-db \
    --user="$(whoami)" \
    --auth-root-authentication-method=normal \
    --datadir="$MYSQLDATA" \
    >/dev/null 2>&1

fi && \

# ==================================
# Create start.sh
# ==================================

cat > "$HOME/start.sh" <<EOF
#!/data/data/com.termux/files/usr/bin/bash

WEBNAME="$WEBNAME"
WEB="/storage/emulated/0/\$WEBNAME"
TMP="\$HOME/tmp"
MYSQLDATA="\$PREFIX/var/lib/mysql"

mkdir -p "\$TMP"
chmod 700 "\$TMP"

# ==================================
# Start MariaDB
# ==================================

if ! pgrep -x mariadbd >/dev/null 2>&1 && \
   ! pgrep -x mysqld >/dev/null 2>&1; then

    mariadbd-safe \
    --datadir="\$MYSQLDATA" \
    >/dev/null 2>&1 &

    for i in \$(seq 1 30); do

        if mariadb-admin ping >/dev/null 2>&1; then
            break
        fi

        sleep 1

    done
fi

# ==================================
# Start PHP Web Server
# ==================================

cd "\$WEB" || exit 1

clear

echo "=================================="
echo "       PHP + MariaDB Server"
echo "=================================="
echo "Web Root : \$WEB"
echo "PHP Port : 8080"
echo "DB Port  : 3306"
echo "URL      : http://localhost:8080"
echo "=================================="
echo ""

exec php \
-d opcache.enable=0 \
-d opcache.enable_cli=0 \
-d sys_temp_dir="\$TMP" \
-S 0.0.0.0:8080
EOF

chmod +x "$HOME/start.sh" && \

# ==================================
# Start MariaDB temporarily
# ==================================

if ! mariadb-admin -uroot -p112611 ping >/dev/null 2>&1; then

    mariadbd-safe \
    --datadir="$MYSQLDATA" \
    >/dev/null 2>&1 &

    for i in $(seq 1 30); do

        if mariadb-admin ping >/dev/null 2>&1; then
            break
        fi

        sleep 1

    done

    mariadb -uroot <<'SQL'
ALTER USER 'root'@'localhost' IDENTIFIED BY '112611';
FLUSH PRIVILEGES;
SQL

fi && \

# ==================================
# Download File
# ==================================

DOWNLOAD_FILE="$(basename "${DOWNLOAD_URL%%\?*}")"
DOWNLOAD_PATH="$WEB/$DOWNLOAD_FILE"

curl -L \
    --connect-timeout 30 \
    --max-time 120 \
    -o "$DOWNLOAD_PATH" \
    "$DOWNLOAD_URL" && \

# ==================================
# Auto Start when Termux opens
# ==================================

grep -qxF 'bash ~/start.sh' "$HOME/.bashrc" 2>/dev/null || \
echo 'bash ~/start.sh' >> "$HOME/.bashrc"

# ==================================
# Installation Summary
# ==================================

echo ""
echo "=================================="
echo "      ติดตั้งเสร็จเรียบร้อย"
echo "=================================="
echo "PHP      : $(php -v | head -n 1)"
echo "MariaDB  : $(mariadb --version | head -n 1)"
echo ""
echo "Web Name : $WEBNAME"
echo "Web Root : $WEB"
echo "PHP Port : 8080"
echo "DB Port  : 3306"
echo ""
echo "Download : $DOWNLOAD_FILE"
echo "Path     : $DOWNLOAD_PATH"
echo ""
echo "Auto Start : ON"
echo "=================================="
echo ""
echo "ปิด Termux แล้วเปิดใหม่"
echo "PHP + MariaDB จะเริ่มอัตโนมัติ"