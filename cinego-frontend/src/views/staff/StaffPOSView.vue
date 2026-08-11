<template>
  <div class="pos">
    <!-- ============ CANVAS TRÁI: các bước ============ -->
    <div class="pos-canvas">
      <!-- Stepper -->
      <ol class="pos-steps">
        <li
          v-for="s in steps"
          :key="s.n"
          class="pos-step"
          :class="{ done: step > s.n, current: step === s.n, clickable: s.n < step }"
          @click="s.n < step && (step = s.n)"
        >
          <span class="pos-step__dot">{{ step > s.n ? '✓' : s.n }}</span>
          <span class="pos-step__label">{{ s.label }}</span>
        </li>
      </ol>

      <!-- BƯỚC 1 — CHỌN PHIM -->
      <section v-show="step === 1" class="pos-pane">
        <header class="pane-head">
          <h3>Chọn phim</h3>
          <span class="pane-hint">Phim đang & sắp chiếu</span>
        </header>

        <div v-if="loading.movies" class="pos-load">Đang tải phim…</div>
        <div v-else class="film-grid">
          <button
            v-for="m in movies"
            :key="m.id"
            class="film-card"
            :class="{ active: selected.movie?.id === m.id }"
            @click="pickMovie(m)"
          >
            <div class="film-poster">
              <img :src="posterUrl(m.poster_url)" :alt="m.title" @error="onImgErr" />
              <span class="film-badge">{{ statusLabel(m.status) }}</span>
            </div>
            <span class="film-title">{{ m.title }}</span>
          </button>
        </div>
      </section>

      <!-- BƯỚC 2 — SUẤT CHIẾU -->
      <section v-show="step === 2" class="pos-pane">
        <header class="pane-head">
          <h3>Chọn ngày & suất</h3>
          <span class="pane-hint">{{ selected.movie?.title }}</span>
        </header>

        <div v-if="loading.dates" class="pos-load">Đang tải lịch chiếu…</div>
        <template v-else>
          <div v-if="dates.length === 0" class="pos-empty">Phim này chưa có suất chiếu sắp tới.</div>
          <template v-else>
            <div class="date-row">
              <button
                v-for="d in dates"
                :key="d"
                class="date-chip"
                :class="{ active: selected.date === d }"
                @click="pickDate(d)"
              >
                <b>{{ dayNum(d) }}</b>
                <span>{{ dayMon(d) }}</span>
              </button>
            </div>

            <div v-if="loading.showtimes" class="pos-load">Đang tải suất…</div>
            <div v-else class="room-list">
              <div v-for="g in showtimeGroups" :key="g.roomId" class="room-block">
                <div class="room-name">🏛️ {{ g.roomName }}</div>
                <div class="time-row">
                  <button
                    v-for="t in g.showtimes"
                    :key="t.id"
                    class="time-chip"
                    :class="{ active: selected.showtime?.id === t.id }"
                    @click="pickShowtime(t, g)"
                  >
                    {{ t.start_time }}
                  </button>
                </div>
              </div>
            </div>
          </template>
        </template>
      </section>

      <!-- BƯỚC 3 — CHỌN GHẾ -->
      <section v-show="step === 3" class="pos-pane">
        <header class="pane-head">
          <h3>Chọn ghế</h3>
          <span class="pane-hint">{{ selected.roomName }} • {{ selected.showtime?.start_time }}</span>
        </header>

        <div v-if="loading.seats" class="pos-load">Đang tải sơ đồ ghế…</div>
        <template v-else>
          <div class="screen-wrap">
            <div class="screen">MÀN HÌNH</div>
            <div class="screen-glow"></div>
          </div>

          <div class="seatmap">
            <div v-for="row in seatRows" :key="row.name" class="seat-row">
              <span class="row-label">{{ row.name }}</span>
              <div class="seat-line">
                <button
                  v-for="seat in row.seats"
                  :key="seat.id"
                  class="seat"
                  :class="[
                    'seat--' + seat.type,
                    seat.status,
                    { picked: pickedIds.has(seat.id) }
                  ]"
                  :disabled="seat.status !== 'available'"
                  :title="`${seat.row_name}${seat.seat_number} • ${money(seat.price)}`"
                  @click="toggleSeat(seat)"
                >
                  {{ seat.seat_number }}
                </button>
              </div>
            </div>
          </div>

          <div class="seat-legend">
            <span><i class="dot lg-avail"></i> Trống</span>
            <span><i class="dot lg-standard"></i> Thường</span>
            <span><i class="dot lg-vip"></i> VIP</span>
            <span><i class="dot lg-couple"></i> Đôi</span>
            <span><i class="dot lg-sold"></i> Đã bán</span>
            <span><i class="dot lg-picked"></i> Đang chọn</span>
          </div>
        </template>
      </section>

      <!-- BƯỚC 4 — COMBO -->
      <section v-show="step === 4" class="pos-pane">
        <header class="pane-head">
          <h3>Bắp nước (tuỳ chọn)</h3>
          <span class="pane-hint">Có thể bỏ qua</span>
        </header>

        <div v-if="loading.combos" class="pos-load">Đang tải combo…</div>
        <div v-else-if="combos.length === 0" class="pos-empty">Chưa có combo nào đang bán.</div>
        <div v-else class="combo-grid">
          <div
            v-for="c in combos"
            :key="c.id"
            class="combo-card"
            :class="{ off: !c.available }"
          >
            <div class="combo-thumb">
              <img :src="posterUrl(c.image_url)" :alt="c.name" @error="onImgErr" />
              <span v-if="!c.available" class="combo-out">Hết hàng</span>
            </div>
            <div class="combo-info">
              <strong>{{ c.name }}</strong>
              <span class="combo-price">{{ money(c.price) }}</span>
            </div>
            <div class="combo-qty" v-if="c.available">
              <button class="qbtn" :disabled="!comboQty[c.id]" @click="decCombo(c)">−</button>
              <span class="qnum">{{ comboQty[c.id] || 0 }}</span>
              <button class="qbtn" @click="incCombo(c)">+</button>
            </div>
          </div>
        </div>
      </section>

      <!-- BƯỚC 5 — KHÁCH + THANH TOÁN -->
      <section v-show="step === 5" class="pos-pane">
        <header class="pane-head">
          <h3>Khách hàng & thanh toán</h3>
          <span class="pane-hint">Gắn khách để tích điểm (hoặc tạo nhanh khách vãng lai)</span>
        </header>

        <!-- Khách đã chọn -->
        <div v-if="selected.customer" class="cust-chip">
          <div class="cust-ava">{{ initials(selected.customer.name) }}</div>
          <div class="cust-meta">
            <strong>{{ selected.customer.name }}</strong>
            <span>{{ selected.customer.phone || selected.customer.email }} • 🏆 {{ selected.customer.membership_tier || 'Bronze' }} • {{ selected.customer.loyalty_points || 0 }}đ</span>
          </div>
          <button class="cust-clear" @click="selected.customer = null">Đổi khách</button>
        </div>

        <template v-else>
          <!-- Tìm khách -->
          <div class="cust-search">
            <input
              v-model="custQuery"
              type="text"
              placeholder="Tìm khách theo tên / SĐT / email…"
              @keyup.enter="doSearchCustomer"
            />
            <button class="btn-mini" :disabled="loading.cust" @click="doSearchCustomer">Tìm</button>
          </div>
          <div v-if="custResults.length" class="cust-results">
            <button v-for="u in custResults" :key="u.id" class="cust-result" @click="pickCustomer(u)">
              <span class="cust-ava sm">{{ initials(u.name) }}</span>
              <span class="cust-r-meta"><strong>{{ u.name }}</strong><span>{{ u.phone || u.email }}</span></span>
              <span class="cust-r-pts">{{ u.loyalty_points || 0 }}đ</span>
            </button>
          </div>

          <!-- Tạo nhanh -->
          <div class="quick-create">
            <div class="qc-title">Hoặc tạo nhanh khách mới</div>
            <div class="qc-grid">
              <input v-model="qc.name" placeholder="Tên khách *" />
              <input v-model="qc.phone" placeholder="Số điện thoại *" />
              <input v-model="qc.email" placeholder="Email (tuỳ chọn)" />
            </div>
            <button class="btn-mini solid" :disabled="loading.qc || !qc.name || !qc.phone" @click="doCreateCustomer">
              {{ loading.qc ? 'Đang tạo…' : '+ Tạo & chọn khách' }}
            </button>
          </div>
        </template>

        <!-- Phương thức thanh toán -->
        <div class="pay-methods">
          <div class="pay-title">Phương thức thu tiền</div>
          <div class="pay-opts">
            <button class="pay-opt" :class="{ active: payment === 'cash' }" @click="payment = 'cash'">💵 Tiền mặt</button>
            <button class="pay-opt" :class="{ active: payment === 'bank_transfer' }" @click="payment = 'bank_transfer'">🏦 Chuyển khoản</button>
          </div>
        </div>
      </section>

      <!-- Điều hướng bước -->
      <div class="pos-nav">
        <button v-if="step > 1" class="btn-ghost" @click="step--">‹ Quay lại</button>
        <span class="spacer"></span>
        <button v-if="step < 5" class="btn-next" :disabled="!canNext" @click="goNext">Tiếp tục ›</button>
      </div>
    </div>

    <!-- ============ CUỐNG VÉ / HOÁ ĐƠN SỐNG ============ -->
    <aside class="pos-ticket">
      <div class="ticket-top">
        <div class="ticket-logo">Cine<b>Go</b></div>
        <span class="ticket-kind">HOÁ ĐƠN QUẦY</span>
      </div>

      <div class="ticket-body">
        <div class="tk-movie" v-if="selected.movie">
          <img :src="posterUrl(selected.movie.poster_url)" :alt="selected.movie.title" @error="onImgErr" />
          <div>
            <strong>{{ selected.movie.title }}</strong>
            <p v-if="selected.showtime">{{ selected.roomName }} • {{ selected.date ? dayFull(selected.date) : '' }} {{ selected.showtime.start_time }}</p>
            <p v-else class="tk-dim">Chưa chọn suất</p>
          </div>
        </div>
        <div v-else class="tk-empty">🎬 Chưa chọn phim</div>

        <div class="tk-rule"></div>

        <div class="tk-line">
          <span>Ghế ({{ selected.seats.length }})</span>
          <b>{{ selected.seats.map(s => s.row_name + s.seat_number).join(', ') || '—' }}</b>
        </div>
        <div class="tk-line"><span>Tiền ghế</span><b>{{ money(seatTotal) }}</b></div>

        <template v-if="comboLines.length">
          <div class="tk-rule dashed"></div>
          <div class="tk-line" v-for="cl in comboLines" :key="cl.id">
            <span>{{ cl.name }} ×{{ cl.qty }}</span><b>{{ money(cl.price * cl.qty) }}</b>
          </div>
        </template>

        <div class="tk-rule dashed"></div>
        <div class="tk-line"><span>Khách</span><b>{{ selected.customer?.name || 'Vãng lai' }}</b></div>
        <div class="tk-line"><span>Thanh toán</span><b>{{ payment === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' }}</b></div>

        <div class="tk-total">
          <span>TỔNG CỘNG</span>
          <span>{{ money(grandTotal) }}</span>
        </div>
      </div>

      <div class="ticket-perf"></div>

      <button class="btn-pay" :disabled="!canSubmit || submitting" @click="submit">
        {{ submitting ? 'Đang tạo đơn…' : '💳 THU TIỀN & TẠO VÉ' }}
      </button>
      <p class="ticket-note">Đơn tạo ở trạng thái đã thanh toán.</p>
    </aside>

    <!-- ============ THÀNH CÔNG ============ -->
    <transition name="pos-fade">
      <div v-if="success" class="pos-done-backdrop">
        <div class="pos-done">
          <div class="done-check">✓</div>
          <h3>Đặt vé thành công!</h3>
          <p>Mã đơn</p>
          <div class="done-code">{{ success.code }}</div>
          <div class="done-actions">
            <button class="btn-ghost" @click="resetAll">Bán đơn mới</button>
            <button class="btn-next" :disabled="loading.print" @click="openPrint">
              {{ loading.print ? 'Đang tải vé…' : '🖨️ Xem & In vé' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal in vé -->
    <transition name="pos-fade">
      <div v-if="printBooking" class="pos-done-backdrop" @click.self="printBooking = null">
        <div class="print-modal">
          <div class="print-head">
            <h3>🎟️ Vé — {{ printBooking.booking_code }}</h3>
            <button class="cust-clear" @click="printBooking = null">Đóng</button>
          </div>
          <TicketPrintable :booking="printBooking" />
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import api from '../../api/axios';
import { toast } from '../../utils/alert';
import TicketPrintable from '../../components/TicketPrintable.vue';

const FALLBACK_IMG = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=200&q=80';

const steps = [
  { n: 1, label: 'Phim' },
  { n: 2, label: 'Suất' },
  { n: 3, label: 'Ghế' },
  { n: 4, label: 'Bắp nước' },
  { n: 5, label: 'Thanh toán' },
];

const step = ref(1);
const loading = reactive({ movies: false, dates: false, showtimes: false, seats: false, combos: false, cust: false, qc: false, print: false });

const movies = ref([]);
const dates = ref([]);
const showtimeGroups = ref([]);
const seats = ref([]);
const combos = ref([]);
const comboQty = reactive({});

const custQuery = ref('');
const custResults = ref([]);
const qc = reactive({ name: '', phone: '', email: '' });
const payment = ref('cash');

const submitting = ref(false);
const success = ref(null);
const printBooking = ref(null);

const selected = reactive({
  movie: null,
  date: null,
  showtime: null,
  roomName: '',
  seats: [],
  customer: null,
});

/* ---------- Helpers ---------- */
const money = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v || 0);
const statusLabel = (s) => (s === 'showing' || s === 'Đang chiếu' ? 'Đang chiếu' : s === 'upcoming' || s === 'Sắp chiếu' ? 'Sắp chiếu' : 'Ngừng');
const initials = (n) => { if (!n) return '👤'; const p = n.trim().split(/\s+/); return (p[0][0] + (p[p.length - 1][0] || '')).toUpperCase(); };
const onImgErr = (e) => { e.target.src = FALLBACK_IMG; };

const posterUrl = (url) => {
  if (!url) return FALLBACK_IMG;
  // Sửa cả trường hợp URL bị lặp tiền tố (…/storage/http://…/storage/…)
  const last = url.lastIndexOf('http');
  if (last > 0) return url.slice(last);
  if (url.startsWith('http') || url.startsWith('blob:')) return url;
  const clean = url.replace(/^(.*\/storage\/)/, '');
  return `http://127.0.0.1:8000/storage/${clean}`;
};

const dayNum = (d) => d.slice(8, 10);
const dayMon = (d) => 'Th' + parseInt(d.slice(5, 7), 10);
const dayFull = (d) => `${d.slice(8, 10)}/${d.slice(5, 7)}/${d.slice(0, 4)}`;

/* ---------- Bước 1: phim ---------- */
const fetchMovies = async () => {
  loading.movies = true;
  try {
    const res = await api.get('/movies');
    const list = res.data.data || res.data || [];
    movies.value = list.filter((m) => m.status !== 'ended' && m.status !== 'Đã kết thúc');
  } catch (e) {
    toast('Không tải được danh sách phim.', 'error');
  } finally {
    loading.movies = false;
  }
};

const pickMovie = async (m) => {
  selected.movie = m;
  selected.date = null;
  selected.showtime = null;
  showtimeGroups.value = [];
  step.value = 2;
  loading.dates = true;
  try {
    const res = await api.get(`/movies/${m.id}/available-dates`);
    dates.value = res.data.data || [];
    if (dates.value.length) pickDate(dates.value[0]);
  } catch (e) {
    dates.value = [];
  } finally {
    loading.dates = false;
  }
};

/* ---------- Bước 2: suất ---------- */
const pickDate = async (d) => {
  selected.date = d;
  selected.showtime = null;
  loading.showtimes = true;
  try {
    const res = await api.get(`/movies/${selected.movie.id}/showtimes`, { params: { date: d } });
    showtimeGroups.value = res.data.data || [];
  } catch (e) {
    showtimeGroups.value = [];
  } finally {
    loading.showtimes = false;
  }
};

const pickShowtime = (t, g) => {
  selected.showtime = t;
  selected.roomName = g.roomName;
};

/* ---------- Bước 3: ghế ---------- */
const fetchSeats = async () => {
  loading.seats = true;
  seats.value = [];
  try {
    const res = await api.get(`/showtimes/${selected.showtime.id}/seats`);
    seats.value = res.data.seats || [];
  } catch (e) {
    toast('Không tải được sơ đồ ghế.', 'error');
  } finally {
    loading.seats = false;
  }
};

const seatRows = computed(() => {
  const map = {};
  for (const s of seats.value) {
    (map[s.row_name] = map[s.row_name] || []).push(s);
  }
  return Object.keys(map)
    .sort()
    .map((name) => ({
      name,
      seats: map[name].sort((a, b) => Number(a.seat_number) - Number(b.seat_number)),
    }));
});

const pickedIds = computed(() => new Set(selected.seats.map((s) => s.id)));

const toggleSeat = (seat) => {
  if (seat.status !== 'available') return;
  const i = selected.seats.findIndex((s) => s.id === seat.id);
  if (i >= 0) selected.seats.splice(i, 1);
  else selected.seats.push(seat);
};

/* ---------- Bước 4: combo ---------- */
const fetchCombos = async () => {
  loading.combos = true;
  try {
    const res = await api.get('/staff/combos');
    combos.value = res.data.data || [];
  } catch (e) {
    combos.value = [];
  } finally {
    loading.combos = false;
  }
};

const incCombo = (c) => {
  if (!c.available) return;
  if ((comboQty[c.id] || 0) >= c.stock) {
    toast(`Chỉ còn ${c.stock} phần ${c.name}.`, 'error');
    return;
  }
  comboQty[c.id] = (comboQty[c.id] || 0) + 1;
};
const decCombo = (c) => {
  if (!comboQty[c.id]) return;
  comboQty[c.id]--;
  if (comboQty[c.id] <= 0) delete comboQty[c.id];
};

const comboLines = computed(() =>
  combos.value
    .filter((c) => comboQty[c.id])
    .map((c) => ({ id: c.id, name: c.name, price: c.price, qty: comboQty[c.id] }))
);

/* ---------- Bước 5: khách ---------- */
const doSearchCustomer = async () => {
  const q = custQuery.value.trim();
  if (q.length < 2) return;
  loading.cust = true;
  try {
    const res = await api.get('/staff/customers/search', { params: { query: q } });
    custResults.value = res.data.data || [];
    if (!custResults.value.length) toast('Không tìm thấy khách. Bạn có thể tạo nhanh bên dưới.');
  } catch (e) {
    custResults.value = [];
  } finally {
    loading.cust = false;
  }
};

const pickCustomer = (u) => {
  selected.customer = u;
  custResults.value = [];
  custQuery.value = '';
};

const doCreateCustomer = async () => {
  loading.qc = true;
  try {
    const res = await api.post('/staff/customers', { name: qc.name, phone: qc.phone, email: qc.email || null });
    selected.customer = res.data.data;
    toast(res.data.existed ? 'Khách đã tồn tại — đã chọn.' : 'Đã tạo & chọn khách.');
    qc.name = qc.phone = qc.email = '';
  } catch (e) {
    toast(e.response?.data?.message || 'Không tạo được khách.', 'error');
  } finally {
    loading.qc = false;
  }
};

/* ---------- Tổng tiền ---------- */
const seatTotal = computed(() => selected.seats.reduce((s, x) => s + (Number(x.price) || 0), 0));
const comboTotal = computed(() => comboLines.value.reduce((s, x) => s + x.price * x.qty, 0));
const grandTotal = computed(() => seatTotal.value + comboTotal.value);

/* ---------- Điều hướng ---------- */
const canNext = computed(() => {
  if (step.value === 1) return !!selected.movie;
  if (step.value === 2) return !!selected.showtime;
  if (step.value === 3) return selected.seats.length > 0;
  return true;
});

const goNext = async () => {
  if (!canNext.value) return;
  if (step.value === 2) { step.value = 3; await fetchSeats(); return; }
  if (step.value === 3) { step.value = 4; if (!combos.value.length) await fetchCombos(); return; }
  step.value++;
};

/* ---------- Gửi đơn ---------- */
const canSubmit = computed(() => selected.showtime && selected.seats.length > 0 && selected.customer);

const submit = async () => {
  if (!canSubmit.value) {
    toast('Cần chọn ghế và gán khách hàng trước khi thu tiền.', 'error');
    return;
  }
  submitting.value = true;
  try {
    const payload = {
      showtime_id: selected.showtime.id,
      seat_ids: selected.seats.map((s) => s.id),
      combos: comboLines.value.map((c) => ({ id: c.id, quantity: c.qty })),
      payment_method: payment.value,
      customer_id: selected.customer.id,
      total_amount: grandTotal.value,
    };
    const res = await api.post('/staff/bookings/pos', payload);
    success.value = { code: res.data.booking_code };
  } catch (e) {
    toast(e.response?.data?.message || 'Tạo đơn thất bại. Vui lòng thử lại.', 'error');
  } finally {
    submitting.value = false;
  }
};

/* ---------- In vé sau khi thành công ---------- */
const openPrint = async () => {
  if (!success.value) return;
  loading.print = true;
  try {
    const look = await api.get('/staff/bookings/lookup', { params: { q: success.value.code } });
    const found = (look.data.data || [])[0];
    if (!found) throw new Error('not found');
    const detail = await api.get(`/staff/bookings/${found.id}`);
    printBooking.value = detail.data;
  } catch (e) {
    toast('Không tải được vé để in. Bạn có thể tra ở tab Tra cứu.', 'error');
  } finally {
    loading.print = false;
  }
};

/* ---------- Reset ---------- */
const resetAll = () => {
  step.value = 1;
  selected.movie = null;
  selected.date = null;
  selected.showtime = null;
  selected.roomName = '';
  selected.seats = [];
  selected.customer = null;
  Object.keys(comboQty).forEach((k) => delete comboQty[k]);
  custResults.value = [];
  custQuery.value = '';
  qc.name = qc.phone = qc.email = '';
  payment.value = 'cash';
  success.value = null;
  printBooking.value = null;
};

fetchMovies();
</script>

<style scoped>
.pos {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 22px;
  align-items: start;
  color: #1e293b;
}
@media (max-width: 1080px) { .pos { grid-template-columns: 1fr; } }

/* ============ CANVAS ============ */
.pos-canvas {
  background: #fff;
  border: 1px solid #eef0f3;
  border-radius: 18px;
  padding: 22px;
  box-shadow: 0 12px 30px rgba(15, 6, 8, 0.05);
}

/* Stepper */
.pos-steps { display: flex; list-style: none; padding: 0; margin: 0 0 22px; gap: 6px; flex-wrap: wrap; }
.pos-step { display: flex; align-items: center; gap: 8px; padding: 6px 12px 6px 6px; border-radius: 999px; opacity: 0.55; transition: 0.2s; }
.pos-step.current { opacity: 1; background: #fff1f2; }
.pos-step.done { opacity: 1; }
.pos-step.clickable { cursor: pointer; }
.pos-step__dot {
  width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center;
  font-size: 12px; font-weight: 800; color: #fff; background: #cbd5e1; flex-shrink: 0;
}
.pos-step.current .pos-step__dot, .pos-step.done .pos-step__dot { background: linear-gradient(135deg, #e50914, #9b000e); }
.pos-step__label { font-size: 13px; font-weight: 700; white-space: nowrap; }

.pane-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 16px; }
.pane-head h3 { font-size: 18px; font-weight: 800; color: #9b000e; }
.pane-hint { font-size: 12.5px; color: #94a3b8; }

.pos-load, .pos-empty { padding: 40px; text-align: center; color: #94a3b8; font-weight: 600; }

/* ---- Phim ---- */
.film-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 14px; }
.film-card { border: none; background: transparent; cursor: pointer; padding: 0; text-align: left; display: flex; flex-direction: column; gap: 8px; }
.film-poster { position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 2/3; box-shadow: 0 6px 16px rgba(0,0,0,0.12); transition: 0.2s; border: 2px solid transparent; }
.film-card:hover .film-poster { transform: translateY(-3px); }
.film-card.active .film-poster { border-color: #e50914; box-shadow: 0 8px 22px rgba(229,9,20,0.35); }
.film-poster img { width: 100%; height: 100%; object-fit: cover; display: block; }
.film-badge { position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.68); color: #fff; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.film-title { font-size: 13px; font-weight: 700; line-height: 1.3; }

/* ---- Ngày / suất ---- */
.date-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
.date-chip { border: 1.5px solid #ececf1; background: #fff; border-radius: 12px; padding: 8px 14px; cursor: pointer; display: flex; flex-direction: column; align-items: center; min-width: 58px; transition: 0.15s; }
.date-chip b { font-size: 18px; font-weight: 800; }
.date-chip span { font-size: 11px; color: #94a3b8; }
.date-chip.active { border-color: #e50914; background: linear-gradient(135deg, #e50914, #9b000e); color: #fff; }
.date-chip.active span { color: #ffe2e5; }

.room-block { margin-bottom: 18px; }
.room-name { font-size: 13px; font-weight: 800; color: #475569; margin-bottom: 8px; }
.time-row { display: flex; gap: 10px; flex-wrap: wrap; }
.time-chip { border: 1.5px solid #ececf1; background: #fff; border-radius: 10px; padding: 9px 18px; font-weight: 800; font-size: 14px; cursor: pointer; transition: 0.15s; }
.time-chip:hover { border-color: #f3a6ac; }
.time-chip.active { border-color: #e50914; background: #fff1f2; color: #9b000e; }

/* ---- Ghế ---- */
.screen-wrap { position: relative; margin: 6px 0 26px; }
.screen {
  height: 34px; border-radius: 60% 60% 8px 8px / 100% 100% 8px 8px;
  background: linear-gradient(180deg, #2b2f3a, #171a22);
  color: #cbd5e1; letter-spacing: 8px; font-size: 12px; font-weight: 800;
  display: grid; place-items: center; box-shadow: 0 -2px 30px rgba(229,9,20,0.25);
}
.screen-glow { height: 26px; background: radial-gradient(ellipse at top, rgba(229,9,20,0.28), transparent 70%); }

.seatmap { display: flex; flex-direction: column; gap: 8px; align-items: center; overflow-x: auto; padding-bottom: 6px; }
.seat-row { display: flex; align-items: center; gap: 10px; }
.row-label { width: 18px; text-align: center; font-size: 12px; font-weight: 800; color: #94a3b8; }
.seat-line { display: flex; gap: 6px; }
.seat {
  width: 30px; height: 30px; border-radius: 8px 8px 6px 6px; border: none; cursor: pointer;
  font-size: 10px; font-weight: 700; color: #334155; transition: 0.12s;
  background: #e7ebf0;
}
.seat--standard { background: #dfe7ff; }
.seat--vip { background: #ffe6c2; }
.seat--couple { background: #ffd9ec; }
.seat:hover:not(:disabled) { transform: translateY(-2px); }
.seat.sold, .seat.holding, .seat.broken { background: #e2e8f0; color: #cbd5e1; cursor: not-allowed; }
.seat.broken { background: repeating-linear-gradient(45deg, #e2e8f0, #e2e8f0 3px, #f1f5f9 3px, #f1f5f9 6px); }
.seat.picked { background: linear-gradient(135deg, #e50914, #9b000e); color: #fff; box-shadow: 0 4px 10px rgba(229,9,20,0.4); }

.seat-legend { display: flex; flex-wrap: wrap; gap: 14px; justify-content: center; margin-top: 20px; font-size: 12px; color: #64748b; }
.seat-legend .dot { width: 13px; height: 13px; border-radius: 4px; display: inline-block; margin-right: 5px; vertical-align: -2px; }
.lg-avail { background: #e7ebf0; } .lg-standard { background: #dfe7ff; } .lg-vip { background: #ffe6c2; }
.lg-couple { background: #ffd9ec; } .lg-sold { background: #e2e8f0; } .lg-picked { background: linear-gradient(135deg, #e50914, #9b000e); }

/* ---- Combo ---- */
.combo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 14px; }
.combo-card { border: 1px solid #eef0f3; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; background: #fff; transition: 0.15s; }
.combo-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
.combo-card.off { opacity: 0.6; }
.combo-thumb { position: relative; aspect-ratio: 16/10; background: #f1f5f9; }
.combo-thumb img { width: 100%; height: 100%; object-fit: cover; }
.combo-out { position: absolute; inset: 0; display: grid; place-items: center; background: rgba(15,23,42,0.55); color: #fff; font-weight: 800; font-size: 13px; }
.combo-info { padding: 10px 12px 4px; display: flex; flex-direction: column; gap: 2px; }
.combo-info strong { font-size: 13.5px; }
.combo-price { color: #e50914; font-weight: 800; font-size: 13px; }
.combo-qty { display: flex; align-items: center; justify-content: center; gap: 14px; padding: 10px; }
.qbtn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #ececf1; background: #fff; font-size: 17px; font-weight: 800; cursor: pointer; color: #9b000e; }
.qbtn:disabled { opacity: 0.4; cursor: not-allowed; }
.qnum { font-weight: 800; min-width: 18px; text-align: center; }

/* ---- Khách ---- */
.cust-chip { display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef0f3; border-radius: 12px; padding: 12px 14px; }
.cust-ava { width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; font-weight: 800; color: #fff; background: linear-gradient(135deg, #e50914, #9b000e); flex-shrink: 0; }
.cust-ava.sm { width: 32px; height: 32px; font-size: 12px; }
.cust-meta { flex: 1; display: flex; flex-direction: column; }
.cust-meta span { font-size: 12px; color: #64748b; }
.cust-clear { border: 1px solid #e2e8f0; background: #fff; border-radius: 8px; padding: 6px 12px; font-weight: 700; font-size: 12.5px; cursor: pointer; color: #475569; }

.cust-search { display: flex; gap: 10px; margin-bottom: 12px; }
.cust-search input, .qc-grid input { flex: 1; border: 1.5px solid #ececf1; border-radius: 10px; padding: 11px 14px; font-size: 14px; outline: none; }
.cust-search input:focus, .qc-grid input:focus { border-color: #e50914; }
.btn-mini { border: 1.5px solid #e50914; background: #fff; color: #9b000e; border-radius: 10px; padding: 0 18px; font-weight: 800; cursor: pointer; }
.btn-mini.solid { background: linear-gradient(135deg, #e50914, #9b000e); color: #fff; border: none; padding: 11px 18px; margin-top: 12px; }
.btn-mini:disabled { opacity: 0.5; cursor: not-allowed; }

.cust-results { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.cust-result { display: flex; align-items: center; gap: 10px; border: 1px solid #eef0f3; background: #fff; border-radius: 10px; padding: 8px 12px; cursor: pointer; text-align: left; }
.cust-result:hover { background: #fdf3f8; border-color: #f3c9cd; }
.cust-r-meta { flex: 1; display: flex; flex-direction: column; }
.cust-r-meta span { font-size: 12px; color: #64748b; }
.cust-r-pts { font-weight: 800; color: #f59e0b; font-size: 12.5px; }

.quick-create { border-top: 1px dashed #e5e7eb; padding-top: 16px; }
.qc-title { font-size: 13px; font-weight: 800; color: #475569; margin-bottom: 10px; }
.qc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
@media (max-width: 640px) { .qc-grid { grid-template-columns: 1fr; } }

.pay-methods { margin-top: 22px; }
.pay-title { font-size: 13px; font-weight: 800; color: #475569; margin-bottom: 10px; }
.pay-opts { display: flex; gap: 12px; }
.pay-opt { flex: 1; border: 1.5px solid #ececf1; background: #fff; border-radius: 12px; padding: 14px; font-weight: 800; font-size: 14px; cursor: pointer; transition: 0.15s; }
.pay-opt.active { border-color: #e50914; background: #fff1f2; color: #9b000e; }

/* Nav */
.pos-nav { display: flex; align-items: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
.spacer { flex: 1; }
.btn-ghost { border: 1.5px solid #e2e8f0; background: #fff; color: #475569; padding: 11px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }
.btn-next { border: none; background: linear-gradient(135deg, #e50914, #9b000e); color: #fff; padding: 11px 26px; border-radius: 10px; font-weight: 800; cursor: pointer; }
.btn-next:disabled { opacity: 0.45; cursor: not-allowed; }

/* ============ CUỐNG VÉ ============ */
.pos-ticket {
  position: sticky; top: 16px;
  background: linear-gradient(160deg, #1b1416, #241a1c);
  border-radius: 18px; color: #fff; overflow: hidden;
  box-shadow: 0 20px 44px rgba(15, 6, 8, 0.3);
}
.ticket-top { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: linear-gradient(120deg, #9b000e, #e50914); }
.ticket-logo { font-size: 20px; font-weight: 800; font-family: 'Arial Narrow', sans-serif; letter-spacing: -0.5px; }
.ticket-logo b { color: #ffd7db; }
.ticket-kind { font-size: 10px; font-weight: 800; letter-spacing: 2px; opacity: 0.85; }

.ticket-body { padding: 18px 20px; }
.tk-movie { display: flex; gap: 12px; margin-bottom: 14px; }
.tk-movie img { width: 46px; height: 66px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.tk-movie strong { font-size: 14px; display: block; }
.tk-movie p { font-size: 11.5px; color: #c7bcbe; margin-top: 4px; }
.tk-dim { color: #8a7d80 !important; }
.tk-empty { text-align: center; color: #8a7d80; padding: 14px 0; font-size: 13px; }

.tk-rule { height: 1px; background: rgba(255,255,255,0.12); margin: 12px 0; }
.tk-rule.dashed { background: repeating-linear-gradient(90deg, rgba(255,255,255,0.22), rgba(255,255,255,0.22) 5px, transparent 5px, transparent 10px); }
.tk-line { display: flex; justify-content: space-between; gap: 12px; font-size: 12.5px; margin: 6px 0; }
.tk-line span { color: #b6abad; }
.tk-line b { text-align: right; font-weight: 700; }
.tk-total { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; font-family: 'Courier New', monospace; }
.tk-total span:first-child { font-size: 12px; letter-spacing: 1px; color: #c7bcbe; }
.tk-total span:last-child { font-size: 22px; font-weight: 800; color: #ffd7db; }

.ticket-perf { height: 16px; background: radial-gradient(circle at 8px 8px, transparent 6px, #241a1c 6px) repeat-x; background-size: 16px 16px; margin-top: 4px; }
.btn-pay { display: block; width: calc(100% - 40px); margin: 0 20px 8px; border: none; cursor: pointer; padding: 15px; border-radius: 12px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-weight: 800; font-size: 14.5px; transition: 0.15s; }
.btn-pay:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(16,185,129,0.4); }
.btn-pay:disabled { opacity: 0.45; cursor: not-allowed; }
.ticket-note { text-align: center; font-size: 10.5px; color: #8a7d80; padding: 0 20px 16px; }

/* ============ THÀNH CÔNG ============ */
.pos-done-backdrop { position: fixed; inset: 0; z-index: 2000; background: rgba(15,6,8,0.55); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 20px; }
.pos-done { background: #fff; border-radius: 20px; padding: 36px; text-align: center; max-width: 380px; width: 100%; }
.done-check { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 38px; display: grid; place-items: center; margin: 0 auto 18px; animation: pop 0.4s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes pop { from { transform: scale(0); } }
.pos-done h3 { font-size: 20px; font-weight: 800; color: #1e293b; }
.pos-done p { color: #94a3b8; font-size: 13px; margin-top: 6px; }
.done-code { font-family: 'Courier New', monospace; font-size: 26px; font-weight: 800; color: #e50914; letter-spacing: 1px; margin: 6px 0 22px; }
.done-actions { display: flex; gap: 12px; }
.done-actions > * { flex: 1; }

.print-modal { background: #fff; border-radius: 18px; padding: 22px; max-width: 820px; width: 100%; max-height: 90vh; overflow-y: auto; }
.print-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.print-head h3 { font-size: 17px; font-weight: 800; color: #9b000e; }

.pos-fade-enter-active, .pos-fade-leave-active { transition: opacity 0.2s; }
.pos-fade-enter-from, .pos-fade-leave-to { opacity: 0; }
</style>
