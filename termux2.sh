clear
echo "== Termux Settings ====================="
echo ""

pkg update -y && \
pkg upgrade -y && \
pkg install -y php mariadb zip unzip && \
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

WEB="/storage/emulated/0/@php"
TMP="$HOME/tmp"
MYSQLDATA="$PREFIX/var/lib/mysql"

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
echo "by mr.Seri | Line: mrSeri"
echo "==============================="

cd "$WEB" || exit 1

exec php \
-d opcache.enable=0 \
-d opcache.enable_cli=0 \
-d sys_temp_dir="$TMP" \
-S 0.0.0.0:8080
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

sleep 3
exit
