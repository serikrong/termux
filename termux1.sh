pkg update -y && \
pkg upgrade -y && \
pkg install -y php mariadb zip unzip curl && \

termux-setup-storage <<EOF
y
EOF

sleep 3

# ==================================
# ตั้งค่าหลัก
# ==================================
WEBNAME="@php"
WEB="/storage/emulated/0/$WEBNAME"
TMP="$HOME/tmp"
MYSQLDATA="$PREFIX/var/lib/mysql"

# ==================================
# สร้างโฟลเดอร์
# ==================================
mkdir -p "$WEB" "$TMP"
chmod 700 "$TMP"

# ==================================
# Initialize MariaDB
# ==================================
if [ ! -d "$MYSQLDATA/mysql" ]; then

    echo ""
    echo "=================================="
    echo "กำลังเตรียมฐานข้อมูล MariaDB"
    echo "=================================="

    mariadb-install-db \
        --user="$(whoami)" \
        --auth-root-authentication-method=normal \
        --datadir="$MYSQLDATA"

    if [ $? -ne 0 ]; then
        echo ""
        echo "ไม่สามารถเตรียม MariaDB ได้"
        exit 1
    fi

fi

# ==================================
# Start MariaDB
# ==================================
echo ""
echo "กำลังตรวจสอบ MariaDB..."

if ! mariadb-admin -uroot -p112611 ping >/dev/null 2>&1; then

    if ! pgrep -x mariadbd >/dev/null 2>&1 && \
       ! pgrep -x mysqld >/dev/null 2>&1; then

        echo "กำลังเริ่ม MariaDB..."

        mariadbd-safe \
            --datadir="$MYSQLDATA" \
            >/dev/null 2>&1 &

    fi

    # รอ MariaDB
    DB_READY=0

    for i in $(seq 1 30); do

        if mariadb-admin ping >/dev/null 2>&1; then
            DB_READY=1
            break
        fi

        sleep 1

    done

    if [ "$DB_READY" -ne 1 ]; then

        echo ""
        echo "=================================="
        echo "MariaDB ไม่สามารถเริ่มทำงานได้"
        echo "=================================="
        exit 1

    fi

    # ==================================
    # ตั้งรหัสผ่าน root
    # ==================================
    mariadb -uroot <<'SQL'
ALTER USER 'root'@'localhost' IDENTIFIED BY '112611';
FLUSH PRIVILEGES;
SQL

    if [ $? -ne 0 ]; then

        echo ""
        echo "ไม่สามารถตั้งรหัสผ่าน MariaDB ได้"
        exit 1

    fi

else

    echo "MariaDB ทำงานอยู่แล้ว"

fi

# ==================================
# Create start.sh
# ==================================
cat > "$HOME/start.sh" <<'STARTSH'
#!/data/data/com.termux/files/usr/bin/bash

# ==================================
# ตั้งค่าหลัก
# ==================================
WEBNAME="@php"
WEB="/storage/emulated/0/$WEBNAME"
TMP="$HOME/tmp"
MYSQLDATA="$PREFIX/var/lib/mysql"

mkdir -p "$TMP"
chmod 700 "$TMP"

# ==================================
# Start MariaDB
# ==================================
if ! pgrep -x mariadbd >/dev/null 2>&1 && \
   ! pgrep -x mysqld >/dev/null 2>&1; then

    mariadbd-safe \
        --datadir="$MYSQLDATA" \
        >/dev/null 2>&1 &

    DB_READY=0

    for i in $(seq 1 30); do

        if mariadb-admin -uroot -p112611 ping >/dev/null 2>&1; then
            DB_READY=1
            break
        fi

        sleep 1

    done

    if [ "$DB_READY" -ne 1 ]; then
        echo "MariaDB ไม่สามารถเริ่มทำงานได้"
        exit 1
    fi

fi

# ==================================
# ตรวจสอบ Web Root
# ==================================
if [ ! -d "$WEB" ]; then
    mkdir -p "$WEB"
fi

# ==================================
# Start PHP Web Server
# ==================================
cd "$WEB" || exit 1

clear

echo "=================================="
echo "       PHP + MariaDB Server"
echo "=================================="
echo "Web Root : $WEB"
echo "PHP Port : 8080"
echo "DB Port  : 3306"
echo "URL      : http://localhost:8080"
echo "=================================="
echo ""

exec php \
    -d opcache.enable=0 \
    -d opcache.enable_cli=0 \
    -d sys_temp_dir="$TMP" \
    -S 0.0.0.0:8080
STARTSH

chmod +x "$HOME/start.sh"

# ==================================
# Auto Start when Termux opens
# ==================================
if ! grep -qxF 'bash ~/start.sh' "$HOME/.bashrc" 2>/dev/null; then
    echo 'bash ~/start.sh' >> "$HOME/.bashrc"
fi

# ==================================
# Installation Summary
# ==================================
echo ""
echo "=================================="
echo "      ติดตั้งเสร็จเรียบร้อย"
echo "=================================="

echo "PHP      : $(php -v | head -n 1)"
echo "MariaDB  : $(mariadb --version | head -n 1)"
echo "ZIP      : $(zip -v 2>/dev/null | head -n 1)"
echo "UNZIP    : $(unzip -v 2>/dev/null | head -n 1)"

echo ""
echo "Web Name : $WEBNAME"
echo "Web Root : $WEB"
echo "PHP Port : 8080"
echo "DB Port  : 3306"
echo "DB User  : root"
echo "DB Pass  : 112611"
echo ""
echo "Auto Start : ON"
echo "=================================="
echo ""
echo "ปิด Termux แล้วเปิดใหม่"
echo "PHP + MariaDB จะเริ่มอัตโนมัติ"
echo ""
