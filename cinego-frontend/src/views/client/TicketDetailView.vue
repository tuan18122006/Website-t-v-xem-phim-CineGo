<template>
  <div class="ticket-page">
    <div class="ticket-box" v-if="loading">
      Đang tải thông tin vé...
    </div>

    <div class="ticket-box" v-else-if="errorMessage">
      <h2>Không tìm thấy vé</h2>
      <p>{{ errorMessage }}</p>
    </div>

    <div class="ticket-box" v-else-if="ticket">
      <h2>THÔNG TIN VÉ ĐÃ ĐẶT</h2>

      <table class="info-table">
        <tbody>
          <tr>
            <td><b>Mã đặt vé</b></td>
            <td>{{ ticket.booking_code }}</td>
          </tr>
          <tr>
            <td><b>Khách hàng</b></td>
            <td>{{ ticket.customer_name }}</td>
          </tr>
          <tr>
            <td><b>Phim</b></td>
            <td>{{ ticket.movie_title }}</td>
          </tr>
          <tr>
            <td><b>Suất chiếu</b></td>
            <td>{{ ticket.showtime }}</td>
          </tr>
          <tr>
            <td><b>Phòng</b></td>
            <td>{{ ticket.room_name }}</td>
          </tr>
          <tr>
            <td><b>Ngày đặt</b></td>
            <td>{{ ticket.booking_time }}</td>
          </tr>
          <tr>
            <td><b>Trạng thái</b></td>
            <td>{{ ticket.payment_status }}</td>
          </tr>
        </tbody>
      </table>

      <h3>Ghế đã đặt</h3>

      <table class="detail-table">
        <thead>
          <tr>
            <th>Ghế</th>
            <th>Loại ghế</th>
            <th>Giá</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="seat in ticket.seats" :key="seat.seat_code">
            <td>{{ seat.seat_code }}</td>
            <td>{{ formatSeatType(seat.seat_type) }}</td>
            <td>{{ formatCurrency(seat.price) }}</td>
          </tr>
        </tbody>
      </table>

      <template v-if="ticket.combos && ticket.combos.length > 0">
        <h3>Combo đã chọn</h3>

        <table class="detail-table">
          <thead>
            <tr>
              <th>Tên combo</th>
              <th>Số lượng</th>
              <th>Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="combo in ticket.combos" :key="combo.name">
              <td>{{ combo.name }}</td>
              <td>{{ combo.quantity }}</td>
              <td>{{ formatCurrency(combo.subtotal) }}</td>
            </tr>
          </tbody>
        </table>
      </template>

      <table class="info-table total-table">
        <tbody>
          <tr>
            <td><b>Tạm tính</b></td>
            <td>{{ formatCurrency(ticket.subtotal) }}</td>
          </tr>
          <tr v-if="ticket.discount_amount > 0">
            <td><b>Giảm giá</b></td>
            <td>
              -{{ formatCurrency(ticket.discount_amount) }}
              <span v-if="ticket.voucher_code">
                ({{ ticket.voucher_code }})
              </span>
            </td>
          </tr>
          <tr>
            <td><b>Tổng thanh toán</b></td>
            <td><b>{{ formatCurrency(ticket.total_amount) }}</b></td>
          </tr>
        </tbody>
      </table>

      <p class="note">
        Vui lòng xuất trình trang này tại quầy hoặc cửa soát vé.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import api from "../../api/axios";

const route = useRoute();

const ticket = ref(null);
const loading = ref(true);
const errorMessage = ref("");

const formatCurrency = (value) => {
  return Number(value || 0).toLocaleString("vi-VN") + "đ";
};

const formatSeatType = (type) => {
  const map = {
    standard: "Ghế thường",
    vip: "Ghế VIP",
    couple: "Ghế đôi",
  };

  return map[type] || type || "N/A";
};

const fetchTicket = async () => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const bookingCode = route.params.bookingCode;

    const response = await api.get(`/tickets/${bookingCode}`);

    ticket.value = response.data.data;
  } catch (error) {
    console.error("Lỗi tải vé:", error);
    errorMessage.value =
      error.response?.data?.message || "Không thể tải thông tin vé.";
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchTicket();
});
</script>

<style scoped>
.ticket-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30px 12px;
  font-family: Arial, Helvetica, sans-serif;
  color: #000;
}

.ticket-box {
  max-width: 700px;
  margin: 0 auto;
  background: #fff;
  border: 1px solid #000;
  padding: 20px;
}

h2 {
  text-align: center;
  margin: 0 0 20px 0;
}

h3 {
  margin-top: 24px;
  margin-bottom: 8px;
}

.info-table,
.detail-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 8px;
}

.info-table td {
  padding: 8px;
  border-bottom: 1px solid #ddd;
}

.info-table td:first-child {
  width: 180px;
}

.detail-table th,
.detail-table td {
  border: 1px solid #000;
  padding: 8px;
}

.detail-table th {
  background: #eee;
  text-align: left;
}

.total-table {
  margin-top: 20px;
}

.note {
  text-align: center;
  margin-top: 20px;
  font-weight: bold;
}
</style>