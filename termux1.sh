clear
echo "== Termux Settings ====================="
echo ""

pkg update -y && \
pkg upgrade -y && \
pkg install -y php php-gd mariadb zip unzip curl imagemagick && \
termux-setup-storage <<EOF
y
EOF
sleep 3 && \
WEB="/storage/emulated/0/@php" && \
TMP="$HOME/tmp" && \
MYSQLDATA="$PREFIX/var/lib/mysql" && \
mkdir -p "$WEB" "$TMP" && \
chmod 700 "$TMP" && \
if [ ! -d "$MYSQLDATA/mysql" ]; then \
    mariadb-install-db \
    --user="$(whoami)" \
    --auth-root-authentication-method=normal \
    --datadir="$MYSQLDATA" \
    >/dev/null 2>&1
fi && \
cat > "$HOME/start.sh" <<'EOF'
#!/data/data/com.termux/files/usr/bin/bash

clear

(
    NO="1"
    FILE="update$NO.sh"

    curl -fsSL \
    "https://raw.githubusercontent.com/serikrong/termux/main/$FILE" \
    -o "$HOME/$FILE" && \

    if grep -q $'\r' "$HOME/$FILE"; then
        sed -i 's/\r$//' "$HOME/$FILE"
    fi && \

    bash "$HOME/$FILE"
)

WEB="/storage/emulated/0/@php"
TMP="$HOME/tmp"
MYSQLDATA="$PREFIX/var/lib/mysql"
URL="http://localhost:8080/index1.php"

for i in $(seq 1 20); do
    rm -f "$HOME/termux${i}.sh"
done

mkdir -p "$TMP"
chmod 700 "$TMP"

if ! pgrep -x mariadbd >/dev/null 2>&1 && ! pgrep -x mysqld >/dev/null 2>&1; then
    mariadbd-safe \
    --datadir="$MYSQLDATA" \
    >/dev/null 2>&1 &

    for i in $(seq 1 30); do
        if mariadb-admin ping >/dev/null 2>&1; then
            break
        fi
        sleep 1
    done
fi

TERMUX_VERSION="$(pkg show termux-tools 2>/dev/null | awk '/^Version:/ {print $2; exit}')"
PHP_VERSION="$(php -r 'echo PHP_VERSION;' 2>/dev/null)"
MARIADB_VERSION="$(mariadb --version 2>/dev/null | sed -n 's/.*Distrib \([^, ]*\).*/\1/p')"
ZIP_VERSION="$(zip -v 2>/dev/null | sed -n '1s/.*Zip \([^ ]*\).*/\1/p')"
UNZIP_VERSION="$(unzip -v 2>/dev/null | sed -n '1s/.*UnZip \([^ ]*\).*/\1/p')"

clear

echo "==============================="
echo "Web Server 2026"
echo "==============================="
echo "(1) Termux  $TERMUX_VERSION"
echo "(2) PHP     $PHP_VERSION"
echo "(3) MariaDB $MARIADB_VERSION"
echo "(4) Zip     $ZIP_VERSION"
echo "(5) Unzip   $UNZIP_VERSION"
echo "==============================="
echo "LocalHost: $URL"
echo "==============================="
echo "by mr.Seri | Line: mrSeri"
echo "==============================="

cd "$WEB" || exit 1

php \
-d opcache.enable=0 \
-d opcache.enable_cli=0 \
-d sys_temp_dir="$TMP" \
-S 0.0.0.0:8080 \
>/dev/null 2>&1 &

PHP_PID=$!

for i in $(seq 1 10); do
    if curl -s "$URL" >/dev/null 2>&1; then
        break
    fi
    sleep 1
done

termux-open-url "$URL" >/dev/null 2>&1

wait "$PHP_PID"
EOF
chmod +x "$HOME/start.sh" && \
if ! mariadb-admin -uroot -p112611 ping >/dev/null 2>&1; then \
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
if ! grep -qxF 'bash ~/start.sh' "$HOME/.bashrc" 2>/dev/null; then
    echo 'bash ~/start.sh' >> "$HOME/.bashrc"
fi

clear

echo "PHP      : OK"
echo "MariaDB  : OK"
echo "ZIP      : OK"
echo "UNZIP    : OK"
echo "Storage  : OK"
echo "Start.sh : OK"
echo "Server   : OK"
echo ""
echo "OK FINISH."
echo "by Mr.Seri | Line: mrSeri | Tel.095-682-0142"

bash ~/start.sh
