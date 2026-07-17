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
                  <tr v-for="ticket in paginatedTickets" :key="ticket.id">
                    <td class="bold-text">{{ ticket.movie_title }}</td>
                    <td>
                      {{ ticket.start_time }} <br />
                      {{ formatDate(ticket.date) }} <br />
                      <strong>{{ ticket.room_name }}</strong>
                    </td>
                    <td class="txt-red bold-text">
                      {{
                        ticket.seats
                          ? ticket.seats
                              .map((seat) =>
                                typeof seat === "object"
                                  ? `${seat.row}${seat.number}`
                                  : seat,
                              )
                              .join(", ")
                          : ""
                      }}
                    </td>
                    <td class="bold-text">
                      {{ formatPrice(ticket.total_price) }}đ
                    </td>
                    <td>
                      <div class="table-actions">
                        <button
                          v-if="subTab === 'upcoming'"
                          @click="viewQrCode(ticket)"
                          class="btn-table-action"
                        >
                          Mã QR
                        </button>
                        <span v-else class="badge badge-success">
                          Đã chiếu
                        </span>
                        <button
                          @click="viewDetails(ticket)"
                          class="btn-table-action"
                        >
                          Chi Tiết
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredTickets.length === 0">
                    <td colspan="5" class="text-center empty-msg">
                      Không có dữ liệu giao dịch đặt vé nào.
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination Controls -->
              <div
                class="pagination-wrapper"
                style="
                  display: flex;
                  justify-content: center;
                  gap: 10px;
                  margin-top: 20px;
                "
              >
                <button
                  class="btn-pagination"
                  :disabled="historyPage === 1"
                  @click="historyPage--"
                >
                  Trước
                </button>
                <span
                  style="font-size: 14px; font-weight: bold; align-self: center"
                >
                  Trang {{ historyPage }} / {{ totalPages }}
                </span>
                <button
                  class="btn-pagination"
                  :disabled="historyPage === totalPages"
                  @click="historyPage++"
                >
                  Sau
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <div
      v-if="isQrModalOpen"
      class="modal-overlay"
      @click.self="isQrModalOpen = false"
    >
      <div
        class="modal-content"
        style="padding: 24px; text-align: center; position: relative"
      >
        <button
          class="btn-close"
          @click="isQrModalOpen = false"
          style="
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e2e8f0;
            color: #1e293b;
          "
        >
          ✕
        </button>
        <h3
          style="
            font-weight: 800;
            color: var(--accent-red);
            margin-bottom: 15px;
          "
        >
          MÃ VÉ CINEGO
        </h3>
        <p class="modal-movie-title" style="font-weight: bold; font-size: 16px">
          {{ selectedTicket?.movie_title }}
        </p>
        <div
          class="qr-img-wrapper"
          style="
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: inline-block;
            margin: 15px 0;
          "
        >
          <img
            :src="getQrUrl(selectedTicket?.booking_code)"
            alt="QR Code"
          />
        </div>
        <p class="modal-code" style="font-size: 14px; margin-bottom: 10px">
          Mã đặt vé:
          <span
            style="font-weight: 800; font-size: 18px; color: var(--accent-red)"
            >{{ selectedTicket?.booking_code }}</span
          >
        </p>
        <div
          class="modal-meta-box"
          style="
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            color: #475569;
          "
        >
          <p style="margin: 0 0 5px 0">
            Phòng: <strong>{{ selectedTicket?.room_name }}</strong> | Ghế:
            <strong>{{ selectedTicket?.seats.join(", ") }}</strong>
          </p>
          <p style="margin: 0">
            Suất: <strong>{{ selectedTicket?.start_time }}</strong> - Ngày:
            <strong>{{ formatDate(selectedTicket?.date) }}</strong>
          </p>
        </div>
      </div>
    </div>



    <!-- Modal Chi Tiết Đơn Hàng -->
    <div
      v-if="isDetailModalOpen"
      class="modal-overlay"
      @click.self="isDetailModalOpen = false"
    >
      <div
        class="modal-content detail-modal-wrapper hide-scrollbar"
        style="
          max-width: 650px;
          width: 90%;
          text-align: left;
          padding: 0;
          border-radius: 12px;
          background: white;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);

          max-height: 85vh;
          overflow-y: auto;
          display: flex;
          flex-direction: column;

          scrollbar-width: none;
          -ms-overflow-style: none;
        "
      >
        <div
          style="
            background: linear-gradient(135deg, var(--accent-red), #990000);
            padding: 20px;
            position: relative;
            color: white;
            text-align: center;
            flex-shrink: 0;
          "
        >
          <button
            class="btn-close"
            @click="isDetailModalOpen = false"
            style="
              color: white;
              background: rgba(0, 0, 0, 0.2);
              border-radius: 50%;
              width: 30px;
              height: 30px;
              display: flex;
              align-items: center;
              justify-content: center;
              position: absolute;
              top: 15px;
              right: 15px;
              font-size: 16px;
              border: none;
              cursor: pointer;
            "
          >
            ✕
          </button>
          <h3
            style="
              margin: 0;
              font-size: 19px; /* Tăng nhẹ font chữ tiêu đề */
              font-weight: 800;
              letter-spacing: 1px;
            "
          >
            CHI TIẾT ĐƠN HÀNG
          </h3>
          <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9">
            Mã đơn: <strong>{{ selectedTicket?.booking_code }}</strong>
          </p>
        </div>

        <div style="padding: 25px; background: white; flex: 1">
          <div style="display: flex; gap: 15px; margin-bottom: 20px">
            <div style="flex: 1">
              <h4
                style="
                  font-size: 19px; /* Tăng kích thước tên phim */
                  color: #1e293b;
                  margin: 0 0 8px 0;
                  font-weight: 800;
                "
              >
                {{ selectedTicket?.movie_title }}
              </h4>
              <p style="margin: 0 0 5px 0; color: #475569; font-size: 14.5px">
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Suất chiếu:</span
                >
                <strong
                  >{{ selectedTicket?.start_time }} -
                  {{ formatDate(selectedTicket?.date) }}</strong
                >
              </p>
              <p style="margin: 0 0 5px 0; color: #475569; font-size: 14.5px">
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Phòng chiếu:</span
                >
                <strong>{{ selectedTicket?.room_name }}</strong>
              </p>
              <p style="margin: 0; color: #475569; font-size: 14.5px">
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Thời gian đặt:</span
                >
                {{ selectedTicket?.created_at }}
              </p>
            </div>
          </div>

          <div
            v-if="
              categorizedSeats.standard.length ||
              categorizedSeats.vip.length ||
              categorizedSeats.couple.length
            "
            style="
              margin-bottom: 20px;
              background: #f8fafc;
              padding: 15px;
              border-radius: 8px;
              border: 1px solid #f1f5f9;
            "
          >
            <h5
              style="
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #1e293b;
                font-weight: 700;
              "
            >
              💺 Ghế Ngồi:
            </h5>
            <ul
              style="
                margin: 0;
                padding: 0;
                font-size: 14.5px;
                color: #475569;
                list-style-type: none;
              "
            >
              <li
                v-if="categorizedSeats.standard.length > 0"
                style="margin-bottom: 6px; display: flex; align-items: center"
              >
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Ghế thường:</span
                >
                <strong style="color: var(--accent-pink); font-weight: 700">{{
                  categorizedSeats.standard.join(", ")
                }}</strong>
              </li>
              <li
                v-if="categorizedSeats.vip.length > 0"
                style="margin-bottom: 6px; display: flex; align-items: center"
              >
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Ghế VIP:</span
                >
                <strong style="color: #f59e0b; font-weight: 700">{{
                  categorizedSeats.vip.join(", ")
                }}</strong>
              </li>
              <li
                v-if="categorizedSeats.couple.length > 0"
                style="margin-bottom: 0; display: flex; align-items: center"
              >
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Ghế đôi:</span
                >
                <strong style="color: #ef4444; font-weight: 700">{{
                  categorizedSeats.couple.join(", ")
                }}</strong>
              </li>
            </ul>
          </div>

          <div
            v-if="selectedTicket?.combos && selectedTicket.combos.length > 0"
            style="
              margin-bottom: 20px;
              background: #f8fafc;
              padding: 15px;
              border-radius: 8px;
              border: 1px solid #f1f5f9;
            "
          >
            <h5
              style="
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #1e293b;
                font-weight: 700;
              "
            >
              🍿 Bắp Nước:
            </h5>
            <ul
              style="
                margin: 0;
                padding-left: 20px;
                font-size: 14.5px;
                color: #475569;
              "
            >
              <li
                v-for="(combo, idx) in selectedTicket.combos"
                :key="idx"
                style="margin-bottom: 4px"
              >
                {{ combo }}
              </li>
            </ul>
          </div>

          <div style="border-top: 2px dashed #e2e8f0; padding-top: 20px">
            <div
              style="
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 14.5px;
                color: #475569;
              "
              v-if="selectedTicket?.total_ticket_price > 0"
            >
              <span>Tổng tiền vé:</span>
              <span style="font-weight: 600"
                >{{ formatPrice(selectedTicket?.total_ticket_price) }}đ</span
              >
            </div>
            <div
              style="
                display: flex;
                justify-content: space-between;
                margin-bottom: 12px;
                font-size: 14.5px;
                color: #475569;
              "
              v-if="selectedTicket?.total_combo_price > 0"
            >
              <span>Tổng tiền bắp nước:</span>
              <span style="font-weight: 600"
                >{{ formatPrice(selectedTicket?.total_combo_price) }}đ</span
              >
            </div>
            <div
              style="
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
                font-size: 14.5px;
                color: #10b981;
              "
              v-if="selectedTicket?.discount_amount > 0"
            >
              <span>Mã giảm giá:</span>
              <span style="font-weight: 600"
                >-{{ formatPrice(selectedTicket?.discount_amount) }}đ</span
              >
            </div>

            <div
              style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #fff1f2;
                padding: 15px;
                border-radius: 8px;
                border: 1px solid #ffe4e6;
              "
            >
              <span style="font-weight: 700; color: #1e293b; font-size: 15px"
                >Tổng thanh toán:</span
              >
              <span
                style="
                  color: var(--accent-pink);
                  font-size: 21px;
                  font-weight: 800;
                "
              >
                {{ formatPrice(selectedTicket?.total_price) }}đ
              </span>
            </div>
          </div>

          <div
            style="
              margin-top: 20px;
              display: flex;
              justify-content: space-between;
              align-items: center;
              font-size: 14px;
              padding-top: 15px;
              border-top: 1px solid #f1f5f9;
            "
          >
            <div>
              <span style="color: #94a3b8">Hình thức:</span>
              <strong
                style="
                  text-transform: uppercase;
                  color: #334155;
                  margin-left: 5px;
                "
                >{{ selectedTicket?.payment_method }}</strong
              >
            </div>
            <div
              :style="{
                padding: '4px 12px',
                borderRadius: '20px',
                fontSize: '12.5px',
                fontWeight: '700',
                backgroundColor:
                  selectedTicket?.status === 'paid' ? '#d1fae5' : '#fee2e2',
                color:
                  selectedTicket?.status === 'paid' ? '#059669' : '#dc2626',
              }"
            >
              {{ selectedTicket?.status_label }}
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import api from "../../api/axios";

const activeTab = ref("info");
const subTab = ref("upcoming");
const isEditingInfo = ref(false);
const btnLoading = ref(false);
const loadingHistory = ref(false);
const isQrModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedTicket = ref(null);

const defaultAvatar =
  "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";

const profileForm = ref({
  id: "",
  name: "",
  phone: "",
  email: "",
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

const historyPage = ref(1);
const historyPerPage = 3;

const totalPages = computed(() => {
  return Math.ceil(filteredTickets.value.length / historyPerPage) || 1;
});

const paginatedTickets = computed(() => {
  const start = (historyPage.value - 1) * historyPerPage;
  return filteredTickets.value.slice(start, start + historyPerPage);
});

// Reset page when sub tab changes
watch(subTab, () => {
  historyPage.value = 1;
});

const statusClass = (status) => {
  switch (status) {
    case 'paid': return 'status-paid';
    case 'pending': return 'status-pending';
    case 'failed': return 'status-failed';
    default: return '';
  }
};

const getQrUrl = (code) => {
  if (!code) return '';
  const url = `${window.location.origin}/staff/dashboard?scan=${encodeURIComponent(code)}`;
  return `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(url)}`;
};

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
    const response = await api.get("/user/bookings");
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
    await api.put(`/user/profile`, {
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

const viewDetails = (ticket) => {
  selectedTicket.value = ticket;
  isDetailModalOpen.value = true;
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

const formatPrice = (price) =>
  price ? Number(price).toLocaleString("vi-VN") : "0";

const categorizedSeats = computed(() => {
  const result = { standard: [], vip: [], couple: [] };

  const ticket = selectedTicket?.value ? selectedTicket.value : selectedTicket;

  if (ticket && Array.isArray(ticket.seats)) {
    ticket.seats.forEach((seat) => {
      if (seat && typeof seat === "object" && seat.row !== undefined) {
        // Tạo nhãn hiển thị như "A1", "F5"
        const seatLabel = `${seat.row}${seat.number}`;
        const type = String(seat.type).toLowerCase().trim();

        if (type === "vip") result.vip.push(seatLabel);
        else if (type === "couple") result.couple.push(seatLabel);
        else result.standard.push(seatLabel);
      }
    });
  }
  return result;
});

onMounted(() => {
  fetchUserData();
  fetchBookingHistory();
});
</script>

<style scoped>
/* ==========================================================================
   CINEGO MODERN PROFILE REDESIGN (WHITE & RED TONE)
   ========================================================================== */

.cinego-profile-container {
  --accent-red: #e71a0f;
  --accent-red-hover: #c4150b;
  --accent-mint: #10b981;
  --bg-light: #f8f9fa;
  --text-dark: #111827;
  --text-muted: #6b7280;
  --card-bg: #ffffff;
  --radius-xl: 16px;
  --radius-lg: 12px;
  --radius-md: 8px;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
  --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 12px 32px rgba(231, 26, 15, 0.12);
  --border-light: #e5e7eb;
}

/* ================== LAYOUT CHUNG ================== */
.cinego-profile-container {
  max-width: 1200px;
  margin: 40px auto 80px;
  font-family: "Inter", "Roboto", sans-serif;
  color: var(--text-dark);
  padding: 0 20px;
  min-height: 70vh;
}

.cinego-main-header {
  margin-bottom: 30px;
  text-align: left;
  border-bottom: 2px solid var(--accent-red);
  padding-bottom: 10px;
  display: inline-block;
}

.cinego-main-header h2 {
  font-size: 26px;
  font-weight: 900;
  color: var(--text-dark);
  text-transform: uppercase;
  margin: 0;
  letter-spacing: 0.5px;
}

.cinego-profile-body {
  display: flex;
  gap: 30px;
  align-items: flex-start;
}

/* ================== SIDEBAR ================== */
.cinego-sidebar {
  width: 260px;
  flex-shrink: 0;
  background: var(--card-bg);
  border-radius: var(--radius-xl);
  padding: 24px 16px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-light);
}

.info-data-row {
  display: flex;
  align-items: center;
  gap: 20px;
}

.info-data-row.column-layout {
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
}

.info-label {
  width: 140px;
  font-size: 14px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-data-row.column-layout .info-label {
  width: auto;
}

.info-text {
  font-size: 16px;
  color: var(--text-dark);
  font-weight: 600;
  background: #f9fafb;
  padding: 10px 16px;
  border-radius: var(--radius-md);
  border: 1px solid #e5e7eb;
  min-width: 250px;
}

.disabled-text {
  color: #9ca3af;
  background: #f3f4f6;
}

.cinego-input {
  flex: 1;
  max-width: 400px;
  padding: 14px 16px;
  border: 2px solid #e5e7eb;
  border-radius: var(--radius-md);
  font-size: 15px;
  color: var(--text-dark);
  font-weight: 500;
  transition: all 0.3s;
  background: #fff;
  outline: none;
}

.cinego-input:focus {
  border-color: var(--accent-red);
  box-shadow: 0 0 0 3px rgba(231, 26, 15, 0.15);
}

.cinego-input.wide {
  width: 100%;
  max-width: 100%;
}

.sidebar-title {
  color: var(--text-dark);
  font-size: 15px;
  font-weight: 800;
  margin: 0 0 20px 8px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.cinego-menu {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cinego-menu-btn {
  background: transparent;
  border: none;
  text-align: left;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  font-size: 14.5px;
  font-weight: 700;
  color: #4b5563; /* Darker grey for better visibility */
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.cinego-menu-btn:hover {
  background: #f3f4f6;
  color: var(--text-dark);
}

.cinego-menu-btn.active {
  background-color: #e71a0f !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(231, 26, 15, 0.3);
}

/* ================== NỘI DUNG CHÍNH ================== */
.cinego-content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 30px;
  min-width: 0;
}

/* BOX THÔNG TIN TỔNG QUAN (HERO CARD) */
.cinego-member-summary-box {
  background: var(--card-bg);
  border-radius: var(--radius-xl);
  padding: 30px;
  display: flex;
  gap: 30px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-light);
  position: relative;
  overflow: hidden;
}

.cinego-member-summary-box::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 6px;
  background: var(--accent-red);
}

.avatar-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  position: relative;
  z-index: 1;
}

.avatar-frame {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  padding: 3px;
  background: var(--accent-red);
  box-shadow: 0 8px 16px rgba(231, 26, 15, 0.2);
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  border: 3px solid #fff;
  background: #fff;
}

.summary-details {
  flex: 1;
  position: relative;
  z-index: 1;
}

.welcome-text {
  font-size: 24px;
  margin: 0 0 5px 0;
  color: var(--text-dark);
}

.welcome-text strong {
  font-weight: 800;
  color: var(--accent-red);
}

.welcome-sub {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0 0 24px 0;
}

/* THỐNG KÊ (GRID) */
.member-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.stat-col {
  background: #f9fafb;
  padding: 16px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-light);
  display: flex;
  flex-direction: column;
  gap: 6px;
  transition: transform 0.2s;
}

.stat-col:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
  border-color: #d1d5db;
}

.stat-label {
  font-size: 11px;
  text-transform: uppercase;
  color: var(--text-muted);
  font-weight: 800;
  letter-spacing: 0.5px;
  margin: 0;
}

.stat-value {
  font-size: 20px;
  font-weight: 900;
  color: var(--text-dark);
  margin: 0;
}

.rank-badge-text {
  display: inline-block;
  background: #111827;
  color: #fff;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  margin: 4px 0;
  width: fit-content;
  letter-spacing: 1px;
}

.stat-sub {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}

.txt-red {
  color: var(--accent-red);
  font-weight: 800;
}

.txt-green {
  color: var(--accent-mint);
  font-weight: 700;
}

/* NÚT BẤM */
.btn-stat-view {
  background: #fff;
  border: 1px solid #d1d5db;
  color: var(--text-dark);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 4px;
  width: fit-content;
}

.btn-stat-view:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.btn-cinego-small {
  background: #fff;
  color: var(--text-dark);
  border: 1px solid #d1d5db;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-block;
}

.btn-cinego-small:hover {
  background: #f9fafb;
  border-color: var(--text-dark);
}

.btn-cinego-submit {
  background: var(--accent-red);
  color: #fff;
  border: none;
  padding: 14px 32px;
  border-radius: 30px;
  font-size: 15px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(231, 26, 15, 0.3);
  margin-top: 20px;
}

.btn-cinego-submit:hover:not(:disabled) {
  background: var(--accent-red-hover);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(231, 26, 15, 0.4);
}

.btn-cinego-submit:disabled {
  background: #d1d5db;
  box-shadow: none;
  cursor: not-allowed;
}

/* KHỐI NỘI DUNG (TAB CONTENT) */
.cinego-tab-dynamic-content {
  background: var(--card-bg);
  border-radius: var(--radius-xl);
  padding: 30px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-light);
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.cinego-section-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f3f4f6;
}

.cinego-section-title h3 {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-dark);
  margin: 0;
  position: relative;
}

.cinego-section-title h3::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -18px;
  width: 40px;
  height: 2px;
  background: var(--accent-red);
}

/* FORM VÀ INPUT SIÊU ĐẸP */
.cinego-info-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-top: 10px;
}

/* ================== BẢNG LỊCH SỬ GIAO DỊCH DẠNG CARD ================== */
.history-filter-toggle {
  display: inline-flex;
  background: #f3f4f6;
  border-radius: 30px;
  padding: 4px;
  gap: 4px;
}

.history-filter-toggle button {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
  padding: 8px 24px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.history-filter-toggle button:hover {
  color: var(--text-dark);
}

.history-filter-toggle button.active {
  background: #fff;
  color: var(--accent-red);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.table-responsive {
  overflow-x: auto;
  padding-bottom: 10px;
}

.cinego-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 12px; /* Tạo khoảng cách giữa các hàng để giống dạng Card */
  min-width: 800px;
}

.cinego-table th {
  padding: 0 20px 8px;
  text-align: left;
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: none;
}

.cinego-table td {
  padding: 18px 20px;
  font-size: 14.5px;
  font-weight: 500;
  color: var(--text-dark);
  background: #fff;
  border-top: 1px solid var(--border-light);
  border-bottom: 1px solid var(--border-light);
  vertical-align: middle;
  transition: all 0.2s;
}

.table-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-start;
  flex-wrap: wrap;
}

/* Bo góc cho thẻ Card của mỗi dòng */
.cinego-table td:first-child {
  border-left: 1px solid var(--border-light);
  border-radius: 12px 0 0 12px;
  font-weight: 700;
  color: var(--text-dark);
}

.btn-pagination {
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #334155;
  padding: 6px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-pagination:hover:not(:disabled) {
  background: var(--text-dark);
  color: #fff;
  border-color: var(--text-dark);
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cinego-table td:last-child {
  border-right: 1px solid var(--border-light);
  border-radius: 0 12px 12px 0;
}

.cinego-table tr:hover td {
  background: #fdf2f2; /* Nền đỏ nhạt khi hover */
  border-color: #fca5a5;
  cursor: pointer;
}

/* Cột Phim & Bold */
.movie-title-cell,
.bold-text {
  font-weight: 800;
  color: var(--text-dark);
}

/* Các trạng thái (Badge) */
.badge {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  display: inline-block;
  text-align: center;
  letter-spacing: 0.3px;
}
.badge-success {
  background: #dcfce7;
  color: #166534;
}
.badge-warning {
  background: #fef9c3;
  color: #854d0e;
}
.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.btn-table-action {
  background: #fff;
  border: 1px solid var(--accent-red);
  color: var(--accent-red);
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  text-transform: uppercase;
}

.btn-table-action:hover {
  background: var(--accent-red);
  color: #fff;
}

/* ================== RESPONSIVE ================== */
@media (max-width: 900px) {
  .cinego-profile-body {
    flex-direction: column;
  }
  .cinego-sidebar {
    width: 100%;
  }
  .member-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .cinego-member-summary-box {
    flex-direction: column;
    text-align: center;
  }
  .member-stats-grid {
    grid-template-columns: 1fr;
  }
  .info-data-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .info-text,
  .cinego-input {
    width: 100%;
    max-width: 100%;
  }
}

/* MODAL CHI TIẾT */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--card-bg);
  width: 100%;
  max-width: 500px;
  border-radius: var(--radius-xl);
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalScale {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal-header {
  background: var(--text-dark);
  color: #fff;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 4px solid var(--accent-red);
}

.modal-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.btn-close {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: #fff;
  font-size: 20px;
  cursor: pointer;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  line-height: 1;
}

.btn-close:hover {
  background: var(--accent-red);
}

.modal-body {
  padding: 24px;
  max-height: 70vh;
  overflow-y: auto;
}

/* NÚT QR CODE */
.btn-qr {
  display: inline-block;
  background: #fff;
  color: var(--text-dark);
  border: 2px solid var(--text-dark);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  margin-right: 8px;
}
.btn-qr:hover {
  background: var(--text-dark);
  color: #fff;
}
</style>
