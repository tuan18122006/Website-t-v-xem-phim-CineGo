<template>
  <div class="monitor-container">
    <div class="monitor-header">
      <h1 class="monitor-title">
        <Monitor :size="28" style="color: var(--accent-pink);" /> 
        GIÁM SÁT VẬN HÀNH SUẤT CHIẾU
      </h1>
      <div class="header-actions">
        
        <div class="date-picker-wrapper">
          <button @click="prevDate" class="btn-toggle" title="Ngày hôm trước">
            <ChevronLeft :size="18"/>
          </button>
          <input 
            type="date" 
            v-model="selectedDate"
            @change="fetchShowtimes"
            class="date-input"
          />
          <button @click="nextDate" class="btn-toggle" title="Ngày tiếp theo">
            <ChevronRight :size="18"/>
          </button>
        </div>
      </div>
    </div>

    <div class="monitor-grid" :class="{
      'left-closed': !isLeftPanelOpen,
      'right-closed': !isRightPanelOpen
    }">
      <!-- CỘT 1: Danh sách suất chiếu -->
      <div class="monitor-col left-panel">
        <div class="col-header clickable" @click="isLeftPanelOpen = !isLeftPanelOpen" title="Ẩn/Hiện Danh Sách">
          <h2><List :size="18" style="min-width: 18px" /> <span v-show="isLeftPanelOpen">DANH SÁCH SUẤT CHIẾU</span></h2>
        </div>
        <div class="col-body custom-scrollbar" v-show="isLeftPanelOpen">
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
            :class="{ 'swap-mode-active': isQuickSwapMode }"
          />
          <div v-if="isQuickSwapMode" class="swap-mode-banner">
            <span>🔄 Chọn ghế trống để đổi...</span>
          </div>
        </div>
      </div>

      <!-- CỘT 3: Bảng điều khiển / Thông tin -->
      <div class="monitor-col right-panel">
        <div class="col-header clickable" @click="isRightPanelOpen = !isRightPanelOpen" title="Ẩn/Hiện Điều Khiển">
          <h2><SlidersHorizontal :size="18" style="min-width: 18px" /> <span v-show="isRightPanelOpen">ĐIỀU KHIỂN & SỰ CỐ</span></h2>
        </div>
        <div class="col-body custom-scrollbar" v-show="isRightPanelOpen">
          
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
                <span class="value">{{ selectedSeat.row_name }}{{ selectedSeat.seat_number }}</span>
                
                <span class="label">Loại:</span>
                <span class="value uppercase">{{ selectedSeat.type === 'vip' ? 'VIP' : selectedSeat.type === 'couple' ? 'Ghế đôi' : 'Ghế thường' }}</span>
                
                <span class="label">Trạng thái:</span>
                <span class="value status">
                  <span v-if="selectedSeat.status === 'available'" class="status-available">Trống</span>
                  <span v-if="selectedSeat.status === 'sold'" class="status-sold">Đã Bán</span>
                  <span v-if="selectedSeat.status === 'holding'" class="status-holding">Đang Giữ</span>
                  <span v-if="selectedSeat.status === 'broken'" class="status-broken">Đang Khóa/Hỏng</span>
                </span>
              </div>
            </div>

            <!-- Nếu ghế đã bán => Hiển thị thao tác cho khách -->
            <div v-if="selectedSeat.status === 'sold' || selectedSeat.booking_id" class="actions-group">
               <div v-if="selectedSeat.status === 'sold'" class="alert alert-success">
                  <CheckCircle2 :size="16" /> Ghế này đã được thanh toán.
               </div>
               <div v-else class="alert alert-warning">
                  <AlertCircle :size="16" /> Ghế này ĐÃ ĐƯỢC THANH TOÁN nhưng ĐANG BỊ BÁO HỎNG/KHÓA! Vui lòng hoàn tiền hoặc đổi lịch cho khách.
               </div>

               <!-- Booking Info Block -->
               <div v-if="loadingBooking" class="booking-info-box loading">
                 <Loader2 class="spin" :size="16"/> Đang tải thông tin đơn...
               </div>
               <div v-else-if="bookingInfo" class="booking-info-box">
                 <p><strong>Mã Đơn:</strong> #{{ bookingInfo.id }}</p>
                 <p><strong>Khách hàng:</strong> {{ bookingInfo.user?.name || 'Khách vãng lai' }}</p>
                 <p><strong>SĐT:</strong> {{ bookingInfo.user?.phone_number || bookingInfo.user?.phone || 'N/A' }}</p>
                 <p><strong>Ghế đã mua:</strong> {{ bookingInfo.booking_details?.map(d => (d.seat.row || '') + (d.seat.number || '')).join(', ') }}</p>
               </div>

               <div v-if="isQuickSwapMode" class="alert alert-warning" style="display:flex; flex-direction:column; gap: 8px;">
                 <span>Vui lòng chọn 1 <strong>ghế trống</strong> trên sơ đồ để chuyển khách sang.</span>
                 <button @click="cancelQuickSwap" class="btn btn-dark" style="padding: 8px; font-size: 12px;">Hủy Thao Tác</button>
               </div>

               <button v-if="!isQuickSwapMode" @click="startQuickSwapMode" class="btn btn-dark" :disabled="!bookingInfo">
                 <ArrowRightLeft :size="18" /> Đổi Ghế Nhanh
               </button>
               <button v-if="!isQuickSwapMode" @click="openRescheduleModal" class="btn btn-dark" :disabled="!bookingInfo">
                 <CalendarDays :size="18" /> Đổi Lịch Chiếu
               </button>
               <button @click="openRefundModal" class="btn btn-danger" :disabled="!bookingInfo">
                 <CircleDollarSign :size="18" /> Hoàn Tiền (Ví)
               </button>
               
               <div style="height: 1px; background: #e5e7eb; margin: 12px 0;"></div>
               
               <button @click="markSeatBroken" class="btn btn-danger-solid">
                 <TriangleAlert :size="18" /> Báo Hỏng Ghế (Gửi Mail)
               </button>
            </div>

            <!-- Nếu ghế đang giữ -->
            <div v-else-if="selectedSeat.status === 'holding'" class="actions-group">
               <div class="alert alert-warning" style="background:#fffbeb; color:#d97706; border-color:#fef3c7;">
                  <Clock :size="16" /> Ghế đang được khách giữ để thanh toán.
               </div>
               <div class="booking-info-box" style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:12px;">
                 <p><strong>Khách hàng:</strong> {{ selectedSeat.holder_name || 'Khách vãng lai' }}</p>
                 <p><strong>Email:</strong> {{ selectedSeat.holder_email }}</p>
                 <p><strong>SĐT:</strong> {{ selectedSeat.holder_phone }}</p>
                 <p><strong>Hết hạn giữ ghế:</strong> {{ selectedSeat.hold_expires_at ? new Date(selectedSeat.hold_expires_at).toLocaleTimeString('vi-VN') : 'N/A' }}</p>
               </div>
               <p style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">
                 * Bạn chưa thể hoàn tiền vì khách chưa thanh toán xong.
               </p>
               <button @click="markSeatBroken" class="btn btn-danger-solid">
                 <TriangleAlert :size="18" /> Báo Hỏng Vật Lý Ngay
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
import { Monitor, List, Armchair, SlidersHorizontal, MousePointerClick, RefreshCw, Loader2, CheckCircle2, ArrowRightLeft, CalendarDays, CircleDollarSign, Lock, TriangleAlert, Unlock, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const isLeftPanelOpen = ref(true);
const isRightPanelOpen = ref(true);

const selectedDate = ref(new Date().toISOString().split('T')[0]);

const prevDate = () => {
  const d = new Date(selectedDate.value);
  d.setDate(d.getDate() - 1);
  selectedDate.value = d.toISOString().split('T')[0];
  fetchShowtimes();
};

const nextDate = () => {
  const d = new Date(selectedDate.value);
  d.setDate(d.getDate() + 1);
  selectedDate.value = d.toISOString().split('T')[0];
  fetchShowtimes();
};

const groupedShowtimes = ref([]);
const loadingShowtimes = ref(false);

const selectedShowtimeId = ref(null);
const seats = ref([]);
const loadingSeats = ref(false);
const selectedSeat = ref(null);

const bookingInfo = ref(null);
const loadingBooking = ref(false);

const isQuickSwapMode = ref(false);
const quickSwapOldSeat = ref(null);

// Reschedule state
const isRescheduleMode = ref(false);
const rescheduleNewShowtime = ref(null);
const rescheduleAvailableSeats = ref([]);
const rescheduleSelectedSeatIds = ref([]);
const rescheduleLoadingSeats = ref(false);

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

const fetchShowtimes = async () => {
  loadingShowtimes.value = true;
  selectedShowtimeId.value = null;
  selectedSeat.value = null;
  seats.value = [];
  try {
    const { data } = await axios.get(`/showtimes/by-date?date=${selectedDate.value}`);
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
  isQuickSwapMode.value = false;
  await refreshSeats();
};

const refreshSeats = async (isBackgroundUpdate = false) => {
  if (!selectedShowtimeId.value) return;
  if (!isBackgroundUpdate) {
    loadingSeats.value = true;
  }
  try {
    const { data } = await axios.get(`/showtimes/${selectedShowtimeId.value}/seats`);
    seats.value = data.seats;
    if (selectedSeat.value) {
      // update selected seat ref
      selectedSeat.value = seats.value.find(s => s.id === selectedSeat.value.id) || null;
    }
  } catch (err) {
    if (!isBackgroundUpdate) {
      Swal.fire('Lỗi', "Lỗi tải sơ đồ ghế", 'error');
    }
  } finally {
    if (!isBackgroundUpdate) {
      loadingSeats.value = false;
    }
  }
};

let pollInterval = null;

onMounted(() => {
  fetchShowtimes();
  
  // Tự động làm mới sơ đồ ghế mỗi 3 giây
  pollInterval = setInterval(() => {
    if (selectedShowtimeId.value) {
      refreshSeats(true);
    }
  }, 3000);
});

import { onUnmounted } from 'vue';
onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});

const handleSeatClick = async (seatObj) => {
  const fullSeat = seats.value.find(s => s.id === seatObj.id);

  if (isQuickSwapMode.value) {
    if (fullSeat.status !== 'available') {
      Swal.fire('Ghế không hợp lệ', 'Vui lòng chọn một ghế đang trống.', 'warning');
      return;
    }

    const isOldCouple = quickSwapOldSeat.value.type === 'couple';
    const isNewCouple = fullSeat.type === 'couple';
    if (isOldCouple !== isNewCouple) {
      Swal.fire('Không hợp lệ', 'Không thể đổi chéo giữa ghế Đôi và ghế Thường/VIP. Vui lòng chọn ghế cùng loại.', 'error');
      return;
    }
    
    // Calculate default times for temp lock
    let stStart = "00:00:00";
    let stEnd = "23:59:00";
    if (selectedShowtimeId.value) {
      for (const movie of groupedShowtimes.value) {
        const st = movie.showtimes.find(s => s.id === selectedShowtimeId.value);
        if (st) {
          stStart = st.start_time;
          stEnd = st.end_time;
          break;
        }
      }
    }
    const localISOStart = `${selectedDate.value}T${stStart}`;
    const localISOEnd = `${selectedDate.value}T${stEnd}`;

    const { isConfirmed, value: lockOptions } = await Swal.fire({
      title: 'Xác nhận Đổi Ghế',
      html: `Đổi khách từ ghế <strong style="color:#ef4444">${quickSwapOldSeat.value.row_name}${quickSwapOldSeat.value.seat_number}</strong> sang ghế <strong style="color:#10b981">${fullSeat.row_name}${fullSeat.seat_number}</strong>.<br><br>` +
            `<div style="text-align: left; margin-top: 15px;">` +
            `<label style="font-size:14px; font-weight:600; margin-bottom:5px; display:block;">Xử lý ghế cũ (${quickSwapOldSeat.value.row_name}${quickSwapOldSeat.value.seat_number}):</label>` +
            `<select id="swal-old-seat-action" class="form-control" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;" onchange="document.getElementById('swal-temp-lock-options').style.display = this.value === 'temp_lock' ? 'block' : 'none'">` +
              `<option value="none">Không khóa (Mở bán lại)</option>` +
              `<option value="temp_lock" selected>Khóa tạm thời theo khung giờ</option>` +
              `<option value="broken">Báo hỏng vĩnh viễn (Sự cố)</option>` +
            `</select>` +
            `</div>` +
            `<div id="swal-temp-lock-options" style="display: block; text-align: left; margin-top: 15px; padding: 15px; background: rgba(245, 158, 11, 0.05); border: 1px dashed #f59e0b; border-radius: 8px;">` +
              `<label style="font-size:13px; font-weight:600; display:block; margin-bottom:5px;">Thời gian bắt đầu:</label>` +
              `<input type="datetime-local" id="swal-lock-start" class="form-control" value="${localISOStart}" style="margin-bottom: 10px; width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">` +
              `<label style="font-size:13px; font-weight:600; display:block; margin-bottom:5px;">Thời gian kết thúc:</label>` +
              `<input type="datetime-local" id="swal-lock-end" class="form-control" value="${localISOEnd}" style="margin-bottom: 10px; width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">` +
              `<label style="font-size:13px; font-weight:600; display:block; margin-bottom:5px;">Lý do (không bắt buộc):</label>` +
              `<input type="text" id="swal-lock-reason" class="form-control" placeholder="VD: Dọn nước đổ" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">` +
            `</div>`,
      showCancelButton: true,
      confirmButtonText: 'Đổi ghế ngay',
      cancelButtonText: 'Hủy',
      preConfirm: () => {
        return {
          action: document.getElementById('swal-old-seat-action').value,
          start: document.getElementById('swal-lock-start').value,
          end: document.getElementById('swal-lock-end').value,
          reason: document.getElementById('swal-lock-reason').value
        };
      }
    });

    if (isConfirmed) {
      submitQuickSwap(fullSeat.id, lockOptions);
    }
    return;
  }

  // Normal click
  selectedSeat.value = fullSeat;
  bookingInfo.value = null;

  if (fullSeat.status === 'sold' || fullSeat.booking_id) {
    // Construct bookingInfo from inline seat data
    const customerSeats = seats.value.filter(s => s.booking_id === fullSeat.booking_id);
    bookingInfo.value = {
      id: fullSeat.booking_id,
      booking_code: fullSeat.booking_code,
      user: {
        name: fullSeat.customer_name,
        email: fullSeat.customer_email,
        phone_number: fullSeat.customer_phone
      },
      booking_details: customerSeats.map(s => ({
        id: s.booking_detail_id,
        seat_id: s.id,
        seat: { row: s.row_name, number: s.seat_number, type: s.type }
      }))
    };
  }
};

const startQuickSwapMode = () => {
  if (!bookingInfo.value) return;
  quickSwapOldSeat.value = selectedSeat.value;
  isQuickSwapMode.value = true;
};

const cancelQuickSwap = () => {
  isQuickSwapMode.value = false;
  quickSwapOldSeat.value = null;
};

const submitQuickSwap = async (newSeatId, lockOptions) => {
  try {
    const bookingDetail = bookingInfo.value.booking_details.find(d => d.seat.row === quickSwapOldSeat.value.row_name && d.seat.number === quickSwapOldSeat.value.seat_number)
                          || bookingInfo.value.booking_details[0]; // fallback
    const { data } = await axios.post('/staff/compensation/swap', {
      booking_detail_id: bookingDetail.id,
      new_seat_id: newSeatId,
      old_seat_action: lockOptions?.action || 'none',
      lock_start: lockOptions?.start,
      lock_end: lockOptions?.end,
      lock_reason: lockOptions?.reason
    });

    Swal.fire('Thành công', data.message || 'Đổi ghế thành công!', 'success');
    isQuickSwapMode.value = false;
    quickSwapOldSeat.value = null;
    bookingInfo.value = null;
    selectedSeat.value = null;
    refreshSeats();
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể đổi ghế', 'error');
  }
};

const markSeatBroken = async () => {
  const result = await Swal.fire({
    title: 'Xác nhận báo hỏng?',
    text: 'Báo hỏng vĩnh viễn chiếc ghế này? Các vé tương lai mua trên ghế này sẽ bị ảnh hưởng!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Đồng ý',
    cancelButtonText: 'Hủy'
  });
  if (!result.isConfirmed) return;

  try {
    const { data } = await axios.post('/staff/seats/broken', { seat_id: selectedSeat.value.id });
    if (data.has_conflict) {
      Swal.fire('CẢNH BÁO', `Thành công, nhưng có ${data.affected_bookings.length} vé đã mua trên ghế này ở các suất chiếu tương lai cần được đổi/hoàn tiền!`, 'warning');
    } else {
      Swal.fire('Thành công', data.message, 'success');
    }
    refreshSeats(false); // Force overlay load
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Lỗi xử lý', 'error');
  }
};

const unlockSeat = async () => {
  const result = await Swal.fire({
    title: 'Xác nhận mở khóa?',
    text: 'Bạn có chắc chắn muốn mở khóa chiếc ghế này?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Đồng ý',
    cancelButtonText: 'Hủy'
  });
  if (!result.isConfirmed) return;

  try {
    const { data } = await axios.post('/staff/seats/unlock', { seat_id: selectedSeat.value.id });
    Swal.fire('Thành công', data.message, 'success');
    refreshSeats(false); // Force overlay load
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



const openRescheduleModal = async () => {
  if (!bookingInfo.value) return;

  const currentShowtimeId = selectedShowtimeId.value;
  let movieId = null;
  for (const group of groupedShowtimes.value) {
    if (group.showtimes.find(s => s.id === currentShowtimeId)) {
      movieId = group.movie_id;
      break;
    }
  }

  if (!movieId) {
    Swal.fire('Lỗi', 'Không xác định được phim.', 'error');
    return;
  }

  // Load all showtimes for this movie - API trả về grouped theo phòng
  const { data } = await axios.get(`/movies/${movieId}/showtimes`);
  const grouped = data.data || data;

  // Flatten: mỗi group có roomName + showtimes[]
  const allShowtimes = [];
  for (const group of grouped) {
    for (const st of group.showtimes) {
      allShowtimes.push({
        id: st.id,
        start_time: st.start_time,
        start_date: st.start_date,
        start_time_display: `${st.start_date} ${st.start_time}`,
        room_name: group.roomName || st.room_name,
        available_seats: st.available_seats
      });
    }
  }

  // Exclude current showtime
  const otherShowtimes = allShowtimes.filter(s => s.id !== currentShowtimeId);

  if (otherShowtimes.length === 0) {
    Swal.fire('Thông báo', 'Không còn suất chiếu nào khác cho phim này.', 'info');
    return;
  }

  // Số ghế cần cho đơn này
  const neededCount = bookingInfo.value.booking_details?.length || 1;


  // Group by Date
  const showtimesByDate = {};
  otherShowtimes.forEach(s => {
    if (!showtimesByDate[s.start_date]) showtimesByDate[s.start_date] = [];
    showtimesByDate[s.start_date].push(s);
  });

  let htmlContent = `<div style="text-align:left">
        <p style="margin-bottom:4px;font-size:14px;">Đang đổi đơn <strong>#${bookingInfo.value.id}</strong> của <strong>${bookingInfo.value.user?.name || 'Khách'}</strong></p>
        <p style="font-size:12px;color:#6b7280;margin-bottom:12px;">Đơn cần <strong>${neededCount} ghế</strong> — chỉ hiển thị các suất có thể đổi được.</p>
        <div style="max-height:300px;overflow-y:auto;padding-right:4px;padding-bottom:10px;">`;

  for (const [date, sts] of Object.entries(showtimesByDate)) {
    // Format YYYY-MM-DD to DD/MM/YYYY
    const dParts = date.split('-');
    const displayDate = dParts.length === 3 ? `${dParts[2]}/${dParts[1]}/${dParts[0]}` : date;

    htmlContent += `<div style="font-weight:bold;font-size:13px;margin-top:10px;margin-bottom:8px;color:#374151;border-bottom:1px solid #e5e7eb;padding-bottom:4px;">📅 Ngày ${displayDate}</div>`;
    htmlContent += `<div style="display:flex;flex-wrap:wrap;gap:8px;">`;
    sts.forEach(s => {
      const avail = s.available_seats ?? 0;
      const isFull    = avail === 0;
      const isInsuff  = avail > 0 && avail < neededCount;
      const isEnough  = avail >= neededCount;

      let borderColor, bgColor, cursor, badgeHtml, titleColor, onclick;

      if (isFull) {
        borderColor = '#e5e7eb'; bgColor = '#f9fafb'; cursor = 'not-allowed'; titleColor = '#9ca3af';
        badgeHtml = `<div style="font-size:11px;color:#ef4444;font-weight:600;margin-top:2px;">🔴 Hết chỗ</div>`;
        onclick = `return false`;
      } else if (isInsuff) {
        borderColor = '#f59e0b'; bgColor = '#fffbeb'; cursor = 'pointer'; titleColor = '#92400e';
        badgeHtml = `<div style="font-size:11px;color:#d97706;font-weight:600;margin-top:2px;">⚠️ Không đủ (còn ${avail}/${neededCount})</div>`;
        onclick = `window._selectRescheduleShowtime(${s.id}, this, ${avail}, ${neededCount})`;
      } else {
        borderColor = '#d1d5db'; bgColor = '#fff'; cursor = 'pointer'; titleColor = '#111827';
        badgeHtml = `<div style="font-size:11px;color:#10b981;font-weight:600;margin-top:2px;">✅ Trống: ${avail} ghế</div>`;
        onclick = `window._selectRescheduleShowtime(${s.id}, this, ${avail}, ${neededCount})`;
      }

      htmlContent += `<button type="button" onclick="${onclick}" 
        class="swal-st-btn${isFull ? ' full' : ''}" data-id="${s.id}" 
        style="border:2px solid ${borderColor};background:${bgColor};border-radius:10px;padding:10px 14px;cursor:${cursor};text-align:center;transition:all 0.2s;min-width:100px;opacity:${isFull ? '0.5' : '1'}">
          <div class="st-time" style="font-size:17px;font-weight:bold;color:${titleColor};">${s.start_time}</div>
          <div style="font-size:11px;color:#6b7280;margin-top:2px;">${s.room_name}</div>
          ${badgeHtml}
       </button>`;
    });
    htmlContent += `</div>`;
  }
  
  htmlContent += `</div>
           <input type="hidden" id="swal-new-showtime-id" value="">
           <style>
             .swal-st-btn:not(.full):hover { border-color: #3b82f6 !important; }
             .swal-st-btn.selected { border-color: #ef4444 !important; background-color: #fef2f2 !important; box-shadow: 0 0 0 1px #ef4444; }
             .swal-st-btn.selected .st-time { color: #ef4444 !important; }
           </style>
           </div>`;

  window._selectRescheduleShowtime = (id, el, avail, needed) => {
    if (avail < needed) {
      // Cảnh báo nhưng vẫn cho chọn
      el.title = `Chỉ còn ${avail}/${needed} ghế trống, bạn có thể đổi ít ghế hơn.`;
    }
    document.getElementById('swal-new-showtime-id').value = id;
    document.querySelectorAll('.swal-st-btn').forEach(btn => btn.classList.remove('selected'));
    el.classList.add('selected');
  };

  // Vòng lặp: Quay lại bước 1 nếu người dùng hủy bước 2
  let newShowtimeId = null;

  while (true) {
    // ── BƯỚC 1: Chọn suất chiếu ─────────────────────────────────────────
    const { isConfirmed: step1OK, value: chosenShowtimeId } = await Swal.fire({
      title: 'Đổi Lịch Chiếu',
      html: htmlContent,
      showCancelButton: true,
      confirmButtonText: 'Tiếp theo: Chọn ghế →',
      cancelButtonText: 'Hủy',
      preConfirm: () => {
        const val = document.getElementById('swal-new-showtime-id').value;
        if (!val) {
          Swal.showValidationMessage('Vui lòng chọn một suất chiếu mới');
          return false;
        }
        return parseInt(val);
      }
    });

    if (!step1OK) return; // Người dùng nhấn Hủy hoàn toàn

    newShowtimeId = chosenShowtimeId;

    // ── BƯỚC 2: Chọn ghế ────────────────────────────────────────────────
    rescheduleLoadingSeats.value = true;
    const newShowtime = otherShowtimes.find(s => s.id === newShowtimeId);
    rescheduleNewShowtime.value = newShowtime;

    const seatsRes = await axios.get(`/showtimes/${newShowtimeId}/seats`);
    const allSeats = seatsRes.data.seats;
    const availableSeats = allSeats.filter(s => s.status === 'available');
    rescheduleAvailableSeats.value = availableSeats;
    rescheduleLoadingSeats.value = false;

    if (availableSeats.length === 0) {
      await Swal.fire('Thông báo', 'Suất chiếu này đã hết ghế trống. Vui lòng chọn suất khác.', 'warning');
      continue;
    }

    const oldSeatLabels = bookingInfo.value.booking_details?.map(d => `${d.seat.row || ''}${d.seat.number || ''}`).join(', ') || '';

    // Sắp xếp ghế theo hàng (A→Z) rồi theo số (1→n)
    const sortedSeats = [...allSeats].sort((a, b) => {
      if (a.row_name < b.row_name) return -1;
      if (a.row_name > b.row_name) return 1;
      return parseInt(a.seat_number) - parseInt(b.seat_number);
    });

    // Nhóm theo hàng để hiển thị ngắt dòng
    const rowGroups = {};
    sortedSeats.forEach(s => {
      if (!rowGroups[s.row_name]) rowGroups[s.row_name] = [];
      rowGroups[s.row_name].push(s);
    });

    const seatHtml = Object.entries(rowGroups).map(([rowName, rowSeats]) => {
      const btnHtml = rowSeats.map(s => {
        // Bỏ qua các ghế ảo (ẩn, xóa, hoặc nửa kia của ghế đôi)
        if (['hidden', 'deleted', 'couple_hidden'].includes(s.type)) return '';

        const label = `${s.row_name}${s.seat_number}`;
        const isAvailable = s.status === 'available';
        const isSold    = s.status === 'sold';
        const isHolding = s.status === 'holding';
        const isCouple  = s.type === 'couple';

        let bg, border, color, cursor, onclick, title;

        if (isAvailable) {
          const typeColor = s.type === 'vip' ? '#ef4444' : s.type === 'couple' ? '#ec4899' : '#6b7280';
          bg = '#fff'; border = typeColor; color = typeColor; cursor = 'pointer';
          onclick = `window._toggleRescheduleSeat(${s.id}, this, ${neededCount})`;
          title = s.type === 'vip' ? 'VIP' : s.type === 'couple' ? 'Ghế đôi' : 'Ghế thường';
        } else if (isSold) {
          bg = '#374151'; border = '#374151'; color = '#fff'; cursor = 'not-allowed';
          onclick = ''; title = 'Đã bán';
        } else if (isHolding) {
          bg = '#f59e0b'; border = '#f59e0b'; color = '#fff'; cursor = 'not-allowed';
          onclick = ''; title = 'Đang giữ chỗ';
        } else { // broken / locked
          bg = '#fee2e2'; border = '#fca5a5'; color = '#ef4444'; cursor = 'not-allowed';
          onclick = ''; title = 'Bị khóa';
        }

        const dataAttrs = isAvailable
          ? `data-seat-id="${s.id}" data-color="${border}"`
          : '';

        // Ghế thường/VIP rộng 36px, ghế đôi rộng gấp đôi + margin = 78px
        const width = isCouple ? '78px' : '36px';

        return `<button type="button" ${onclick ? `onclick="${onclick}"` : ''} ${dataAttrs}
          style="width:${width};height:32px;margin:3px;padding:0;border-radius:6px;border:2px solid ${border};background:${bg};color:${color};font-weight:600;font-size:11px;cursor:${cursor};transition:all .2s;opacity:${isAvailable ? '1' : '0.65'};display:inline-flex;align-items:center;justify-content:center;box-sizing:border-box"
          title="${title}">
          ${label}
        </button>`;
      }).join('');
      // Mỗi hàng ghế chiếm 1 dòng riêng
      return `<div style="display:flex;flex-wrap:nowrap;justify-content:center;margin-bottom:4px">${btnHtml}</div>`;
    }).join('');


    window._rescheduleSeatIds = [];
    window._toggleRescheduleSeat = (seatId, el, maxCount) => {
      // Chỉ tìm ghế trong popup Swal, không ảnh hưởng sơ đồ chính
      const swalContainer = document.querySelector('.swal2-html-container');
      const allSwalSeats = () => swalContainer ? swalContainer.querySelectorAll('[data-seat-id]') : [];

      const color = el.getAttribute('data-color');
      const idx = window._rescheduleSeatIds.indexOf(seatId);
      if (idx > -1) {
        // Bỏ chọn
        window._rescheduleSeatIds.splice(idx, 1);
        el.style.background = '#fff';
        el.style.color = color;
        el.style.opacity = '1';
        el.style.cursor = 'pointer';
        // Mở lại tất cả ghế chưa chọn (chỉ trong popup)
        allSwalSeats().forEach(btn => {
          if (!window._rescheduleSeatIds.includes(parseInt(btn.getAttribute('data-seat-id')))) {
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
          }
        });
      } else {
        if (window._rescheduleSeatIds.length >= maxCount) return; // Đã đủ — khóa
        window._rescheduleSeatIds.push(seatId);
        el.style.background = color;
        el.style.color = '#fff';
        if (window._rescheduleSeatIds.length >= maxCount) {
          // Làm mờ các ghế chưa chọn (chỉ trong popup)
          allSwalSeats().forEach(btn => {
            const id = parseInt(btn.getAttribute('data-seat-id'));
            if (!window._rescheduleSeatIds.includes(id)) {
              btn.style.opacity = '0.35';
              btn.style.cursor = 'not-allowed';
            }
          });
        }
      }
      const counter = document.getElementById('swal-seat-counter');
      if (counter) {
        counter.textContent = `Đã chọn: ${window._rescheduleSeatIds.length}/${maxCount} ghế`;
        counter.style.color = window._rescheduleSeatIds.length >= maxCount ? '#10b981' : '#f59e0b';
      }
    };

    const newShowtimeLabel = `${newShowtime.start_time_display} - ${newShowtime.room_name || ''}`;

    const { isConfirmed: step2OK, value: finalSeatIds } = await Swal.fire({
      title: 'Chọn ghế cho suất mới',
      width: '650px',
      html:
        `<div style="text-align:left;font-size:13px;margin-bottom:12px">
          <p>Suất mới: <strong>${newShowtimeLabel}</strong></p>
          <p>Ghế cũ của khách: <strong style="color:#ef4444">${oldSeatLabels}</strong></p>
          <p id="swal-seat-counter" style="color:#f59e0b;font-weight:700;margin-top:8px">Đã chọn: 0/${neededCount} ghế</p>
        </div>
        <div style="max-height:350px;overflow-y:auto;padding:4px;border:1px solid #eee;border-radius:8px;text-align:center">
          ${seatHtml}
        </div>`,
      showCancelButton: true,
      confirmButtonText: 'Xác nhận đổi lịch',
      cancelButtonText: '← Quay lại chọn suất',
      preConfirm: () => {
        if (window._rescheduleSeatIds.length !== neededCount) {
          Swal.showValidationMessage(`Vui lòng chọn đúng ${neededCount} ghế`);
          return false;
        }

        let oldCoupleCount = 0;
        bookingInfo.value.booking_details.forEach(d => {
          if (d.seat.type === 'couple') oldCoupleCount++;
        });

        let newCoupleCount = 0;
        window._rescheduleSeatIds.forEach(id => {
          const s = allSeats.find(seat => seat.id === id);
          if (s && s.type === 'couple') newCoupleCount++;
        });

        if (oldCoupleCount !== newCoupleCount) {
          Swal.showValidationMessage(`Số lượng ghế Đôi mới (${newCoupleCount}) không khớp với đơn gốc (${oldCoupleCount})`);
          return false;
        }

        return [...window._rescheduleSeatIds];
      }
    });

    if (!step2OK) continue; // Quay lại bước 1

    // ── BƯỚC 3: Submit ────────────────────────────────────────────────────
    try {
      const seatMapping = {};
      bookingInfo.value.booking_details.forEach((detail, index) => {
         seatMapping[detail.seat_id] = finalSeatIds[index];
      });

      const { data: result } = await axios.post('/staff/compensation/reschedule', {
        booking_id: bookingInfo.value.id,
        new_showtime_id: newShowtimeId,
        seat_mapping: seatMapping
      });
      Swal.fire('Thành công! 🎬', result.message || 'Đổi lịch thành công!', 'success');
      refreshSeats();
      selectedSeat.value = null;
      bookingInfo.value = null;
    } catch (err) {
      Swal.fire('Lỗi', err.response?.data?.message || 'Lỗi hệ thống', 'error');
    }
    break; // Thoát vòng lặp
  }
};

const openRefundModal = async () => {
  if (!bookingInfo.value) return;
  const bookingId = bookingInfo.value.id;
  const bookingCode = bookingInfo.value.booking_code;
  const customerName = bookingInfo.value.user?.name || 'Khách';

  const result = await Swal.fire({
    title: 'Hoàn tiền vào ví?',
    html: `
      <div style="text-align: left; font-size: 14px;">
        <p><strong>Mã đơn:</strong> ${bookingCode}</p>
        <p><strong>Khách hàng:</strong> ${customerName}</p>
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
      booking_id: bookingId
    });
    Swal.fire('Thành công', data.message, 'success');
    refreshSeats();
    selectedSeat.value = null;
    bookingInfo.value = null;
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể hoàn tiền.', 'error');
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
  grid-template-columns: 350px 1fr 350px;
  gap: 24px;
  height: calc(100vh - 120px);
  min-height: 600px;
  transition: all 0.3s ease;
}

.monitor-grid.left-closed {
  grid-template-columns: 60px 1fr 350px;
}

.monitor-grid.right-closed {
  grid-template-columns: 350px 1fr 60px;
}

.monitor-grid.left-closed.right-closed {
  grid-template-columns: 60px 1fr 60px;
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

.btn-toggle.active {
  background: var(--bg-body);
  color: var(--accent-pink);
  border-color: rgba(216, 45, 139, 0.2);
}

.date-picker-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-surface);
  padding: 4px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
}

.date-picker-wrapper .date-input {
  border: none;
  background: transparent;
  padding: 4px 8px;
  font-weight: 600;
  color: var(--text-primary);
}

.date-picker-wrapper .date-input:focus {
  box-shadow: none;
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
  white-space: nowrap;
  overflow: hidden;
}

.col-header.clickable {
  cursor: pointer;
  user-select: none;
  transition: background 0.2s;
}

.col-header.clickable:hover {
  background: rgba(0,0,0,0.06);
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

/* Swap mode */
.seatmap-wrapper {
  position: relative;
}

.swap-mode-banner {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(245, 158, 11, 0.92);
  color: #fff;
  font-weight: 700;
  font-size: 14px;
  padding: 10px 20px;
  border-radius: 30px;
  white-space: nowrap;
  animation: pulse-banner 1.5s ease-in-out infinite;
  box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
  pointer-events: none;
  z-index: 10;
}

@keyframes pulse-banner {
  0%, 100% { opacity: 1; transform: translateX(-50%) scale(1); }
  50% { opacity: 0.8; transform: translateX(-50%) scale(1.03); }
}

.swap-mode-active {
  outline: 2px dashed #f59e0b;
  border-radius: 8px;
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

.booking-info-box {
  background: rgba(16, 185, 129, 0.05);
  border: 1px dashed rgba(16, 185, 129, 0.3);
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
}

.booking-info-box.loading {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
}

.booking-info-box p {
  margin: 0 0 6px 0;
  color: var(--text-primary);
}
.booking-info-box p:last-child {
  margin-bottom: 0;
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

.alert-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
  font-weight: 600;
  font-size: 13px;
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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-dark {
  background: #334155;
}
.btn-dark:hover:not(:disabled) { background: #1e293b; }

.btn-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}
.btn-danger:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.2);
}

.btn-warning {
  background: #f59e0b;
}
.btn-warning:hover:not(:disabled) { background: #d97706; }

.btn-danger-solid {
  background: #ef4444;
}
.btn-danger-solid:hover:not(:disabled) { background: #dc2626; }

.btn-success-solid {
  background: #10b981;
}
.btn-success-solid:hover:not(:disabled) { background: #059669; }

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.6);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: var(--bg-card);
  padding: 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  color: var(--text-primary);
  box-shadow: 0 10px 25px rgba(0,0,0,0.5);
  border: 1px solid var(--border-color);
}

.modal-content h3 {
  margin-top: 0;
  color: var(--accent-pink);
  margin-bottom: 10px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  font-size: 14px;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: rgba(255,255,255,0.05);
  color: var(--text-primary);
}
.form-control:focus {
  outline: none;
  border-color: var(--accent-pink);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: normal !important;
}

.modal-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

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
