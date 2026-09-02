<template>
  <div class="pos">
    <div class="pos-grain" aria-hidden="true"></div>

    <div class="marquee">
      <div class="bulbs"></div>
      <div class="marquee-mid">
        <span class="marquee-tag"><i class="live-dot"></i> NOW SELLING</span>
        <h2 class="marquee-title">PHÒNG VÉ <b>CINEGO</b></h2>
        <span class="marquee-sub">Box&nbsp;Office · Quầy bán vé</span>
      </div>
      <div class="bulbs"></div>
    </div>

    <div class="pos-grid">

      <div class="pos-canvas">

        <ol class="steps">
          <li
            v-for="s in steps"
            :key="s.n"
            class="step"
            :class="{ done: step > s.n, current: step === s.n, clickable: s.n < step }"
            @click="s.n < step && (step = s.n)"
          >
            <span class="step-dot">{{ step > s.n ? '✓' : s.n }}</span>
            <span class="step-label">{{ s.label }}</span>
          </li>
        </ol>

        <section v-show="step === 1" class="pane">
          <header class="pane-head"><h3>Chọn phim</h3><span class="pane-hint">Đang & sắp chiếu</span></header>
          <div v-if="loading.movies" class="state">Đang tải phim…</div>
          <div v-else class="film-grid">
            <button
              v-for="m in pagedMovies"
              :key="m.id"
              class="film"
              :class="{ active: selected.movie?.id === m.id }"
              @click="pickMovie(m)"
            >
              <div class="film-poster">
                <img :src="posterUrl(m.poster_url)" :alt="m.title" @error="onImgErr" />
                <span class="film-badge">{{ statusLabel(m.status) }}</span>
                <span class="film-pick">✓ Đã chọn</span>
              </div>
              <span class="film-title">{{ m.title }}</span>
            </button>
          </div>
          <div v-if="!loading.movies && movieTotalPages > 1" class="film-pager">
            <button class="btn-ghost sm" :disabled="moviePage === 1" @click="moviePage--">‹ Trước</button>
            <span class="pager-info">Trang {{ moviePage }} / {{ movieTotalPages }}</span>
            <button class="btn-ghost sm" :disabled="moviePage === movieTotalPages" @click="moviePage++">Sau ›</button>
          </div>
        </section>

        <section v-show="step === 2" class="pane">
          <header class="pane-head"><h3>Chọn ngày & suất</h3><span class="pane-hint">{{ selected.movie?.title }}</span></header>
          <div v-if="loading.dates" class="state">Đang tải lịch chiếu…</div>
          <template v-else>
            <div v-if="dates.length === 0" class="state">Phim này chưa có suất chiếu sắp tới.</div>
            <template v-else>
              <div class="date-row">
                <button
                  v-for="d in dates"
                  :key="d"
                  class="date-chip"
                  :class="{ active: selected.date === d }"
                  @click="pickDate(d)"
                >
                  <b>{{ dayNum(d) }}</b><span>{{ dayMon(d) }}</span>
                </button>
              </div>
              <div v-if="loading.showtimes" class="state">Đang tải suất…</div>
              <div v-else class="rooms">
                <div v-for="g in showtimeGroups" :key="g.roomId" class="room">
                  <div class="room-name">🏛️ {{ g.roomName }}</div>
                  <div class="time-row">
                    <button
                      v-for="t in g.showtimes"
                      :key="t.id"
                      class="time-chip"
                      :class="{ active: selected.showtime?.id === t.id }"
                      @click="pickShowtime(t, g)"
                    >{{ t.start_time }}</button>
                  </div>
                </div>
              </div>
            </template>
          </template>
        </section>

        <section v-show="step === 3" class="pane">
          <header class="pane-head"><h3>Chọn ghế</h3><span class="pane-hint">{{ selected.roomName }} • {{ selected.showtime?.start_time }}</span></header>
          <div v-if="loading.seats" class="state">Đang tải sơ đồ ghế…</div>
          <template v-else>
            <SeatMap
              :seats="mappedSeats"
              mode="client"
              :selectedSeatIds="selectedSeatIds"
              @seat-clicked="onSeatClick"
            />
            <p class="seat-note">
              Ghế đôi tính là 1 vé (2 chỗ ngồi liền). Quy định: không được để trống 1 ghế đơn lẻ ở giữa các ghế đã chọn hoặc sát rìa hàng ghế.
            </p>
          </template>
        </section>

        <section v-show="step === 4" class="pane">
          <header class="pane-head"><h3>Bắp nước</h3><span class="pane-hint">Tuỳ chọn — có thể bỏ qua</span></header>
          <div v-if="loading.combos" class="state">Đang tải combo…</div>
          <div v-else-if="combos.length === 0" class="state">Chưa có combo nào đang bán.</div>
          <div v-else class="combo-grid">
            <div v-for="c in combos" :key="c.id" class="combo" :class="{ off: !c.available }">
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
                <button class="qbtn plus" @click="incCombo(c)">+</button>
              </div>
            </div>
          </div>
        </section>

        <section v-show="step === 5" class="pane">
          <header class="pane-head"><h3>Khách hàng & thanh toán</h3><span class="pane-hint">Gắn khách để tích điểm</span></header>

          <div v-if="selected.customer" class="cust-chip">
            <div class="cust-ava">{{ initials(selected.customer.name) }}</div>
            <div class="cust-meta">
              <strong>{{ selected.customer.name }}</strong>
              <span>{{ selected.customer.phone || selected.customer.email }} · 🏆 {{ selected.customer.membership_tier || 'Bronze' }} · {{ selected.customer.cine_points || 0 }}đ</span>
            </div>
            <button class="cust-clear" @click="selected.customer = null">Đổi</button>
          </div>

          <template v-else>
            <div class="cust-search">
              <input v-model="custQuery" type="text" placeholder="Tìm khách theo tên / SĐT / email…" @keyup.enter="doSearchCustomer" />
              <button class="btn-mini" :disabled="loading.cust" @click="doSearchCustomer">Tìm</button>
            </div>
            <div v-if="custResults.length" class="cust-results">
              <button v-for="u in custResults" :key="u.id" class="cust-result" @click="pickCustomer(u)">
                <span class="cust-ava sm">{{ initials(u.name) }}</span>
                <span class="cust-r-meta"><strong>{{ u.name }}</strong><span>{{ u.phone || u.email }}</span></span>
                <span class="cust-r-pts">{{ u.cine_points || 0 }}đ</span>
              </button>
            </div>
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

          <div class="pay-methods">
            <div class="qc-title">Phương thức thu tiền</div>
            <div class="pay-opts">
              <button class="pay-opt" :class="{ active: payment === 'cash' }" @click="payment = 'cash'"><span class="pay-ic">💵</span> Tiền mặt</button>
              <button class="pay-opt" :class="{ active: payment === 'vnpay' }" @click="payment = 'vnpay'"><span class="pay-ic">🏦</span> VNPay (online)</button>
            </div>
            <p class="pay-hint">
              {{ payment === 'cash'
                ? 'Nhân viên thu tiền mặt và tự xác nhận. Vé + email xác nhận gửi tới email khách.'
                : 'Chuyển tới cổng VNPay. Thanh toán thành công sẽ tự hoàn tất đơn và gửi email cho khách.' }}
            </p>
          </div>
        </section>

        <div class="nav">
          <button v-if="step > 1" class="btn-ghost" @click="step--">‹ Quay lại</button>
          <span class="spacer"></span>
          <button v-if="step < 5" class="btn-next" :disabled="!canNext" @click="goNext">Tiếp tục ›</button>
        </div>
      </div>

      <aside class="ticket">
        <div class="ticket-head">
          <div class="ticket-logo">Cine<b>Go</b></div>
          <div class="ticket-admit">ADMIT&nbsp;ONE</div>
        </div>

        <div class="ticket-body">
          <div v-if="selected.movie" class="tk-movie">
            <img :src="posterUrl(selected.movie.poster_url)" :alt="selected.movie.title" @error="onImgErr" />
            <div>
              <strong>{{ selected.movie.title }}</strong>
              <p v-if="selected.showtime">{{ selected.roomName }} · {{ selected.date ? dayFull(selected.date) : '' }} {{ selected.showtime.start_time }}</p>
              <p v-else class="dim">Chưa chọn suất</p>
            </div>
          </div>
          <div v-else class="tk-empty">🎬 Chưa chọn phim</div>

          <div class="tk-rule"></div>
          <div class="tk-line"><span>Ghế ({{ selected.seats.length }})</span><b>{{ selected.seats.map(s => s.row_name + s.seat_number).join(', ') || '—' }}</b></div>
          <div class="tk-line"><span>Tiền ghế</span><b>{{ money(seatTotal) }}</b></div>

          <template v-if="comboLines.length">
            <div class="tk-rule dash"></div>
            <div class="tk-line" v-for="cl in comboLines" :key="cl.id"><span>{{ cl.name }} ×{{ cl.qty }}</span><b>{{ money(cl.price * cl.qty) }}</b></div>
          </template>

          <div class="tk-rule dash"></div>
          <div class="tk-line"><span>Khách</span><b>{{ selected.customer?.name || 'Vãng lai' }}</b></div>
          <div class="tk-line"><span>Thu tiền</span><b>{{ payment === 'cash' ? 'Tiền mặt' : 'VNPay (online)' }}</b></div>
        </div>

        <div class="ticket-perf"><span class="notch l"></span><span class="notch r"></span></div>

        <div class="ticket-stub">
          <div class="stub-total">
            <span>TỔNG CỘNG</span>
            <b>{{ money(grandTotal) }}</b>
          </div>
          <div class="barcode"></div>
          <button class="btn-pay" :disabled="!canSubmit || submitting" @click="submit">
            {{ submitting ? 'Đang xử lý…' : (payment === 'cash' ? 'THU TIỀN & TẠO VÉ' : 'THANH TOÁN VNPAY') }}
          </button>
          <p class="stub-note">Đơn tạo ở trạng thái đã thanh toán</p>
        </div>
      </aside>
    </div>

    <transition name="fade">
      <div v-if="success" class="ov-backdrop">
        <div class="success-pane">
          <div class="done-glow"></div>
          <div class="done-check">✓</div>
          <h3>Đặt vé thành công!</h3>
          <p>Mã đơn</p>
          <div class="done-code">{{ success.code }}</div>
          <div class="done-actions">
            <button class="btn-ghost" @click="resetAll">Bán đơn mới</button>
            <button class="btn-next" :disabled="loading.print" @click="openPrint">{{ loading.print ? 'Đang tải vé…' : '🖨️ Xem & In vé' }}</button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="printBooking" class="ov-backdrop light" @click.self="printBooking = null">
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
import { ref, reactive, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/axios';
import { toast } from '../../utils/alert';
import TicketPrintable from '../../components/TicketPrintable.vue';
import SeatMap from '../../components/SeatMap.vue';

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
const moviePage = ref(1);
const MOVIES_PER_PAGE = 12;
const movieTotalPages = computed(() => Math.max(1, Math.ceil(movies.value.length / MOVIES_PER_PAGE)));
const pagedMovies = computed(() => {
  const start = (moviePage.value - 1) * MOVIES_PER_PAGE;
  return movies.value.slice(start, start + MOVIES_PER_PAGE);
});
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

const money = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v || 0);
const statusLabel = (s) => (s === 'showing' || s === 'Đang chiếu' ? 'Đang chiếu' : s === 'upcoming' || s === 'Sắp chiếu' ? 'Sắp chiếu' : 'Ngừng');
const initials = (n) => { if (!n) return '👤'; const p = n.trim().split(/\s+/); return (p[0][0] + (p[p.length - 1][0] || '')).toUpperCase(); };
const onImgErr = (e) => { e.target.src = FALLBACK_IMG; };

const posterUrl = (url) => {
  if (!url) return FALLBACK_IMG;
  const last = url.lastIndexOf('http');
  if (last > 0) return url.slice(last);
  if (url.startsWith('http') || url.startsWith('blob:')) return url;
  const clean = url.replace(/^(.*\/storage\/)/, '');
  return `http://127.0.0.1:8000/storage/${clean}`;
};

const dayNum = (d) => d.slice(8, 10);
const dayMon = (d) => 'Th' + parseInt(d.slice(5, 7), 10);
const dayFull = (d) => `${d.slice(8, 10)}/${d.slice(5, 7)}/${d.slice(0, 4)}`;

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
  selected.seats = [];
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

const pickDate = async (d) => {
  selected.date = d;
  selected.showtime = null;
  selected.seats = [];
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
  if (selected.showtime?.id !== t.id) selected.seats = [];
  selected.showtime = t;
  selected.roomName = g.roomName;
};

const fetchSeats = async () => {
  loading.seats = true;
  seats.value = [];
  try {
    const res = await api.get(`/showtimes/${selected.showtime.id}/seats`);
    seats.value = res.data.seats || [];
    // Chỉ giữ ghế đang chọn thuộc đúng phòng của suất này (tránh gửi ghế sai phòng)
    const ids = new Set(seats.value.map((s) => s.id));
    selected.seats = selected.seats.filter((s) => ids.has(s.id));
  } catch (e) {
    toast('Không tải được sơ đồ ghế.', 'error');
  } finally {
    loading.seats = false;
  }
};

const mappedSeats = computed(() =>
  seats.value.map((s) => ({
    id: s.id,
    row: s.row_name,
    number: s.seat_number,
    type: s.type || 'standard',
    is_booked: s.status !== 'available',
  }))
);

const selectedSeatIds = computed(() => selected.seats.map((s) => s.id));

const onSeatClick = (mapped) => {
  const raw = seats.value.find((s) => s.id === mapped.id);
  if (!raw || raw.status !== 'available') return;
  const i = selected.seats.findIndex((s) => s.id === raw.id);
  if (i >= 0) {
    selected.seats.splice(i, 1);
  } else {
    if (selected.seats.length >= 8) {
      toast('Chỉ được chọn tối đa 8 ghế mỗi lần.', 'error');
      return;
    }
    selected.seats.push(raw);
  }
};

const validateSeatSelection = () => {
  const chosen = selected.seats;
  if (chosen.length > 8) { toast('Chỉ được chọn tối đa 8 ghế mỗi lần.', 'error'); return false; }
  if (chosen.length < 2) return true;

  const ids = new Set(chosen.map((s) => s.id));
  const rows = {};
  mappedSeats.value.forEach((seat) => {
    if (!['standard', 'vip', 'couple'].includes(seat.type)) return;
    (rows[seat.row] = rows[seat.row] || []).push(seat);
  });

  for (const key in rows) {
    const rowSeats = rows[key].sort((a, b) => parseInt(a.number) - parseInt(b.number));
    if (!rowSeats.some((s) => ids.has(s.id))) continue;

    for (let i = 0; i < rowSeats.length; i++) {
      const cur = rowSeats[i];
      if (cur.is_booked || ids.has(cur.id)) continue;

      const L = i > 0 ? rowSeats[i - 1] : null;
      const R = i < rowSeats.length - 1 ? rowSeats[i + 1] : null;
      const leftEmpty = L && !L.is_booked && !ids.has(L.id);
      const rightEmpty = R && !R.is_booked && !ids.has(R.id);
      const isolated = !leftEmpty && !rightEmpty;

      if (isolated) {
        const leftCaused = L && ids.has(L.id);
        const rightCaused = R && ids.has(R.id);
        if (leftCaused || rightCaused) {
          toast('Không được để trống 1 ghế đơn lẻ ở giữa các ghế đã chọn hoặc sát rìa hàng ghế.', 'error');
          return false;
        }
      }
    }
  }
  return true;
};

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

const seatTotal = computed(() => selected.seats.reduce((s, x) => s + (Number(x.price) || 0), 0));
const comboTotal = computed(() => comboLines.value.reduce((s, x) => s + x.price * x.qty, 0));
const grandTotal = computed(() => seatTotal.value + comboTotal.value);

const canNext = computed(() => {
  if (step.value === 1) return !!selected.movie;
  if (step.value === 2) return !!selected.showtime;
  if (step.value === 3) return selected.seats.length > 0;
  return true;
});

const goNext = async () => {
  if (!canNext.value) return;
  if (step.value === 2) { step.value = 3; await fetchSeats(); return; }
  if (step.value === 3) {
    if (!validateSeatSelection()) return;
    step.value = 4; if (!combos.value.length) await fetchCombos(); return;
  }
  step.value++;
};

const canSubmit = computed(() => selected.showtime && selected.seats.length > 0 && selected.customer);

const submit = async () => {
  if (!canSubmit.value) {
    toast('Cần chọn ghế và gán khách hàng trước khi thu tiền.', 'error');
    return;
  }
  if (!validateSeatSelection()) return;
  submitting.value = true;
  try {
    const base = {
      showtime_id: selected.showtime.id,
      seat_ids: selected.seats.map((s) => s.id),
      combos: comboLines.value.map((c) => ({ id: c.id, quantity: c.qty })),
      customer_id: selected.customer.id,
      total_amount: grandTotal.value,
    };

    if (payment.value === 'vnpay') {

      const res = await api.post('/staff/bookings/pos/vnpay', base);
      if (res.data?.payment_url) {
        window.location.href = res.data.payment_url;
        return;
      }
      toast('Không tạo được link thanh toán VNPay.', 'error');
    } else {

      const res = await api.post('/staff/bookings/pos', { ...base, payment_method: 'cash' });
      success.value = { code: res.data.booking_code };
      clearPersist();
    }
  } catch (e) {
    toast(e.response?.data?.message || 'Xử lý thất bại. Vui lòng thử lại.', 'error');
  } finally {
    submitting.value = false;
  }
};

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

const resetAll = () => {
  step.value = 1;
  moviePage.value = 1;
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
  clearPersist();
};

const route = useRoute();
const router = useRouter();
const STORE_KEY = 'staff_pos_state';

const persist = () => {
  try {
    localStorage.setItem(STORE_KEY, JSON.stringify({
      step: step.value,
      movie: selected.movie,
      date: selected.date,
      showtime: selected.showtime,
      roomName: selected.roomName,
      seats: selected.seats,
      customer: selected.customer,
      comboQty: { ...comboQty },
      payment: payment.value,
      moviePage: moviePage.value,
    }));
  } catch (e) { /* bỏ qua nếu localStorage lỗi */ }
};

const clearPersist = () => {
  try { localStorage.removeItem(STORE_KEY); } catch (e) { /* noop */ }
};

const restore = async () => {
  let s = null;
  try { s = JSON.parse(localStorage.getItem(STORE_KEY) || 'null'); } catch (e) { s = null; }
  if (!s || !s.movie) return;

  selected.movie = s.movie;
  selected.date = s.date;
  selected.showtime = s.showtime;
  selected.roomName = s.roomName || '';
  selected.seats = Array.isArray(s.seats) ? s.seats : [];
  selected.customer = s.customer || null;
  payment.value = s.payment || 'cash';
  moviePage.value = s.moviePage || 1;
  Object.keys(comboQty).forEach((k) => delete comboQty[k]);
  Object.assign(comboQty, s.comboQty || {});
  step.value = s.step || 1;

  try {
    if (selected.movie && step.value >= 2) {
      const dRes = await api.get(`/movies/${selected.movie.id}/available-dates`);
      dates.value = dRes.data.data || [];
      if (selected.date) {
        const sRes = await api.get(`/movies/${selected.movie.id}/showtimes`, { params: { date: selected.date } });
        showtimeGroups.value = sRes.data.data || [];
      }
    }
    if (selected.showtime && step.value >= 3) {
      await fetchSeats();
      const avail = new Set(seats.value.filter((x) => x.status === 'available').map((x) => x.id));
      selected.seats = selected.seats.filter((x) => avail.has(x.id));
    }
    if (step.value >= 4 && !combos.value.length) {
      await fetchCombos();
    }
  } catch (e) { /* nếu nạp lại lỗi thì vẫn giữ được bước, chỉ thiếu dữ liệu */ }
};

let ready = false;
watch([step, selected, comboQty, payment, moviePage], () => { if (ready) persist(); }, { deep: true });

(async () => {
  await fetchMovies();
  if (route.query.pos_pay) {
    clearPersist();
    if (route.query.pos_pay === 'success' && route.query.code) {
      success.value = { code: String(route.query.code) };
    } else {
      toast('Thanh toán VNPay không thành công hoặc đã huỷ. Vui lòng thử lại.', 'error');
    }
    // Bỏ query khỏi URL để reload sau không hiện lại popup cũ / không xoá nhầm việc đang làm
    router.replace({ path: route.path }).catch(() => {});
  } else {
    await restore();
  }
  ready = true;
})();
</script>

<style scoped>
.pos {
  --ink: #120c0e;
  --ink-2: #1b1315;
  --line: rgba(255, 255, 255, 0.09);
  --red: #e50914;
  --red-deep: #9b000e;
  --gold: #f5c249;
  --gold-soft: #ffe4a8;
  --txt: #f4eef0;
  --muted: #a89a9e;
  position: relative;
  border-radius: 22px;
  padding: 0 0 24px;
  background:
    radial-gradient(1200px 420px at 50% -80px, rgba(229, 9, 20, 0.16), transparent 70%),
    radial-gradient(700px 500px at 100% 0%, rgba(245, 194, 73, 0.08), transparent 70%),
    linear-gradient(180deg, #17100f, #0d090a 60%);
  color: var(--txt);
  overflow: hidden;
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.pos-grain {
  position: absolute; inset: 0; pointer-events: none; opacity: 0.05; z-index: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
.pos > * { position: relative; z-index: 1; }

.marquee { display: flex; align-items: stretch; gap: 14px; padding: 20px 26px 4px; }
.bulbs {
  flex: 1; align-self: center; height: 10px; border-radius: 6px;
  background-image: radial-gradient(circle, var(--gold-soft) 0 2px, transparent 2.6px);
  background-size: 18px 10px; background-position: center;
  filter: drop-shadow(0 0 4px rgba(245, 194, 73, 0.7));
  opacity: 0.9; animation: bulbs 1.4s steps(2) infinite;
}
@keyframes bulbs { 50% { opacity: 0.45; } }
.marquee-mid { text-align: center; flex-shrink: 0; }
.marquee-tag { font-size: 10.5px; font-weight: 800; letter-spacing: 3px; color: var(--gold); display: inline-flex; align-items: center; gap: 6px; }
.live-dot { width: 7px; height: 7px; border-radius: 50%; background: #ff5a5f; box-shadow: 0 0 8px #ff5a5f; animation: blink 1.2s infinite; }
@keyframes blink { 50% { opacity: 0.3; } }
.marquee-title { font-size: 26px; font-weight: 900; letter-spacing: 4px; margin: 2px 0; text-shadow: 0 2px 18px rgba(229, 9, 20, 0.5); }
.marquee-title b { color: var(--red); }
.marquee-sub { font-size: 11px; color: var(--muted); letter-spacing: 2px; }

.pos-grid { display: grid; grid-template-columns: 1fr 360px; gap: 22px; padding: 12px 24px 0; align-items: start; }
@media (max-width: 1120px) { .pos-grid { grid-template-columns: 1fr; } }

.pos-canvas {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.045), rgba(255, 255, 255, 0.02));
  border: 1px solid var(--line); border-radius: 18px; padding: 22px 22px 18px;
  backdrop-filter: blur(6px);
}

.steps { display: flex; list-style: none; padding: 0; margin: 0 0 20px; gap: 2px; flex-wrap: nowrap; overflow-x: auto; position: relative; scrollbar-width: none; }
.steps::-webkit-scrollbar { display: none; }
.step { display: flex; align-items: center; gap: 6px; padding: 4px 10px 4px 4px; border-radius: 999px; opacity: 0.5; transition: 0.25s; flex-shrink: 0; }
.step.current { opacity: 1; background: rgba(229, 9, 20, 0.14); box-shadow: inset 0 0 0 1px rgba(229, 9, 20, 0.35); }
.step.done { opacity: 1; }
.step.clickable { cursor: pointer; }
.step-dot { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; font-size: 10px; font-weight: 900; color: #2a1416; background: #5b4a4d; flex-shrink: 0; transition: 0.25s; }
.step.current .step-dot { background: linear-gradient(135deg, var(--gold), #e0a63a); color: #3a2500; box-shadow: 0 0 12px rgba(245, 194, 73, 0.6); }
.step.done .step-dot { background: linear-gradient(135deg, var(--red), var(--red-deep)); color: #fff; }
.step-label { font-size: 11px; font-weight: 800; white-space: nowrap; }

.pane-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 18px; border-bottom: 1px solid var(--line); padding-bottom: 12px; }
.pane-head h3 { font-size: 19px; font-weight: 900; letter-spacing: 0.3px; }
.pane-hint { font-size: 12.5px; color: var(--muted); }
.state { padding: 44px; text-align: center; color: var(--muted); font-weight: 600; }

.film-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(126px, 1fr)); gap: 16px; }
.film { border: none; background: transparent; cursor: pointer; padding: 0; text-align: left; display: flex; flex-direction: column; gap: 9px; }
.film-poster { position: relative; border-radius: 14px; overflow: hidden; aspect-ratio: 2/3; border: 2px solid transparent; box-shadow: 0 10px 24px rgba(0, 0, 0, 0.45); transition: 0.25s; }
.film-poster::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 55%, rgba(0, 0, 0, 0.55)); }
.film:hover .film-poster { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(0, 0, 0, 0.6); }
.film.active .film-poster { border-color: var(--gold); box-shadow: 0 0 0 1px var(--gold), 0 14px 32px rgba(245, 194, 73, 0.35); }
.film-poster img { width: 100%; height: 100%; object-fit: cover; display: block; }
.film-badge { position: absolute; top: 8px; left: 8px; z-index: 2; background: rgba(0, 0, 0, 0.7); color: var(--gold-soft); font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; letter-spacing: 0.3px; }
.film-pick { position: absolute; bottom: 8px; left: 8px; z-index: 2; font-size: 11px; font-weight: 800; color: #fff; opacity: 0; transform: translateY(4px); transition: 0.2s; }
.film.active .film-pick { opacity: 1; transform: none; }
.film-title { font-size: 13px; font-weight: 700; line-height: 1.3; color: var(--txt); }

.date-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 22px; }
.date-chip { border: 1px solid var(--line); background: rgba(255, 255, 255, 0.03); color: var(--txt); border-radius: 13px; padding: 9px 15px; cursor: pointer; display: flex; flex-direction: column; align-items: center; min-width: 60px; transition: 0.18s; }
.date-chip b { font-size: 19px; font-weight: 900; }
.date-chip span { font-size: 11px; color: var(--muted); }
.date-chip:hover { border-color: rgba(245, 194, 73, 0.5); }
.date-chip.active { border-color: transparent; background: linear-gradient(135deg, var(--red), var(--red-deep)); box-shadow: 0 8px 18px rgba(229, 9, 20, 0.4); }
.date-chip.active span { color: #ffdadd; }

.room { margin-bottom: 20px; }
.room-name { font-size: 13px; font-weight: 800; color: var(--gold-soft); margin-bottom: 10px; }
.time-row { display: flex; gap: 10px; flex-wrap: wrap; }
.time-chip { border: 1px solid var(--line); background: rgba(255, 255, 255, 0.03); color: var(--txt); border-radius: 11px; padding: 10px 20px; font-weight: 800; font-size: 14px; cursor: pointer; transition: 0.18s; }
.time-chip:hover { border-color: rgba(245, 194, 73, 0.5); transform: translateY(-1px); }
.time-chip.active { border-color: var(--gold); color: var(--gold-soft); background: rgba(245, 194, 73, 0.12); box-shadow: 0 0 14px rgba(245, 194, 73, 0.25); }

.stage { position: relative; margin: 4px 0 34px; height: 74px; }
.beam { position: absolute; top: 20px; left: 50%; transform: translateX(-50%); width: 78%; height: 200px; background: linear-gradient(180deg, rgba(245, 194, 73, 0.22), transparent 68%); clip-path: polygon(28% 0, 72% 0, 100% 100%, 0 100%); pointer-events: none; }
.screen { position: relative; height: 30px; border-radius: 60% 60% 10px 10px / 100% 100% 10px 10px; background: linear-gradient(180deg, #f4eef0, #b9b1b3 60%, #6f696b); box-shadow: 0 -3px 34px rgba(245, 194, 73, 0.55), 0 6px 16px rgba(0, 0, 0, 0.5); display: grid; place-items: center; }
.screen span { font-size: 11px; font-weight: 900; letter-spacing: 8px; color: #2a2224; }

.seatmap { display: flex; flex-direction: column; gap: 5px; align-items: center; overflow-x: auto; padding-bottom: 6px; }
.seat-row { display: flex; align-items: center; gap: 7px; }
.row-label { width: 13px; text-align: center; font-size: 9px; font-weight: 800; color: var(--muted); }
.seat-line { display: flex; gap: 4px; }
.seat {
  width: 23px; height: 23px; border: none; cursor: pointer; font-size: 8px; font-weight: 800; color: #2a2224;
  border-radius: 5px 5px 4px 4px; transition: 0.12s; position: relative;
  background: #4a4145; color: #2a2224;
  box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.25);
}
.seat--standard { background: #8fa4e6; }
.seat--vip { background: #e9b866; }
.seat--couple { background: #e884b7; }
.seat:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.08); }
.seat.sold, .seat.holding, .seat.broken { background: #362d31; color: #5a4e52; cursor: not-allowed; box-shadow: none; }
.seat.broken { background: repeating-linear-gradient(45deg, #362d31, #362d31 3px, #241d20 3px, #241d20 6px); }
.seat.picked { background: linear-gradient(135deg, var(--gold), #e0a63a); color: #3a2500; box-shadow: 0 0 12px rgba(245, 194, 73, 0.7), inset 0 -2px 0 rgba(0, 0, 0, 0.2); transform: translateY(-2px); }

.legend { display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; margin-top: 22px; font-size: 12px; color: var(--muted); }
.legend .dot { width: 13px; height: 13px; border-radius: 4px; display: inline-block; margin-right: 5px; vertical-align: -2px; }
.lg-avail { background: #4a4145; } .lg-standard { background: #8fa4e6; } .lg-vip { background: #e9b866; }
.lg-couple { background: #e884b7; } .lg-sold { background: #362d31; } .lg-picked { background: linear-gradient(135deg, var(--gold), #e0a63a); }

.combo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(196px, 1fr)); gap: 16px; }
.combo { border: 1px solid var(--line); border-radius: 15px; overflow: hidden; display: flex; flex-direction: column; background: rgba(255, 255, 255, 0.03); transition: 0.18s; }
.combo:hover { border-color: rgba(245, 194, 73, 0.4); transform: translateY(-2px); box-shadow: 0 12px 26px rgba(0, 0, 0, 0.4); }
.combo.off { opacity: 0.5; }
.combo-thumb { position: relative; width: 100%; height: 140px; background: #241d20; overflow: hidden; }
.combo-thumb img { display: block; width: 100%; height: 100%; object-fit: cover; }
.combo-out { position: absolute; inset: 0; display: grid; place-items: center; background: rgba(10, 6, 7, 0.62); color: var(--gold-soft); font-weight: 800; font-size: 13px; letter-spacing: 1px; }
.combo-info { padding: 11px 13px 4px; display: flex; flex-direction: column; gap: 3px; }
.combo-info strong { font-size: 13.5px; }
.combo-price { color: var(--gold); font-weight: 800; font-size: 13px; }
.combo-qty { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 12px; }
.qbtn { width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--line); background: rgba(255, 255, 255, 0.05); font-size: 18px; font-weight: 800; cursor: pointer; color: var(--gold-soft); transition: 0.15s; }
.qbtn.plus:hover { background: rgba(245, 194, 73, 0.16); border-color: var(--gold); }
.qbtn:disabled { opacity: 0.35; cursor: not-allowed; }
.qnum { font-weight: 900; min-width: 18px; text-align: center; font-size: 15px; }

.cust-chip { display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.04); border: 1px solid var(--line); border-radius: 14px; padding: 13px 15px; }
.cust-ava { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; font-weight: 900; color: #3a2500; background: linear-gradient(135deg, var(--gold), #e0a63a); flex-shrink: 0; }
.cust-ava.sm { width: 32px; height: 32px; font-size: 12px; }
.cust-meta { flex: 1; display: flex; flex-direction: column; }
.cust-meta span { font-size: 12px; color: var(--muted); }
.cust-clear { border: 1px solid var(--line); background: rgba(255, 255, 255, 0.05); color: var(--txt); border-radius: 9px; padding: 7px 14px; font-weight: 700; font-size: 12.5px; cursor: pointer; }
.cust-clear:hover { border-color: var(--red); color: #ff8a90; }

.cust-search { display: flex; gap: 10px; margin-bottom: 12px; }
.cust-search input, .qc-grid input {
  flex: 1; width: 100%; border: 1px solid var(--line); border-radius: 11px; padding: 12px 15px; font-size: 14px; outline: none;
  background: rgba(255, 255, 255, 0.04); color: var(--txt);
}
.cust-search input::placeholder, .qc-grid input::placeholder { color: #7d7175; }
.cust-search input:focus, .qc-grid input:focus { border-color: var(--gold); background: rgba(255, 255, 255, 0.06); }
.btn-mini { border: 1px solid var(--gold); background: rgba(245, 194, 73, 0.12); color: var(--gold-soft); border-radius: 11px; padding: 0 20px; font-weight: 800; cursor: pointer; }
.btn-mini.solid { background: linear-gradient(135deg, var(--red), var(--red-deep)); color: #fff; border: none; padding: 12px 18px; margin-top: 12px; }
.btn-mini:disabled { opacity: 0.5; cursor: not-allowed; }

.cust-results { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.cust-result { display: flex; align-items: center; gap: 11px; border: 1px solid var(--line); background: rgba(255, 255, 255, 0.03); border-radius: 11px; padding: 9px 13px; cursor: pointer; text-align: left; transition: 0.15s; }
.cust-result:hover { border-color: rgba(245, 194, 73, 0.5); background: rgba(245, 194, 73, 0.08); }
.cust-r-meta { flex: 1; display: flex; flex-direction: column; }
.cust-r-meta span { font-size: 12px; color: var(--muted); }
.cust-r-meta strong { font-size: 13.5px; }
.cust-r-pts { font-weight: 900; color: var(--gold); font-size: 12.5px; }

.quick-create { border-top: 1px dashed var(--line); padding-top: 18px; }
.qc-title { font-size: 13px; font-weight: 800; color: var(--gold-soft); margin-bottom: 11px; }
.qc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
@media (max-width: 640px) { .qc-grid { grid-template-columns: 1fr; } }

.pay-methods { margin-top: 24px; }
.pay-opts { display: flex; gap: 12px; }
.pay-opt { flex: 1; border: 1px solid var(--line); background: rgba(255, 255, 255, 0.03); color: var(--txt); border-radius: 13px; padding: 16px; font-weight: 800; font-size: 14px; cursor: pointer; transition: 0.18s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.pay-opt:hover { border-color: rgba(245, 194, 73, 0.4); }
.pay-opt.active { border-color: var(--gold); background: rgba(245, 194, 73, 0.14); color: var(--gold-soft); box-shadow: 0 0 16px rgba(245, 194, 73, 0.2); }
.pay-ic { font-size: 18px; }
.pay-hint { margin-top: 10px; font-size: 12px; color: var(--muted); line-height: 1.5; }

.nav { display: flex; align-items: center; margin-top: 26px; padding-top: 18px; border-top: 1px solid var(--line); }
.spacer { flex: 1; }
.btn-ghost { border: 1px solid var(--line); background: rgba(255, 255, 255, 0.04); color: var(--txt); padding: 12px 22px; border-radius: 11px; font-weight: 800; cursor: pointer; transition: 0.15s; }
.btn-ghost:hover { border-color: rgba(255, 255, 255, 0.3); }
.btn-ghost.sm { padding: 8px 16px; font-size: 13px; }

.film-pager { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 20px; }
.pager-info { font-size: 13px; font-weight: 700; color: var(--muted); }

.seat-note { text-align: center; font-size: 12px; color: var(--muted); margin-top: 14px; line-height: 1.5; }
.btn-next { border: none; background: linear-gradient(135deg, var(--red), var(--red-deep)); color: #fff; padding: 12px 30px; border-radius: 11px; font-weight: 900; cursor: pointer; box-shadow: 0 8px 20px rgba(229, 9, 20, 0.35); transition: 0.15s; }
.btn-next:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(229, 9, 20, 0.5); }
.btn-next:disabled { opacity: 0.4; cursor: not-allowed; box-shadow: none; }

.ticket {
  position: sticky; top: 16px;
  background: linear-gradient(180deg, #241a1c, #17100f);
  border: 1px solid rgba(245, 194, 73, 0.22); border-radius: 18px; overflow: hidden;
  box-shadow: 0 24px 50px rgba(0, 0, 0, 0.5);
}
.ticket-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: linear-gradient(120deg, var(--red-deep), var(--red)); position: relative; }
.ticket-head::after { content: ''; position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255, 255, 255, 0.25) 0 1px, transparent 1.6px); background-size: 12px 12px; opacity: 0.18; }
.ticket-logo { font-size: 21px; font-weight: 900; font-family: 'Arial Narrow', sans-serif; letter-spacing: -0.5px; }
.ticket-logo b { color: var(--gold-soft); }
.ticket-admit { font-size: 11px; font-weight: 900; letter-spacing: 2px; color: #fff; border: 1.5px dashed rgba(255, 255, 255, 0.7); padding: 4px 9px; border-radius: 6px; }

.ticket-body { padding: 18px 20px; }
.tk-movie { display: flex; gap: 12px; margin-bottom: 14px; }
.tk-movie img { width: 48px; height: 70px; object-fit: cover; border-radius: 7px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5); }
.tk-movie strong { font-size: 14px; display: block; }
.tk-movie p { font-size: 11.5px; color: #c7bcbe; margin-top: 4px; }
.dim { color: #8a7d80 !important; }
.tk-empty { text-align: center; color: #8a7d80; padding: 16px 0; font-size: 13px; }

.tk-rule { height: 1px; background: rgba(255, 255, 255, 0.12); margin: 13px 0; }
.tk-rule.dash { background: repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.22) 0 5px, transparent 5px 10px); }
.tk-line { display: flex; justify-content: space-between; gap: 12px; font-size: 12.5px; margin: 7px 0; }
.tk-line span { color: #b6abad; }
.tk-line b { text-align: right; font-weight: 700; }

.ticket-perf { position: relative; height: 20px; background: repeating-linear-gradient(90deg, rgba(245, 194, 73, 0.35) 0 6px, transparent 6px 12px); }
.notch { position: absolute; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; border-radius: 50%; background: #0d090a; }
.notch.l { left: -10px; } .notch.r { right: -10px; }

.ticket-stub { padding: 16px 20px 18px; background: linear-gradient(180deg, #1d1517, #17100f); }
.stub-total { display: flex; justify-content: space-between; align-items: baseline; font-family: 'Courier New', monospace; }
.stub-total span { font-size: 12px; letter-spacing: 1px; color: #c7bcbe; }
.stub-total b { font-size: 24px; font-weight: 900; color: var(--gold-soft); }
.barcode { height: 42px; margin: 14px 0; border-radius: 4px; background-color: #f4eef0; background-image: repeating-linear-gradient(90deg, #16100f 0 2px, transparent 2px 4px, #16100f 4px 7px, transparent 7px 9px, #16100f 9px 11px, transparent 11px 15px); }
.btn-pay { display: block; width: 100%; border: none; cursor: pointer; padding: 15px; border-radius: 12px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-weight: 900; font-size: 14.5px; letter-spacing: 0.5px; transition: 0.15s; }
.btn-pay:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(16, 185, 129, 0.45); }
.btn-pay:disabled { opacity: 0.4; cursor: not-allowed; }
.stub-note { text-align: center; font-size: 10.5px; color: #8a7d80; margin-top: 10px; }

.ov-backdrop { position: fixed; inset: 0; z-index: 2000; background: rgba(10, 6, 7, 0.66); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; padding: 20px; }
.success-pane { position: relative; background: linear-gradient(180deg, #241a1c, #160f11); border: 1px solid rgba(245, 194, 73, 0.25); border-radius: 22px; padding: 40px 36px; text-align: center; max-width: 390px; width: 100%; overflow: hidden; }
.done-glow { position: absolute; top: -60px; left: 50%; transform: translateX(-50%); width: 240px; height: 240px; background: radial-gradient(circle, rgba(16, 185, 129, 0.35), transparent 70%); }
.done-check { position: relative; width: 76px; height: 76px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 40px; display: grid; place-items: center; margin: 0 auto 18px; box-shadow: 0 0 30px rgba(16, 185, 129, 0.6); animation: pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes pop { from { transform: scale(0); } }
.done h3 { position: relative; font-size: 21px; font-weight: 900; color: var(--txt); }
.done p { position: relative; color: var(--muted); font-size: 13px; margin-top: 6px; }
.done-code { position: relative; font-family: 'Courier New', monospace; font-size: 27px; font-weight: 900; color: var(--gold-soft); letter-spacing: 2px; margin: 6px 0 24px; }
.done-actions { position: relative; display: flex; gap: 12px; }
.done-actions > * { flex: 1; }

.ov-backdrop.light { background: rgba(15, 6, 8, 0.5); }
.print-modal { background: #fff; border-radius: 18px; padding: 22px; max-width: 820px; width: 100%; max-height: 90vh; overflow-y: auto; color: #1e293b; }
.print-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.print-head h3 { font-size: 17px; font-weight: 800; color: #9b000e; }
.print-modal .cust-clear { border: 1px solid #e2e8f0; background: #fff; color: #475569; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
