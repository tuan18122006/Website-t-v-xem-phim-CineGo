<template>
  <div 
    class="seat-map-container" 
    :class="{ 'disable-scroll': isSelectionLocked }"
  >
    <!-- Nút Bật/Tắt khóa màn hình dành cho Mobile -->
    <div v-if="mode === 'admin'" class="mobile-lock-toggle">
      <label class="switch">
        <input type="checkbox" v-model="isSelectionLocked" />
        <span class="slider round"></span>
      </label>
      <span class="lock-label">Khóa Cuộn (Quét ghế Mobile)</span>
    </div>

    <div class="screen-indicator">MÀN HÌNH CHÍNH</div>
    
    <!-- KHU VỰC VẼ KHUNG CHỌN (BOX SELECTION) -->
    <div 
      class="cinema-floor" 
      ref="floorRef"
      @mousedown="handleMouseDown"
      @touchstart="handleTouchStart"
    >
      <!-- Vùng Chọn (Selection Box) màu xanh -->
      <div 
        v-if="box.show"
        class="selection-box"
        :style="{
          left: box.left + 'px',
          top: box.top + 'px',
          width: box.width + 'px',
          height: box.height + 'px'
        }"
      ></div>

      <div class="decorative-aisle left-aisle">
        <div class="aisle-track"></div>
        <div class="aisle-text">LỐI VÀO</div>
      </div>

      <div class="seats-grid">
        <div 
          v-for="seat in sortedSeats" 
          :key="seat.id"
          :data-seat-id="seat.id"
          :class="getSeatClass(seat)"
          @click="handleSeatClick(seat, $event)"
          class="seat-item"
        >
          <span v-if="seat.type !== 'hidden' && seat.type !== 'couple_hidden'" class="seat-label">
            {{ seat.row }}{{ seat.number }}
          </span>
        </div>
      </div>

      <div class="decorative-aisle right-aisle">
        <div class="aisle-track"></div>
        <div class="aisle-text">LỐI RA</div>
      </div>
    </div>
    
    <div class="legend">
      <div class="legend-item"><div class="seat-box standard"></div> Thường</div>
      <div class="legend-item"><div class="seat-box vip"></div> VIP</div>
      <div class="legend-item"><div class="seat-box couple"></div> Đôi</div>
      <div class="legend-item" v-if="mode === 'client'"><div class="seat-box booked"></div> Đã bán</div>
      <div class="legend-item" v-if="mode === 'client'"><div class="seat-box selected"></div> Đang chọn</div>
      <div class="legend-item" v-if="mode === 'admin'"><div class="seat-box hidden-demo"></div> Khoảng trống</div>
      <div class="legend-item" v-if="mode === 'admin'"><div class="seat-box admin-selected"></div> Đang quét chọn</div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, reactive, onBeforeUnmount } from 'vue';

const props = defineProps({
  seats: { type: Array, required: true },
  mode: { type: String, default: 'client' },
  selectedSeatIds: { type: Array, default: () => [] } 
});

const emit = defineEmits(['seat-clicked', 'selection-changed']);

const floorRef = ref(null);
const isSelectionLocked = ref(false);

const sortedSeats = computed(() => {
  return [...props.seats].sort((a, b) => {
    if (a.row === b.row) return a.number - b.number;
    return a.row.localeCompare(b.row);
  });
});

// --- ADMIN STATE ---
const adminSelectedIds = ref(new Set()); 

// --- BOX SELECTION (LASSO) STATE ---
const box = reactive({
  show: false,
  startX: 0,
  startY: 0,
  left: 0,
  top: 0,
  width: 0,
  height: 0
});

// Cache DOM elements để tối ưu tốc độ tính toán, không gọi DOM API liên tục
let seatDOMElements = [];
let isDragging = false;
let startSelectionIds = new Set();

// --- LOGIC VẼ KHUNG CHỌN ---

const initDrag = (clientX, clientY) => {
  if (props.mode !== 'admin') return;
  isDragging = true;
  box.show = true;

  // Tọa độ vẽ hộp nằm trong tọa độ của Container (cinema-floor)
  const floorRect = floorRef.value.getBoundingClientRect();
  box.startX = clientX - floorRect.left;
  box.startY = clientY - floorRect.top;
  box.left = box.startX;
  box.top = box.startY;
  box.width = 0;
  box.height = 0;

  // Sao chép lại mảng ghế đang chọn ban đầu
  startSelectionIds = new Set(adminSelectedIds.value);

  // Cache lại toàn bộ vị trí của các ghế trên màn hình để tính toán va chạm siêu nhanh
  seatDOMElements = Array.from(floorRef.value.querySelectorAll('.seat-item')).map(el => {
    return {
      el: el,
      id: parseInt(el.getAttribute('data-seat-id')),
      rect: el.getBoundingClientRect()
    };
  });
};

const updateDrag = (clientX, clientY) => {
  if (!isDragging) return;

  const floorRect = floorRef.value.getBoundingClientRect();
  const currentX = clientX - floorRect.left;
  const currentY = clientY - floorRect.top;

  box.left = Math.min(box.startX, currentX);
  box.top = Math.min(box.startY, currentY);
  box.width = Math.abs(currentX - box.startX);
  box.height = Math.abs(currentY - box.startY);

  // Tọa độ tuyệt đối của hộp chọn trên màn hình
  const boxRect = {
    left: floorRect.left + box.left,
    right: floorRect.left + box.left + box.width,
    top: floorRect.top + box.top,
    bottom: floorRect.top + box.top + box.height
  };

  // NATIVE DOM MANIPULATION: 
  // Bypass Vue Reactivity để đổi màu class CSS trực tiếp (Giúp đạt 60FPS không giật lag)
  for (let i = 0; i < seatDOMElements.length; i++) {
    const seat = seatDOMElements[i];
    
    // Kiểm tra Box Intersection (Thuật toán 2 hình chữ nhật giao nhau)
    const isIntersecting = !(
      boxRect.right < seat.rect.left || 
      boxRect.left > seat.rect.right || 
      boxRect.bottom < seat.rect.top || 
      boxRect.top > seat.rect.bottom
    );

    if (isIntersecting) {
      adminSelectedIds.value.add(seat.id);
      seat.el.classList.add('seat-admin-selected');
    } else {
      // Nếu lúc đầu chưa được chọn thì bỏ class
      if (!startSelectionIds.has(seat.id)) {
        adminSelectedIds.value.delete(seat.id);
        seat.el.classList.remove('seat-admin-selected');
      }
    }
  }
};

const endDrag = () => {
  if (!isDragging) return;
  isDragging = false;
  box.show = false;
  
  // Đồng bộ mảng Selection với Vue
  emit('selection-changed', Array.from(adminSelectedIds.value));
};

// --- MOUSE EVENTS ---
const handleMouseDown = (e) => {
  if (e.button !== 0) return; // Chỉ chuột trái
  if (e.target.closest('.seat-item')) return; // Nếu bấm trực tiếp vào ghế thì để click xử lý
  initDrag(e.clientX, e.clientY);
  window.addEventListener('mousemove', handleMouseMove);
  window.addEventListener('mouseup', handleMouseUp);
};

const handleMouseMove = (e) => {
  if (!isDragging) return;
  e.preventDefault(); // Tránh bôi đen text ngoài ý muốn
  updateDrag(e.clientX, e.clientY);
};

const handleMouseUp = () => {
  endDrag();
  window.removeEventListener('mousemove', handleMouseMove);
  window.removeEventListener('mouseup', handleMouseUp);
};

// --- TOUCH EVENTS (MOBILE) ---
const handleTouchStart = (e) => {
  if (e.target.closest('.seat-item')) return;
  if (!isSelectionLocked.value) return; // Nếu chưa khóa cuộn thì ko cho quét
  
  const touch = e.touches[0];
  initDrag(touch.clientX, touch.clientY);
  window.addEventListener('touchmove', handleTouchMove, { passive: false });
  window.addEventListener('touchend', handleTouchEnd);
};

const handleTouchMove = (e) => {
  if (!isDragging) return;
  e.preventDefault(); // Chặn cuộn trang
  const touch = e.touches[0];
  updateDrag(touch.clientX, touch.clientY);
};

const handleTouchEnd = () => {
  endDrag();
  window.removeEventListener('touchmove', handleTouchMove);
  window.removeEventListener('touchend', handleTouchEnd);
};

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', handleMouseMove);
  window.removeEventListener('mouseup', handleMouseUp);
  window.removeEventListener('touchmove', handleTouchMove);
  window.removeEventListener('touchend', handleTouchEnd);
});


// --- CLICK TỪNG GHẾ ---
const handleSeatClick = (seat, event) => {
  if (props.mode === 'client') {
    if (seat.is_booked || seat.type === 'hidden' || seat.type === 'couple_hidden') return;
    emit('seat-clicked', seat);
  } else if (props.mode === 'admin') {
    // Click đơn lẻ (Có thể giữ CTRL / SHIFT để chọn nhiều)
    if (adminSelectedIds.value.has(seat.id)) {
      adminSelectedIds.value.delete(seat.id);
    } else {
      adminSelectedIds.value.add(seat.id);
    }
    emit('selection-changed', Array.from(adminSelectedIds.value));
  }
};

const clearSelection = () => {
  adminSelectedIds.value.clear();
  // Native DOM clear để chắc chắn
  if (floorRef.value) {
    const seats = floorRef.value.querySelectorAll('.seat-admin-selected');
    seats.forEach(s => s.classList.remove('seat-admin-selected'));
  }
  emit('selection-changed', []);
};

defineExpose({ clearSelection });

// --- CSS CLASSES ---
const getSeatClass = (seat) => {
  let classes = ['seat-base'];
  
  if (seat.type === 'standard') classes.push('seat-standard');
  if (seat.type === 'vip') classes.push('seat-vip');
  if (seat.type === 'couple') classes.push('seat-couple');
  if (seat.type === 'couple_hidden') classes.push('seat-couple-hidden');
  
  if (props.mode === 'admin') {
    if (seat.type === 'hidden') classes.push('seat-hidden-admin');
    classes.push('cursor-pointer');
    if (adminSelectedIds.value.has(seat.id)) {
      classes.push('seat-admin-selected');
    }
  } 
  else if (props.mode === 'client') {
    if (seat.type === 'hidden' || seat.type === 'couple_hidden') {
      classes.push('seat-hidden-client');
    } else if (seat.is_booked) {
      classes.push('seat-booked');
    } else {
      classes.push('cursor-pointer');
      if (props.selectedSeatIds.includes(seat.id)) {
        classes.push('seat-selected');
      }
    }
  }

  return classes.join(' ');
};
</script>

<style scoped>
.seat-map-container {
  display: flex; flex-direction: column; align-items: center;
  padding: 40px 20px;
  background: radial-gradient(circle at top, #1a1c29, #0b0f19);
  border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);
  border: 1px solid rgba(255,255,255,0.05);
  user-select: none;
}
.disable-scroll { touch-action: none; }

.mobile-lock-toggle {
  display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
  background: rgba(255, 255, 255, 0.1); padding: 10px 20px;
  border-radius: 12px; border: 1px solid rgba(255,255,255,0.2);
}
.lock-label { color: #fbbf24; font-size: 14px; font-weight: 700; }
.switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #475569; transition: .4s; border-radius: 24px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: #ef4444; }
input:checked + .slider:before { transform: translateX(20px); }

.screen-indicator {
  width: 80%; max-width: 600px; height: 50px;
  background: linear-gradient(to bottom, #ef4444, #7f1d1d);
  border-top-left-radius: 50% 30px; border-top-right-radius: 50% 30px;
  display: flex; justify-content: center; align-items: center;
  color: #fff; font-weight: 900; font-size: 14px; letter-spacing: 12px;
  margin-bottom: 50px;
  box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4), inset 0 2px 5px rgba(255,255,255,0.5);
  border-top: 2px solid rgba(255,255,255,0.4);
}

.cinema-floor {
  display: flex; align-items: center; gap: 30px; margin-bottom: 40px;
  position: relative;
}

/* HỘP QUÉT CHỌN (BLUE BOX) */
.selection-box {
  position: absolute;
  background: rgba(59, 130, 246, 0.3); /* Màu xanh dương trong suốt */
  border: 1px solid #3b82f6;
  border-radius: 2px;
  pointer-events: none; /* Xuyên thấu sự kiện chuột */
  z-index: 100;
  backdrop-filter: blur(1px);
}

.decorative-aisle {
  position: relative; width: 40px; height: 100%; min-height: 400px;
  background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px; overflow: hidden; display: flex; justify-content: center; align-items: center;
}
.aisle-text {
  writing-mode: vertical-rl; text-orientation: upright;
  color: rgba(255, 255, 255, 0.15); font-weight: 900; font-size: 18px; letter-spacing: 10px; z-index: 2;
}
.aisle-track {
  position: absolute; top: -100%; left: 0; width: 100%; height: 50%;
  background: linear-gradient(to bottom, transparent, rgba(239, 68, 68, 0.5), transparent);
  animation: slide-down 4s infinite linear; z-index: 1;
}
.right-aisle .aisle-track { animation-delay: 2s; }
@keyframes slide-down { 0% { top: -100%; } 100% { top: 200%; } }

.seats-grid {
  display: grid; grid-template-columns: repeat(10, minmax(40px, 1fr)); gap: 15px;
}

/* TỐI ƯU HIỆU NĂNG: Bỏ transition nặng, dùng will-change */
.seat-base {
  width: 45px; height: 45px; border-radius: 12px 12px 6px 6px;
  display: flex; justify-content: center; align-items: center;
  font-size: 13px; font-weight: 800; color: white;
  will-change: transform, border-color, background-color;
  position: relative;
  box-shadow: 0 4px 6px rgba(0,0,0,0.3); /* Giảm độ nặng bóng đổ */
  border: 2px solid transparent;
}
.seat-base::after {
  content: ''; position: absolute; bottom: 4px; width: 80%; height: 4px;
  background: rgba(0,0,0,0.3); border-radius: 4px;
}
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { transform: translateY(-2px); z-index: 10; }

.seat-standard { background: linear-gradient(145deg, #4b5563, #374151); border-color: #6b7280; }
.seat-vip { background: linear-gradient(145deg, #ef4444, #b91c1c); border-color: #f87171; color: #fff; }
.seat-couple { background: linear-gradient(145deg, #ec4899, #be185d); grid-column: span 2; width: 100%; border-color: #f472b6; }
.seat-couple-hidden { display: none !important; }

.seat-booked { background: linear-gradient(145deg, #1f2937, #111827) !important; color: #374151; cursor: not-allowed; opacity: 0.6; box-shadow: inset 0 4px 10px rgba(0,0,0,0.8) !important; }
.seat-booked::after { display: none; }

.seat-selected { background: linear-gradient(145deg, #10b981, #059669) !important; border: 2px solid #fff !important; transform: translateY(-5px); color: white; }
.seat-hidden-client { opacity: 0; pointer-events: none; }
.seat-hidden-admin { background: transparent !important; border: 2px dashed #4b5563 !important; color: #4b5563; box-shadow: none; }
.seat-hidden-admin::after { display: none; }

.seat-admin-selected {
  border: 3px solid #fbbf24 !important;
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.8) !important;
  transform: translateY(-5px); z-index: 5;
}

.legend { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); color: #e5e7eb; }
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; }
.seat-box { width: 20px; height: 20px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.2); }
.seat-box.standard { background: linear-gradient(145deg, #4b5563, #374151); }
.seat-box.vip { background: linear-gradient(145deg, #ef4444, #b91c1c); }
.seat-box.couple { background: linear-gradient(145deg, #ec4899, #be185d); }
.seat-box.booked { background: #1f2937; }
.seat-box.selected { background: linear-gradient(145deg, #10b981, #059669); }
.seat-box.admin-selected { border: 2px solid #fbbf24; background: transparent; }
.seat-box.hidden-demo { border: 2px dashed #4b5563; background: transparent; }
</style>
