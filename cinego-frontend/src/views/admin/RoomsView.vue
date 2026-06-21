<template>
  <div class="rooms-view">
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; border-left: 4px solid #ef4444;">
      <h2 style="color: #111827; margin-top: 0;">Quản lý Rạp & Thiết kế Sơ đồ Ghế (Thành viên 1)</h2>
      <p style="color: #4b5563;">
        Đây là phần giao diện nhúng sẵn <b>Sơ đồ ghế 3D</b>. Khi ghép code, Thành viên 1 chỉ cần gọi API lấy mảng ghế truyền vào đây. 
        Thử click vào các ghế bên dưới để đổi loại ghế (Thường -> VIP -> Đôi -> Lối đi).
      </p>
    </div>
    
    <!-- Gọi Component SeatMap dùng chung ra -->
    <SeatMap :seats="testSeats" mode="admin" @seat-clicked="onAdminClick" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SeatMap from '../../components/SeatMap.vue';

// Giả lập dữ liệu mảng ghế
const generateTestSeats = () => {
  const seats = [];
  const rows = ['A', 'B', 'C', 'D', 'E', 'F'];
  let id = 1;
  rows.forEach(row => {
    for (let i = 1; i <= 10; i++) {
      let type = 'standard';
      if (row === 'E' || row === 'F') type = 'vip'; // Hàng cuối là VIP
      
      // Giả lập lối đi
      if (i === 5 || i === 6) type = 'hidden'; 
      if (row === 'A' && (i === 1 || i === 10)) type = 'hidden';
      
      seats.push({ id: id++, row, number: i, type, is_booked: false });
    }
  });
  return seats;
};

const testSeats = ref(generateTestSeats());

// Logic khi Admin click vào ghế để thiết kế
const onAdminClick = (seat) => {
  const types = ['standard', 'vip', 'couple', 'hidden'];
  const currentIndex = types.indexOf(seat.type);
  seat.type = types[(currentIndex + 1) % types.length];
};
</script>
