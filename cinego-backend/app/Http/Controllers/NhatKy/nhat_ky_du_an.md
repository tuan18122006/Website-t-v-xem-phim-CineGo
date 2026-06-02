# NHẬT KÝ DỰ ÁN CINEGO (CINEGO PROJECT DIARY)

Nhật ký này ghi lại toàn bộ quá trình đại tu thiết kế, thương hiệu và dọn dẹp hệ thống **CineGo** (Laravel 11 Backend & Vue 3 Frontend) thực hiện vào tháng 6/2026. Đây là tài liệu bàn giao cơ sở (Base Project) cho nhóm học tập 5 thành viên tự tay phát triển tiếp.

---

## 📅 THÔNG TIN CHUNG
* **Dự án**: CineGo Cinema System (Hệ thống Đặt vé Rạp chiếu phim Hiện đại)
* **Công nghệ**: Laravel 11 (Backend Sanctum API) & Vue 3 (Frontend SPA với Vite & Pinia)
* **Ý tưởng thương hiệu**: Màu chủ đạo **Trắng sáng & Đỏ Đô / Đỏ Rạp Chiếu Phim (Cinema Red & Deep Burgundy)**. Thiết kế độc lập, brand sạch, không chứa logo hay yếu tố liên quan đến MoMo.
* **Mô hình vận hành**: Giai đoạn đầu vận hành **1 cụm rạp duy nhất** (CineGo Cinema) với quy trình tinh gọn, sẵn sàng mở rộng chuỗi rạp trong tương lai.

---

## 🛠️ CHI TIẾT CÁC THAY ĐỔI ĐÃ THỰC HIỆN

### 🎨 1. Hệ thống Màu sắc & Nhận diện Thương hiệu (Branding)
* **Biến màu toàn cục ([style.css](file:///c:/laragon/www/CineGo/cinego-frontend/src/style.css)):**
  * Chuyển đổi toàn bộ sắc hồng cũ sang **Màu Đỏ Rạp Chiếu Phim (Cinema Red - `#e50914`)**.
  * Chuyển đổi sắc tím/đỏ tối sang **Màu Đỏ Đô / Burgundy Quý Phái (`#9b000e`)**.
* **Nền giao diện chính ([App.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/App.vue)):** Đổi dải nền `radial-gradient` sang màu trắng ánh đỏ dịu nhẹ (`#fff0f2`), mang lại cảm giác sạch sẽ, hiện đại và vô cùng chuyên nghiệp.

---

### 🖥️ 2. Giao diện Client (Frontend Vue 3)

#### A. Thanh điều hướng thông minh ([Navbar.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/components/Navbar.vue))
* Loại bỏ hoàn toàn logo MoMo cũ. Thiết kế **Hộp Logo CineGo màu Đỏ** nổi bật (chữ `Cine` màu trắng, chữ `Go` màu đỏ trên nền trắng nổi bật).
* Cấu hình đầy đủ 7 danh mục điều hướng khách hàng chuẩn hệ thống rạp chiếu phim chuyên nghiệp (Lịch chiếu, Rạp chiếu, Phim chiếu, Review, Top phim, Blog, Về CineGo).

#### B. Banner Poster Phim Động (Hero Slider) trên Trang Chủ ([HomeView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/client/HomeView.vue))
* Thay thế banner tĩnh bằng **Hệ thống trượt poster phim động (Hero Slideshow)** siêu phẩm tuần này (Star Wars, Doraemon, Ma Xó) chất lượng cao.
* Tích hợp bộ đếm thời gian tự động chuyển tiếp poster sau **5 giây** đi kèm cơ chế giải phóng bộ đếm thông minh (`onUnmounted`) để tối ưu hóa bộ nhớ.
* Tích hợp nút mũi tên điều hướng trái/phải (`❮` / `❯`) dạng kính mờ và hàng nút chấm tròn (Dots indicator) cho phép điều khiển thủ công mượt mà.
* Sử dụng lớp phủ gradient đỏ đô/đen sâu lắng (`linear-gradient(to right, rgba(18, 5, 8, 0.95) 35%, ...)`) giúp phần chữ hiển thị sắc nét với hiệu ứng Fade vô cùng cao cấp.
* Click nút **"ĐẶT VÉ NGAY"** trên slide sẽ tự động chuyển trực tiếp khách hàng đến đúng trang đặt vé chi tiết của bộ phim tương ứng.

#### C. Chân trang chuyên nghiệp toàn cục ([Footer.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/components/Footer.vue))
* Tách toàn bộ mã nguồn chân trang chuyên nghiệp (thông tin pháp lý thương hiệu **CineGo Cinema System**, địa chỉ chi nhánh Hà Nội - Đà Nẵng - Hồ Chí Minh, hotline chăm sóc khách hàng) thành một component độc lập `Footer.vue`.
* Đưa vào file layout chính `App.vue` để hiển thị đồng bộ ở chân mọi trang client.
* **Tự động ẩn Navbar & Footer ở trang Admin:** Sử dụng computed check route `route.path.startsWith('/admin')` để tự động ẩn thanh điều hướng và chân trang client khi truy cập vào trang Admin, đảm bảo trang quản trị luôn rộng rãi, fullscreen và không bị lỗi bố cục.

#### D. Quy trình Đặt vé Nhanh Tuyến tính 3 Bước ([QuickBookingView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/client/QuickBookingView.vue))
* Tối giản hóa quy trình đa rạp phức tạp của Moveek thành quy trình **3 bước tuyến tính, dọc từ trên xuống dưới siêu tinh gọn dành riêng cho 1 rạp duy nhất**:
  * **Bước 1: Chọn Phim** (Chọn phim đang chiếu lướt ngang).
  * **Bước 2: Chọn Ngày Chiếu** (Chọn ngày bằng thanh nút chip ngang).
  * **Bước 3: Chọn Suất Chiếu & Đặt Vé** (Hiển thị các phòng chiếu của cụm rạp CineGo và các suất chiếu tương ứng).
* Loại bỏ toàn bộ các biến Reactive phức tạp như Tỉnh thành, Hệ thống rạp ảo và các hàm watch phức tạp, giúp mã nguồn Frontend cực kỳ ngắn gọn, trực quan và dễ hiểu cho các thành viên mới bắt đầu học.

---

### 📊 3. Trang Quản trị Admin Panel ([DashboardView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/admin/DashboardView.vue))
* Thiết kế Sidebar trái cố định có hộp logo CineGo đồng bộ, tích hợp hoạt ảnh hover đỏ đô thời thượng.
* Tích hợp biểu đồ doanh thu tuần dạng đồ thị đường **SVG Line Chart** trực quan, kết hợp với các thẻ thông tin (Tổng số phim, Suất chiếu hôm nay, Doanh thu tuần) phản hồi động.
* Tích hợp trực tiếp các view CRUD quản lý Phim (`MoviesView.vue`) và Suất chiếu (`ShowtimesView.vue`) dưới dạng các tab động để tối ưu trải nghiệm vận hành tập trung.

---

### 🔌 4. Dọn dẹp & Chuẩn hóa Backend (Laravel 11)

#### A. Giữ nguyên Cơ sở dữ liệu (Database) & Models
* Toàn bộ cấu trúc bảng cơ sở dữ liệu (`movies`, `rooms`, `seats`, `showtimes`, `bookings`...) cùng các model Eloquent đi kèm vẫn được bảo toàn nguyên vẹn để làm khung xương vững chắc cho dự án.
* **Cơ chế Mock Fallback thông minh:** Khi gọi API backend không có dữ liệu thật (trả về 404/403 do đã dọn dẹp controller), Frontend client sẽ tự động chuyển vùng hiển thị dữ liệu mẫu để giao diện luôn chạy mượt mà cho việc trình chiếu.

#### B. Giữ lại Hệ thống Phân quyền (Sanctum) & Dọn sạch code nghiệp vụ
* Giữ lại duy nhất file [AuthController.php](file:///c:/laragon/www/CineGo/cinego-backend/app/Http/Controllers/Api/AuthController.php) làm nhiệm vụ Xác thực cốt lõi (Đăng ký, Đăng nhập, Đăng xuất, User profile và vai trò).
* **Xóa bỏ hoàn toàn 5 controller nghiệp vụ cũ** (`MovieController`, `ShowtimeController`, `SeatController`, `SeatHoldController`, `BookingController`) và các route liên quan trong [api.php](file:///c:/laragon/www/CineGo/cinego-backend/routes/api.php).
* Việc này tạo ra một không gian làm việc "mới tinh" để các thành viên trong nhóm tự thiết kế, lập trình các API nghiệp vụ của riêng mình từ đầu.
* Bổ sung đầy đủ các chú thích phân vùng route chi tiết trong `api.php` để nhóm dễ dàng định vị nơi viết route public, route khách hàng, route quản lý admin.

#### C. Khai báo Gate phân quyền admin-only ([AppServiceProvider.php](file:///c:/laragon/www/CineGo/cinego-backend/app/Providers/AppServiceProvider.php))
* Chủ động định nghĩa Gate `admin-only` dựa trên cột `role` trong bảng `users`:
  ```php
  Gate::define('admin-only', function (User $user) {
      return $user->role === 'admin';
  });
  ```
* Giúp nhóm lập tức ứng dụng cơ chế bảo mật route middleware `can:admin-only` một cách an toàn và dễ dàng mà không cần lo lắng về các cấu hình bảo mật lồng chéo.

---

## 📈 ĐỊNH HƯỚNG MỞ RỘNG (SCALE-UP CHUỖI RẠP) DÀNH CHO NHÓM
Khi dự án hoàn tất và nhóm muốn nâng cấp lên thành hệ thống quản lý **Nhiều rạp (Chuỗi rạp)**:
1. **DB Migration:** Tạo bảng `cinemas` (id, name, address, city...) và bổ sung khóa ngoại `cinema_id` vào bảng phòng chiếu `rooms`.
2. **API Controller:** Lọc lịch chiếu phim (`ShowtimeController`) thêm điều kiện `cinema_id` thông qua bảng `rooms`.
3. **Frontend:** Gọi API `api.get('/cinemas')` để lấy danh sách rạp thật từ database hiển thị lên trang đặt vé nhanh thay vì khóa cứng cụm rạp CineGo duy nhất.
4. **Admin Panel:** Thêm tab CRUD "Quản lý Chi Nhánh" và bộ lọc "Chọn rạp" ở biểu đồ thống kê để xem doanh thu của từng cơ sở hoặc toàn bộ tổng công ty.
