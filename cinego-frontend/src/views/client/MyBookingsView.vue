<template>
  <div class="my-bookings">
    <h2>Lịch sử đơn hàng của tôi</h2>

    <div class="filters">
      <div class="filter-item">
        <label>Từ ngày</label>
        <input type="date" v-model="fromDate" />
      </div>
      <div class="filter-item">
        <label>Đến ngày</label>
        <input type="date" v-model="toDate" />
      </div>
      <div class="filter-item">
        <label>Loại khách</label>
        <select v-model="customerType">
          <option value="">Tất cả</option>
          <option value="member">Member</option>
          <option value="guest">Guest</option>
        </select>
      </div>
      <div class="filter-item">
        <label>Trạng thái đơn</label>
        <select v-model="orderStatus">
          <option value="">Tất cả</option>
          <option value="pending">Chờ thanh toán</option>
          <option value="paid">Đã thanh toán</option>
          <option value="cancelled">Đã hủy</option>
          <option value="refunded">Đã hoàn tiền</option>
        </select>
      </div>
      <div class="filter-item search">
        <label>Tìm kiếm</label>
        <input type="text" placeholder="Mã đơn, tên, email, phim..." v-model="search" @keyup.enter="fetchBookings" />
      </div>
      <div class="filter-actions">
        <button @click="fetchBookings">Áp dụng</button>
        <button @click="resetFilters">Đặt lại</button>
        <button @click="exportCsv">Xuất CSV</button>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải...</div>

    <table v-if="!loading" class="bookings-table">
      <thead>
        <tr>
          <th>Mã</th>
          <th>Khách</th>
          <th>Phim</th>
          <th>Suất</th>
          <th>Tổng</th>
          <th>Thanh toán</th>
          <th>Trạng thái đơn</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="b in bookings" :key="b.id">
          <td>{{ b.booking_code }}</td>
          <td>{{ b.customer_name }}</td>
          <td>{{ b.movie_title }}</td>
          <td>{{ b.showtime_at }}</td>
          <td>{{ formatCurrency(b.total_amount) }}</td>
          <td>{{ b.payment_method }}</td>
          <td>{{ statusLabel(b.order_status) }}</td>
          <td>
            <button @click="viewDetail(b.id)">Xem</button>
            <button v-if="isAdmin" @click="reprint(b.id)">In lại</button>
            <button v-if="isAdmin" @click="refund(b.id)">Hoàn tiền</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal detail -->
    <div v-if="showDetail" class="detail-modal" @click.self="closeDetail">
      <div class="detail-card">
        <button class="close" @click="closeDetail">✕</button>
        <div v-if="detailLoading">Đang tải chi tiết...</div>
        <div v-else-if="detail">
          <h3>Đơn {{ detail.booking_code }}</h3>
          <p>Khách: {{ detail.customer.name }} • {{ detail.customer.phone }}</p>
          <p>Phim: {{ detail.movie.title }} • {{ detail.showtime_at }}</p>
          <p>Tổng: {{ formatCurrency(detail.total_amount) }}</p>
          <TicketPrintable :booking="detail" />
        </div>
      </div>
    </div>

    <!-- Refund Reason Modal -->
    <div v-if="showRefundModal" class="detail-modal" @click.self="closeRefundModal">
      <div class="detail-card">
        <button class="close" @click="closeRefundModal">✕</button>
        <div>
          <h3>Yêu cầu hoàn tiền</h3>
          <p>Vui lòng nhập lý do hoàn tiền cho đơn này:</p>
          <textarea v-model="refundReason" rows="4" style="width:100%"></textarea>
          <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
            <button class="btn-ghost" @click="closeRefundModal">Hủy</button>
            <button class="btn-primary" :disabled="refundLoading || !refundReason.trim()" @click="sendRefund(refundTargetId)">
              {{ refundLoading ? 'Đang gửi…' : 'Gửi yêu cầu' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/axios';
import TicketPrintable from '../../components/TicketPrintable.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const isAdmin = auth.isAdmin;

const fromDate = ref('');
const toDate = ref('');
const customerType = ref('');
const orderStatus = ref('');
const search = ref('');
const bookings = ref([]);
const loading = ref(false);

const showDetail = ref(false);
const detail = ref(null);
const detailLoading = ref(false);

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);

const fetchBookings = async () => {
  loading.value = true;
  try {
    // If admin view, call admin orders endpoint with filters
    const params = {};
    if (fromDate.value) params.from_date = fromDate.value;
    if (toDate.value) params.to_date = toDate.value;
    if (customerType.value) params.customer_type = customerType.value;
    if (orderStatus.value) params.order_status = orderStatus.value;
    if (search.value) params.search = search.value;

    const url = isAdmin ? '/admin/orders' : '/bookings/history';
    const res = await api.get(url, { params });
    bookings.value = res.data.data || res.data || [];
  } catch (err) {
    console.error('Fetch bookings error', err);
    bookings.value = [];
    alert('Không lấy được danh sách đơn.');
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  fromDate.value = '';
  toDate.value = '';
  customerType.value = '';
  orderStatus.value = '';
  search.value = '';
  fetchBookings();
};

const viewDetail = async (id) => {
  showDetail.value = true;
  detailLoading.value = true;
  try {
    const url = isAdmin ? `/admin/orders/${id}` : `/staff/bookings/${id}`;
    const res = await api.get(url);
    detail.value = res.data.data || res.data;
  } catch (err) {
    console.error('Detail error', err);
    alert('Không tải được chi tiết.');
    showDetail.value = false;
  } finally {
    detailLoading.value = false;
  }
};

const closeDetail = () => {
  showDetail.value = false;
  detail.value = null;
};

const exportCsv = () => {
  // Build CSV from currently loaded bookings (client-side)
  if (!bookings.value || bookings.value.length === 0) {
    alert('Không có đơn để xuất');
    return;
  }

  const headers = ['Mã đơn', 'Khách', 'Email', 'SĐT', 'Phim', 'Suất', 'Tổng', 'Thanh toán', 'Trạng thái đơn', 'Ngày tạo'];
  const rows = bookings.value.map(b => [
    b.booking_code,
    b.customer_name,
    b.customer_email || '',
    b.customer_phone || '',
    b.movie_title || '',
    b.created_at_full || b.created_at || '',
    b.total_amount,
    b.payment_method || '',
    statusLabel(b.order_status),
    b.created_at_full || b.created_at || '',
  ]);

  const csvContent = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.setAttribute('download', `bookings_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};

const statusLabel = (status) => {
  return {
    pending: 'Chờ thanh toán',
    paid: 'Đã thanh toán',
    cancelled: 'Đã hủy',
    refunded: 'Đã hoàn tiền',
  }[status] || status;
};

const reprint = async (id) => {
  // Simply open printable ticket modal by fetching details and showing TicketPrintable
  await viewDetail(id);
};
const showRefundModal = ref(false);
const refundTargetId = ref(null);
const refundReason = ref('');
const refundLoading = ref(false);

const refund = (id) => {
  if (!confirm('Bạn có chắc muốn bắt đầu quy trình hoàn tiền cho đơn này?')) return;
  refundTargetId.value = id;
  refundReason.value = '';
  showRefundModal.value = true;
};

const closeRefundModal = () => {
  showRefundModal.value = false;
  refundTargetId.value = null;
  refundReason.value = '';
  refundLoading.value = false;
};

const sendRefund = async (id) => {
  if (!id || !refundReason.value.trim()) return;
  refundLoading.value = true;
  try {
    await api.post(`/admin/orders/${id}/refund`, { reason: refundReason.value.trim() });
    alert('Yêu cầu hoàn tiền đã được gửi và đang chờ phê duyệt.');
    closeRefundModal();
    await fetchBookings();
  } catch (err) {
    console.error('Refund error', err);
    alert(err.response?.data?.message || 'Không thể gửi yêu cầu hoàn tiền.');
  } finally {
    refundLoading.value = false;
  }
};

onMounted(() => fetchBookings());
</script>

<style scoped>
.my-bookings { padding: 18px; }
.filters { display:flex; gap:12px; flex-wrap:wrap; align-items:end; margin-bottom:12px; }
.filter-item { display:flex; flex-direction:column; gap:6px; }
.filter-actions { display:flex; gap:8px; align-items:center; }
.bookings-table { width:100%; border-collapse:collapse; }
.bookings-table th, .bookings-table td { padding:10px; border-bottom:1px solid #eee; }
.detail-modal { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; }
.detail-card { background:white; padding:20px; width:90%; max-width:800px; border-radius:8px; position:relative; }
.detail-card .close { position:absolute; right:12px; top:12px; }
</style>
