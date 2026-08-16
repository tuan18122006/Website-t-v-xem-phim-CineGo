<template>
  <div class="orders-tab-content">
    <div class="glass-panel orders-panel">
      <div class="orders-panel__head">
        <div>
          <h3>Quản lý đơn hàng</h3>
          <p>Xem đơn đã đặt / đang đặt, phân biệt thành viên và khách vãng lai, lọc theo thời gian hoặc phim.</p>
        </div>
        <button class="btn-export" @click="loadOrders"><RefreshCw :size="15" style="vertical-align:-2px" /> Tải lại</button>
      </div>

      <div class="orders-toolbar">
        <input v-model="ordersSearch" type="text" placeholder="Tìm theo mã đơn, tên khách, phim…" />
        <input v-model="ordersFromDate" type="date" />
        <input v-model="ordersToDate" type="date" />
        <select v-model="ordersMovieFilter">
          <option value="">Tất cả phim</option>
          <option v-for="movie in movieOptions" :key="movie" :value="movie">{{ movie }}</option>
        </select>
        <select v-model="ordersCustomerType">
          <option value="">Tất cả khách</option>
          <option value="member">Thành viên</option>
          <option value="guest">Khách vãng lai</option>
        </select>
        <select v-model="ordersStatusFilter">
          <option value="">Tất cả trạng thái</option>
          <option value="pending">Chờ thanh toán</option>
          <option value="waiting_confirmation">Chờ xác nhận QR</option>
          <option value="paid">Đã thanh toán</option>
          <option value="cancelled">Đã hủy</option>
          <option value="refunded">Đã hoàn tiền</option>
        </select>
        <button class="orders-filter-btn" @click="loadOrders">Lọc</button>
      </div>

      <div v-if="ordersLoading" class="lookup-state">
        <div class="lookup-spinner"></div>
        <p>Đang tải đơn hàng…</p>
      </div>

      <div v-else-if="filteredOrders.length === 0" class="lookup-state">
        <span class="lookup-state__art"><Receipt :size="20" /></span>
        <h4>Không có đơn hàng phù hợp</h4>
      </div>

      <div v-else class="orders-table-wrap">
        <table class="report-table lookup-table orders-table">
          <thead>
            <tr>
              <th>Mã đơn</th>
              <th>Khách hàng</th>
              <th>Phim / Suất chiếu</th>
              <th>Tổng tiền</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in filteredOrders" :key="order.id" class="clickable-row lookup-row" @click="viewOrderDetail(order)">
              <td class="cell-code">{{ order.booking_code }}</td>
              <td>
                <div class="cell-customer">
                  <span class="cell-avatar">{{ initials(order.customer_name) }}</span>
                  <div>
                    <span class="cell-name">{{ order.customer_name || 'Khách vãng lai' }}</span>
                    <div class="muted small">{{ order.customer_phone || '—' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="cell-movie">
                  <span class="cell-movie__title">{{ order.movie_title }}</span>
                  <div class="muted small">{{ order.room_name }} • {{ order.showtime_at || '—' }}</div>
                </div>
              </td>
              <td class="cell-total">{{ formatCurrency(order.total_amount) }}</td>
              <td>
                <span class="pay-pill" :class="payClass(order.order_status)">{{ payLabel(order.order_status) }}</span>
              </td>
              <td>
                <button class="btn-ghost" @click.stop="viewOrderDetail(order)">Chi tiết</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <transition name="lk-fade">
      <div v-if="showOrderModal" class="lk-backdrop" @click.self="showOrderModal = false">
        <div class="lk-modal">
          <div class="lk-modal__head">
            <h3><Ticket :size="15" style="vertical-align:-2px" /> Chi tiết đơn {{ selectedOrder?.booking_code }}</h3>
            <button class="lk-modal__close" @click="showOrderModal = false">✕</button>
          </div>

          <div v-if="orderDetailLoading" class="lookup-state">
            <div class="lookup-spinner"></div>
            <p>Đang tải chi tiết đơn hàng…</p>
          </div>

          <div v-else-if="selectedOrderDetail">
            <section class="lk-section">
              <h4 class="lk-section__title"><User :size="15" style="vertical-align:-2px" /> Khách hàng</h4>
              <div class="lk-kv"><span>Họ tên</span><b>{{ selectedOrderDetail.customer?.name || '—' }}</b></div>
              <div class="lk-kv"><span>Điện thoại</span><b>{{ selectedOrderDetail.customer?.phone || '—' }}</b></div>
              <div class="lk-kv"><span>Email</span><b>{{ selectedOrderDetail.customer?.email || '—' }}</b></div>
            </section>

            <section class="lk-section">
              <h4 class="lk-section__title"><Clapperboard :size="15" style="vertical-align:-2px" /> Suất chiếu</h4>
              <div class="lk-movie">
                <img v-if="selectedOrderDetail.movie?.poster_url" :src="selectedOrderDetail.movie.poster_url" :alt="selectedOrderDetail.movie.title" />
                <div>
                  <strong>{{ selectedOrderDetail.movie?.title }}</strong>
                  <p class="muted"><Building2 :size="15" style="vertical-align:-2px" /> {{ selectedOrderDetail.room_name }} • <Clock :size="15" style="vertical-align:-2px" /> {{ selectedOrderDetail.showtime_at || '—' }}</p>
                </div>
              </div>
            </section>

            <section v-if="selectedOrderDetail.check_in_count" class="lk-section">
              <h4 class="lk-section__title"><Ticket :size="15" style="vertical-align:-2px" /> Lịch sử check-in — {{ selectedOrderDetail.check_in_count }} lần</h4>
              <ul class="om-ci-list">
                <li v-for="(c, i) in selectedOrderDetail.check_ins" :key="i">
                  <b>Lần {{ i + 1 }}:</b> {{ c.checked_in_at }}<span v-if="c.reason"> — lí do: {{ c.reason }}</span>
                </li>
              </ul>
            </section>

            <section class="lk-section">
              <h4 class="lk-section__title"><Ticket :size="15" style="vertical-align:-2px" /> Chi tiết vé</h4>
              <TicketPrintable :booking="selectedOrderDetail" :show-print="false" />
            </section>

            <section class="lk-section">
              <h4 class="lk-section__title"><Settings :size="15" style="vertical-align:-2px" /> Trạng thái đơn hàng</h4>
              <div class="lk-kv">
                <span>Trạng thái hiện tại</span>
                <strong>{{ payLabel(selectedOrderDetail.payment_status) }}</strong>
              </div>
              <div class="lk-kv">
                <span>Phương thức thanh toán</span>
                <strong>{{ methodLabel(selectedOrderDetail.payment_method) }}</strong>
              </div>

              <div v-if="selectedOrderDetail.payment_method === 'bank_transfer' && (selectedOrderDetail.payment_status === 'waiting_confirmation' || selectedOrderDetail.payment_status === 'pending')" style="margin-top: 15px;">
                <div v-if="selectedOrderDetail.payment_status === 'waiting_confirmation'" style="background: #fef9c3; border: 1px solid #fde047; padding: 10px 14px; border-radius: 8px; font-size: 13px; color: #854d0e; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                  <Hourglass :size="15" style="vertical-align:-2px" /> Khách hàng đã báo cáo chuyển khoản. Vui lòng kiểm tra và xác nhận!
                </div>
                <button class="btn-primary" style="width: 100%; background: #10b981; border: none;" :disabled="orderStatusUpdating" @click="confirmQRPayment">
                  {{ orderStatusUpdating ? 'Đang xử lý…' : 'Xác nhận đơn hàng đã thanh toán' }}
                </button>
              </div>
            </section>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';
import { RefreshCw, Receipt, Ticket, User, Clapperboard, Building2, Clock, Settings, Hourglass } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import TicketPrintable from '../../components/TicketPrintable.vue';

const orders = ref([]);
const ordersLoading = ref(false);
const ordersSearch = ref('');
const ordersFromDate = ref('');
const ordersToDate = ref('');
const ordersMovieFilter = ref('');
const ordersCustomerType = ref('');
const ordersStatusFilter = ref('');
const showOrderModal = ref(false);
const orderDetailLoading = ref(false);
const orderStatusUpdating = ref(false);
const selectedOrder = ref(null);
const selectedOrderDetail = ref(null);
const selectedOrderStatus = ref('');
const movieOptions = ref([]);

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};

const initials = (name) => {
  if (!name) return '?';
  const parts = name.split(' ');
  return (parts[0][0] + (parts[parts.length - 1][0] || '')).toUpperCase();
};

const payLabel = (status) => {
  return {
    pending: 'Chờ xử lý',
    waiting_confirmation: 'Chờ xác nhận QR',
    paid: 'Đã thanh toán',
    cancelled: 'Đã hủy',
    refunded: 'Đã hoàn tiền',
  }[status] || status;
};

const methodLabel = (method) => {
  return {
    vnpay: 'VNPay',
    bank_transfer: 'Chuyển khoản (QR)',
    cash: 'Tiền mặt',
  }[method] || method;
};

const payClass = (status) => {
  return {
    paid: 'is-paid',
    pending: 'is-pending',
    waiting_confirmation: 'is-waiting',
    cancelled: 'is-failed',
    refunded: 'is-refunded',
  }[status] || '';
};

const loadOrders = async () => {
  ordersLoading.value = true;
  try {
    const res = await api.get('/admin/orders', {
      params: {
        search: ordersSearch.value,
        from_date: ordersFromDate.value,
        to_date: ordersToDate.value,
        movie: ordersMovieFilter.value,
        customer_type: ordersCustomerType.value,
        order_status: ordersStatusFilter.value,
      },
    });
    const rawData = res.data?.data?.data || res.data?.data || [];
    orders.value = rawData.map(order => ({
      ...order,
      customer_name: order.user?.name,
      customer_phone: order.user?.phone,
      customer_email: order.user?.email,
      movie_title: order.showtime?.movie?.title,
      room_name: order.showtime?.room?.name,
      showtime_at: order.showtime?.start_time,
      order_status: order.payment_status,
      customer_type: order.user_id ? 'member' : 'guest',
      created_at_full: order.created_at
    }));
    movieOptions.value = [...new Set(orders.value.map((o) => o.movie_title).filter(Boolean))];
  } catch (err) {
    console.error('Load orders error:', err);
    orders.value = [];
  } finally {
    ordersLoading.value = false;
  }
};

const filteredOrders = computed(() => {
  const search = ordersSearch.value.trim().toLowerCase();
  return orders.value.filter((order) => {
    const matchesSearch = !search || [order.booking_code, order.customer_name, order.customer_email, order.customer_phone, order.movie_title].some((value) => String(value || '').toLowerCase().includes(search));
    const matchesMovie = !ordersMovieFilter.value || order.movie_title === ordersMovieFilter.value;
    const matchesType = !ordersCustomerType.value || order.customer_type === ordersCustomerType.value;
    const matchesStatus = !ordersStatusFilter.value || order.order_status === ordersStatusFilter.value;
    const createdAt = order.created_at_full || '';
    const fromOk = !ordersFromDate.value || createdAt >= ordersFromDate.value;
    const toOk = !ordersToDate.value || createdAt <= `${ordersToDate.value} 23:59:59`;
    return matchesSearch && matchesMovie && matchesType && matchesStatus && fromOk && toOk;
  });
});

const viewOrderDetail = async (order) => {
  showOrderModal.value = true;
  orderDetailLoading.value = true;
  selectedOrder.value = order;
  selectedOrderDetail.value = null;
  selectedOrderStatus.value = order.order_status || 'pending';
  try {
    const res = await api.get(`/admin/orders/${order.id}`);
    selectedOrderDetail.value = res.data;
    selectedOrderStatus.value = res.data.order_status || 'pending';
  } catch (err) {
    console.error('Load order detail error:', err);
  } finally {
    orderDetailLoading.value = false;
  }
};

const confirmQRPayment = async () => {
  const currentStatus = selectedOrderDetail.value?.payment_status;
  if (!selectedOrder.value || !['pending', 'waiting_confirmation'].includes(currentStatus)) {
    return;
  }
  
  const result = await Swal.fire({
    title: 'Xác nhận đơn hàng?',
    text: 'Bạn có chắc chắn đã nhận được tiền chuyển khoản của đơn hàng này và muốn xuất vé?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Đã nhận tiền & Xuất vé',
    cancelButtonText: 'Hủy bỏ'
  });

  if (!result.isConfirmed) {
    return;
  }

  orderStatusUpdating.value = true;
  try {
    await api.patch(`/admin/orders/${selectedOrder.value.id}/status`, {
      status: 'paid',
    });
    await loadOrders();
    if (selectedOrderDetail.value) {
      selectedOrderDetail.value.payment_status = 'paid';
    }
    Swal.fire({
      icon: 'success',
      title: 'Thành công',
      text: 'Xác nhận thanh toán và xuất vé thành công!',
      timer: 1500,
      showConfirmButton: false
    });
  } catch (err) {
    console.error('Update order status error:', err);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi cập nhật',
      text: err.response?.data?.message || 'Không thể cập nhật trạng thái đơn hàng.',
    });
  } finally {
    orderStatusUpdating.value = false;
  }
};

onMounted(() => {
  loadOrders();
});
</script>

<style scoped>
/* ORDERS TAB STYLES */
.orders-tab-content { display: flex; flex-direction: column; gap: 18px; }
.orders-panel { padding: 20px; display: flex; flex-direction: column; gap: 16px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
.orders-panel__head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.orders-panel__head h3 { margin: 0 0 4px; font-size: 20px; color: #1e293b; }
.orders-panel__head p { margin: 0; color: var(--text-secondary); font-size: 13px; }
.orders-toolbar { display: grid; grid-template-columns: repeat(5, 1fr) auto; gap: 10px; align-items: center; }
.orders-toolbar input, .orders-toolbar select { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; }
.orders-toolbar input:focus, .orders-toolbar select:focus { outline: none; border-color: var(--accent-pink); box-shadow: 0 0 0 3px rgba(216, 45, 139, 0.12); }
.orders-filter-btn { border: none; background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); color: white; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; }
/* TABLE ROW STYLING LIKE BOOKING LOOKUP */
.lookup-table { width: 100%; border-collapse: collapse; }
.lookup-table th {
  text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 700;
  text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.lookup-row { cursor: pointer; transition: background 0.15s; }
.lookup-row:hover { background: #fdf3f8; }
.lookup-table td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid rgba(0, 0, 0, 0.04); vertical-align: middle; }

.cell-code { font-weight: 800; color: var(--accent-pink); font-family: 'Courier New', monospace; }
.cell-customer { display: flex; align-items: center; gap: 8px; }
.cell-avatar {
  width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
  display: grid; place-items: center; font-size: 11px; font-weight: 800; color: #fff;
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
}
.cell-name { font-weight: 700; color: #1e293b; }
.cell-contact { display: flex; flex-direction: column; gap: 2px; font-size: 12.5px; }
.cell-movie { display: flex; flex-direction: column; gap: 2px; }
.cell-movie__title { font-weight: 700; color: #1e293b; }
.cell-total { font-weight: 800; color: #1e293b; }

.pay-pill { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; display: inline-block; }
.pay-pill.is-paid { background: #edfcf5; color: var(--accent-mint); }
.pay-pill.is-pending { background: #fffaf0; color: #dd6b20; }
.pay-pill.is-waiting { background: #fef9c3; color: #854d0e; }
.pay-pill.is-failed { background: #fee2e2; color: #dc2626; }
.pay-pill.is-refunded { background: #f1f5f9; color: #475569; }

.orders-table-wrap { overflow-x: auto; }
.small { font-size: 12px; }

/* Lookup/modal styles */
.lookup-state { text-align: center; padding: 40px; color: #64748b; }
.lookup-spinner { width: 40px; height: 40px; border: 3px solid #f3f4f6; border-top-color: var(--accent-pink); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px; }
@keyframes spin { to { transform: rotate(360deg); } }
.lookup-state__art { font-size: 48px; display: block; margin-bottom: 16px; }

.btn-ghost { background: transparent; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; cursor: pointer; color: #475569; font-weight: 600; transition: all 0.2s; }
.btn-ghost:hover { border-color: var(--accent-pink); color: var(--accent-pink); }
.muted { color: #94a3b8; }

/* Modal styles */
.lk-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.lk-modal { background: #fff; border-radius: 16px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
.lk-modal__head { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px; }
.lk-modal__head h3 { margin: 0; font-size: 20px; color: #0f172a; }
.lk-modal__close { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; }
.lk-section { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px dashed #e2e8f0; }
.lk-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.lk-section__title { font-size: 15px; color: #475569; margin: 0 0 12px; }
.lk-kv { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 14px; }
.lk-kv span { color: #64748b; }
.lk-movie { display: flex; gap: 16px; align-items: center; }
.lk-movie img { width: 60px; height: 85px; object-fit: cover; border-radius: 8px; }
.status-select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; width: 150px; }
.btn-primary { width: 100%; padding: 12px; border: none; border-radius: 8px; background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); color: white; font-weight: 700; cursor: pointer; margin-top: 16px; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-export { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-export:hover { background: #f1f5f9; }

.lk-fade-enter-active, .lk-fade-leave-active { transition: opacity 0.3s ease; }
.lk-fade-enter-from, .lk-fade-leave-to { opacity: 0; }
.om-ci-list { margin: 0; padding-left: 18px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 12px 14px 12px 32px; }
.om-ci-list li { font-size: 12.5px; color: #475569; line-height: 1.7; }
</style>
