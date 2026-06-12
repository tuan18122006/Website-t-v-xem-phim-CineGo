# NHẬT KÝ DỰ ÁN CINEGO (CINEGO PROJECT DIARY)

Nhật ký này ghi lại toàn bộ quá trình đại tu thiết kế, thương hiệu và dọn dẹp hệ thống **CineGo** (Laravel 11 Backend & Vue 3 Frontend). Đây là tài liệu bàn giao cơ sở để theo dõi tiến độ phát triển qua các giai đoạn.

---

## 📅 THÔNG TIN CHUNG
* **Dự án**: CineGo Cinema System (Hệ thống Đặt vé Rạp chiếu phim Hiện đại)
* **Công nghệ**: Laravel 11 (Backend Sanctum API) & Vue 3 (Frontend SPA với Vite & Pinia)
* **Ý tưởng thương hiệu**: Màu chủ đạo **Trắng sáng & Đỏ Đô / Đỏ Rạp Chiếu Phim (Cinema Red & Deep Burgundy)**.

---

## 🛠️ CHI TIẾT CÁC THAY ĐỔI ĐÃ THỰC HIỆN

### 🎨 1. Hệ thống Màu sắc & Nhận diện Thương hiệu (Branding Base)
* **Biến màu toàn cục ([style.css](file:///c:/laragon/www/CineGo/cinego-frontend/src/style.css)):**
  * Chuyển đổi toàn bộ sắc hồng cũ sang **Màu Đỏ Rạp Chiếu Phim (Cinema Red - `#e50914`)**.
  * Chuyển đổi sắc tím/đỏ tối sang **Màu Đỏ Đô / Burgundy Quý Phái (`#9b000e`)**.
* **Nền giao diện chính ([App.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/App.vue)):** Đổi dải nền `radial-gradient` sang màu trắng ánh đỏ dịu nhẹ (`#fff0f2`), mang lại cảm giác sạch sẽ, hiện đại.

---

### 🖥️ 2. Giao diện Client Base (Frontend Vue 3)
* **Thanh điều hướng thông minh ([Navbar.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/components/Navbar.vue)):**
  * Thiết kế Hộp Logo CineGo màu Đỏ nổi bật (chữ `Cine` màu trắng, chữ `Go` màu đỏ trên nền trắng).
* **Banner Poster Phim Động (Hero Slider) trên Trang Chủ ([HomeView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/client/HomeView.vue)):**
  * Tích hợp hệ thống trượt poster phim động (Hero Slideshow) tự động chuyển sau 5 giây, có nút điều khiển kính mờ.

---

### 🔌 3. Dọn dẹp & Chuẩn hóa Backend Base (Laravel 11)
* **Giữ nguyên Cơ sở dữ liệu (Database) & Models:** Cấu trúc bảng `movies`, `rooms`, `seats`, `showtimes`, `bookings` được bảo toàn.
* **Xác thực:** Giữ lại [AuthController.php](file:///c:/laragon/www/CineGo/cinego-backend/app/Http/Controllers/Api/AuthController.php) cho đăng ký/đăng nhập. Xóa bỏ các controller cũ để làm sạch không gian phát triển mới.

---

## ⚡ CẬP NHẬT MỚI: HỆ THỐNG QUẢN LÝ TÀI KHOẢN NÂNG CAO & 4 TRANG CLIENT MỚI (Ngày 13/06/2026)

Chúng tôi đã triển khai đợt nâng cấp lớn về cấu trúc cơ sở dữ liệu, API nghiệp vụ, đại tu giao diện quản lý tài khoản chuyên nghiệp và xây dựng thêm 4 trang giao diện khách hàng.

### 1. Cơ sở dữ liệu & Nghiệp vụ Backend (Laravel 11)
* **Database Migrations:**
  * Tạo migration nâng cấp bảng `users` (thêm hạng thành viên, điểm thưởng, tổng chi tiêu, trạng thái làm việc, lý do khóa).
  * Bổ sung cột `age` (Tuổi) vào bảng `users` (`2026_06_13_000002_add_age_to_users_table.php`).
  * Tạo mới bảng `user_devices_logs` (log thiết bị/IP), `shift_logs` (quản lý ca trực POS), `action_logs` (audit log bảo mật) và `refund_requests` (duyệt hoàn vé).
* **Models & Relationships:** Khai báo liên kết đầy đủ trong `User.php`, `Voucher.php`, và tạo các model mới tương ứng.
* **Controllers & Routes:**
  * **[UserController.php](file:///c:/laragon/www/CineGo/cinego-backend/app/Http/Controllers/Api/UserController.php):** Cung cấp các API tìm kiếm/lọc nâng cao, xem Profile 360°, khóa/mở khóa tài khoản kèm lý do, thay đổi hạng thành viên, tặng/thu hồi voucher, ẩn danh tính bảo vệ dữ liệu (GDPR).
  * **ShiftController & RefundController:** Xử lý nghiệp vụ ca trực đối soát và quy trình đề xuất/duyệt hoàn vé giải phóng ghế ngồi tự động.

### 2. Đại tu Giao diện Quản trị ([UserManagement.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/admin/UserManagement.vue))
* **Tối ưu hóa Bố cục:** Khôi phục sidebar dọc màu đen (`CineGo Hub`) gọn gàng ở cột trái, giải quyết tình trạng lồng ghép sidebar thừa thãi của Dashboard chính.
* **Loại bỏ Checkbox tích chọn:** Gỡ bỏ hoàn toàn cột checkbox tích chọn và dải bulk actions để giao diện bảng thoáng đãng, tinh giản.
* **Click Avatar xem nhanh Profile 360°:** Nhấp chuột vào avatar tròn của bất kỳ người dùng nào trên bảng sẽ tự động mở hộp thoại chi tiết Profile 360°.
* **Hiển thị Tên, Tuổi, SĐT:** Khối thông tin nhanh hiển thị đầy đủ tên tuổi và số điện thoại. Tích hợp thêm trường nhập tuổi trong form thêm/sửa.
* **Hộp thoại Custom Dialog:** Loại bỏ toàn bộ `confirm()` và `prompt()` mặc định của trình duyệt, thay thế bằng modal popup xác nhận/nhập liệu mượt mà, đồng bộ với thiết kế.
* **Thẻ VIP 3D Tilt:** Tích hợp hiệu ứng nghiêng xoay góc nhìn 3D tương tác chân thực trên thẻ thành viên VIP khi di chuột qua.

### 3. Giải quyết lỗi tải lại trang ([DashboardView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/admin/DashboardView.vue))
* Đồng bộ và lưu trữ `activeTab` của Admin Dashboard vào `localStorage` của trình duyệt. F5 reload trang sẽ giữ đúng tab đang làm việc hiện tại, không bị reset chuyển hướng về màn hình stats chính.

### 4. Triển khai 4 trang Client mới (Review, Top Phim, Blog, Về CineGo)
* **Review Phim ([ReviewPhimView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/client/ReviewPhimView.vue)):** Bố cục lưới thẻ review, play button giả lập, badge điểm trung bình và ý kiến đánh giá có gắn nhãn *"Đã mua vé qua CineGo"*.
* **Top Phim ([TopMovies.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/client/TopMovies.vue)):** Bảng xếp hạng phim đang hot và phim hay nhất, đánh số thứ tự kim loại (Vàng, Bạc, Đồng) kèm nút đặt vé nhanh.
* **Blog Phim ([BlogPhimView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/client/BlogPhimView.vue)):** Bài viết tiêu điểm (Hero post) nổi bật và lưới bài viết tin tức điện ảnh mới cập nhật.
* **Về CineGo ([AboutCineGoView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/client/AboutCineGoView.vue)):** Giới thiệu phòng chiếu Luxury, ghế VIP da 100%, thẻ thành viên đặc quyền.
* **Định tuyến & Navbar:** Khai báo 4 định tuyến lazy-loaded mới trong `router/index.js` và cập nhật Navbar.vue liên kết trực tiếp tới các trang này.
