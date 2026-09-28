#!/data/data/com.termux/files/usr/bin/bash

clear
echo "======================================"
echo "Update termuk"
echo "======================================"
pkg update -y && \
pkg upgrade -y

echo "======================================"
echo "Update Program"
echo "======================================"
read -r -p "กด Enter เพื่อดำเนินการต่อ..."
