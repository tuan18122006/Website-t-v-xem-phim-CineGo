<template>
  <div class="tp">
    <!-- Thanh công cụ: chọn kiểu vé + in (không in ra giấy) -->
    <div class="tp-toolbar tp-noprint">
      <div class="tp-switch">
        <button
          v-for="v in variants"
          :key="v.id"
          type="button"
          :class="{ active: variant === v.id }"
          @click="variant = v.id"
        >{{ v.label }}</button>
      </div>
      <button type="button" class="tp-print" @click="doPrint">🖨️ In vé</button>
    </div>

    <!-- Khu vực vé (được in ra giấy) -->
    <div class="tp-region">
      <!-- ============ MẪU 1 — CUỐNG VÉ KÉP (CGV) ============ -->
      <div v-if="variant === 1" class="tp-stack">
        <article v-for="(s, i) in booking.seats" :key="'m1' + i" class="t1-movie tp-ticket">
          <div class="t1-stub">
            <span class="t1-stub__label">GHẾ</span>
            <span class="t1-stub__seat">{{ s.label }}</span>
            <span class="t1-stub__type">{{ typeLabel(s.type) }}</span>
          </div>
          <div class="t1-body">
            <div class="t1-brand">
              <span class="t1-logo">Cine<b>Go</b></span>
              <small>VÉ XEM PHIM</small>
            </div>
            <div class="t1-title">{{ booking.movie.title }}</div>
            <div class="t1-grid">
              <div class="t1-cell"><span>Rạp / Phòng</span><b>{{ booking.room_name }}</b></div>
              <div class="t1-cell"><span>Suất chiếu</span><b>{{ booking.showtime_at }}</b></div>
              <div class="t1-cell"><span>Định dạng</span><b>{{ booking.format }} · {{ booking.translation }}</b></div>
              <div class="t1-cell"><span>Giá vé</span><b>{{ formatCurrency(s.price) }}</b></div>
            </div>
            <div class="t1-foot">
              <div class="tp-bc"><span v-for="(bar, bi) in makeBars(s.ticket_code)" :key="bi" :style="barStyle(bar)"></span></div>
              <div class="t1-code">{{ s.ticket_code }}<small>{{ booking.booking_code }}</small></div>
            </div>
          </div>
        </article>

        <article v-for="(c, i) in booking.combos" :key="'c1' + i" class="t1-combo tp-ticket">
          <div class="t1-combo__top"><b>VÉ BẮP NƯỚC</b><span>🍿</span></div>
          <div class="t1-combo__mid">
            <div class="t1-combo__name">{{ c.name }}</div>
            <div class="t1-combo__line"><span>Số lượng</span><b>× {{ c.quantity }}</b></div>
            <div class="t1-combo__line"><span>Thành tiền</span><b>{{ formatCurrency(c.price * c.quantity) }}</b></div>
          </div>
          <div class="t1-combo__bc"><div class="tp-bc"><span v-for="(bar, bi) in makeBars(comboCode(i))" :key="bi" :style="barStyle(bar)"></span></div></div>
        </article>
      </div>

      <!-- ============ MẪU 2 — BOARDING PASS ============ -->
      <div v-else-if="variant === 2" class="tp-stack">
        <article v-for="(s, i) in booking.seats" :key="'m2' + i" class="t2-movie tp-ticket">
          <div class="t2-main">
            <div class="t2-head">
              <span class="t2-brand">Cine<b>Go</b></span>
              <span class="t2-tag">Movie Boarding Pass</span>
            </div>
            <div class="t2-route">
              <span class="t2-route__movie">{{ booking.movie.title }}</span>
              <span class="t2-route__dash"></span>
              <span class="t2-route__ico">🎬</span>
              <span class="t2-route__dash"></span>
              <span class="t2-route__movie t2-route__room">{{ booking.room_name }}</span>
            </div>
            <div class="t2-info">
              <div><span>Ngày</span><b>{{ stDate }}</b></div>
              <div><span>Giờ</span><b>{{ stTime }}</b></div>
              <div><span>Định dạng</span><b>{{ booking.format }}</b></div>
              <div><span>Giá</span><b>{{ formatCurrency(s.price) }}</b></div>
            </div>
            <div class="t2-passenger">
              <span>Khách hàng: <b>{{ booking.customer.name || '—' }}</b></span>
              <span>Loại ghế: <b>{{ typeLabel(s.type) }}</b></span>
            </div>
          </div>
          <div class="t2-stub">
            <div class="t2-stub__seat"><span>GHẾ</span><b>{{ s.label }}</b></div>
            <div class="t2-stub__bc"><div class="tp-bc tp-bc--v"><span v-for="(bar, bi) in makeBars(s.ticket_code)" :key="bi" :style="barStyleV(bar)"></span></div></div>
            <div class="t2-stub__code">{{ s.ticket_code }}</div>
          </div>
        </article>

        <article v-for="(c, i) in booking.combos" :key="'c2' + i" class="t2-combo tp-ticket">
          <div class="t2-combo__main">
            <span class="t2-combo__tag">Snack Pass</span>
            <span class="t2-combo__name">{{ c.name }}</span>
            <div class="t2-combo__row"><span>Số lượng</span><b>× {{ c.quantity }}</b></div>
            <div class="t2-combo__row"><span>Thành tiền</span><b>{{ formatCurrency(c.price * c.quantity) }}</b></div>
            <div class="t2-combo__row"><span>Mã</span><b>{{ comboCode(i) }}</b></div>
          </div>
          <div class="t2-combo__stub"><b>🍿 BẮP NƯỚC</b></div>
        </article>
      </div>

      <!-- ============ MẪU 3 — HÓA ĐƠN NHIỆT ============ -->
      <div v-else class="tp-stack">
        <article v-for="(s, i) in booking.seats" :key="'m3' + i" class="t3-movie tp-ticket">
          <div class="t3-center">
            <div class="t3-logo">Cine<b>Go</b></div>
            <div class="t3-sub">{{ booking.room_name }}</div>
          </div>
          <hr class="t3-rule" />
          <div class="t3-title">{{ booking.movie.title }}</div>
          <div class="t3-seatbig">GHẾ {{ s.label }}</div>
          <hr class="t3-rule" />
          <div class="t3-line"><span>Suất chiếu</span><b>{{ booking.showtime_at }}</b></div>
          <div class="t3-line"><span>Định dạng</span><b>{{ booking.format }} · {{ booking.translation }}</b></div>
          <div class="t3-line"><span>Loại ghế</span><b>{{ typeLabel(s.type) }}</b></div>
          <div class="t3-line"><span>Khách hàng</span><b>{{ booking.customer.name || '—' }}</b></div>
          <hr class="t3-rule" />
          <div class="t3-total"><span>GIÁ VÉ</span><span>{{ formatCurrency(s.price) }}</span></div>
          <div class="tp-bc t3-bc"><span v-for="(bar, bi) in makeBars(s.ticket_code)" :key="bi" :style="barStyle(bar)"></span></div>
          <div class="t3-code">* {{ s.ticket_code }} *</div>
          <div class="t3-foot">Mã đơn {{ booking.booking_code }}<br />Xuất trình vé tại cửa soát vé</div>
        </article>

        <article v-for="(c, i) in booking.combos" :key="'c3' + i" class="t3-combo tp-ticket">
          <div class="t3-center">
            <div class="t3-logo">Cine<b>Go</b></div>
            <div class="t3-sub">PHIẾU BẮP NƯỚC · QUẦY F&amp;B</div>
          </div>
          <hr class="t3-rule" />
          <div class="t3-title">🍿 {{ c.name }}</div>
          <hr class="t3-rule" />
          <div class="t3-line"><span>Số lượng</span><b>× {{ c.quantity }}</b></div>
          <hr class="t3-rule" />
          <div class="t3-total"><span>THÀNH TIỀN</span><span>{{ formatCurrency(c.price * c.quantity) }}</span></div>
          <div class="tp-bc t3-bc"><span v-for="(bar, bi) in makeBars(comboCode(i))" :key="bi" :style="barStyle(bar)"></span></div>
          <div class="t3-code">* {{ comboCode(i) }} *</div>
          <div class="t3-foot">Đổi phiếu tại quầy bắp nước<br />Cảm ơn quý khách!</div>
        </article>
      </div>

      <p v-if="!booking.combos.length" class="tp-note tp-noprint">
        🍿 Đơn này không có vé bắp nước.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  booking: { type: Object, required: true },
});

const variants = [
  { id: 1, label: 'Kiểu CGV' },
  { id: 2, label: 'Boarding pass' },
  { id: 3, label: 'Hóa đơn nhiệt' },
];
const variant = ref(1);

const formatCurrency = (val) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);

const typeLabel = (t) => ({ standard: 'Thường', vip: 'VIP', couple: 'Đôi' }[t] || t);

// Tách "19:30 - 08/07/2026" thành giờ và ngày để hiển thị trên vé
const stTime = computed(() => (props.booking.showtime_at || '').split(' - ')[0] || '—');
const stDate = computed(() => (props.booking.showtime_at || '').split(' - ')[1] || '—');

// Mã cho phiếu bắp nước (combo không có sẵn mã riêng trong dữ liệu)
const comboCode = (i) => `${props.booking.booking_code}-C${i + 1}`;

// Sinh mã vạch giả (barcode) từ chuỗi mã: các thanh đen/trắng xen kẽ, độ rộng thay đổi ổn định.
const makeBars = (code) => {
  const s = String(code || 'CINEGO');
  let h = 2166136261;
  for (let i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); }
  const rng = () => { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return ((h >>> 0) % 1000) / 1000; };
  const widths = [1, 1, 2, 2, 3];
  const arr = [];
  for (let i = 0; i < 44; i++) arr.push({ w: widths[Math.floor(rng() * widths.length)], black: i % 2 === 0 });
  return arr;
};
const barStyle = (bar) => ({ width: (bar.w * 1.4).toFixed(1) + 'px', background: bar.black ? '#111' : 'transparent' });
const barStyleV = (bar) => ({ height: (bar.w * 1.4).toFixed(1) + 'px', width: '100%', background: bar.black ? '#111' : 'transparent' });

// In: chỉ khu vực vé được in (xem style @media print bên dưới)
const doPrint = () => {
  document.body.classList.add('tp-printing');
  const cleanup = () => {
    document.body.classList.remove('tp-printing');
    window.removeEventListener('afterprint', cleanup);
  };
  window.addEventListener('afterprint', cleanup);
  window.print();
  setTimeout(cleanup, 1200);
};
</script>

<!-- ===== Style TOÀN CỤC chỉ để in (không scoped để isolate vùng in) ===== -->
<style>
@media print {
  @page { margin: 8mm; }
  body.tp-printing * { visibility: hidden !important; }
  body.tp-printing .tp-region,
  body.tp-printing .tp-region * { visibility: visible !important; }
  body.tp-printing .tp-region {
    position: fixed; left: 0; top: 0; width: 100%;
    margin: 0; padding: 0; background: #fff; overflow: visible; z-index: 99999;
  }
  body.tp-printing .tp-stack { gap: 16px; }
  body.tp-printing .tp-ticket { break-inside: avoid; box-shadow: none !important; }
  body.tp-printing .tp-noprint { display: none !important; }
}
</style>

<style scoped>
.tp {
  --cgv-red: #e30613;
  --cgv-dark: #16110f;
  --bp-indigo: #17233f;
  --bp-teal: #0fb5a6;
  --tp-muted: #6f6a63;
  --tp-hair: #e2ddd4;
  --font-cond: "Arial Narrow", "Helvetica Neue Condensed", "Helvetica Neue", Arial, sans-serif;
  --font-mono: "Courier New", ui-monospace, Menlo, monospace;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Toolbar */
.tp-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.tp-switch { display: inline-flex; background: #f1ecf1; border-radius: 10px; padding: 4px; gap: 3px; }
.tp-switch button {
  border: none; background: transparent; cursor: pointer;
  padding: 8px 14px; border-radius: 7px; font-size: 13px; font-weight: 700; color: #64748b;
  transition: all .15s;
}
.tp-switch button.active { background: #fff; color: var(--cgv-red); box-shadow: 0 2px 8px rgba(227, 6, 19, .16); }
.tp-print {
  border: none; cursor: pointer; padding: 9px 18px; border-radius: 9px;
  background: linear-gradient(135deg, var(--cgv-red), #a30810); color: #fff;
  font-size: 13.5px; font-weight: 800; white-space: nowrap;
}
.tp-print:hover { filter: brightness(1.05); }

.tp-region { overflow-x: auto; }
.tp-stack { display: flex; flex-direction: column; align-items: center; gap: 20px; padding: 4px; }
.tp-note { text-align: center; color: var(--tp-muted); font-size: 13px; }

/* Barcode dùng chung */
.tp-bc { display: flex; align-items: stretch; height: 42px; background: #fff; }
.tp-bc span { display: block; }
.tp-bc--v { flex-direction: column; width: 100%; height: 100%; }

/* ===================== MẪU 1 — CUỐNG VÉ KÉP (CGV) ===================== */
.t1-movie {
  width: 460px; max-width: 100%;
  display: grid; grid-template-columns: 118px 1fr;
  background: #fff; border-radius: 14px; overflow: hidden;
  box-shadow: 0 16px 40px rgba(20, 10, 8, .18);
}
.t1-stub {
  background: linear-gradient(160deg, var(--cgv-red) 0%, #a30810 100%);
  color: #fff; padding: 18px 12px; position: relative;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; text-align: center;
}
.t1-stub::after {
  content: ""; position: absolute; top: 0; bottom: 0; right: -1px; width: 2px;
  background-image: radial-gradient(circle, rgba(255, 255, 255, .9) 1.6px, transparent 1.8px);
  background-size: 2px 11px; background-repeat: repeat-y;
}
.t1-stub__label { font-size: 10px; font-weight: 700; letter-spacing: 2px; opacity: .85; }
.t1-stub__seat { font-family: var(--font-cond); font-weight: 800; font-size: 48px; line-height: .9; letter-spacing: 1px; }
.t1-stub__type { font-size: 11px; font-weight: 700; background: rgba(255, 255, 255, .2); padding: 3px 9px; border-radius: 999px; }
.t1-body { padding: 16px 18px 14px; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
.t1-brand { display: flex; align-items: center; justify-content: space-between; }
.t1-logo { font-family: var(--font-cond); font-weight: 800; font-size: 19px; letter-spacing: .5px; color: #16110f; }
.t1-logo b { color: var(--cgv-red); }
.t1-brand small { font-size: 10px; color: var(--tp-muted); letter-spacing: 1.5px; font-weight: 700; }
.t1-title { font-family: var(--font-cond); font-weight: 800; font-size: 22px; line-height: 1.05; text-transform: uppercase; color: #16110f; }
.t1-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
.t1-cell span { display: block; font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; color: var(--tp-muted); font-weight: 700; }
.t1-cell b { font-size: 13.5px; color: #16110f; }
.t1-foot { border-top: 1px dashed var(--tp-hair); padding-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.t1-foot .tp-bc { flex: 1; }
.t1-code { font-family: var(--font-mono); font-size: 12px; font-weight: 700; text-align: right; color: #16110f; }
.t1-code small { display: block; color: var(--tp-muted); font-weight: 400; font-size: 10px; }

.t1-combo {
  width: 300px; max-width: 100%;
  background: var(--cgv-dark); color: #fff; border-radius: 14px; overflow: hidden;
  box-shadow: 0 16px 40px rgba(20, 10, 8, .22);
}
.t1-combo__top { background: linear-gradient(160deg, var(--cgv-red), #7d060d); padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; }
.t1-combo__top b { font-family: var(--font-cond); font-weight: 800; font-size: 18px; letter-spacing: 1px; }
.t1-combo__top span { font-size: 24px; }
.t1-combo__mid { padding: 16px 18px; display: flex; flex-direction: column; gap: 8px; }
.t1-combo__name { font-size: 16px; font-weight: 800; }
.t1-combo__line { display: flex; justify-content: space-between; font-size: 12.5px; color: #d9d2cf; }
.t1-combo__line b { color: #fff; }
.t1-combo__bc { background: #fff; margin: 4px 18px 16px; border-radius: 6px; padding: 7px; }

/* ===================== MẪU 2 — BOARDING PASS ===================== */
.t2-movie {
  width: 620px; max-width: 100%;
  display: grid; grid-template-columns: 1fr 150px;
  background: #fbfcfe; border-radius: 16px; overflow: hidden;
  box-shadow: 0 16px 40px rgba(16, 22, 40, .18); border: 1px solid #e6eaf2;
}
.t2-main { padding: 20px 22px; display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.t2-head { display: flex; align-items: center; justify-content: space-between; }
.t2-brand { font-family: var(--font-cond); font-weight: 800; font-size: 20px; letter-spacing: .5px; color: var(--bp-indigo); }
.t2-brand b { color: var(--bp-teal); }
.t2-tag { font-size: 10px; font-weight: 800; letter-spacing: 2px; color: var(--bp-teal); text-transform: uppercase; }
.t2-route { display: flex; align-items: center; gap: 12px; }
.t2-route__movie { font-family: var(--font-cond); font-weight: 800; font-size: 22px; line-height: 1; text-transform: uppercase; color: var(--bp-indigo); }
.t2-route__room { flex-shrink: 0; }
.t2-route__dash { flex: 1; height: 2px; background: repeating-linear-gradient(90deg, var(--bp-indigo) 0 6px, transparent 6px 12px); opacity: .3; }
.t2-route__ico { font-size: 18px; }
.t2-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.t2-info > div span { display: block; font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; color: var(--tp-muted); font-weight: 700; }
.t2-info > div b { font-size: 14px; color: var(--bp-indigo); }
.t2-passenger { border-top: 1px solid #e6eaf2; padding-top: 12px; display: flex; justify-content: space-between; gap: 10px; font-size: 12px; flex-wrap: wrap; }
.t2-passenger span { color: var(--tp-muted); }
.t2-passenger b { color: var(--bp-indigo); }
.t2-stub { background: var(--bp-indigo); color: #fff; position: relative; padding: 20px 14px; display: flex; flex-direction: column; align-items: center; gap: 14px; text-align: center; }
.t2-stub::before { content: ""; position: absolute; left: -1px; top: 0; bottom: 0; width: 2px; background-image: radial-gradient(circle, #fbfcfe 1.6px, transparent 1.8px); background-size: 2px 12px; }
.t2-stub__seat span { font-size: 9px; letter-spacing: 1.5px; opacity: .7; font-weight: 700; }
.t2-stub__seat b { font-family: var(--font-cond); font-size: 40px; line-height: .9; display: block; }
.t2-stub__bc { background: #fff; padding: 6px; border-radius: 5px; height: 86px; width: 42px; }
.t2-stub__code { font-family: var(--font-mono); font-size: 10px; letter-spacing: .5px; }

.t2-combo {
  width: 300px; max-width: 100%;
  display: grid; grid-template-columns: 1fr 74px;
  background: #fbfcfe; border: 1px solid #e6eaf2; border-radius: 14px; overflow: hidden;
  box-shadow: 0 14px 32px rgba(16, 22, 40, .14);
}
.t2-combo__main { padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; min-width: 0; }
.t2-combo__tag { font-size: 9px; font-weight: 800; letter-spacing: 2px; color: var(--bp-teal); text-transform: uppercase; }
.t2-combo__name { font-weight: 800; font-size: 15px; color: var(--bp-indigo); line-height: 1.15; }
.t2-combo__row { display: flex; justify-content: space-between; font-size: 12px; color: var(--tp-muted); }
.t2-combo__row b { color: var(--bp-indigo); }
.t2-combo__stub { background: var(--bp-teal); color: #06322d; display: flex; align-items: center; justify-content: center; }
.t2-combo__stub b { font-family: var(--font-cond); font-weight: 800; font-size: 19px; writing-mode: vertical-rl; letter-spacing: 2px; }

/* ===================== MẪU 3 — HÓA ĐƠN NHIỆT ===================== */
.t3-movie, .t3-combo {
  width: 300px; max-width: 100%;
  background: #fff; color: #111; font-family: var(--font-mono);
  padding: 20px 20px 18px; box-shadow: 0 14px 32px rgba(0, 0, 0, .12); position: relative;
}
.t3-movie::after, .t3-combo::after {
  content: ""; position: absolute; left: 0; right: 0; bottom: -8px; height: 8px;
  background: linear-gradient(135deg, #fff 33%, transparent 0) -10px 0,
             linear-gradient(-135deg, #fff 33%, transparent 0) -10px 0;
  background-size: 16px 16px; background-repeat: repeat-x;
  filter: drop-shadow(0 2px 1px rgba(0, 0, 0, .06));
}
.t3-center { text-align: center; }
.t3-logo { font-family: var(--font-cond); font-weight: 800; font-size: 26px; letter-spacing: 2px; }
.t3-logo b { color: var(--cgv-red); }
.t3-sub { font-size: 10.5px; letter-spacing: .5px; color: #333; margin-top: 2px; }
.t3-rule { border: 0; border-top: 1px dashed #9a9a9a; margin: 12px 0; }
.t3-title { text-align: center; font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: .5px; line-height: 1.2; }
.t3-line { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; padding: 3px 0; }
.t3-line span { color: #555; }
.t3-line b { text-align: right; }
.t3-seatbig { text-align: center; font-family: var(--font-cond); font-weight: 800; font-size: 38px; letter-spacing: 2px; margin: 4px 0; }
.t3-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; padding-top: 4px; }
.t3-bc { margin: 12px auto 6px; height: 46px; }
.t3-code { text-align: center; font-size: 12px; letter-spacing: 2px; font-weight: 700; }
.t3-foot { text-align: center; font-size: 10.5px; color: #444; margin-top: 8px; line-height: 1.5; }
</style>
