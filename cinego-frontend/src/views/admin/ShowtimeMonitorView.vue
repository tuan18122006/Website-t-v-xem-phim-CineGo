<template>
  <div class="monitor-container">
    <div class="monitor-header">
      <h1 class="monitor-title">
        <Monitor :size="28" style="color: var(--accent-pink);" /> 
        GIÁM SÁT VẬN HÀNH SUẤT CHIẾU
      </h1>
      <div class="header-actions">
        <button @click="isLeftPanelOpen = !isLeftPanelOpen" class="btn-toggle" title="Ẩn/Hiện danh sách">
          <PanelLeftClose v-if="isLeftPanelOpen" :size="20"/>
          <PanelLeftOpen v-else :size="20"/>
        </button>
        <button @click="isRightPanelOpen = !isRightPanelOpen" class="btn-toggle" title="Ẩn/Hiện điều khiển">
          <PanelRightClose v-if="isRightPanelOpen" :size="20"/>
          <PanelRightOpen v-else :size="20"/>
        </button>
        <input 
          type="date" 
          v-model="selectedDate"
          @change="fetchShowtimes"
          class="date-input"
        />
      </div>
    </div>

    <div class="monitor-grid" :class="{
      'left-closed': !isLeftPanelOpen,
      'right-closed': !isRightPanelOpen
    }">
      <!-- CỘT 1: Danh sách suất chiếu -->
      <div v-if="isLeftPanelOpen" class="monitor-col left-panel">
        <div class="col-header">
          <h2><List :size="18" /> DANH SÁCH SUẤT CHIẾU</h2>
        </div>
        <div class="col-body custom-scrollbar">
          <div v-if="loadingShowtimes" class="empty-state">Đang tải...</div>
          <div v-else-if="groupedShowtimes.length === 0" class="empty-state">Không có suất chiếu nào.</div>
          
          <div v-for="movie in groupedShowtimes" :key="movie.movie_id" class="movie-group">
            <h3 class="movie-title">{{ movie.title }}</h3>
            <div class="showtime-list">
              <div 
                v-for="st in movie.showtimes" 
                :key="st.id"
                @click="selectShowtime(st.id)"
                class="showtime-item"
                :class="{ 'active': selectedShowtimeId === st.id }"
              >
                <div class="st-top">
                  <span class="st-time">{{ st.start_time }}</span>
                  <span class="st-room">{{ st.room_name }}</span>
                </div>
                <div class="st-bottom">
                  {{ st.format }} • {{ st.translation }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CỘT 2: Ma trận ghế Real-time -->
      <div class="monitor-col col-main">
        <div class="col-header flex-between">
          <h2><Armchair :size="18" /> SƠ ĐỒ GHẾ</h2>
          <button @click="refreshSeats" v-if="selectedShowtimeId" class="btn-refresh">
            <RefreshCw :size="14" /> Làm mới
          </button>
        </div>
        <div class="col-body center-content custom-scrollbar seatmap-wrapper">
          <div v-if="!selectedShowtimeId" class="empty-state">Vui lòng chọn 1 suất chiếu để xem.</div>
          <div v-else-if="loadingSeats" class="empty-state"><Loader2 class="spin" :size="20" /> Đang tải dữ liệu...</div>
          
          <SeatMap 
            v-else
            :seats="mappedSeats" 
            mode="client" 
            :allowBookedClick="true"
            :selectedSeatIds="selectedSeat ? [selectedSeat.id] : []" 
            @seat-clicked="handleSeatClick"
          />
        </div>
      </div>

      <!-- CỘT 3: Bảng điều khiển / Thông tin -->
      <div v-if="isRightPanelOpen" class="monitor-col right-panel">
        <div class="col-header">
          <h2><SlidersHorizontal :size="18" /> ĐIỀU KHIỂN & SỰ CỐ</h2>
        </div>
        <div class="col-body custom-scrollbar">
          
          <div v-if="!selectedSeat" class="empty-state padding-large">
            <MousePointerClick :size="40" style="opacity: 0.5; margin-bottom: 15px;" />
            <p>Chọn một ghế trên sơ đồ để xem chi tiết và thao tác.</p>
          </div>

          <div v-else class="control-panel">
            <!-- Thông tin ghế cơ bản -->
            <div class="info-card">
              <h3 class="info-title">THÔNG TIN GHẾ</h3>
              <div class="info-grid">
                <span class="label">Vị trí:</span>
                <span class="value">{{ selectedSeatLabel }}</span>
                
                <span class="label">Loại:</span>
                <span class="value uppercase">{{ selectedSeat.type }}</span>
                
                <span class="label">Trạng thái:</span>
                <span class="value status">
                  <span v-if="selectedSeat.status === 'available'" class="status-available">Trống</span>
                  <span v-if="selectedSeat.status === 'sold'" class="status-sold">Đã Bán</span>
                  <span v-if="selectedSeat.status === 'holding'" class="status-holding">Đang Giữ</span>
                  <span v-if="selectedSeat.status === 'broken'" class="status-broken">Đang Khóa/Hỏng</span>
                </span>
              </div>
            </div>

            <!-- Nếu ghế đã bán => Hiển thị thông tin khách hàng -->
            <div v-if="selectedSeat.status === 'sold'" class="info-card">
              <h3 class="info-title">THÔNG TIN KHÁCH HÀNG</h3>
              <div class="info-grid">
                <span class="label">Mã đơn:</span>
                <span class="value">{{ selectedSeat.booking_code }}</span>
                
                <span class="label">Họ tên:</span>
                <span class="value">{{ selectedSeat.customer_name }}</span>
                
                <span class="label">Email:</span>
                <span class="value">{{ selectedSeat.customer_email }}</span>
                
                <span class="label">Số điện thoại:</span>
                <span class="value">{{ selectedSeat.customer_phone }}</span>
              </div>
            </div>

            <!-- Nếu ghế đã bán => Hiển thị thao tác cho khách -->
            <div v-if="selectedSeat.status === 'sold'" class="actions-group">
               <div class="alert alert-success">
                  <CheckCircle2 :size="16" /> Ghế này đã được thanh toán.
               </div>
               <button @click="openQuickSwapModal" class="btn btn-dark">
                 <ArrowRightLeft :size="18" /> Đổi Ghế Nhanh
               </button>
               <button @click="openRescheduleModal" class="btn btn-dark">
                 <CalendarDays :size="18" /> Chuyển Suất Chiếu
               </button>
               <button @click="openRefundModal" class="btn btn-danger">
                 <CircleDollarSign :size="18" /> Hoàn Tiền
               </button>
               <button @click="markSeatBroken" class="btn btn-danger-solid">
                 <TriangleAlert :size="18" /> Báo Hỏng Vật Lý
               </button>
            </div>

            <!-- Nếu ghế đang trống hoặc hỏng => Thao tác vật lý -->
            <div v-else class="actions-group">
               <button v-if="selectedSeat.status === 'available'" @click="openTempLockModal" class="btn btn-warning">
                 <Lock :size="18" /> Khóa Tạm Thời
               </button>
               
               <button v-if="selectedSeat.status === 'available'" @click="markSeatBroken" class="btn btn-danger-solid">
                 <TriangleAlert :size="18" /> Báo Hỏng Vật Lý
               </button>

               <button v-if="selectedSeat.status === 'broken'" @click="unlockSeat" class="btn btn-success-solid">
                 <Unlock :size="18" /> Mở Khóa Ghế
               </button>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from '../../api/axios';
import SeatMap from '../../components/SeatMap.vue';
import Swal from 'sweetalert2';
import { Monitor, List, Armchair, SlidersHorizontal, MousePointerClick, RefreshCw, Loader2, CheckCircle2, ArrowRightLeft, CalendarDays, CircleDollarSign, Lock, TriangleAlert, Unlock, PanelLeftClose, PanelLeftOpen, PanelRightClose, PanelRightOpen } from 'lucide-vue-next';

const isLeftPanelOpen = ref(true);
const isRightPanelOpen = ref(true);

const selectedDate = ref(new Date().toISOString().split('T')[0]);
const groupedShowtimes = ref([]);
const loadingShowtimes = ref(false);

const selectedShowtimeId = ref(null);
const seats = ref([]);
const loadingSeats = ref(false);
const selectedSeat = ref(null);

const mappedSeats = computed(() => {
  return seats.value.map(seat => {
    let isBooked = false;
    if (seat.status === 'sold' || seat.status === 'broken' || seat.status === 'holding') {
      isBooked = true;
    }
    
    return {
      id: seat.id,
      row: seat.row_name,
      number: seat.seat_number,
      type: seat.type,
      status: seat.status,
      is_booked: isBooked,
    };
  });
});

const displayNumbers = computed(() => {
  const map = new Map();
  const seatsByRow = {};
  
  seats.value.forEach(seat => {
    if (!seatsByRow[seat.row_name]) seatsByRow[seat.row_name] = [];
    seatsByRow[seat.row_name].push(seat);
  });

  Object.keys(seatsByRow).forEach(row => {
    let currentDisplayNum = 1;
    const sorted = seatsByRow[row].sort((a, b) => a.seat_number - b.seat_number);
    sorted.forEach(seat => {
      if (seat.type !== 'hidden' && seat.type !== 'deleted' && seat.type !== 'couple_hidden') {
        map.set(seat.id, currentDisplayNum);
        currentDisplayNum++;
      }
    });
  });
  return map;
});

const selectedSeatLabel = computed(() => {
  if (!selectedSeat.value) return '';
  const num = displayNumbers.value.get(selectedSeat.value.id);
  return num ? `${selectedSeat.value.row_name}${num}` : `${selectedSeat.value.row_name}${selectedSeat.value.seat_number}`;
});

const seatLabel = (seat) => {
  if (!seat) return '';
  const num = displayNumbers.value.get(seat.id);
  return num ? `${seat.row_name}${num}` : `${seat.row_name}${seat.seat_number}`;
};

const seatLabelFromList = (seat, allSeats) => {
  if (!seat) return '';
  const seatsByRow = {};
  allSeats.forEach(s => {
    if (!seatsByRow[s.row_name]) seatsByRow[s.row_name] = [];
    seatsByRow[s.row_name].push(s);
  });
  const sorted = (seatsByRow[seat.row_name] || []).sort((a, b) => a.seat_number - b.seat_number);
  let displayNum = 1;
  for (const s of sorted) {
    if (s.id === seat.id) break;
    if (s.type !== 'hidden' && s.type !== 'deleted' && s.type !== 'couple_hidden') displayNum++;
  }
  return `${seat.row_name}${displayNum}`;
};

const fetchShowtimes = async () => {
  loadingShowtimes.value = true;
  selectedShowtimeId.value = null;
  selectedSeat.value = null;
  seats.value = [];
  try {
    const { data } = await axios.get(`/showtimes/by-date?date=${selectedDate.value}&all=true`);
    groupedShowtimes.value = data.data;
  } catch (err) {
    Swal.fire('Lỗi', "Lỗi tải lịch chiếu", 'error');
  } finally {
    loadingShowtimes.value = false;
  }
};

const selectShowtime = async (id) => {
  selectedShowtimeId.value = id;
  selectedSeat.value = null;
  await refreshSeats();
};

const refreshSeats = async () => {
  if (!selectedShowtimeId.value) return;
  loadingSeats.value = true;
  try {
    const { data } = await axios.get(`/showtimes/${selectedShowtimeId.value}/seats`);
    seats.value = data.seats;
    if (selectedSeat.value) {
      // update selected seat ref
      selectedSeat.value = seats.value.find(s => s.id === selectedSeat.value.id) || null;
    }
  } catch (err) {
    Swal.fire('Lỗi', "Lỗi tải sơ đồ ghế", 'error');
  } finally {
    loadingSeats.value = false;
  }
};

const handleSeatClick = (seatObj) => {
  // Find full seat data
  const fullSeat = seats.value.find(s => s.id === seatObj.id);
  selectedSeat.value = fullSeat;
};

const markSeatBroken = async () => {
  const seat = selectedSeat.value;
  const result = await Swal.fire({
    title: 'Báo hỏng ghế?',
    html: `
      <div style="text-align: left; font-size: 14px;">
        <p>Bạn muốn báo hỏng ghế <strong style="color: #ef4444;">${seatLabel(seat)}</strong> (${seat.type})?</p>
        <p style="margin-top: 10px; color: #94a3b8;">Hệ thống sẽ tự động gửi email thông báo cho khách hàng đã mua vé trên ghế này ở các suất chiếu tương lai.</p>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Xác nhận báo hỏng',
    cancelButtonText: 'Hủy'
  });

  if (!result.isConfirmed) return;

  try {
    const { data } = await axios.post('/staff/seats/broken', { seat_id: seat.id });
    Swal.fire('Thành công', data.message, 'success');
    if (data.has_conflict) {
      Swal.fire({
        title: 'CẢNH BÁO',
        html: `<p>Có <strong>${data.affected_bookings.length}</strong> vé đã mua trên ghế này ở các suất chiếu tương lai cần được đổi/hoàn tiền!</p>`,
        icon: 'warning',
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Đã hiểu'
      });
    }
    refreshSeats();
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Lỗi xử lý', 'error');
  }
};

const unlockSeat = async () => {
  if (!confirm('Xác nhận mở khóa chiếc ghế này?')) return;
  try {
    const { data } = await axios.post('/staff/seats/unlock', { seat_id: selectedSeat.value.id });
    Swal.fire('Thành công', data.message, 'success');
    refreshSeats();
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Lỗi xử lý', 'error');
  }
};

const openTempLockModal = async () => {
  if (!selectedShowtimeId.value || !selectedSeat.value) return;

  // Find room_id and times from groupedShowtimes
  let roomId = null;
  let stStart = null;
  let stEnd = null;
  for (const movie of groupedShowtimes.value) {
    const st = movie.showtimes.find(s => s.id === selectedShowtimeId.value);
    if (st) {
      roomId = st.room_id;
      stStart = st.start_time;
      stEnd = st.end_time;
      break;
    }
  }

  if (!roomId) {
    Swal.fire('Lỗi', 'Không xác định được phòng chiếu.', 'error');
    return;
  }

  // Pre-fill with current showtime exact date & time
  const localISOTime = `${selectedDate.value}T${stStart}`;
  const localISOEnd = `${selectedDate.value}T${stEnd}`;

  const { value: formValues } = await Swal.fire({
    title: 'Khóa Ghế Tạm Thời',
    html:
      '<div style="text-align: left; margin-bottom: 15px;">' +
      '<label class="block mb-2 font-bold text-sm">Thời gian bắt đầu</label>' +
      '<input id="swal-start" type="datetime-local" class="swal2-input" style="width: 100%; margin: 0; box-sizing: border-box;" value="' + localISOTime + '">' +
      '</div>' +
      '<div style="text-align: left; margin-bottom: 15px;">' +
      '<label class="block mb-2 font-bold text-sm">Thời gian kết thúc</label>' +
      '<input id="swal-end" type="datetime-local" class="swal2-input" style="width: 100%; margin: 0; box-sizing: border-box;" value="' + localISOEnd + '">' +
      '</div>' +
      '<div style="text-align: left;">' +
      '<label class="block mb-2 font-bold text-sm">Lý do khóa</label>' +
      '<input id="swal-reason" class="swal2-input" placeholder="Nhập lý do (tùy chọn)" style="width: 100%; margin: 0; box-sizing: border-box;">' +
      '</div>',
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Xác nhận khóa',
    cancelButtonText: 'Hủy',
    preConfirm: () => {
      const start = document.getElementById('swal-start').value;
      const end = document.getElementById('swal-end').value;
      const reason = document.getElementById('swal-reason').value;

      if (!start || !end) {
        Swal.showValidationMessage('Vui lòng chọn đầy đủ thời gian bắt đầu và kết thúc');
        return false;
      }

      if (new Date(start) >= new Date(end)) {
        Swal.showValidationMessage('Thời gian kết thúc phải lớn hơn thời gian bắt đầu');
        return false;
      }

      return { start, end, reason };
    }
  });

  if (formValues) {
    try {
      const { data } = await axios.post('/staff/seats/lock', {
        seat_id: selectedSeat.value.id,
        room_id: roomId,
        start_time: formValues.start,
        end_time: formValues.end,
        reason: formValues.reason
      });

      Swal.fire('Thành công', data.message, 'success');
      
      if (data.has_conflict) {
        Swal.fire('CẢNH BÁO', `Có ${data.affected_bookings.length} vé đã mua trên ghế này bị ảnh hưởng! Vui lòng kiểm tra lại.`, 'warning');
      }

      refreshSeats();
    } catch (error) {
      Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi xảy ra', 'error');
    }
  }
};

const openRefundModal = async () => {
  const seat = selectedSeat.value;
  if (!seat || !seat.booking_id) {
    Swal.fire('Lỗi', 'Không tìm thấy thông tin đơn hàng.', 'error');
    return;
  }

  const result = await Swal.fire({
    title: 'Hoàn tiền vào ví?',
    html: `
      <div style="text-align: left; font-size: 14px;">
        <p><strong>Mã đơn:</strong> ${seat.booking_code}</p>
        <p><strong>Khách hàng:</strong> ${seat.customer_name}</p>
        <p><strong>Ghế:</strong> ${seatLabel(seat)} (${seat.type})</p>
        <p style="color: #ef4444; font-weight: 700; font-size: 16px; margin-top: 10px;">Toàn bộ số tiền sẽ được hoàn vào ví tiền của khách.</p>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Xác nhận hoàn tiền',
    cancelButtonText: 'Hủy'
  });

  if (!result.isConfirmed) return;

  try {
    const { data } = await axios.post('/staff/compensation/refund', {
      booking_id: seat.booking_id
    });
    Swal.fire('Thành công', data.message, 'success');
    refreshSeats();
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể hoàn tiền.', 'error');
  }
};

const openQuickSwapModal = async () => {
  const seat = selectedSeat.value;
  if (!seat || !seat.booking_detail_id) {
    Swal.fire('Lỗi', 'Không tìm thấy thông tin vé.', 'error');
    return;
  }

  const availableSeats = seats.value.filter(s =>
    s.status === 'available' && s.id !== seat.id && s.type !== 'hidden' && s.type !== 'deleted' && s.type !== 'couple_hidden'
  );

  if (availableSeats.length === 0) {
    Swal.fire('Thông báo', 'Không còn ghế trống trong phòng này.', 'info');
    return;
  }

  const seatOptions = availableSeats.map(s =>
    `<option value="${s.id}">${seatLabel(s)} - ${s.type} (${s.price.toLocaleString('vi-VN')}đ)</option>`
  ).join('');

  const { value: formValues } = await Swal.fire({
    title: 'Đổi Ghế Nhanh',
    html: `
      <div style="text-align: left; font-size: 14px;">
        <p><strong>Ghế hiện tại:</strong> ${seatLabel(seat)} (${seat.type})</p>
        <p style="margin-top: 10px;"><strong>Chọn ghế mới:</strong></p>
        <select id="swal-new-seat" class="swal2-input" style="width: 100%; margin: 5px 0;">
          <option value="" disabled selected>Chọn ghế mới...</option>
          ${seatOptions}
        </select>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Đổi ghế',
    cancelButtonText: 'Hủy',
    preConfirm: () => {
      const newSeatId = document.getElementById('swal-new-seat').value;
      if (!newSeatId) {
        Swal.showValidationMessage('Vui lòng chọn ghế mới');
        return false;
      }
      return { new_seat_id: parseInt(newSeatId) };
    }
  });

  if (!formValues) return;

  try {
    const { data } = await axios.post('/staff/compensation/swap', {
      booking_detail_id: seat.booking_detail_id,
      new_seat_id: formValues.new_seat_id
    });
    Swal.fire('Thành công', data.message, 'success');
    refreshSeats();
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể đổi ghế.', 'error');
  }
};

const openRescheduleModal = async () => {
  const seat = selectedSeat.value;
  if (!seat || !seat.booking_id) {
    Swal.fire('Lỗi', 'Không tìm thấy thông tin đơn hàng.', 'error');
    return;
  }

  let currentMovieId = null;
  let currentShowtime = null;
  for (const movie of groupedShowtimes.value) {
    const st = movie.showtimes.find(s => s.id === selectedShowtimeId.value);
    if (st) {
      currentMovieId = movie.movie_id;
      currentShowtime = st;
      break;
    }
  }

  if (!currentMovieId) {
    Swal.fire('Lỗi', 'Không xác định được phim.', 'error');
    return;
  }

  const otherShowtimes = [];
  for (const movie of groupedShowtimes.value) {
    if (movie.movie_id === currentMovieId) {
      for (const st of movie.showtimes) {
        if (st.id !== selectedShowtimeId.value) {
          otherShowtimes.push(st);
        }
      }
    }
  }

  if (otherShowtimes.length === 0) {
    Swal.fire('Thông báo', 'Không có suất chiếu khác cùng phim trong ngày.', 'info');
    return;
  }

  const stOptions = otherShowtimes.map(st =>
    `<option value="${st.id}">${st.start_time} - ${st.room_name} (${st.format})</option>`
  ).join('');

  const { value: formValues } = await Swal.fire({
    title: 'Chuyển Suất Chiếu',
    html: `
      <div style="text-align: left; font-size: 14px;">
        <p><strong>Suất hiện tại:</strong> ${currentShowtime.start_time} - ${currentShowtime.room_name}</p>
        <p style="margin-top: 10px;"><strong>Chọn suất mới:</strong></p>
        <select id="swal-new-showtime" class="swal2-input" style="width: 100%; margin: 5px 0;">
          <option value="" disabled selected>Chọn suất chiếu mới...</option>
          ${stOptions}
        </select>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Chuyển suất',
    cancelButtonText: 'Hủy',
    preConfirm: () => {
      const newShowtimeId = document.getElementById('swal-new-showtime').value;
      if (!newShowtimeId) {
        Swal.showValidationMessage('Vui lòng chọn suất chiếu mới');
        return false;
      }
      return { new_showtime_id: parseInt(newShowtimeId) };
    }
  });

  if (!formValues) return;

  try {
    const { data: newSeatsData } = await axios.get(`/showtimes/${formValues.new_showtime_id}/seats`);
    const newAvailable = newSeatsData.seats.filter(s => s.status === 'available' && s.type !== 'hidden' && s.type !== 'deleted' && s.type !== 'couple_hidden');

    if (newAvailable.length === 0) {
      Swal.fire('Thông báo', 'Suất chiếu mới không còn ghế trống.', 'info');
      return;
    }

    const newSeatOptions = newAvailable.map(s =>
      `<option value="${s.id}">${seatLabelFromList(s, newAvailable)} - ${s.type}</option>`
    ).join('');

    const { value: seatMapping } = await Swal.fire({
      title: 'Chọn ghế ở suất mới',
      html: `
        <div style="text-align: left; font-size: 14px;">
          <p><strong>Ghế hiện tại:</strong> ${seatLabel(seat)} (${seat.type})</p>
          <p style="margin-top: 10px;"><strong>Chọn ghế mới:</strong></p>
          <select id="swal-map-seat" class="swal2-input" style="width: 100%; margin: 5px 0;">
            <option value="" disabled selected>Chọn ghế...</option>
            ${newSeatOptions}
          </select>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Xác nhận chuyển',
      cancelButtonText: 'Hủy',
      preConfirm: () => {
        const newSeatId = document.getElementById('swal-map-seat').value;
        if (!newSeatId) {
          Swal.showValidationMessage('Vui lòng chọn ghế');
          return false;
        }
        return { new_seat_id: parseInt(newSeatId) };
      }
    });

    if (!seatMapping) return;

    const seatMappingObj = {};
    seatMappingObj[seat.id] = seatMapping.new_seat_id;

    const { data } = await axios.post('/staff/compensation/reschedule', {
      booking_id: seat.booking_id,
      new_showtime_id: formValues.new_showtime_id,
      seat_mapping: seatMappingObj
    });
    Swal.fire('Thành công', data.message, 'success');
    refreshSeats();
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể chuyển suất chiếu.', 'error');
  }
};

onMounted(() => {
  fetchShowtimes();
});
</script>

<style scoped>
.monitor-container {
  padding: 24px;
  background-color: var(--bg-body);
  color: var(--text-primary);
  min-height: 100vh;
}

.monitor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.monitor-title {
  font-size: 24px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--text-primary);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.header-actions {
  display: flex;
  gap: 16px;
}

.date-input {
  background: var(--bg-surface);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 10px 16px;
  font-family: inherit;
  font-size: 14px;
  outline: none;
  transition: all 0.2s ease;
}

.date-input:focus {
  border-color: var(--accent-pink);
  box-shadow: 0 0 0 3px rgba(216, 45, 139, 0.1);
}

.monitor-grid {
  display: grid;
  grid-template-columns: 2.5fr 7fr 2.5fr;
  gap: 24px;
  height: calc(100vh - 120px);
  min-height: 600px;
  transition: all 0.3s ease;
}

.monitor-grid.left-closed {
  grid-template-columns: 9.5fr 2.5fr;
}

.monitor-grid.right-closed {
  grid-template-columns: 2.5fr 9.5fr;
}

.monitor-grid.left-closed.right-closed {
  grid-template-columns: 1fr;
}

.btn-toggle {
  background: var(--bg-surface);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-toggle:hover {
  border-color: var(--accent-pink);
  color: var(--accent-pink);
}

.monitor-col {
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}

.col-header {
  padding: 16px 20px;
  background: rgba(0,0,0,0.02);
  border-bottom: 1px solid var(--border-color);
}

.col-header.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.col-header h2 {
  font-size: 16px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  color: var(--text-primary);
}

.col-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.col-body.center-content {
  display: flex;
  background: rgba(0,0,0,0.01);
}
.col-body.center-content > * {
  margin: auto;
}

.empty-state {
  color: var(--text-muted);
  text-align: center;
  padding: 40px 20px;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.empty-state.padding-large {
  padding: 60px 20px;
}

.movie-group {
  margin-bottom: 24px;
}

.movie-title {
  color: var(--accent-pink);
  font-weight: 700;
  font-size: 15px;
  margin-bottom: 12px;
}

.showtime-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.showtime-item {
  padding: 12px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.showtime-item:hover {
  border-color: var(--accent-pink);
  background: rgba(216, 45, 139, 0.02);
}

.showtime-item.active {
  border-color: var(--accent-pink);
  background: rgba(216, 45, 139, 0.05);
}

.st-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.st-time {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}

.st-room {
  font-size: 11px;
  padding: 4px 8px;
  background: rgba(0,0,0,0.05);
  border-radius: 4px;
  color: var(--text-secondary);
  font-weight: 600;
}

.st-bottom {
  font-size: 12px;
  color: var(--text-muted);
}

.btn-refresh {
  background: var(--bg-body);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
}

.btn-refresh:hover {
  background: var(--accent-pink);
  color: white;
  border-color: var(--accent-pink);
}

.spin {
  animation: spin 1s linear infinite;
}
@keyframes spin { 100% { transform: rotate(360deg); } }

/* Controls */
.control-panel {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.info-card {
  background: rgba(0,0,0,0.02);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 16px;
}

.info-title {
  color: var(--accent-pink);
  font-weight: 700;
  font-size: 14px;
  border-bottom: 1px dashed var(--border-color);
  padding-bottom: 10px;
  margin-bottom: 12px;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 8px;
  font-size: 13px;
}

.info-grid .label {
  color: var(--text-muted);
}

.info-grid .value {
  font-weight: 700;
  text-align: right;
  color: var(--text-primary);
}

.uppercase { text-transform: uppercase; }

.status-available { color: var(--text-secondary); }
.status-sold { color: var(--accent-mint); }
.status-holding { color: #f59e0b; }
.status-broken { color: #ef4444; }

.actions-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.alert-success {
  background: rgba(16, 185, 129, 0.1);
  color: var(--accent-mint);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s ease;
  color: white;
}

.btn-dark {
  background: #334155;
}
.btn-dark:hover { background: #1e293b; }

.btn-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}
.btn-danger:hover {
  background: rgba(239, 68, 68, 0.2);
}

.btn-warning {
  background: #f59e0b;
}
.btn-warning:hover { background: #d97706; }

.btn-danger-solid {
  background: #ef4444;
}
.btn-danger-solid:hover { background: #dc2626; }

.btn-success-solid {
  background: var(--accent-mint);
}
.btn-success-solid:hover { background: #059669; }

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.2);
}
</style>
