#!/bin/bash
git filter-branch -f --msg-filter '
  MSG=$(cat)
  if [[ "$MSG" == *"Fix UI/UX and toast notifications, enhance components"* ]]; then
    echo "Cải thiện giao diện, thông báo toast và các thành phần"
  elif [[ "$MSG" == *"Remove POS feature and fix banner image saving bug"* ]]; then
    echo "Xóa chức năng POS và sửa lỗi lưu ảnh banner"
  else
    echo "$MSG"
  fi
' -- --all
