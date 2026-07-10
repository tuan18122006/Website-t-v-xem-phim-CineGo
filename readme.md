# 1. Clone code
git clone <https://github.com/tuan18122006/Website-t-v-xem-phim-CineGo>
cd cinego-backend

# 2. Cài thư viện PHP (dựa theo composer.lock đã commit)
composer install

# 3. Tải file .env thật từ Google Drive, đặt vào gốc thư mục
#    (hoặc copy .env.example rồi tự điền DB/VNPay riêng của máy mình)

# 4. Tạo APP_KEY riêng cho máy mình (mỗi máy nên có key riêng, không dùng chung)
php artisan key:generate

# 5. Tạo database rỗng tên "cinego" trong MySQL trước, rồi chạy để lấy dữ liệu:
php artisan migrate --seed

# 6. Tạo symlink cho ảnh upload (BẮT BUỘC, hay bị quên nhất)
php artisan storage:link

# 7. Chạy server
cd cinego-backend
php artisan serve
cd cinego-frontend
npm run devn

# 8. Tài khoản test thanh toán
 Ngân hàng :NCB
 STK: 9704198526191432198
 Tên chủ thẻ: NGUYEN VAN A
 Ngày phát hành: 07/15
 Mật khẩu OTP: 123456