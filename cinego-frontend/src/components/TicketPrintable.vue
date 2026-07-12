<template>
  <div class="tp">
    <!-- Thanh công cụ: in (không in ra giấy) -->
    <div class="tp-toolbar tp-noprint" style="justify-content: flex-end;">
      <button type="button" class="tp-print" @click="doPrint">🖨️ In vé</button>
    </div>

    <!-- Khu vực vé (được in ra giấy) -->
    <div class="tp-region">
      <div class="tp-stack">
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

const doPrint = (e) => {
  const tpRegion = e.target.closest('.tp').querySelector('.tp-region');
  const clone = tpRegion.cloneNode(true);
  
  const printContainer = document.createElement('div');
  printContainer.id = 'tp-print-container';
  printContainer.appendChild(clone);
  document.body.appendChild(printContainer);

  const pageStyle = document.createElement('style');
  pageStyle.innerHTML = '@page { size: 80mm 200mm; margin: 0; }';
  document.head.appendChild(pageStyle);

  document.body.classList.add('tp-printing');
  
  const cleanup = () => {
    document.body.classList.remove('tp-printing');
    if (document.body.contains(printContainer)) {
      document.body.removeChild(printContainer);
    }
    if (document.head.contains(pageStyle)) {
      document.head.removeChild(pageStyle);
    }
    window.removeEventListener('afterprint', cleanup);
  };
  
  window.addEventListener('afterprint', cleanup);
  window.print();
  setTimeout(cleanup, 1200);
};
</script>

<!-- ===== Style TOÀN CỤC chỉ để in ===== -->
<style>
@media print {
  body.tp-printing #app { display: none !important; }
  body.tp-printing #tp-print-container {
    position: relative; width: 100%;
    margin: 0 auto; padding: 10mm 5mm; background: #fff; z-index: 99999;
  }
  body.tp-printing .tp-stack { gap: 16px; flex-direction: column; align-items: center; }
  body.tp-printing .tp-ticket { break-inside: avoid; box-shadow: none !important; }
  body.tp-printing .tp-noprint { display: none !important; }
}
</style>

<style scoped>
.tp {
  --cgv-red: #e30613;
  --cgv-dark: #16110f;
  --tp-muted: #6f6a63;
  --font-cond: "Arial Narrow", "Helvetica Neue Condensed", "Helvetica Neue", Arial, sans-serif;
  --font-mono: "Courier New", ui-monospace, Menlo, monospace;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Toolbar */
.tp-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.tp-print {
  border: none; cursor: pointer; padding: 9px 18px; border-radius: 9px;
  background: linear-gradient(135deg, var(--cgv-red), #a30810); color: #fff;
  font-size: 13.5px; font-weight: 800; white-space: nowrap;
}
.tp-print:hover { filter: brightness(1.05); }

.tp-region { overflow-x: auto; }
.tp-stack { display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; align-items: flex-start; gap: 20px; padding: 4px; }
.tp-note { text-align: center; color: var(--tp-muted); font-size: 13px; }

/* Barcode dùng chung */
.tp-bc { display: flex; align-items: stretch; height: 42px; background: #fff; }
.tp-bc span { display: block; }

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
