# BẢNG PHÂN CÔNG NHIỆM VỤ DỰ ÁN CINEGO (MÔ HÌNH TECH LEAD)

Dự án này sử dụng mô hình **Tách biệt Frontend (Vue 3) và Backend (Laravel 11)**. Để tối ưu hóa quản lý, dự án sẽ được vận hành theo cơ cấu: **1 Trưởng nhóm (Tech Lead/QA) điều phối + 4 Lập trình viên (Fullstack) triển khai tính năng.**

---

## 🌟 VAI TRÒ CỦA TRƯỞNG NHÓM (ATUAN - TECH LEAD, DEVOPS & QA)
Thay vì code một chức năng cụ thể (như Đặt vé hay Rạp phim), Trưởng nhóm sẽ nắm giữ sinh mệnh của toàn bộ dự án, đảm bảo mã nguồn chạy ổn định và trơn tru từ đầu đến cuối.

**1. Khởi tạo Nền tảng (Common Files):**
* Thiết kế và kiểm soát các file **Database Migration** cốt lõi.
* Tự tay viết toàn bộ file `DatabaseSeeder.php` tạo sẵn dữ liệu mẫu (Các Rạp, hàng trăm Ghế, Phim mẫu, User mẫu) để 4 Dev có data chuẩn dùng chung.
* **[QUAN TRỌNG] Code các Component UI dùng chung:** Trưởng nhóm sẽ tự tay viết một Component `SeatMap.vue` (Ma trận ghế). Component này xài chung cho cả Admin (Thành viên 1) và Client (Thành viên 3) nhằm đồng bộ 100% giao diện rạp.
* Thiết lập cấu trúc thư mục, code sẵn Khung Layout (Header, Footer, Sidebar Admin) để các thành viên chỉ việc nhúng nội dung vào.

**2. Quản lý Mã nguồn (Git Master):**
* Nắm giữ quyền cao nhất của kho lưu trữ GitHub. 
* Khi các Dev làm xong tính năng, Trưởng nhóm là người trực tiếp Review (Kiểm duyệt) code.
* Đứng ra gỡ rối các Xung đột mã nguồn (Merge Conflict) và tự tay bấm nút Merge lên nhánh `main`.

**3. Kiểm thử & Đảm bảo chất lượng (QA / Tester):**
* Đóng vai trò là khách hàng khó tính. Trực tiếp chạy thử nghiệm (Test) toàn bộ các chức năng do 4 Dev làm ra.
* Ghép nối các luồng với nhau (Từ Admin thêm suất chiếu -> Client thấy phim -> Khách mua vé -> Admin xem doanh thu) để đảm bảo hệ thống không bị đứt gãy. Bắt Bug và trả về cho Dev sửa.

---

## 👨‍💻 PHÂN CÔNG 4 THÀNH VIÊN DEV (LÀM CẢ FRONTEND & BACKEND)

### 🎟️ THÀNH VIÊN 1: Quản Lý Không Gian Rạp (Admin)
**Mục tiêu:** Quản lý không gian vật lý, phim ảnh và ma trận ghế ngồi.
* **Backend:** Code API CRUD (Thêm/Sửa/Xóa) cho `movies` và `rooms`. 
* **Nghiệp vụ khó:** Khi gọi API tạo Rạp, phải code vòng lặp tự động sinh ra hàng loạt ghế (bảng `seats`) tương ứng với rạp đó. Viết API cập nhật hàng loạt (bulk-update) để đổi loại ghế (Thường -> VIP -> Lối đi).
* **Frontend:** Xây dựng trang Quản lý Phim. Xây dựng trang Quản lý Rạp. Với tính năng "Visual Editor", **KHÔNG cần tự code HTML/CSS sơ đồ**, mà Import Component `SeatMap.vue` do Trưởng nhóm làm sẵn, sau đó gắn API lưu thiết kế ghế xuống DB.
* **✅ Thành quả bàn giao (Deliverables):**
  * **Giao diện Quản lý Phim:** Bảng danh sách phim, form Thêm/Sửa/Xóa phim (cho phép upload ảnh Poster).
  * **Giao diện Quản lý Rạp:** Bảng danh sách Rạp. Khi bấm "Thêm Rạp mới", Database phải tự động sinh ra hàng trăm ghế. 
  * **Chức năng Visual Editor (Tạo hình rạp thực tế):** Nút "Chỉnh sửa sơ đồ ghế" mở ra một ma trận ghế trực quan. Để "cắt gọt" ma trận vuông vức ban đầu thành hình dáng rạp ngoài đời thật (có lối đi ở giữa rạp, hoặc bị khuyết góc), Admin chỉ cần click vào ghế để biến nó thành loại "Lối đi" (ghế sẽ tàng hình/trong suốt). Các ghế còn lại click để đổi loại (Xám=Thường, Đỏ=VIP, Hồng=Couple). Bấm "Lưu" phải ghi nhận chính xác xuống Database.

### 🕒 THÀNH VIÊN 2: Quản Lý Lịch Chiếu & Cấu Hình Giá (Admin)
**Mục tiêu:** Đưa phim vào rạp chiếu đúng giờ và định giá cho từng ghế.
* **Backend:** Code API CRUD cho `showtimes` và `price_configs`.
* **Nghiệp vụ khó (Chống đụng lịch):** Viết câu truy vấn Database phức tạp để kiểm tra xem khoảng thời gian chiếu của suất chuẩn bị thêm có bị trùng (overlap) với bất kỳ suất chiếu nào khác trong cùng Rạp đó không. 
* **Frontend:** Code giao diện Quản lý Suất chiếu. Có Dropdown chọn Phim, chọn Rạp. Tự động dùng Javascript tính giờ kết thúc dựa trên thời lượng phim. Giao diện thiết lập giá riêng cho từng loại ghế của suất chiếu đó.
* **✅ Thành quả bàn giao (Deliverables):**
  * **Giao diện Quản lý Lịch chiếu:** Form thêm lịch chiếu cho phép chọn Phim và Rạp từ dữ liệu của Thành viên 1. Nếu Admin cố tình xếp 2 phim chiếu cùng một giờ vào cùng một phòng, hệ thống phải báo lỗi đỏ chót từ Backend (Chống trùng lịch).
  * **Giao diện Cấu hình Giá:** Giao diện cho phép Admin nhập tay giá tiền cho từng loại ghế (Thường, VIP, Couple) của chính suất chiếu vừa tạo.

### 🛒 THÀNH VIÊN 3: Luồng Đặt Vé & Thanh Toán (Client)
**Mục tiêu:** Trái tim của dự án, nơi tạo ra doanh thu. Xử lý giữ ghế và xuất vé.
* **Backend:** Code API lấy danh sách ghế trống/đã mua của một suất chiếu. API Giữ chỗ (Hold) và API Xác nhận Thanh toán (`bookings`, `booking_details`).
* **Nghiệp vụ khó:** Xử lý mảng dữ liệu ghế, đảm bảo khóa ghế an toàn để 2 người không mua trùng 1 ghế. Gọi Helper tính tổng tiền chính xác dựa trên loại ghế khách chọn.
* **Frontend:** Xây dựng màn hình Đặt vé. **KHÔNG cần tự vẽ lưới ghế**, mà Import Component `SeatMap.vue` do Nhóm trưởng cấp sẵn. Chỉ cần xử lý mảng logic: ghế nào mua rồi thì đẩy mảng cấm click, tính tổng tiền Live ở góc màn hình. Chuyển sang màn hình tóm tắt hóa đơn và gọi API chốt đơn.
* **✅ Thành quả bàn giao (Deliverables):**
  * **Trang Chọn ghế (Client):** Giao diện sơ đồ rạp hiện lên chính xác theo đúng hình dáng mà Thành viên 1 đã vẽ. Những ghế đã bị người khác mua phải chuyển màu xám và cấm click. Khi khách click vào ghế trống, góc màn hình tự động cộng dồn tổng tiền dựa trên giá vé mà Thành viên 2 đã cài đặt.
  * **Trang Thanh toán (Client):** Màn hình hiển thị Hóa đơn cuối cùng (Phim gì, rạp nào, ghế nào). Bấm "Xác nhận thanh toán" gọi API mượt mà và chuyển sang trang "Thành công" hiển thị vé.

### 👥 THÀNH VIÊN 4: Giao Diện Khách Hàng & Quản Lý Nhân Sự (Client + Admin)
**Mục tiêu:** Thu hút khách hàng bằng giao diện đẹp và quản lý quyền hạn nhân viên rạp.
* **Backend:** Code API hiển thị danh sách phim Đang chiếu / Sắp chiếu cho Trang chủ. API CRUD tài khoản `users` với điều kiện `role = staff`.
* **Nghiệp vụ khó:** Code phân quyền (Authorization) đảm bảo tài khoản Staff đăng nhập vào Admin chỉ được xem danh sách vé, không có quyền xóa phim hay sửa giá vé.
* **Frontend:** Chịu trách nhiệm toàn bộ "Mặt tiền" của Web: Code Trang chủ cực đẹp, hiển thị danh sách phim có Filter lọc thể loại. Code trang Admin quản lý danh sách nhân viên.
* **✅ Thành quả bàn giao (Deliverables):**
  * **Trang chủ rạp phim (Client):** Giao diện cực xịn, có slide (carousel) banner phim nổi bật, danh sách phim lướt ngang. Bấm vào Phim hiện ra trang Chi tiết phim kèm nút "Mua vé ngay".
  * **Trang Quản lý Nhân viên (Admin):** Form Thêm/Sửa/Xóa tài khoản nhân viên. 
  * **Chức năng Phân quyền:** Demo được việc dùng tài khoản Nhân viên đăng nhập vào hệ thống Admin thì các menu "Xóa Phim", "Sửa Giá Vé" bị ẩn đi hoặc báo lỗi cấm truy cập. Nhân viên chỉ được xem danh sách vé.

---

> **💡 MẸO DÀNH CHO CÁC LẬP TRÌNH VIÊN (DEV):**
> Nhóm đang áp dụng AI để đẩy nhanh tiến độ. Khi gặp đoạn logic khó (Ví dụ: Chống trùng lịch, Vòng lặp sinh ghế, Vẽ sơ đồ HTML/CSS), các bạn hãy copy chính xác phần **Nghiệp vụ khó** ở trên, dán vào ChatGPT/Gemini kèm câu lệnh: *"Tôi đang dùng Laravel 11 (hoặc Vue 3), hãy viết code cho yêu cầu sau..."* để có giải pháp chính xác nhất!
