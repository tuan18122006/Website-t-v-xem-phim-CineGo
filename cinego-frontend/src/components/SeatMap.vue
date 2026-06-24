<template>
  <div class="seat-map-container" :class="{ 'disable-scroll': isSelectionLocked }">
    <!-- Nút Bật/Tắt khóa màn hình dành cho Mobile (Chỉ hiện ở chế độ admin) -->
    <div v-if="mode === 'admin'" class="mobile-lock-toggle">
      <label class="switch">
        <input type="checkbox" v-model="isSelectionLocked" />
        <span class="slider round"></span>
      </label>
      <span class="lock-label">Bật Quét Chọn Ghế (Dành cho Mobile)</span>
    </div>

    <div class="screen-indicator">MÀN HÌNH CHÍNH</div>
    
    <div class="cinema-floor" @mouseleave="stopDragging" @mouseup="stopDragging">
      <!-- Lối đi bên trái (Trang trí) -->
      <div class="decorative-aisle left-aisle">
        <div class="aisle-track"></div>
        <div class="aisle-text">LỐI ĐI</div>
      </div>

      <!-- Lưới ghế -->
      <div class="seats-grid">
        <div 
          v-for="seat in sortedSeats" 
          :key="seat.id"
          :class="getSeatClass(seat)"
          @mousedown="startDragging(seat)"
          @mouseenter="dragOver(seat)"
          @mouseup="stopDragging"
          @click="handleSeatClick(seat)"
          class="seat-item"
        >
          <span v-if="seat.type !== 'hidden' && seat.type !== 'couple_hidden'" class="seat-label">
            {{ seat.row }}{{ seat.number }}
          </span>
        </div>
      </div>

      <!-- Lối đi bên phải (Trang trí) -->
      <div class="decorative-aisle right-aisle">
        <div class="aisle-track"></div>
        <div class="aisle-text">LỐI ĐI</div>
      </div>
    </div>
    
    <!-- Chú thích -->
    <div class="legend">
      <div class="legend-item"><div class="seat-box standard"></div> Thường</div>
      <div class="legend-item"><div class="seat-box vip"></div> VIP</div>
      <div class="legend-item"><div class="seat-box couple"></div> Đôi</div>
      <div class="legend-item" v-if="mode === 'client'"><div class="seat-box booked"></div> Đã bán</div>
      <div class="legend-item" v-if="mode === 'client'"><div class="seat-box selected"></div> Đang chọn</div>
      <div class="legend-item" v-if="mode === 'admin'"><div class="seat-box admin-selected"></div> Đang quét chọn</div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  seats: { type: Array, required: true },
  mode: { type: String, default: 'client' },
  selectedSeatIds: { type: Array, default: () => [] } // Client selected
});

const emit = defineEmits(['seat-clicked', 'selection-changed']);

const sortedSeats = computed(() => {
  return [...props.seats].sort((a, b) => {
    if (a.row === b.row) return a.number - b.number;
    return a.row.localeCompare(b.row);
  });
});

// --- STATE QUÉT CHỌN GHẾ (ADMIN) ---
const isSelectionLocked = ref(false); // Khóa màn hình mobile
const isDragging = ref(false);
const adminSelectedIds = ref([]); // Mảng chứa ID các ghế được quét chọn ở chế độ Admin

const startDragging = (seat) => {
  if (props.mode !== 'admin') return;
  isDragging.value = true;
  
  // Nút chuột xuống: Nếu ghế đã có trong mảng thì bỏ ra, nếu chưa thì thêm vào (Toggle)
  if (adminSelectedIds.value.includes(seat.id)) {
    adminSelectedIds.value = adminSelectedIds.value.filter(id => id !== seat.id);
  } else {
    adminSelectedIds.value.push(seat.id);
  }
  emit('selection-changed', adminSelectedIds.value);
};

const dragOver = (seat) => {
  if (props.mode !== 'admin' || !isDragging.value) return;
  // Trong lúc kéo, chỉ Add thêm ghế (không gỡ ra để quét cho mượt)
  if (!adminSelectedIds.value.includes(seat.id)) {
    adminSelectedIds.value.push(seat.id);
    emit('selection-changed', adminSelectedIds.value);
  }
};

const stopDragging = () => {
  if (props.mode !== 'admin') return;
  isDragging.value = false;
};

// --- CLICK GHẾ CƠ BẢN ---
const handleSeatClick = (seat) => {
  if (props.mode === 'client') {
    if (seat.is_booked || seat.type === 'hidden' || seat.type === 'couple_hidden') return;
    emit('seat-clicked', seat);
  }
  // Ở Admin, việc click đã được xử lý chung bởi mousedown/mouseup bên trên
};

// Hàm Reset vùng chọn cho Admin (được gọi từ RoomEditorView thông qua ref)
const clearSelection = () => {
  adminSelectedIds.value = [];
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
    // Highlight ghế đang được quét chọn
    if (adminSelectedIds.value.includes(seat.id)) {
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
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  background: radial-gradient(circle at top, #1a1c29, #0b0f19);
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
  border: 1px solid rgba(255,255,255,0.05);
  user-select: none; /* Tránh bôi đen text khi kéo chuột */
}

/* Khóa cuộn trang khi kéo trên Mobile */
.disable-scroll {
  touch-action: none;
}

/* =========================================
   UI CÔNG TẮC BẬT CHẾ ĐỘ QUÉT TRÊN MOBILE
========================================= */
.mobile-lock-toggle {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  background: rgba(255, 255, 255, 0.1);
  padding: 10px 20px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.2);
}
.lock-label {
  color: #fbbf24;
  font-size: 14px;
  font-weight: 700;
}
.switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #475569; transition: .4s; border-radius: 24px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: #ef4444; }
input:checked + .slider:before { transform: translateX(20px); }

/* =========================================
   MÀN HÌNH CHÍNH & RẠP CHIẾU
========================================= */
.screen-indicator {
  width: 80%;
  max-width: 600px;
  height: 50px;
  background: linear-gradient(to bottom, #ef4444, #7f1d1d);
  border-top-left-radius: 50% 30px;
  border-top-right-radius: 50% 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: #fff;
  font-weight: 900;
  font-size: 14px;
  letter-spacing: 12px;
  margin-bottom: 50px;
  box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4), inset 0 2px 5px rgba(255,255,255,0.5);
  border-top: 2px solid rgba(255,255,255,0.4);
}

.cinema-floor {
  display: flex;
  align-items: center;
  gap: 30px; /* Khoảng cách giữa ghế và lối đi */
  margin-bottom: 40px;
}

/* =========================================
   HIỆU ỨNG LỐI ĐI 2 BÊN (AISLE DECORATION)
========================================= */
.decorative-aisle {
  position: relative;
  width: 40px;
  height: 100%;
  min-height: 400px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
}

.aisle-text {
  writing-mode: vertical-rl;
  text-orientation: upright;
  color: rgba(255, 255, 255, 0.15);
  font-weight: 900;
  font-size: 18px;
  letter-spacing: 10px;
  z-index: 2;
}

/* Vạch sáng trượt dọc lối đi */
.aisle-track {
  position: absolute;
  top: -100%;
  left: 0;
  width: 100%;
  height: 50%;
  background: linear-gradient(to bottom, transparent, rgba(239, 68, 68, 0.5), transparent);
  animation: slide-down 4s infinite linear;
  z-index: 1;
}

.right-aisle .aisle-track {
  animation-delay: 2s; /* Lối đi bên phải chạy lệch nhịp với bên trái */
}

@keyframes slide-down {
  0% { top: -100%; }
  100% { top: 200%; }
}

/* =========================================
   LƯỚI GHẾ
========================================= */
.seats-grid {
  display: grid;
  grid-template-columns: repeat(10, minmax(40px, 1fr)); 
  gap: 15px;
}

.seat-base {
  width: 45px;
  height: 45px;
  border-radius: 12px 12px 6px 6px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 13px;
  font-weight: 800;
  color: white;
  transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, border 0.15s;
  will-change: transform;
  position: relative;
  box-shadow: 0 6px 12px rgba(0,0,0,0.4), inset 0 2px 4px rgba(255,255,255,0.3);
}

.seat-base::after {
  content: ''; position: absolute; bottom: 4px; width: 80%; height: 4px;
  background: rgba(0,0,0,0.3); border-radius: 4px;
}

.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover {
  transform: scale(1.1) translateY(-4px); 
  box-shadow: 0 12px 20px rgba(0,0,0,0.7), inset 0 2px 4px rgba(255,255,255,0.6);
  z-index: 10;
}

/* LOẠI GHẾ */
.seat-standard { background: linear-gradient(145deg, #4b5563, #374151); border: 1px solid #6b7280; }
.seat-vip { background: linear-gradient(145deg, #ef4444, #b91c1c); border: 1px solid #f87171; color: #fff; }
.seat-couple { 
  background: linear-gradient(145deg, #ec4899, #be185d); 
  grid-column: span 2; 
  width: 100%;
  border: 1px solid #f472b6; 
}
.seat-couple-hidden {
  display: none !important; /* Ghế tàng hình dành cho nửa sau của ghế đôi */
}

/* TRẠNG THÁI CLIENT */
.seat-booked {
  background: linear-gradient(145deg, #1f2937, #111827) !important; color: #374151;
  cursor: not-allowed; opacity: 0.6; box-shadow: inset 0 4px 10px rgba(0,0,0,0.8) !important;
}
.seat-booked::after { display: none; }

.seat-selected {
  background: linear-gradient(145deg, #10b981, #059669) !important; border: 2px solid #fff;
  box-shadow: 0 0 25px rgba(16, 185, 129, 0.8), inset 0 2px 4px rgba(255,255,255,0.6) !important;
  transform: translateY(-5px); color: white;
}

.seat-hidden-client { opacity: 0; pointer-events: none; }
.seat-hidden-admin { background: transparent !important; border: 2px dashed #4b5563 !important; box-shadow: none !important; color: #4b5563; }
.seat-hidden-admin::after { display: none; }

/* TRẠNG THÁI QUÉT CHỌN ADMIN */
.seat-admin-selected {
  border: 3px solid #fbbf24 !important;
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.8), inset 0 2px 4px rgba(255,255,255,0.6) !important;
  transform: translateY(-5px);
  z-index: 5;
}

/* CHÚ THÍCH */
.legend { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); color: #e5e7eb; }
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; }
.seat-box { width: 20px; height: 20px; border-radius: 4px; }
.seat-box.standard { background: linear-gradient(145deg, #4b5563, #374151); }
.seat-box.vip { background: linear-gradient(145deg, #ef4444, #b91c1c); }
.seat-box.couple { background: linear-gradient(145deg, #ec4899, #be185d); }
.seat-box.booked { background: #1f2937; }
.seat-box.selected { background: linear-gradient(145deg, #10b981, #059669); }
.seat-box.admin-selected { border: 2px solid #fbbf24; background: transparent; }
</style>
