## Demo QR bằng điện thoại

1. Kết nối máy tính và điện thoại cùng Wi-Fi.
          cmd 
          ipconfig
          Tìm dòng IPv4, ví dụ:
          IPv4 Address . . . . . . . . . . : 192.168.1.5

2. Chạy backend:

   php artisan serve --host=0.0.0.0 --port=8000

3. Chạy frontend:

   npm run dev -- --host 0.0.0.0

4. Mở link Network mà Vite hiển thị, ví dụ:

   Sửa .env backend
   FRONTEND_URL=http://localhost:5173
   thành
   FRONTEND_URL=http://192.168.1.5:5173
   APP_URL=http://192.168.1.5:8000
   Sau đó chạy:
   php artisan config:clear
   php artisan cache:clear
   

5. Đặt vé bằng link IP này, không dùng localhost.
6. Quét QR trong email bằng điện thoại.