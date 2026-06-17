<template>
  <!-- 
    ========================================================================
    🔥 COMPONENT DÙNG CHUNG: MA TRẬN SƠ ĐỒ GHẾ (SEAT MAP)
    Người viết: Nhóm trưởng (Atuan)
    Mục đích: Dùng chung cho cả trang Quản lý Rạp (Admin) và Đặt vé (Client).
    ========================================================================
  -->
  <div class="seat-map-container">
    <div class="screen-indicator">MÀN HÌNH CHÍNH</div>
    
    <div class="seats-grid">
      <!-- Vòng lặp tự động vẽ từng ghế lấy từ Database -->
      <div 
        v-for="seat in sortedSeats" 
        :key="seat.id"
        :class="getSeatClass(seat)"
        @click="handleSeatClick(seat)"
        class="seat-item"
      >
        <!-- Chỉ hiển thị số/chữ nếu không phải là Lối đi (hidden) -->
        <span v-if="seat.type !== 'hidden'" class="seat-label">
          {{ seat.row }}{{ seat.number }}
        </span>
      </div>
    </div>
    
    <!-- Chú thích màu sắc ghế ở bên dưới -->
    <div class="legend">
      <div class="legend-item"><div class="seat-box standard"></div> Thường</div>
      <div class="legend-item"><div class="seat-box vip"></div> VIP</div>
      <div class="legend-item"><div class="seat-box couple"></div> Đôi</div>
      <div class="legend-item" v-if="mode === 'client'"><div class="seat-box booked"></div> Đã bán</div>
      <div class="legend-item" v-if="mode === 'client'"><div class="seat-box selected"></div> Đang chọn</div>
      <div class="legend-item" v-if="mode === 'admin'"><div class="seat-box hidden-demo"></div> Lối đi (Ẩn)</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/*
  -------------------------------------------------------------
  1. KHAI BÁO PROPS (Dữ liệu đầu vào do Thành viên 1 & 3 truyền vào)
  -------------------------------------------------------------
*/
const props = defineProps({
  // Mảng chứa toàn bộ dữ liệu ghế lấy từ API Backend
  seats: {
    type: Array,
    required: true
  },
  // mode = 'admin' (Dành cho Thành viên 1 thiết kế sơ đồ rạp).
  // mode = 'client' (Dành cho Thành viên 3 cho khách đặt vé).
  mode: {
    type: String,
    default: 'client', 
  },
  // Mảng ID các ghế mà khách đang bấm chọn (chỉ dùng khi mode='client').
  selectedSeatIds: {
    type: Array,
    default: () => []
  }
});

/*
  -------------------------------------------------------------
  2. KHAI BÁO EMITS (Sự kiện gửi ngược ra cho các Thành viên xử lý)
  -------------------------------------------------------------
*/
const emit = defineEmits(['seat-clicked']);

// Hàm sắp xếp ghế theo đúng thứ tự (A1 -> A10, B1 -> B10...)
const sortedSeats = computed(() => {
  return [...props.seats].sort((a, b) => {
    if (a.row === b.row) return a.number - b.number;
    return a.row.localeCompare(b.row);
  });
});

/*
  -------------------------------------------------------------
  3. LOGIC XỬ LÝ MÀU SẮC (CSS CLASS) CHO TỪNG GHẾ
  -------------------------------------------------------------
*/
const getSeatClass = (seat) => {
  let classes = ['seat-base'];
  
  // 3.1. Phân loại ghế (Thường, VIP, Couple)
  if (seat.type === 'standard') classes.push('seat-standard');
  if (seat.type === 'vip') classes.push('seat-vip');
  if (seat.type === 'couple') classes.push('seat-couple');
  
  // 3.2. Nếu đang mở ở màn hình Admin (Thành viên 1 dùng)
  if (props.mode === 'admin') {
    // Admin nhìn thấy cả lối đi (viền đứt nét) để còn biết mà click đổi loại
    if (seat.type === 'hidden') classes.push('seat-hidden-admin');
    classes.push('cursor-pointer'); // Ghế nào cũng click được
  } 
  
  // 3.3. Nếu đang mở ở màn hình Khách hàng (Thành viên 3 dùng)
  else if (props.mode === 'client') {
    if (seat.type === 'hidden') {
      classes.push('seat-hidden-client'); // Lối đi -> Tàng hình hoàn toàn
    } else if (seat.is_booked) {
      classes.push('seat-booked'); // Ghế bị người khác mua -> Xám xịt
    } else {
      classes.push('cursor-pointer'); // Ghế trống -> Cho phép click
      // Nếu ID ghế đang nằm trong mảng khách chọn thì chuyển màu xanh lá
      if (props.selectedSeatIds.includes(seat.id)) {
        classes.push('seat-selected');
      }
    }
  }

  return classes.join(' ');
};

/*
  -------------------------------------------------------------
  4. HÀM BẮT SỰ KIỆN CLICK CHUỘT
  -------------------------------------------------------------
*/
const handleSeatClick = (seat) => {
  // Thành viên 3 LƯU Ý: Nếu khách hàng bấm vào ghế đã mua hoặc lối đi thì chặn lại
  if (props.mode === 'client' && (seat.is_booked || seat.type === 'hidden')) {
    return; // Không làm gì cả
  }
  
  // Nếu hợp lệ, Bắn sự kiện ra ngoài kèm data của ghế đó 
  // Để Thành viên 1 hoặc 3 tự viết logic thêm/sửa mảng của họ.
  emit('seat-clicked', seat);
};
</script>

<style scoped>
/* ========================================================
   CSS VẼ RẠP PHIM CHUẨN CINEGO (Dark Mode & Premium 3D)
   Các Dev không cần tự viết CSS ghế ở trang của mình nữa!
======================================================== */
.seat-map-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px;
  background: radial-gradient(circle at top, #1a1c29, #0b0f19); /* Nền tối gradient sang trọng */
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
  border: 1px solid rgba(255,255,255,0.05);
}

/* Vẽ màn hình cong có viền sáng Glow */
.screen-indicator {
  width: 85%;
  height: 60px;
  background: linear-gradient(to bottom, #ef4444, #7f1d1d); /* Màu đỏ rạp chiếu */
  border-top-left-radius: 50% 30px;
  border-top-right-radius: 50% 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: #fff;
  font-weight: 900;
  font-size: 14px;
  letter-spacing: 12px;
  margin-bottom: 60px;
  /* Đổ bóng phát sáng (Glow) đỏ rực rỡ cho màn hình */
  box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4), inset 0 2px 5px rgba(255,255,255,0.5);
  border-top: 2px solid rgba(255,255,255,0.4);
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

/* Lưới sơ đồ ghế (Tự động canh đều) */
.seats-grid {
  display: grid;
  grid-template-columns: repeat(10, minmax(40px, 1fr)); 
  gap: 15px;
  margin-bottom: 50px;
}

/* CSS Cơ bản cho 1 cái ghế (Thiết kế 3D nổi bật) */
.seat-base {
  width: 45px;
  height: 45px;
  border-radius: 12px 12px 6px 6px; /* Bo tròn phía trên giống ghế thật */
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 13px;
  font-weight: 800;
  color: white;
  
  /* TỐI ƯU HÓA: Chỉ animate transform và box-shadow, tốc độ 0.15s nhanh dứt khoát */
  transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
  will-change: transform; /* Kích hoạt tăng tốc phần cứng (GPU) để di chuột mượt như bôi mỡ */
  
  user-select: none;
  position: relative;
  /* Đổ bóng 3D làm ghế nổi cộm lên khỏi mặt phẳng */
  box-shadow: 0 6px 12px rgba(0,0,0,0.4), inset 0 2px 4px rgba(255,255,255,0.3);
}

/* Hiệu ứng tựa lưng giả (để làm nó giống ghế rạp ngoài đời thực hơn) */
.seat-base::after {
  content: '';
  position: absolute;
  bottom: 4px;
  width: 80%;
  height: 4px;
  background: rgba(0,0,0,0.3);
  border-radius: 4px;
}

.cursor-pointer {
  cursor: pointer;
}

/* Hiệu ứng hover sang trọng khi rà chuột */
.cursor-pointer:hover {
  /* TỐI ƯU HÓA: Nảy biên độ nhỏ hơn một chút, loại bỏ filter: brightness gây nặng card màn hình */
  transform: scale(1.1) translateY(-4px); 
  box-shadow: 0 12px 20px rgba(0,0,0,0.7), inset 0 2px 4px rgba(255,255,255,0.6);
  z-index: 10;
}

/* --- MÀU SẮC PHÂN LOẠI GHẾ (DÙNG GRADIENT ĐỂ TẠO KHỐI 3D BÓNG BẨY) --- */
.seat-standard { 
  background: linear-gradient(145deg, #4b5563, #374151); 
  border: 1px solid #6b7280;
}
.seat-vip { 
  background: linear-gradient(145deg, #ef4444, #b91c1c); 
  border: 1px solid #f87171;
  color: #fff;
}
.seat-couple { 
  background: linear-gradient(145deg, #ec4899, #be185d); 
  grid-column: span 2; /* Ghế đôi chiếm diện tích bằng 2 ghế thường */
  width: 100%;
  border: 1px solid #f472b6;
}

/* --- TRẠNG THÁI KHÁCH HÀNG --- */
.seat-booked {
  background: linear-gradient(145deg, #1f2937, #111827) !important; 
  color: #374151;
  cursor: not-allowed;
  opacity: 0.6;
  /* Làm ghế thụt lõm xuống nền sàn */
  box-shadow: inset 0 4px 10px rgba(0,0,0,0.8) !important;
  border: 1px solid #374151;
}

.seat-booked::after {
  display: none; /* Bỏ tựa lưng để trông xẹp xuống */
}

.seat-selected {
  background: linear-gradient(145deg, #10b981, #059669) !important; 
  border: 2px solid #fff;
  /* Glow xanh ngọc rực rỡ tỏa sáng */
  box-shadow: 0 0 25px rgba(16, 185, 129, 0.8), inset 0 2px 4px rgba(255,255,255,0.6) !important;
  transform: translateY(-5px);
  color: white;
}

/* --- TRẠNG THÁI LỐI ĐI (TẠO HÌNH RẠP) --- */
.seat-hidden-client {
  opacity: 0; /* Khách hàng sẽ không thấy ghế này */
  pointer-events: none;
}

.seat-hidden-admin {
  background: transparent !important;
  border: 2px dashed #4b5563 !important;
  box-shadow: none !important;
  color: #4b5563;
}
.seat-hidden-admin::after { display: none; }

/* --- CHÚ THÍCH BÊN DƯỚI --- */
.legend {
  display: flex;
  gap: 30px;
  flex-wrap: wrap;
  justify-content: center;
  padding-top: 30px;
  border-top: 1px solid rgba(255,255,255,0.1);
  color: #e5e7eb;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 15px;
  font-weight: 500;
}

/* Ô màu trong phần chú thích cũng phải có 3D */
.seat-box {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  box-shadow: inset 0 2px 4px rgba(255,255,255,0.3), 0 2px 4px rgba(0,0,0,0.4);
}

.seat-box.standard { background: linear-gradient(145deg, #4b5563, #374151); border: 1px solid #6b7280; }
.seat-box.vip { background: linear-gradient(145deg, #ef4444, #b91c1c); border: 1px solid #f87171; }
.seat-box.couple { background: linear-gradient(145deg, #ec4899, #be185d); border: 1px solid #f472b6; }
.seat-box.booked { background: #1f2937; box-shadow: inset 0 2px 4px rgba(0,0,0,0.8); border: 1px solid #374151; }
.seat-box.selected { background: linear-gradient(145deg, #10b981, #059669); border: 1px solid white; box-shadow: 0 0 10px rgba(16, 185, 129, 0.8); }
.seat-box.hidden-demo { border: 2px dashed #4b5563; background: transparent; box-shadow: none; }
</style>
