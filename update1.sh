#!/data/data/com.termux/files/usr/bin/bash

read -r -p "กด Enter เพื่อเริ่ม Update Termux..." _

pkg update -y && \
pkg upgrade -y
