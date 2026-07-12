<template>
  <div class="lookup">
    <!-- Thanh tìm kiếm -->
    <div class="lookup-search glass-panel">
      <div class="lookup-search__head">
        <h3>🔎 Tra Cứu Đơn Hàng — Hỗ Trợ Khách</h3>
        <p>Khách quên mã vé hoặc mất điện thoại? Tìm nhanh theo <b>số điện thoại</b>, <b>email</b> hoặc <b>mã đơn</b>.</p>
      </div>

      <form class="lookup-search__bar" @submit.prevent="doSearch">
        <span class="lookup-search__icon">🔍</span>
        <input
          v-model="query"
          type="text"
          placeholder="Nhập SĐT, email hoặc mã đơn (VD: 0901234567 / an@gmail.com / CG-582910)…"
          autofocus
        />
        <button v-if="query" type="button" class="lookup-search__clear" @click="clearSearch" aria-label="Xóa">✕</button>
        <button type="submit" class="lookup-search__btn" :disabled="loading">
          {{ loading ? 'Đang tìm…' : 'Tìm kiếm' }}
        </button>
        <button type="button" class="lookup-camera__btn" @click="showCamera = !showCamera" title="Quét mã QR bằng Camera">
          📷 Bật Camera
        </button>
        <label class="lookup-camera__btn" title="Tải ảnh QR lên" style="display: flex; align-items: center; justify-content: center; cursor: pointer; margin: 0;">
          🖼️ Tải Ảnh
          <qrcode-capture @detect="onDetect" style="display: none;"></qrcode-capture>
        </label>
      </form>

      <!-- Camera wrapper -->
      <div v-if="showCamera" class="camera-wrapper">
        <qrcode-stream @detect="onDetect"></qrcode-stream>
        <button type="button" class="camera-close" @click="showCamera = false">Đóng Camera</button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="lookup-state">
      <div class="lookup-spinner"></div>
      <p>Đang tra cứu đơn hàng…</p>
    </div>

    <!-- Chưa tìm -->
    <div v-else-if="!searched" class="lookup-state">
      <span class="lookup-state__art">🎫</span>
      <p>Nhập thông tin khách để bắt đầu tra cứu.</p>
    </div>

    <!-- Không có kết quả -->
    <div v-else-if="results.length === 0" class="lookup-state">
      <span class="lookup-state__art">🕵️</span>
      <h4>Không tìm thấy đơn hàng nào</h4>
      <p>Thử lại với số điện thoại/email khác, hoặc kiểm tra lại chính tả.</p>
    </div>

    <!-- Danh sách kết quả -->
    <div v-else class="lookup-results glass-panel">
      <div class="lookup-results__head">
        <span>Tìm thấy <b>{{ results.length }}</b> đơn</span>
        <span class="lookup-results__hint">Bấm vào một dòng để xem chi tiết</span>
      </div>

      <table class="lookup-table">
        <thead>
          <tr>
            <th>Mã đơn</th>
            <th>Khách hàng</th>
            <th>Liên hệ</th>
            <th>Phim / Suất</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in results" :key="b.id" class="lookup-row" @click="openDetail(b.id)">
            <td class="cell-code">{{ b.booking_code }}</td>
            <td>
              <div class="cell-customer">
                <span class="cell-avatar">{{ initials(b.customer_name) }}</span>
                <span class="cell-name">{{ b.customer_name || 'Khách' }}</span>
              </div>
            </td>
            <td>
              <div class="cell-contact">
                <span>📞 {{ b.customer_phone || '—' }}</span>
                <span class="muted">✉️ {{ b.customer_email || '—' }}</span>
              </div>
            </td>
            <td>
              <div class="cell-movie">
                <span class="cell-movie__title">{{ b.movie_title }}</span>
                <span class="muted">🏛️ {{ b.room_name }} • {{ b.showtime_at || '—' }}</span>
              </div>
            </td>
            <td class="cell-total">{{ formatCurrency(b.total_amount) }}</td>
            <td>
              <span class="pay-pill" :class="payClass(b.payment_status)">{{ payLabel(b.payment_status) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL CHI TIẾT ĐƠN -->
    <transition name="lk-fade">
      <div v-if="showDetail" class="lk-backdrop" @click.self="closeDetail">
        <div class="lk-modal">
          <div class="lk-modal__head">
            <h3>🎟️ Đơn {{ detail?.booking_code }}</h3>
            <button class="lk-modal__close" @click="closeDetail" aria-label="Đóng">✕</button>
          </div>

          <div v-if="detailLoading" class="lookup-state">
            <div class="lookup-spinner"></div>
            <p>Đang tải chi tiết…</p>
          </div>

          <div v-else-if="detail" class="lk-body">
            <!-- Khách hàng -->
            <section class="lk-section">
              <h4 class="lk-section__title">👤 Khách hàng</h4>
              <div class="lk-kv"><span>Họ tên</span><b>{{ detail.customer.name || '—' }}</b></div>
              <div class="lk-kv"><span>Điện thoại</span><b>{{ detail.customer.phone || '—' }}</b></div>
              <div class="lk-kv"><span>Email</span><b>{{ detail.customer.email || '—' }}</b></div>
            </section>

            <!-- Suất chiếu -->
            <section class="lk-section">
              <h4 class="lk-section__title">🎬 Suất chiếu</h4>
              <div class="lk-movie">
                <img v-if="detail.movie.poster_url" :src="detail.movie.poster_url" :alt="detail.movie.title" />
                <div>
                  <strong>{{ detail.movie.title }}</strong>
                  <p class="muted">
                    🏛️ {{ detail.room_name }} • 🕒 {{ detail.showtime_at || '—' }}
                  </p>
                  <p class="muted">{{ detail.format }} • {{ detail.translation }}</p>
                </div>
              </div>
            </section>

            <!-- Vé của khách: vé xem phim (mỗi ghế 1 vé) + vé bắp nước (mỗi combo 1 phiếu) -->
            <section class="lk-section">
              <h4 class="lk-section__title">
                🎟️ Vé của khách — {{ detail.seat_count }} vé phim · {{ detail.combo_count }} phiếu bắp nước
              </h4>
              <TicketPrintable :booking="detail" />
            </section>

            <!-- Thanh toán -->
            <section class="lk-section lk-section--pay">
              <div class="lk-kv"><span>Tạm tính</span><b>{{ formatCurrency(detail.subtotal) }}</b></div>
              <div class="lk-kv" v-if="detail.discount_amount > 0">
                <span>Giảm giá {{ detail.voucher_code ? `(${detail.voucher_code})` : '' }}</span>
                <b class="text-mint">- {{ formatCurrency(detail.discount_amount) }}</b>
              </div>
              <div class="lk-kv lk-kv--total"><span>Tổng thanh toán</span><b>{{ formatCurrency(detail.total_amount) }}</b></div>
              <div class="lk-pay-row">
                <span class="method-badge">{{ detail.payment_method || '—' }}</span>
                <span class="pay-pill" :class="payClass(detail.payment_status)">{{ payLabel(detail.payment_status) }}</span>
                <span class="muted">Đặt lúc {{ detail.created_at }}</span>
              </div>
            </section>
          </div>

          <div class="lk-modal__foot" v-if="detail">
            <button class="btn-ghost" @click="closeDetail">Đóng</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/axios';
import TicketPrintable from '../../components/TicketPrintable.vue';
import { QrcodeStream, QrcodeCapture } from 'vue-qrcode-reader';

const route = useRoute();


const query = ref('');
const loading = ref(false);
const searched = ref(false);
const results = ref([]);
const showCamera = ref(false);

const onDetect = (detectedCodes) => {
  if (detectedCodes && detectedCodes.length > 0) {
    let rawValue = detectedCodes[0].rawValue;
    try {
      const url = new URL(rawValue);
      const scanParam = url.searchParams.get('scan');
      if (scanParam) rawValue = scanParam;
    } catch(e) {}
    
    query.value = rawValue;
    showCamera.value = false;
    doSearch();
  }
};

const showDetail = ref(false);
const detail = ref(null);
const detailLoading = ref(false);

const formatCurrency = (val) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);

const initials = (name) => {
  if (!name) return '👤';
  const parts = name.trim().split(/\s+/);
  return (parts[0][0] + (parts[parts.length - 1][0] || '')).toUpperCase();
};

const typeLabel = (t) => ({ standard: 'Thường', vip: 'VIP', couple: 'Đôi' }[t] || t);

const payLabel = (s) => ({ paid: 'Đã thanh toán', pending: 'Chờ xử lý', failed: 'Thất bại' }[s] || s);
const payClass = (s) => ({ paid: 'is-paid', pending: 'is-pending', failed: 'is-failed' }[s] || '');

const doSearch = async () => {
  const q = query.value.trim();
  if (q.length < 3) {
    alert('Vui lòng nhập ít nhất 3 ký tự để tra cứu.');
    return;
  }
  loading.value = true;
  searched.value = true;
  try {
    const res = await api.get('/staff/bookings/lookup', { params: { q } });
    results.value = res.data.data || [];
  } catch (err) {
    console.error('Lookup error:', err);
    results.value = [];
    alert(err.response?.data?.message || 'Có lỗi khi tra cứu đơn hàng.');
  } finally {
    loading.value = false;
  }
};

const clearSearch = () => {
  query.value = '';
  results.value = [];
  searched.value = false;
};

onMounted(() => {
  if (route.query.scan) {
    query.value = route.query.scan;
    doSearch().then(() => {
      if (results.value.length > 0) {
        openDetail(results.value[0].id);
      }
    });
  }
});

const openDetail = async (id) => {
  showDetail.value = true;
  detailLoading.value = true;
  detail.value = null;
  try {
    const res = await api.get(`/staff/bookings/${id}`);
    detail.value = res.data;
  } catch (err) {
    console.error('Detail error:', err);
    alert('Không tải được chi tiết đơn hàng.');
    showDetail.value = false;
  } finally {
    detailLoading.value = false;
  }
};

const closeDetail = () => {
  showDetail.value = false;
  detail.value = null;
};
</script>

<style scoped>
.lookup { display: flex; flex-direction: column; gap: 22px; }
.muted { color: var(--text-muted); }
.text-mint { color: var(--accent-mint); }

/* ===== SEARCH ===== */
.lookup-search {
  padding: 24px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.lookup-search__head h3 { font-size: 17px; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
.lookup-search__head p { font-size: 13.5px; color: var(--text-secondary); }
.lookup-search__head b { color: var(--accent-pink); }

.lookup-search__bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 8px 0 16px;
  height: 52px;
  background: #faf8fa;
  border: 1.5px solid #ececf1;
  border-radius: 12px;
  transition: var(--transition-smooth);
}
.lookup-search__bar:focus-within { border-color: var(--accent-pink); background: #fff; box-shadow: 0 0 0 4px rgba(216, 45, 139, 0.08); }
.lookup-search__icon { font-size: 15px; opacity: 0.6; }
.lookup-search__bar input { flex: 1; border: none; outline: none; background: transparent; font-size: 14.5px; color: #1e293b; }
.lookup-search__clear {
  border: none; background: #f1f5f9; color: #64748b;
  width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 11px;
}
.lookup-search__clear:hover { background: #fde2ef; color: var(--accent-pink); }
.lookup-search__btn {
  border: none; cursor: pointer;
  padding: 0 22px; height: 40px; border-radius: 9px;
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: #fff; font-weight: 800; font-size: 13.5px;
  transition: var(--transition-smooth);
}
.lookup-search__btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(216, 45, 139, 0.28); }
.lookup-search__btn:disabled { opacity: 0.6; cursor: not-allowed; }

.lookup-camera__btn {
  border: 1.5px solid var(--accent-pink);
  background: white;
  color: var(--accent-pink);
  cursor: pointer;
  padding: 0 16px; height: 40px; border-radius: 9px;
  font-weight: 800; font-size: 13.5px;
  transition: var(--transition-smooth);
}
.lookup-camera__btn:hover { background: var(--accent-pink); color: white; }

.camera-wrapper {
  margin-top: 16px;
  border-radius: 12px;
  overflow: hidden;
  border: 4px solid var(--accent-pink);
  background: #000;
  position: relative;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.camera-close {
  position: absolute;
  bottom: 10px; left: 50%;
  transform: translateX(-50%);
  background: rgba(0,0,0,0.6);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  cursor: pointer;
  z-index: 10;
}
.camera-close:hover { background: rgba(0,0,0,0.8); }

/* ===== STATES ===== */
.lookup-state {
  display: flex; flex-direction: column; align-items: center; text-align: center;
  gap: 8px; padding: 56px 20px; color: var(--text-muted);
}
.lookup-state__art { font-size: 46px; }
.lookup-state h4 { font-size: 16px; font-weight: 800; color: #1e293b; }
.lookup-spinner {
  width: 40px; height: 40px; border-radius: 50%;
  border: 4px solid #f1e3ec; border-top-color: var(--accent-pink);
  animation: lk-spin 0.8s linear infinite;
}
@keyframes lk-spin { to { transform: rotate(360deg); } }

/* ===== RESULTS TABLE ===== */
.lookup-results { padding: 8px 8px 4px; background: #fff; border: 1px solid rgba(0, 0, 0, 0.05); }
.lookup-results__head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 14px; font-size: 13px; color: var(--text-secondary);
}
.lookup-results__head b { color: var(--accent-pink); }
.lookup-results__hint { font-size: 12px; color: var(--text-muted); }

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

.pay-pill { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.pay-pill.is-paid { background: #edfcf5; color: var(--accent-mint); }
.pay-pill.is-pending { background: #fffaf0; color: #dd6b20; }
.pay-pill.is-failed { background: #fee2e2; color: #dc2626; }

/* ===== DETAIL MODAL ===== */
.lk-backdrop {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(15, 6, 8, 0.5); backdrop-filter: blur(6px);
  display: flex; align-items: flex-start; justify-content: center;
  padding: 40px 20px; overflow-y: auto;
}
.lk-modal { width: 100%; max-width: 760px; background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 30px 70px rgba(0, 0, 0, 0.35); }
.lk-modal__head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 22px; color: #fff;
  background: linear-gradient(120deg, var(--accent-violet), var(--accent-pink));
}
.lk-modal__head h3 { font-size: 17px; font-weight: 800; }
.lk-modal__close {
  border: none; background: rgba(255, 255, 255, 0.2); color: #fff;
  width: 30px; height: 30px; border-radius: 8px; cursor: pointer; font-size: 14px;
}
.lk-modal__close:hover { background: rgba(255, 255, 255, 0.35); }

.lk-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 18px; }
.lk-section { display: flex; flex-direction: column; gap: 8px; }
.lk-section__title { font-size: 13px; font-weight: 800; color: #1e293b; }
.lk-kv { display: flex; justify-content: space-between; gap: 12px; font-size: 13.5px; }
.lk-kv span { color: var(--text-secondary); }
.lk-kv b { color: #1e293b; text-align: right; }

.lk-movie { display: flex; gap: 12px; }
.lk-movie img { width: 60px; height: 88px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
.lk-movie strong { font-size: 15px; color: #1e293b; }
.lk-movie p { font-size: 12.5px; margin-top: 3px; }

.lk-seats { display: flex; flex-wrap: wrap; gap: 8px; }
.lk-seat {
  position: relative;
  padding: 7px 12px; border-radius: 8px; font-weight: 800; font-size: 13px;
  font-family: 'Courier New', monospace; color: #fff;
  background: #64748b;
}
.lk-seat--vip { background: linear-gradient(135deg, #f59e0b, #d97706); }
.lk-seat--couple { background: linear-gradient(135deg, #ec4899, #be185d); }
.lk-seat--standard { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.lk-seat__in {
  position: absolute; top: -5px; right: -5px;
  width: 15px; height: 15px; border-radius: 50%;
  background: var(--accent-mint); color: #fff; font-size: 9px;
  display: grid; place-items: center; font-style: normal;
}

.lk-combos { display: flex; flex-direction: column; gap: 6px; }
.lk-combo { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: #faf8fa; border-radius: 8px; font-size: 13px; }
.lk-combo__name { flex: 1; font-weight: 600; color: #1e293b; }
.lk-combo__qty { font-weight: 800; color: var(--accent-pink); }
.lk-combo__price { font-weight: 700; color: #1e293b; }
.lk-empty-line { font-size: 13px; }

.lk-section--pay { border-top: 1px dashed #e5e7eb; padding-top: 14px; gap: 6px; }
.lk-kv--total { font-size: 15px; margin-top: 4px; }
.lk-kv--total b { color: var(--accent-pink); font-size: 17px; font-weight: 800; }
.lk-pay-row { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
.method-badge {
  background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 5px;
  font-size: 11px; font-weight: 700; text-transform: uppercase;
}

.lk-modal__foot { padding: 14px 22px 20px; display: flex; justify-content: flex-end; }
.btn-ghost {
  border: 1.5px solid #e2e8f0; background: #fff; color: #475569;
  padding: 10px 22px; border-radius: 10px; font-weight: 700; font-size: 13.5px; cursor: pointer;
}
.btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }

/* transitions */
.lk-fade-enter-active { transition: opacity 0.2s; }
.lk-fade-enter-active .lk-modal { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.lk-fade-enter-from { opacity: 0; }
.lk-fade-enter-from .lk-modal { transform: translateY(-24px) scale(0.96); }
</style>
