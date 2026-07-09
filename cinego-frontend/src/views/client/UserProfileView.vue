<template>
  <div class="cinego-profile-container">
    <div class="cinego-main-header">
      <h2>THÔNG TIN CHUNG</h2>
    </div>

    <div class="cinego-profile-body">
      <aside class="cinego-sidebar">
        <h3 class="sidebar-title">TÀI KHOẢN CINEGO</h3>
        <nav class="cinego-menu">
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'info' }"
            @click="activeTab = 'info'"
          >
            THÔNG TIN CHUNG
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'security' }"
            @click="activeTab = 'security'"
          >
            ĐỔI MẬT KHẨU
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'history' }"
            @click="activeTab = 'history'"
          >
            LỊCH SỬ GIAO DỊCH
          </button>
        </nav>
      </aside>

      <main class="cinego-content-area">
        <div class="cinego-member-summary-box">
          <div class="avatar-block">
            <div class="avatar-frame">
              <img
                :src="profileForm.avatar_url"
                alt="Avatar"
                class="avatar-img"
              />
            </div>
            <label for="avatar-file" class="btn-cinego-small">Thay đổi</label>
            <input
              type="file"
              id="avatar-file"
              @change="handleAvatarUpload"
              accept="image/*"
              hidden
            />
          </div>

          <div class="summary-details">
            <p class="welcome-text">
              Xin chào <strong>{{ profileForm.name }}</strong
              >,
            </p>
            <p class="welcome-sub">
              Với trang này, bạn sẽ quản lý được tất cả thông tin tài khoản của
              mình.
            </p>

            <div class="member-stats-grid">
              <div class="stat-col rank-col">
                <p class="stat-label">Cấp Độ Thẻ</p>
                <span class="rank-badge-text">⭐ MEMBER</span>
                <p class="stat-sub">
                  Tổng Chi Tiêu: <span class="txt-red">0 đ</span>
                </p>
                <p class="stat-sub">
                  Điểm CineGo: <span class="txt-red">0 P</span>
                </p>
              </div>
              <div class="stat-col">
                <p class="stat-label">Thẻ quà tặng</p>
                <p class="stat-value">0 đ</p>
                <button class="btn-stat-view">Xem</button>
              </div>
              <div class="stat-col">
                <p class="stat-label">Voucher</p>
                <p class="stat-value">0</p>
                <button class="btn-stat-view">Xem</button>
              </div>
              <div class="stat-col">
                <p class="stat-label">Coupon</p>
                <p class="stat-value">1</p>
                <button class="btn-stat-view">Xem</button>
              </div>
            </div>
          </div>
        </div>

        <div class="cinego-tab-dynamic-content">
          <div v-if="activeTab === 'info'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Thông tin tài khoản</h3>
              <button
                type="button"
                @click="isEditingInfo = !isEditingInfo"
                class="btn-cinego-small"
              >
                {{ isEditingInfo ? "Hủy" : "Thay đổi" }}
              </button>
            </div>

            <form @submit.prevent="updateProfile" class="cinego-info-form">
              <div class="info-data-row">
                <span class="info-label">Tên :</span>
                <input
                  v-if="isEditingInfo"
                  type="text"
                  v-model="profileForm.name"
                  class="cinego-input"
                  required
                />
                <span v-else class="info-text">{{ profileForm.name }}</span>
              </div>
              <div class="info-data-row">
                <span class="info-label">Email :</span>
                <span class="info-text disabled-text">{{
                  profileForm.email
                }}</span>
              </div>
              <div class="info-data-row">
                <span class="info-label">Điện thoại :</span>
                <input
                  v-if="isEditingInfo"
                  type="text"
                  v-model="profileForm.phone"
                  class="cinego-input"
                />
                <span v-else class="info-text">{{
                  profileForm.phone || "Chưa cập nhật"
                }}</span>
              </div>
              <div
                class="info-data-row"
                v-if="isEditingInfo || profileForm.birthday"
              >
                <span class="info-label">Ngày sinh :</span>
                <input
                  v-if="isEditingInfo"
                  type="date"
                  v-model="profileForm.birthday"
                  class="cinego-input"
                />
                <span v-else class="info-text">{{
                  formatDate(profileForm.birthday)
                }}</span>
              </div>

              <button
                type="submit"
                v-if="isEditingInfo"
                class="btn-cinego-submit"
                :disabled="btnLoading"
              >
                {{ btnLoading ? "Đang lưu..." : "LƯU THÔNG TIN" }}
              </button>
            </form>
          </div>

          <div v-if="activeTab === 'security'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Đổi mật khẩu bảo mật</h3>
            </div>
            <form @submit.prevent="changePassword" class="cinego-info-form">
              <div class="info-data-row column-layout">
                <label class="info-label">Mật khẩu hiện tại:</label>
                <input
                  type="password"
                  v-model="passwordForm.old_password"
                  class="cinego-input wide"
                  required
                  placeholder=""
                />
              </div>
              <div class="info-data-row column-layout">
                <label class="info-label"
                  >Mật khẩu mới (Tối thiểu 8 ký tự):</label
                >
                <input
                  type="password"
                  v-model="passwordForm.new_password"
                  class="cinego-input wide"
                  required
                  placeholder=""
                />
              </div>
              <div class="info-data-row column-layout">
                <label class="info-label">Xác nhận mật khẩu mới:</label>
                <input
                  type="password"
                  v-model="passwordForm.confirm_password"
                  class="cinego-input wide"
                  required
                  placeholder=""
                />
              </div>

              <button
                type="submit"
                class="btn-cinego-submit red-btn"
                :disabled="btnLoading"
              >
                {{ btnLoading ? "Đang xử lý..." : "CẬP NHẬT MẬT KHẨU" }}
              </button>
            </form>
          </div>

          <div v-if="activeTab === 'history'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Lịch sử giao dịch đặt vé</h3>
              <div class="history-filter-toggle">
                <button
                  :class="{ active: subTab === 'upcoming' }"
                  @click="subTab = 'upcoming'"
                >
                  Vé sắp chiếu
                </button>
                |
                <button
                  :class="{ active: subTab === 'past' }"
                  @click="subTab = 'past'"
                >
                  Vé cũ
                </button>
              </div>
            </div>

            <div v-if="loadingHistory" class="cinego-loading">
              Đang quét vé từ hệ thống...
            </div>
            <div v-else class="cinego-history-table-wrapper">
              <table class="cinego-table">
                <thead>
                  <tr>
                    <th>Tên Phim</th>
                    <th>Suất Chiếu / Phòng</th>
                    <th>Ghế Ngồi</th>
                    <th>Giá Vé</th>
                    <th>Hành Động</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ticket in filteredTickets" :key="ticket.id">
                    <td class="bold-text">{{ ticket.movie_title }}</td>
                    <td>
                      {{ ticket.start_time }} <br />
                      {{ formatDate(ticket.date) }} <br />
                      <strong>{{ ticket.room_name }}</strong>
                    </td>
<td class="txt-red bold-text">
                  {{ ticket.seats ? ticket.seats.map(seat => typeof seat === 'object' ? `${seat.row}${seat.number}` : seat).join(", ") : '' }}
                </td>                    <td class="bold-text">
                      {{ formatPrice(ticket.total_price) }}đ
                    </td>
                    <td>
                      <button
                        v-if="subTab === 'upcoming'"
                        @click="viewQrCode(ticket)"
                        class="btn-table-action"
                      >
                        Xem Mã QR
                      </button>
                      <span
                        v-else
                        class="status-text-badge"
                        :class="ticket.status"
                        >{{ ticket.status_label || "Đã xong" }}</span
                      >
                    </td>
                  </tr>
                  <tr v-if="filteredTickets.length === 0">
                    <td colspan="5" class="text-center empty-msg">
                      Không có dữ liệu giao dịch đặt vé nào.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
        </div>
      </main>
    </div>

    <div
      v-if="isQrModalOpen"
      class="cinego-modal-backdrop"
      @click.self="isQrModalOpen = false"
    >
      <div class="cinego-modal-content">
        <button class="close-modal-x" @click="isQrModalOpen = false">✕</button>
        <h3>MÃ VÉ SOÁT VÉ CINEGO</h3>
        <p class="modal-movie-title">{{ selectedTicket?.movie_title }}</p>
        <div class="qr-img-wrapper">
          <img
            :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${selectedTicket?.booking_code}`"
            alt="QR Code"
          />
        </div>
        <p class="modal-code">
          Mã đặt vé: <span>{{ selectedTicket?.booking_code }}</span>
        </p>
        <div class="modal-meta-box">
          <p>
            Phòng: {{ selectedTicket?.room_name }} | Ghế:
            {{ selectedTicket?.seats.join(", ") }}
          </p>
          <p>
            Suất: {{ selectedTicket?.start_time }} - Ngày:
            {{ formatDate(selectedTicket?.date) }}
          </p>
        </div>
      </div>
    </div>
    
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../api/axios";

const activeTab = ref("info");
const subTab = ref("upcoming");
const isEditingInfo = ref(false);
const btnLoading = ref(false);
const loadingHistory = ref(false);
const isQrModalOpen = ref(false);
const selectedTicket = ref(null);

const defaultAvatar =
  "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";

const profileForm = ref({
  id: "",
  name: "Phạm Đức Thắng",
  phone: "0966618450",
  email: "duclaconbo2001@gmail.com",
  birthday: "",
  avatar_url: "",
});
const passwordForm = ref({
  old_password: "",
  new_password: "",
  confirm_password: "",
});
const bookingHistory = ref([]);

const filteredTickets = computed(() => {
  const todayStr = new Date().toISOString().split("T")[0];
  return bookingHistory.value.filter((ticket) => {
    if (subTab.value === "upcoming") {
      return ticket.date >= todayStr && ticket.status !== "cancelled";
    } else {
      return ticket.date < todayStr || ticket.status === "cancelled";
    }
  });
});

const fetchUserData = async () => {
  try {
    const response = await api.get("/me");
    const data = response.data?.data || response.data;

    if (data) {
      profileForm.value = {
        id: data.id || "",
        name: data.name || "",
        phone: data.phone || "",
        email: data.email || "",
        birthday: data.birthday || "",
        avatar_url: data.avatar_url || defaultAvatar,
      };
    }
  } catch (err) {
    console.error("Lỗi lấy profile từ DB:", err);
  }
};

const fetchBookingHistory = async () => {
  loadingHistory.value = true;
  try {
    const response = await api.get("/bookings");
    bookingHistory.value = response.data?.data || response.data || [];
  } catch (err) {
    console.error("Lỗi lấy lịch sử vé:", err);
  } finally {
    loadingHistory.value = false;
  }
};

const updateProfile = async () => {
  btnLoading.value = true;
  try {
    await api.put(`/admin/users/${profileForm.value.id}`, {
      name: profileForm.value.name,
      phone: profileForm.value.phone,
      birthday: profileForm.value.birthday,
    });
    alert("Cập nhật thông tin thành công!");
    isEditingInfo.value = false;
  } catch (err) {
    alert("Lỗi cập nhật dữ liệu!");
  } finally {
    btnLoading.value = false;
  }
};

const handleAvatarUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append("avatar", file);
  try {
    const response = await api.post("/user/avatar", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    profileForm.value.avatar_url = response.data.avatar_url;
    alert("Thay đổi ảnh đại diện thành công!");
  } catch (err) {
    console.error(err);
  }
};

const changePassword = async () => {
  if (passwordForm.value.new_password.length < 8) {
    return alert("Mật khẩu mới phải từ 8 ký tự!");
  }
  if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
    return alert("Mật khẩu nhập lại không khớp!");
  }
  btnLoading.value = true;
  try {
    await api.post("/user/change-password", {
      old_password: passwordForm.value.old_password,
      new_password: passwordForm.value.new_password,
    });
    alert("Đổi mật khẩu thành công!");
    passwordForm.value = {
      old_password: "",
      new_password: "",
      confirm_password: "",
    };
  } catch (err) {
    alert(err.response?.data?.message || "Mật khẩu cũ sai!");
  } finally {
    btnLoading.value = false;
  }
};

const viewQrCode = (ticket) => {
  selectedTicket.value = ticket;
  isQrModalOpen.value = true;
};

const formatDate = (dateStr) => {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return d.toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

const formatPrice = (price) => (price ? price.toLocaleString("vi-VN") : "0");

onMounted(() => {
  fetchUserData();
  fetchBookingHistory();
});
</script>

<style scoped>
.cinego-profile-container {
  max-width: 1100px;
  margin: 20px auto;
  font-family: Arial, sans-serif;
  color: #333;
  background-color: #fdfcf0;
  padding: 15px;
}

.cinego-main-header {
  background-color: #222;
  color: #fff;
  padding: 8px 20px;
  text-align: center;
  font-size: 18px;
  letter-spacing: 1px;
}
.cinego-main-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: normal;
}

.cinego-profile-body {
  display: flex;
  margin-top: 20px;
  gap: 25px;
}

/* SIDEBAR MENU MŨI TÊN */
.cinego-sidebar {
  width: 220px;
  flex-shrink: 0;
}
.sidebar-title {
  color: #e71a0f;
  font-size: 22px;
  margin-top: 0;
  margin-bottom: 15px;
  font-weight: bold;
}
.cinego-menu {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.cinego-menu-btn {
  background-color: #bcbdbe;
  color: #444;
  border: none;
  text-align: left;
  padding: 10px 15px;
  font-weight: bold;
  font-size: 13px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s ease;
}
.cinego-menu-btn::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  border-left: 10px solid #fdfcf0;
  border-top: 18px solid transparent;
  border-bottom: 18px solid transparent;
}
.cinego-menu-btn.active {
  background-color: #e71a0f;
  color: #fff;
}

/* KHU VỰC THÔNG TIN TỔNG QUAN THÀNH VIÊN */
.cinego-content-area {
  flex-grow: 1;
}

.cinego-member-summary-box {
  display: flex;
  background-color: #f4f4ec;
  border: 1px solid #e0dfd5;
  border-radius: 4px;
  padding: 15px;
  gap: 20px;
  margin-bottom: 30px;
}

.avatar-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.avatar-frame {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  border: 2px solid #ccc;
  overflow: hidden;
  background-color: #fff;
}
.avatar-frame img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.btn-cinego-small {
  background-color: #8f8f8f;
  color: #fff;
  border: none;
  padding: 4px 12px;
  font-size: 12px;
  cursor: pointer;
  border-radius: 3px;
  text-decoration: none;
}
.btn-cinego-small:hover {
  background-color: #666;
}

.summary-details {
  flex-grow: 1;
}
.welcome-text {
  font-size: 16px;
  margin: 0 0 4px 0;
}
.welcome-sub {
  font-size: 12px;
  color: #666;
  margin: 0 0 15px 0;
}

.member-stats-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr 1fr;
  border: 1px solid #dcdbd0;
  background-color: #fff;
  border-radius: 5px;
  overflow: hidden;
}
.stat-col {
  padding: 10px;
  text-align: center;
  border-right: 1px solid #dcdbd0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.stat-col:last-child {
  border-right: none;
}
.rank-col {
  align-items: flex-start;
  text-align: left;
  padding-left: 15px;
}

.stat-label {
  font-size: 12px;
  color: #555;
  margin: 0 0 4px 0;
}
.rank-badge-text {
  font-weight: bold;
  color: #23527c;
  font-size: 14px;
  margin-bottom: 5px;
}
.stat-sub {
  font-size: 11px;
  margin: 2px 0;
}
.txt-red {
  color: #e71a0f;
  font-weight: bold;
}
.stat-value {
  font-size: 15px;
  font-weight: bold;
  margin: 0 0 6px 0;
}

.btn-stat-view {
  background-color: #337ab7;
  color: #fff;
  border: none;
  padding: 2px 14px;
  font-size: 11px;
  border-radius: 3px;
  cursor: pointer;
}
.btn-stat-view:hover {
  background-color: #286090;
}

/* CHỈNH SỬA DETAIL THÔNG TIN TÀI KHOẢN */
.cinego-section-block {
  margin-top: 15px;
}
.cinego-section-title {
  border-bottom: 1px solid #ccc;
  padding-bottom: 5px;
  margin-bottom: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.cinego-section-title h3 {
  font-size: 16px;
  font-weight: bold;
  margin: 0;
  text-transform: uppercase;
}

.cinego-info-form {
  padding: 5px 0;
}
.info-data-row {
  display: flex;
  margin-bottom: 12px;
  font-size: 14px;
  align-items: center;
}
.info-data-row.column-layout {
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
}
.info-label {
  width: 120px;
  font-weight: bold;
  color: #555;
}
.info-text {
  color: #333;
}
.disabled-text {
  color: #888;
  font-style: italic;
}

.cinego-input {
  padding: 6px 10px;
  border: 1px solid #ccc;
  border-radius: 3px;
  font-size: 13px;
  width: 250px;
}
.cinego-input.wide {
  width: 100%;
  max-width: 400px;
}

.btn-cinego-submit {
  background-color: #444;
  color: #fff;
  border: none;
  padding: 8px 20px;
  font-size: 13px;
  font-weight: bold;
  cursor: pointer;
  margin-top: 10px;
  border-radius: 3px;
}
.btn-cinego-submit.red-btn {
  background-color: #e71a0f;
}
.btn-cinego-submit:hover {
  opacity: 0.9;
}

/* CSS TABLE PHẦN LỊCH SỬ GIAO DỊCH */
.history-filter-toggle {
  font-size: 13px;
  color: #999;
}
.history-filter-toggle button {
  background: none;
  border: none;
  color: #337ab7;
  cursor: pointer;
  font-size: 13px;
  padding: 0 5px;
}
.history-filter-toggle button.active {
  color: #e71a0f;
  font-weight: bold;
  text-decoration: underline;
}

.cinego-history-table-wrapper {
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}
.cinego-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}
.cinego-table th {
  background-color: #eee;
  padding: 10px;
  border-bottom: 2px solid #ddd;
  font-weight: bold;
}
.cinego-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #eee;
  vertical-align: middle;
}
.bold-text {
  font-weight: bold;
}
.empty-msg {
  padding: 30px !important;
  color: #999;
}

.btn-table-action {
  background-color: #e71a0f;
  color: #fff;
  border: none;
  padding: 4px 10px;
  font-size: 12px;
  border-radius: 3px;
  cursor: pointer;
}

/* CSS MODAL HIỂN THỊ MÃ QR */
.cinego-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.cinego-modal-content {
  background-color: #fff;
  padding: 25px;
  border-radius: 6px;
  width: 340px;
  text-align: center;
  position: relative;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}
.close-modal-x {
  position: absolute;
  right: 12px;
  top: 10px;
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
}
.modal-movie-title {
  font-weight: bold;
  color: #e71a0f;
  font-size: 15px;
  margin: 8px 0;
}
.qr-img-wrapper {
  background: #fff;
  padding: 10px;
  border: 1px solid #eee;
  display: inline-block;
  margin: 10px 0;
}
.modal-code {
  font-size: 13px;
}
.modal-code span {
  font-weight: bold;
  color: #337ab7;
  font-size: 15px;
}
.modal-meta-box {
  background: #f9f9f9;
  padding: 10px;
  font-size: 12px;
  border-radius: 4px;
  margin-top: 10px;
  text-align: left;
  line-height: 1.4;
}
</style>
