#!/data/data/com.termux/files/usr/bin/bash

echo "Update โปรแกรม"
pkg update -y && \
pkg upgrade -y

echo "update php1.zip"
read -r -p "กด Enter เพื่อดำเนินการต่อ..."
