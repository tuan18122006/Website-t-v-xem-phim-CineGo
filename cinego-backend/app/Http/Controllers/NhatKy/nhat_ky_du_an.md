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

---

## 🆕 NHẬT KÝ PHIÊN LÀM VIỆC - THÁNG 6/2026 (PHIÊN 2: HOÀN THIỆN ADMIN PANEL)

> **Thời gian thực hiện:** 10–11/06/2026  
> **Người thực hiện:** Antigravity AI + Nhóm dự án CineGo

---

### 🔤 5. Sửa Lỗi Phông Chữ Toàn Trang (Font Fix)

* **Vấn đề phát hiện:** Phông chữ trên toàn bộ giao diện (cả trang Client lẫn Admin) bị hiển thị không đúng — chữ tiếng Việt bị lỗi dấu, font dự phòng trình duyệt thay thế trông rất mất thẩm mỹ.
* **Nguyên nhân gốc rễ:** Font `Inter` được khai báo trong `style.css` nhưng chưa được import đúng cách từ Google Fonts.
* **Giải pháp:**
  * Bổ sung `@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap')` vào đầu file [`style.css`](file:///c:/laragon/www/CineGo/cinego-frontend/src/style.css).
  * Đặt `font-family: 'Inter', sans-serif` toàn cục cho thẻ `body`, `*` và các class tiêu đề để đảm bảo nhất quán.
* **Kết quả:** Toàn bộ trang web (Client + Admin) hiển thị chữ tiếng Việt sắc nét, không còn lỗi font.

---

### 🧹 6. Ẩn Thanh Trạng Thái Cố Định Ở Cuối Trang (Fixed Bottom Bar Removal)

* **Vấn đề phát hiện:** Khi số lượng mục trong danh sách (phim, suất chiếu, thể loại...) ít hoặc khi phóng to trình duyệt (zoom), một thanh trạng thái cố định ở phía dưới cùng màn hình xuất hiện và che khuất nội dung, gây mất thẩm mỹ.
* **Giải pháp áp dụng cho tất cả các trang admin:**
  * Loại bỏ phần tử `bottom-status-bar` khỏi các View: `MoviesView.vue`, `ShowtimesView.vue`, `GenreManagement.vue` và các view tương tự.
  * Cập nhật CSS của `.admin-layout` trong `DashboardView.vue`:
    * `min-height: 100vh` (đảm bảo trang luôn kéo dài chạm đáy màn hình).
    * `border-radius: 0` và `box-shadow: none` (loại bỏ viền bo góc gây ra khoảng trống xám).
    * `overflow: visible` thay vì `overflow: hidden` (tránh cắt xén nội dung khi phóng to).
* **Kết quả:** Giao diện Admin kéo dài đúng 100% chiều cao màn hình, không còn khoảng trống xám ở đáy trang dù mức zoom là bao nhiêu.

---

### 🪟 7. Sửa Lỗi Modal Popup Bị Che Khuất (Modal Backdrop Fix)

* **Vấn đề phát hiện:** Khi mở hộp thoại thêm/sửa (Modal), phần nền mờ (`modal-backdrop`) không bao phủ toàn bộ màn hình — phần Sidebar bên trái vẫn hiển thị, khiến trải nghiệm bị vỡ bố cục ở một số mức zoom.
* **Nguyên nhân gốc rễ:** Class `.glass-panel` được gán trực tiếp vào thẻ gốc (`root container`) của mỗi View. Vì `.glass-panel` có thuộc tính `backdrop-filter: blur()`, nó tạo ra một **stacking context mới**, khiến phần tử con `position: fixed` của `modal-backdrop` bị giam hãm bên trong thay vì bao phủ toàn bộ viewport.
* **Giải pháp:**
  * Xóa class `.glass-panel` khỏi thẻ `<div>` gốc của `MoviesView.vue` và `ShowtimesView.vue`.
  * Bọc **chỉ phần bảng danh sách** (list table) bằng một thẻ `<div class="glass-panel list-card">` riêng biệt.
  * Phần Modal `<div class="modal-backdrop">` giờ đây là con trực tiếp của container không có `backdrop-filter`, cho phép nó trải rộng `position: fixed` đúng toàn màn hình.
* **Kết quả:** Hộp thoại (Modal) Thêm/Sửa phim và Suất chiếu hiện đóng băng toàn bộ giao diện (kể cả Sidebar), hiển thị chuẩn ở mọi mức zoom từ 90% đến 200%.

---

### 🏷️ 8. Phóng To Nhãn Trạng Thái (Status Pill Enlargement)

* **Vấn đề phát hiện:** Cột "Trạng Thái" trong bảng danh sách Phim và Suất chiếu hiển thị badge trạng thái (Đang chiếu / Hoạt động / Đã hủy...) quá nhỏ, khó đọc và không nổi bật.
* **Giải pháp:**
  * Tăng kích thước class `.status-pill-cine` trong cả `MoviesView.vue` và `ShowtimesView.vue`:
    * `padding: 10px 20px` (tăng từ `4px 10px`).
    * `font-size: 16px` (tăng từ `13px`).
    * `font-weight: 800`.
    * `white-space: nowrap` (tránh xuống dòng).
  * Tăng độ rộng cột `.col-status` lên `170px` để vừa đủ chứa nhãn lớn hơn.
* **Kết quả:** Nhãn trạng thái dễ đọc, nổi bật và chuyên nghiệp hơn, phù hợp với chuẩn thiết kế CineGo.

---

### 🔌 9. Xây Dựng Backend API Quản Lý Suất Chiếu (Showtime CRUD API)

Sau khi các thành viên nhóm tự xây dựng phần quản lý phim, phần backend cho Suất Chiếu được phát triển và tích hợp hoàn chỉnh:

#### A. RoomController ([RoomController.php](file:///c:/laragon/www/CineGo/cinego-backend/app/Http/Controllers/Api/RoomController.php))
* Tạo mới `RoomController.php` với hàm `index()` trả về danh sách tất cả phòng chiếu (`rooms`) từ database.
* Đăng ký route public `GET /api/rooms` trong `api.php` — **không yêu cầu đăng nhập** — để Frontend có thể tải danh sách phòng khi mở hộp thoại thêm suất chiếu.

#### B. ShowtimeController ([ShowtimeController.php](file:///c:/laragon/www/CineGo/cinego-backend/app/Http/Controllers/Api/ShowtimeController.php))
* Tạo mới `ShowtimeController.php` với đầy đủ 3 phương thức:
  * **`index()`**: Lấy toàn bộ danh sách suất chiếu kèm tên phim (`movie_title`) và tên phòng (`room_name`) thông qua Eloquent relationship.
  * **`store(Request $request)`**: Validate và tạo suất chiếu mới với các trường `movie_id`, `room_id`, `start_time`, `end_time`, `format`, `translation`. Mặc định trạng thái là `active`.
  * **`destroy($id)`**: Tìm và xóa suất chiếu theo ID, trả về lỗi 404 nếu không tìm thấy.
* Đăng ký 3 route admin trong `api.php` (yêu cầu Sanctum + quyền `admin-only`):
  * `GET /api/admin/showtimes`
  * `POST /api/admin/showtimes`
  * `DELETE /api/admin/showtimes/{id}`

---

### 🖥️ 10. Giao Diện Quản Lý Suất Chiếu Frontend ([ShowtimesView.vue](file:///c:/laragon/www/CineGo/cinego-frontend/src/views/admin/ShowtimesView.vue))

* **Bảng danh sách suất chiếu** với đầy đủ cột: ID, Tên Phim, Phòng Chiếu, Giờ Bắt Đầu, Giờ Kết Thúc, Định Dạng, Dịch Thuật, Trạng Thái, Hành Động.
* **Spinner loading** trong khi tải dữ liệu từ API.
* **Empty state** khi chưa có suất chiếu nào.
* **Modal Thêm Suất Chiếu** với các tính năng nổi bật:
  * Dropdown chọn Phim (hiển thị tên phim kèm thời lượng).
  * Dropdown chọn Phòng Chiếu (hiển thị tên phòng kèm sức chứa).
  * Input `datetime-local` cho Giờ Bắt Đầu.
  * **Tự động tính Giờ Kết Thúc** dựa trên thời lượng phim đã chọn (hàm `calculateEndTime`).
  * Chọn Định Dạng (2D / 3D / IMAX) và Hình Thức Dịch Thuật (Phụ đề / Thuyết minh).
* **Chức năng Xóa** suất chiếu có xác nhận trước khi thực hiện.
* Giao diện áp dụng nhất quán tông màu Trắng & Đỏ Cinema Red của CineGo.

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

