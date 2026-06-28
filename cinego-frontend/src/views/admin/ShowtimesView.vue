<template>
  <div class="stv">
    <!-- ===== HERO: BẢNG ĐIỀU PHỐI (cinematic dark marquee) ===== -->
    <header class="stv-hero">
      <div class="stv-hero__grain"></div>
      <div class="stv-hero__filmstrip"></div>

      <div class="stv-hero__row">
        <div class="stv-hero__intro">
          <span class="stv-hero__kicker">🎬 PHÒNG ĐIỀU PHỐI</span>
          <h2 class="stv-hero__title">Lịch Chiếu &amp; Suất Phim</h2>
          <p class="stv-hero__desc">
            Xếp phim vào phòng đúng giờ — hệ thống <b>tự động chống trùng lịch</b> trong cùng một phòng.
          </p>
        </div>

        <button class="stv-hero__cta" @click="openCreateModal">
          <span class="stv-hero__cta-plus">＋</span>
          <span>Thêm Suất Chiếu</span>
        </button>
      </div>

      <div class="stv-hero__stats">
        <div class="stv-stat">
          <span class="stv-stat__num">{{ showtimes.length }}</span>
          <span class="stv-stat__label">Tổng suất chiếu</span>
        </div>
        <div class="stv-stat stv-stat--mint">
          <span class="stv-stat__num">{{ activeCount }}</span>
          <span class="stv-stat__label">Đang hoạt động</span>
        </div>
        <div class="stv-stat stv-stat--gold">
          <span class="stv-stat__num">{{ todayCount }}</span>
          <span class="stv-stat__label">Chiếu hôm nay</span>
        </div>
      </div>
    </header>

    <!-- ===== TOOLBAR: tìm kiếm + lọc định dạng ===== -->
    <div class="stv-toolbar">
      <label class="stv-search">
        <span class="stv-search__icon">🔍</span>
        <input v-model="searchQuery" type="text" placeholder="Tìm theo tên phim hoặc phòng chiếu…" />
        <button v-if="searchQuery" class="stv-search__clear" @click="searchQuery = ''" aria-label="Xóa">✕</button>
      </label>

      <div class="stv-segment">
        <button
          v-for="d in dateOptions"
          :key="d"
          class="stv-segment__btn"
          :class="{ active: dateFilter === d }"
          @click="dateFilter = d"
        >{{ d }}</button>
      </div>

      <div class="stv-segment">
        <button
          v-for="f in formatOptions"
          :key="f"
          class="stv-segment__btn"
          :class="{ active: formatFilter === f }"
          @click="formatFilter = f"
        >{{ f }}</button>
      </div>
    </div>

    <!-- ===== LOADING ===== -->
    <div v-if="loading" class="stv-loading">
      <div class="stv-spinner"></div>
      <p>Đang tải lịch chiếu từ hệ thống…</p>
    </div>

    <!-- ===== EMPTY ===== -->
    <div v-else-if="filteredShowtimes.length === 0" class="stv-empty">
      <div class="stv-empty__art">🎞️</div>
      <h3>{{ showtimes.length === 0 ? 'Chưa có suất chiếu nào' : 'Không tìm thấy suất chiếu phù hợp' }}</h3>
      <p>{{ showtimes.length === 0 ? 'Hãy tạo suất chiếu đầu tiên để khởi động phòng vé.' : 'Thử đổi từ khóa hoặc bộ lọc định dạng.' }}</p>
      <button v-if="showtimes.length === 0" class="stv-empty__btn" @click="openCreateModal">＋ Tạo suất chiếu</button>
    </div>

    <!-- ===== TICKET GRID ===== -->
    <div v-else class="stv-grid">
      <article
        v-for="st in filteredShowtimes"
        :key="st.id"
        class="ticket"
        :class="{ 'ticket--off': st.status !== 'active' }"
        @click="openDetail(st.id)"
        title="Bấm để xem chi tiết suất chiếu"
      >
        <!-- Cuống vé: giờ bắt đầu -->
        <div class="ticket__stub">
          <span class="ticket__stub-time">{{ timeOnly(st.start_time) }}</span>
          <span class="ticket__stub-date">{{ dateOnly(st.start_time) }}</span>
          <span class="ticket__stub-id">#{{ st.id }}</span>
        </div>

        <!-- Đường xé răng cưa -->
        <div class="ticket__tear"></div>

        <!-- Thân vé -->
        <div class="ticket__body">
          <div class="ticket__top">
            <h3 class="ticket__movie" :title="st.movie_title">{{ st.movie_title }}</h3>
            <span class="ticket__status" :class="st.status === 'active' ? 'is-on' : 'is-off'">
              <span class="ticket__status-dot"></span>
              {{ st.status === 'active' ? 'Hoạt động' : 'Đã hủy' }}
            </span>
          </div>

          <!-- Timeline giờ chiếu -->
          <div class="ticket__timeline">
            <span class="tl-node tl-node--start"></span>
            <span class="tl-time">{{ timeOnly(st.start_time) }}</span>
            <span class="tl-track">
              <span class="tl-dur">{{ durationLabel(st) }}</span>
            </span>
            <span class="tl-time">{{ timeOnly(st.end_time) }}</span>
            <span class="tl-node tl-node--end"></span>
          </div>

          <!-- Nhãn thông tin -->
          <div class="ticket__tags">
            <span class="tg tg--room">🏛️ {{ st.room_name }}</span>
            <span class="tg tg--format">{{ st.format }}</span>
            <span class="tg tg--trans">💬 {{ st.translation }}</span>
          </div>
        </div>

        <button class="ticket__del" @click.stop="askDelete(st)" title="Xóa suất chiếu">🗑️</button>
      </article>
    </div>

    <!-- ===== MODAL: TẠO SUẤT CHIẾU ===== -->
    <transition name="modal-fade">
      <div v-if="showModal" class="stv-backdrop" @click.self="closeModal">
        <div class="stv-modal">
          <!-- Marquee header -->
          <div class="stv-modal__marquee">
            <div class="stv-modal__marquee-dots"></div>
            <h3>✨ Lên Lịch Suất Chiếu Mới</h3>
            <button class="stv-modal__close" @click="closeModal" aria-label="Đóng">✕</button>
          </div>

          <form @submit.prevent="saveShowtime" class="stv-form">
            <!-- Banner lỗi chống trùng lịch -->
            <transition name="error-pop">
              <div v-if="formError" class="error-banner" role="alert">
                <span class="error-banner__icon">⚠️</span>
                <div class="error-banner__body">
                  <strong class="error-banner__title">Không thể xếp lịch</strong>
                  <p class="error-banner__msg">{{ formError }}</p>
                </div>
                <button type="button" class="error-banner__close" @click="formError = ''" aria-label="Đóng">✕</button>
              </div>
            </transition>

            <div class="stv-field">
              <label>Chọn phim <i>*</i></label>
              <select v-model="form.movie_id" required class="stv-input stv-input--select" @change="onMovieChange">
                <option value="" disabled>— Chọn phim cần chiếu —</option>
                <option v-for="movie in movies" :key="movie.id" :value="movie.id">
                  {{ movie.title }} • {{ movie.duration }} phút
                </option>
              </select>
            </div>

            <div class="stv-field">
              <label>Phòng chiếu <i>*</i></label>
              <select
                v-model="form.room_id"
                required
                class="stv-input stv-input--select"
                :class="{ 'is-error': formError }"
                @change="formError = ''"
              >
                <option value="" disabled>— Chọn phòng chiếu —</option>
                <option v-for="room in rooms" :key="room.id" :value="room.id">
                  {{ room.name }} • {{ room.total_seats }} ghế
                </option>
              </select>
            </div>

            <div class="stv-grid2">
              <div class="stv-field">
                <label>Giờ bắt đầu <i>*</i></label>
                <input
                  v-model="form.start_time"
                  type="datetime-local"
                  required
                  class="stv-input"
                  :class="{ 'is-error': formError }"
                  @change="onStartTimeChange"
                />
              </div>
              <div class="stv-field">
                <label>Giờ kết thúc <span class="stv-auto">tự tính</span></label>
                <input v-model="form.end_time" type="datetime-local" required readonly class="stv-input stv-input--readonly" />
              </div>
            </div>

            <div class="stv-grid2">
              <div class="stv-field">
                <label>Định dạng <i>*</i></label>
                <select v-model="form.format" required class="stv-input stv-input--select">
                  <option value="2D">2D</option>
                  <option value="3D">3D</option>
                  <option value="IMAX">IMAX</option>
                </select>
              </div>
              <div class="stv-field">
                <label>Dịch thuật <i>*</i></label>
                <select v-model="form.translation" required class="stv-input stv-input--select">
                  <option value="Phụ đề">Phụ đề (Vietsub)</option>
                  <option value="Thuyết minh">Thuyết minh</option>
                </select>
              </div>
            </div>

            <!-- Preview vé trực tiếp -->
            <transition name="preview-pop">
              <div v-if="previewMovie" class="stv-preview">
                <span class="stv-preview__tag">Xem trước</span>
                <div class="stv-preview__line">
                  <strong>{{ previewMovie.title }}</strong>
                  <span class="stv-preview__fmt">{{ form.format }}</span>
                </div>
                <div class="stv-preview__time" v-if="form.start_time">
                  🕒 {{ timeOnly(form.start_time) }} → {{ form.end_time ? timeOnly(form.end_time) : '—' }}
                  <span class="stv-preview__dur">({{ previewMovie.duration }} phút)</span>
                </div>
                <div class="stv-preview__time" v-else>Chọn giờ bắt đầu để xem khung giờ chiếu.</div>
              </div>
            </transition>

            <div class="stv-form__footer">
              <button type="button" class="stv-btn stv-btn--ghost" @click="closeModal">Hủy bỏ</button>
              <button type="submit" class="stv-btn stv-btn--solid" :disabled="submitting">
                <span v-if="submitting" class="stv-btn__spin"></span>
                {{ submitting ? 'Đang tạo…' : 'Xác nhận lên lịch' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- ===== MODAL: CHI TIẾT SUẤT CHIẾU ===== -->
    <transition name="modal-fade">
      <div v-if="showDetailModal" class="stv-backdrop" @click.self="closeDetail">
        <div class="stv-modal">
          <div class="stv-modal__marquee">
            <div class="stv-modal__marquee-dots"></div>
            <h3>🎟️ Chi Tiết Suất Chiếu</h3>
            <button class="stv-modal__close" @click="closeDetail" aria-label="Đóng">✕</button>
          </div>

          <div class="stv-detail">
            <!-- Loading -->
            <div v-if="detailLoading" class="stv-loading">
              <div class="stv-spinner"></div>
              <p>Đang tải chi tiết…</p>
            </div>

            <template v-else-if="detail">
              <!-- Tiêu đề phim -->
              <div class="dt-head">
                <img
                  v-if="detail.movie && detail.movie.poster_url"
                  :src="detail.movie.poster_url"
                  :alt="detail.movie_title"
                  class="dt-poster"
                />
                <div class="dt-head__info">
                  <h4 class="dt-title">{{ detail.movie_title }}</h4>
                  <div class="dt-badges">
                    <span class="tg tg--format">{{ detail.format }}</span>
                    <span class="tg tg--trans">💬 {{ detail.translation }}</span>
                    <span
                      class="ticket__status"
                      :class="detail.status === 'active' ? 'is-on' : 'is-off'"
                    >
                      <span class="ticket__status-dot"></span>
                      {{ detail.status === 'active' ? 'Hoạt động' : 'Đã hủy' }}
                    </span>
                  </div>
                  <p v-if="detail.movie" class="dt-sub">
                    ⏱️ {{ detail.movie.duration }} phút
                    <span v-if="detail.movie.rating"> • ⭐ {{ detail.movie.rating }}</span>
                  </p>
                </div>
              </div>

              <!-- Thông tin suất -->
              <div class="dt-rows">
                <div class="dt-row">
                  <span class="dt-row__label">🏛️ Phòng chiếu</span>
                  <span class="dt-row__value">{{ detail.room_name }}</span>
                </div>
                <div class="dt-row">
                  <span class="dt-row__label">📅 Ngày chiếu</span>
                  <span class="dt-row__value">{{ dateOnly(detail.start_time) }}</span>
                </div>
                <div class="dt-row">
                  <span class="dt-row__label">🕒 Khung giờ</span>
                  <span class="dt-row__value">
                    {{ timeOnly(detail.start_time) }} → {{ timeOnly(detail.end_time) }}
                    <em class="dt-dur">({{ durationLabel(detail) }})</em>
                  </span>
                </div>
                <div class="dt-row">
                  <span class="dt-row__label">#️⃣ Mã suất</span>
                  <span class="dt-row__value">#{{ detail.id }}</span>
                </div>
              </div>

              <!-- Tình hình đặt vé -->
              <div class="dt-seats">
                <div class="dt-seats__head">
                  <span>🎫 Tình hình đặt vé</span>
                  <strong>{{ detail.booked_seats }} / {{ detail.total_seats }} ghế</strong>
                </div>
                <div class="dt-seats__bar">
                  <div class="dt-seats__fill" :style="{ width: seatPercent + '%' }"></div>
                </div>
                <div class="dt-seats__foot">
                  <span>Đã bán {{ seatPercent }}%</span>
                  <span>Còn trống {{ detail.available_seats }} ghế</span>
                </div>
              </div>
            </template>

            <div class="stv-form__footer">
              <button type="button" class="stv-btn stv-btn--ghost" @click="closeDetail">Đóng</button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ===== TOAST: "vé vừa được in" (tự thiết kế) ===== -->
    <transition name="ticket-print">
      <div
        v-if="toast.show"
        class="cg-toast"
        :class="`cg-toast--${toast.type}`"
        role="status"
      >
        <!-- Cuống vé có dấu mộc -->
        <div class="cg-toast__stub">
          <span class="cg-toast__stamp">
            <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </span>
          <span class="cg-toast__stub-brand">CINEGO</span>
          <span class="cg-toast__stub-sprockets"></span>
        </div>

        <!-- Đường xé răng cưa -->
        <span class="cg-toast__perf"></span>

        <!-- Thân thông báo -->
        <div class="cg-toast__body">
          <span class="cg-toast__kicker">🎬 {{ toast.kicker }}</span>
          <strong class="cg-toast__title">{{ toast.title }}</strong>
          <p class="cg-toast__msg">{{ toast.message }}</p>
        </div>

        <button class="cg-toast__close" @click="hideToast" aria-label="Đóng">✕</button>
        <span class="cg-toast__timer"></span>
      </div>
    </transition>

    <!-- ===== HỘP THOẠI XÁC NHẬN XÓA (tự thiết kế) ===== -->
    <transition name="modal-fade">
      <div v-if="confirmState.show" class="stv-backdrop cg-confirm-backdrop" @click.self="cancelDelete">
        <div class="cg-confirm">
          <div class="cg-confirm__ribbon">⚠️ KHÔNG THỂ HOÀN TÁC</div>

          <div class="cg-confirm__icon">
            <span class="cg-confirm__icon-glow"></span>
            🎫
          </div>

          <h3 class="cg-confirm__title">Gỡ suất chiếu khỏi lịch?</h3>
          <p class="cg-confirm__desc">
            Bạn sắp xóa vĩnh viễn suất chiếu sau khỏi hệ thống phòng vé:
          </p>

          <div v-if="confirmState.target" class="cg-confirm__ticket">
            <span class="cg-confirm__ticket-time">{{ timeOnly(confirmState.target.start_time) }}</span>
            <div class="cg-confirm__ticket-info">
              <strong>{{ confirmState.target.movie_title }}</strong>
              <span>🏛️ {{ confirmState.target.room_name }} • {{ dateOnly(confirmState.target.start_time) }}</span>
            </div>
            <span class="cg-confirm__ticket-id">#{{ confirmState.target.id }}</span>
          </div>

          <div class="cg-confirm__actions">
            <button type="button" class="stv-btn stv-btn--ghost" @click="cancelDelete">Giữ lại</button>
            <button type="button" class="cg-confirm__del" @click="confirmDelete">🗑️ Xóa suất chiếu</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';

const showtimes = ref([]);
const movies = ref([]);
const rooms = ref([]);
const loading = ref(false);
const showModal = ref(false);
const submitting = ref(false);
const formError = ref('');

const searchQuery = ref('');
const formatFilter = ref('Tất cả');
const formatOptions = ['Tất cả', '2D', '3D', 'IMAX'];

const dateFilter = ref('Tất cả');
const dateOptions = ['Tất cả', 'Hôm nay', 'Sắp tới'];

// ----- Chi tiết suất chiếu -----
const showDetailModal = ref(false);
const detail = ref(null);
const detailLoading = ref(false);

// ----- Toast "đã in vé" (thông báo tự thiết kế) -----
const toast = ref({ show: false, kicker: '', title: '', message: '', type: 'success' });
let toastTimer = null;
const showToast = (title, message, { kicker = 'SUẤT CHIẾU MỚI', type = 'success' } = {}) => {
  if (toastTimer) clearTimeout(toastTimer);
  toast.value = { show: true, kicker, title, message, type };
  toastTimer = setTimeout(() => { toast.value.show = false; }, 4200);
};
const hideToast = () => {
  if (toastTimer) clearTimeout(toastTimer);
  toast.value.show = false;
};

const form = ref({
  movie_id: '',
  room_id: '',
  start_time: '',
  end_time: '',
  format: '2D',
  translation: 'Phụ đề'
});

/* ---------- Computed ---------- */
const activeCount = computed(() => showtimes.value.filter(s => s.status === 'active').length);

const isToday = (dt) => {
  if (!dt) return false;
  const d = new Date(dt);
  const now = new Date();
  return d.getDate() === now.getDate() && d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
};

const todayCount = computed(() => showtimes.value.filter(s => isToday(s.start_time)).length);

const filteredShowtimes = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  const now = new Date();
  return showtimes.value.filter(s => {
    const matchFormat = formatFilter.value === 'Tất cả' || s.format === formatFilter.value;
    const matchSearch = !q
      || (s.movie_title || '').toLowerCase().includes(q)
      || (s.room_name || '').toLowerCase().includes(q);
    const matchDate = dateFilter.value === 'Tất cả'
      || (dateFilter.value === 'Hôm nay' && isToday(s.start_time))
      || (dateFilter.value === 'Sắp tới' && s.start_time && new Date(s.start_time) >= now);
    return matchFormat && matchSearch && matchDate;
  });
});

const previewMovie = computed(() => movies.value.find(m => m.id === form.value.movie_id) || null);

/* ---------- Helpers ---------- */
const timeOnly = (dt) => {
  if (!dt) return '--:--';
  return new Date(dt).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};

const dateOnly = (dt) => {
  if (!dt) return '';
  return new Date(dt).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const durationLabel = (st) => {
  if (!st.start_time || !st.end_time) return '';
  const mins = Math.round((new Date(st.end_time) - new Date(st.start_time)) / 60000);
  if (mins <= 0) return '';
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  return h > 0 ? `${h}h${m ? m + "'" : ''}` : `${m}'`;
};

/* ---------- Modal ---------- */
const openCreateModal = () => {
  form.value = { movie_id: '', room_id: '', start_time: '', end_time: '', format: '2D', translation: 'Phụ đề' };
  formError.value = '';
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const onMovieChange = () => { formError.value = ''; calculateEndTime(); };
const onStartTimeChange = () => { formError.value = ''; calculateEndTime(); };

const calculateEndTime = () => {
  if (!form.value.start_time || !form.value.movie_id) return;
  const movie = movies.value.find(m => m.id === form.value.movie_id);
  if (!movie) return;
  const start = new Date(form.value.start_time);
  const end = new Date(start.getTime() + movie.duration * 60 * 1000);
  const tzOffset = end.getTimezoneOffset() * 60000;
  form.value.end_time = new Date(end.getTime() - tzOffset).toISOString().slice(0, 16);
};

/* ---------- API ---------- */
const fetchShowtimes = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/showtimes');
    showtimes.value = res.data;
  } catch (err) {
    console.error('Fetch showtimes error:', err);
  } finally {
    loading.value = false;
  }
};

const fetchMovies = async () => {
  try {
    const res = await api.get('/movies');
    movies.value = res.data.data || res.data;
  } catch (err) {
    console.error('Fetch movies error:', err);
  }
};

const fetchRooms = async () => {
  try {
    const res = await api.get('/rooms');
    rooms.value = res.data.data || res.data;
  } catch (err) {
    console.error('Fetch rooms error:', err);
  }
};

/* ---------- Validate (hiển thị trong banner của modal) ---------- */
const validateForm = () => {
  const errors = [];
  if (!form.value.movie_id) errors.push('Vui lòng chọn phim cần chiếu.');
  if (!form.value.room_id) errors.push('Vui lòng chọn phòng chiếu.');
  if (!form.value.start_time) errors.push('Vui lòng chọn giờ bắt đầu.');
  if (!form.value.end_time) errors.push('Chưa tính được giờ kết thúc (kiểm tra lại phim & giờ bắt đầu).');
  if (form.value.start_time && form.value.end_time
    && new Date(form.value.end_time) <= new Date(form.value.start_time)) {
    errors.push('Giờ kết thúc phải sau giờ bắt đầu.');
  }
  if (form.value.start_time && new Date(form.value.start_time) < new Date()) {
    errors.push('Giờ bắt đầu không được nằm trong quá khứ.');
  }
  if (!form.value.format) errors.push('Vui lòng chọn định dạng.');
  if (!form.value.translation) errors.push('Vui lòng chọn loại dịch thuật.');
  return errors;
};

const saveShowtime = async () => {
  const errors = validateForm();
  if (errors.length) {
    formError.value = errors.join('\n');
    return;
  }

  submitting.value = true;
  formError.value = '';
  try {
    await api.post('/admin/showtimes', form.value);
    const movieName = previewMovie.value ? previewMovie.value.title : 'Suất chiếu';
    const timeLabel = form.value.start_time
      ? `${dateOnly(form.value.start_time)} • ${timeOnly(form.value.start_time)}`
      : '';
    showToast(movieName, `Đã lên lịch ${timeLabel} — vé sẵn sàng mở bán.`, { kicker: 'ĐÃ IN SUẤT CHIẾU' });
    showModal.value = false;
    await fetchShowtimes();
  } catch (err) {
    console.error('Save showtime error:', err);
    const res = err.response?.data;
    if (res?.message) {
      formError.value = res.message;
    } else if (res?.errors) {
      formError.value = Object.values(res.errors).flat()[0];
    } else {
      formError.value = 'Có lỗi xảy ra khi tạo suất chiếu. Vui lòng thử lại!';
    }
  } finally {
    submitting.value = false;
  }
};

/* ---------- Xóa suất chiếu (xác nhận bằng hộp thoại tự thiết kế) ---------- */
const confirmState = ref({ show: false, target: null });

const askDelete = (st) => {
  confirmState.value = { show: true, target: st };
};
const cancelDelete = () => {
  confirmState.value = { show: false, target: null };
};
const confirmDelete = async () => {
  const st = confirmState.value.target;
  if (!st) return;
  confirmState.value = { show: false, target: null };
  try {
    await api.delete(`/admin/showtimes/${st.id}`);
    showToast('Đã gỡ suất chiếu', `Suất chiếu #${st.id} đã được xóa khỏi lịch.`, { kicker: 'HỦY SUẤT CHIẾU', type: 'danger' });
    await fetchShowtimes();
  } catch (err) {
    console.error('Delete showtime error:', err);
    showToast('Không thể xóa', 'Đã xảy ra lỗi khi gỡ suất chiếu. Vui lòng thử lại!', { kicker: 'LỖI HỆ THỐNG', type: 'danger' });
  }
};

/* ---------- Chi tiết suất chiếu ---------- */
const openDetail = async (id) => {
  showDetailModal.value = true;
  detailLoading.value = true;
  detail.value = null;
  try {
    const res = await api.get(`/admin/showtimes/${id}`);
    detail.value = res.data;
  } catch (err) {
    console.error('Fetch showtime detail error:', err);
    showToast('Không tải được chi tiết', 'Vui lòng thử lại sau giây lát.', { kicker: 'LỖI HỆ THỐNG', type: 'danger' });
    showDetailModal.value = false;
  } finally {
    detailLoading.value = false;
  }
};

const closeDetail = () => {
  showDetailModal.value = false;
  detail.value = null;
};

const seatPercent = computed(() => {
  if (!detail.value || !detail.value.total_seats) return 0;
  return Math.round((detail.value.booked_seats / detail.value.total_seats) * 100);
});

onMounted(async () => {
  await fetchShowtimes();
  await fetchMovies();
  await fetchRooms();
});
</script>

<style scoped>
.stv {
  display: flex;
  flex-direction: column;
  gap: 24px;
  color: #1e293b;
}

/* ===================== HERO ===================== */
.stv-hero {
  position: relative;
  overflow: hidden;
  border-radius: 22px;
  padding: 30px 34px 26px;
  color: #fff;
  background:
    radial-gradient(circle at 88% -30%, rgba(229, 9, 20, 0.55) 0%, transparent 45%),
    radial-gradient(circle at 10% 120%, rgba(255, 191, 0, 0.18) 0%, transparent 40%),
    linear-gradient(125deg, #1a0205 0%, #4a0610 45%, #7d0411 100%);
  box-shadow: 0 22px 48px rgba(123, 4, 17, 0.38);
  isolation: isolate;
}
/* hạt phim mờ */
.stv-hero__grain {
  position: absolute;
  inset: 0;
  z-index: -1;
  opacity: 0.5;
  background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
  background-size: 4px 4px;
}
/* cuộn phim chạy dọc cạnh phải */
.stv-hero__filmstrip {
  position: absolute;
  top: 0; bottom: 0; right: 0;
  width: 26px;
  z-index: -1;
  background:
    linear-gradient(#0000 0 0) padding-box,
    repeating-linear-gradient(to bottom, transparent 0 10px, rgba(255, 255, 255, 0.16) 10px 18px);
  border-left: 2px dashed rgba(255, 255, 255, 0.18);
  -webkit-mask: linear-gradient(to left, #000 60%, transparent);
          mask: linear-gradient(to left, #000 60%, transparent);
}

.stv-hero__row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}
.stv-hero__kicker {
  display: inline-block;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 2.5px;
  padding: 6px 14px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(6px);
}
.stv-hero__title {
  margin: 14px 0 8px;
  font-size: 34px;
  font-weight: 800;
  letter-spacing: -0.5px;
  line-height: 1.1;
  text-shadow: 0 4px 18px rgba(0, 0, 0, 0.35);
}
.stv-hero__desc {
  margin: 0;
  max-width: 540px;
  font-size: 15px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.82);
}
.stv-hero__desc b { color: #ffd6da; font-weight: 700; }

.stv-hero__cta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 24px;
  border: none;
  border-radius: 14px;
  background: #fff;
  color: #9b000e;
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.28);
  transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.22s;
  white-space: nowrap;
}
.stv-hero__cta:hover { transform: translateY(-3px) scale(1.03); box-shadow: 0 16px 34px rgba(0, 0, 0, 0.36); }
.stv-hero__cta-plus {
  display: grid; place-items: center;
  width: 24px; height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e50914, #9b000e);
  color: #fff; font-size: 18px; font-weight: 700;
}

.stv-hero__stats {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 26px;
}
.stv-stat {
  flex: 1;
  min-width: 130px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 14px 18px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(8px);
  border-left: 4px solid #ff5a64;
}
.stv-stat--mint { border-left-color: #2ee6a6; }
.stv-stat--gold { border-left-color: #ffce4d; }
.stv-stat__num { font-size: 28px; font-weight: 800; line-height: 1; }
.stv-stat__label { font-size: 12.5px; font-weight: 600; color: rgba(255, 255, 255, 0.78); }

/* ===================== TOOLBAR ===================== */
.stv-toolbar {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.stv-search {
  flex: 1;
  min-width: 240px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 16px;
  height: 48px;
  background: #fff;
  border: 1.5px solid #ececf1;
  border-radius: 14px;
  transition: all 0.2s;
}
.stv-search:focus-within { border-color: #e50914; box-shadow: 0 0 0 4px rgba(229, 9, 20, 0.1); }
.stv-search__icon { font-size: 15px; opacity: 0.6; }
.stv-search input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 15px;
  color: #1e293b;
}
.stv-search__clear {
  border: none; background: #f1f5f9; color: #64748b;
  width: 22px; height: 22px; border-radius: 50%;
  cursor: pointer; font-size: 11px; line-height: 1;
}
.stv-search__clear:hover { background: #fee2e2; color: #e50914; }

.stv-segment {
  display: inline-flex;
  padding: 5px;
  background: #f4f1f4;
  border-radius: 14px;
  gap: 4px;
}
.stv-segment__btn {
  border: none;
  background: transparent;
  padding: 9px 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}
.stv-segment__btn:hover { color: #9b000e; }
.stv-segment__btn.active {
  background: #fff;
  color: #e50914;
  box-shadow: 0 3px 10px rgba(229, 9, 20, 0.18);
}

/* ===================== LOADING / EMPTY ===================== */
.stv-loading {
  display: flex; flex-direction: column; align-items: center; gap: 16px;
  padding: 70px 0; color: #94a3b8; font-weight: 600;
}
.stv-spinner {
  width: 42px; height: 42px; border-radius: 50%;
  border: 4px solid #f1e3e5; border-top-color: #e50914;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.stv-empty {
  display: flex; flex-direction: column; align-items: center; text-align: center;
  gap: 8px; padding: 64px 20px;
  background: #fff;
  border: 2px dashed #f0d5d9;
  border-radius: 22px;
}
.stv-empty__art { font-size: 54px; filter: grayscale(0.1); margin-bottom: 6px; animation: float 3s ease-in-out infinite; }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.stv-empty h3 { margin: 0; font-size: 19px; font-weight: 800; color: #1e293b; }
.stv-empty p { margin: 0; color: #94a3b8; font-size: 14.5px; }
.stv-empty__btn {
  margin-top: 14px;
  border: none; cursor: pointer;
  padding: 12px 24px; border-radius: 12px;
  background: linear-gradient(135deg, #e50914, #9b000e);
  color: #fff; font-weight: 800; font-size: 14px;
  box-shadow: 0 8px 20px rgba(229, 9, 20, 0.28);
}

/* ===================== TICKET GRID ===================== */
.stv-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(370px, 1fr));
  gap: 22px;
}

.ticket {
  position: relative;
  display: grid;
  grid-template-columns: 96px 18px 1fr;
  background: #fff;
  border-radius: 18px;
  border: 1px solid #f3dde0;
  box-shadow: 0 6px 22px rgba(15, 23, 42, 0.07);
  transition: transform 0.26s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.26s;
  overflow: hidden;
  cursor: pointer;
}
.ticket:hover {
  transform: translateY(-5px);
  box-shadow: 0 18px 38px rgba(229, 9, 20, 0.16);
}
.ticket--off { opacity: 0.62; filter: grayscale(0.35); }

/* Cuống vé */
.ticket__stub {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  padding: 16px 6px;
  background: linear-gradient(165deg, #e50914 0%, #9b000e 100%);
  color: #fff;
  position: relative;
}
.ticket__stub::after {
  content: '';
  position: absolute;
  inset: 8px auto 8px 8px;
  width: 0;
}
.ticket__stub-time { font-size: 23px; font-weight: 800; font-family: 'Courier New', monospace; letter-spacing: 0.5px; }
.ticket__stub-date { font-size: 11px; font-weight: 600; opacity: 0.85; text-align: center; }
.ticket__stub-id {
  margin-top: 5px;
  font-size: 10.5px; font-weight: 700;
  padding: 2px 8px; border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
}

/* Đường xé răng cưa giữa cuống và thân */
.ticket__tear {
  position: relative;
  background: #fff;
}
.ticket__tear::before {
  content: '';
  position: absolute;
  top: 0; bottom: 0; left: 50%;
  transform: translateX(-50%);
  border-left: 2.5px dashed #f3c9cd;
}
/* hai lỗ tròn khoét trên & dưới */
.ticket__tear::after {
  content: '';
  position: absolute;
  left: 50%;
  top: -9px;
  transform: translateX(-50%);
  width: 16px; height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow:
    0 0 0 1px #f3dde0,
    0 calc(100% + 18px) 0 0 #fff,
    0 calc(100% + 18px) 0 1px #f3dde0;
}

/* Thân vé */
.ticket__body {
  padding: 16px 50px 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}
.ticket__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}
.ticket__movie {
  margin: 0;
  font-size: 17px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.25;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.ticket__status {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  flex-shrink: 0;
  font-size: 11.5px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  white-space: nowrap;
}
.ticket__status.is-on { background: #e6f9f0; color: #047857; }
.ticket__status.is-off { background: #fee2e2; color: #b91c1c; }
.ticket__status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.ticket__status.is-on .ticket__status-dot { animation: livepulse 1.5s ease-in-out infinite; }
@keyframes livepulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(4, 120, 87, 0.5); } 50% { box-shadow: 0 0 0 5px rgba(4, 120, 87, 0); } }

/* Timeline */
.ticket__timeline {
  display: flex;
  align-items: center;
  gap: 8px;
}
.tl-node { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.tl-node--start { background: #e50914; box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.15); }
.tl-node--end { background: #94a3b8; box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.18); }
.tl-time { font-size: 13px; font-weight: 800; color: #334155; font-family: 'Courier New', monospace; }
.tl-track {
  flex: 1;
  position: relative;
  height: 3px;
  border-radius: 999px;
  background: repeating-linear-gradient(to right, #e2c4c7 0 6px, transparent 6px 12px);
  display: flex;
  justify-content: center;
}
.tl-dur {
  position: absolute;
  top: -10px;
  font-size: 10.5px;
  font-weight: 800;
  color: #9b000e;
  background: #fff;
  padding: 0 6px;
}

/* Tags */
.ticket__tags { display: flex; flex-wrap: wrap; gap: 7px; }
.tg {
  font-size: 12px;
  font-weight: 700;
  padding: 5px 11px;
  border-radius: 8px;
}
.tg--room { background: #f1f5f9; color: #334155; }
.tg--format { background: #fee2e2; color: #b91c1c; }
.tg--trans { background: #eef6f1; color: #047857; }

.ticket__del {
  position: absolute;
  top: 12px; right: 12px;
  width: 32px; height: 32px;
  border: none; border-radius: 9px;
  background: #fff;
  border: 1px solid #fde0e2;
  cursor: pointer;
  font-size: 14px;
  opacity: 0;
  transform: translateY(-4px);
  transition: all 0.2s;
}
.ticket:hover .ticket__del { opacity: 1; transform: translateY(0); }
.ticket__del:hover { background: #fee2e2; border-color: #e50914; }

/* ===================== MODAL ===================== */
.stv-backdrop {
  position: fixed; inset: 0;
  background: rgba(15, 6, 8, 0.55);
  backdrop-filter: blur(8px);
  z-index: 999;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 44px 20px;
  overflow-y: auto;
}
.stv-modal {
  width: 100%;
  max-width: 580px;
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 30px 70px rgba(0, 0, 0, 0.4);
}
.stv-modal__marquee {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background:
    radial-gradient(circle at 90% -40%, rgba(229, 9, 20, 0.6) 0%, transparent 50%),
    linear-gradient(120deg, #1a0205, #7d0411);
  color: #fff;
}
.stv-modal__marquee h3 { margin: 0; font-size: 19px; font-weight: 800; }
.stv-modal__marquee-dots {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 5px;
  background: repeating-linear-gradient(to right, #ffd6da 0 8px, transparent 8px 16px);
  opacity: 0.5;
}
.stv-modal__close {
  border: none;
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  width: 32px; height: 32px; border-radius: 9px;
  font-size: 15px; cursor: pointer;
  transition: all 0.2s;
}
.stv-modal__close:hover { background: rgba(255, 255, 255, 0.3); transform: rotate(90deg); }

.stv-form {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.stv-field { display: flex; flex-direction: column; gap: 7px; }
.stv-field label {
  font-size: 13.5px; font-weight: 700; color: #334155;
  display: flex; align-items: center; gap: 6px;
}
.stv-field label i { color: #e50914; font-style: normal; }
.stv-auto {
  font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 999px;
  background: #eef6f1; color: #047857;
}
.stv-input {
  width: 100%;
  border: 1.5px solid #e6e2e6;
  padding: 13px 16px;
  border-radius: 12px;
  outline: none;
  font-size: 15px;
  background: #faf9fa;
  color: #1e293b;
  transition: all 0.2s;
}
.stv-input:focus { border-color: #e50914; background: #fff; box-shadow: 0 0 0 4px rgba(229, 9, 20, 0.1); }
.stv-input--readonly { background: #f1f5f9; color: #64748b; font-family: 'Courier New', monospace; }
.stv-input--select {
  appearance: none;
  cursor: pointer;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e50914' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 18px;
  padding-right: 42px;
}
.stv-input.is-error {
  border-color: #ef4444 !important;
  background: #fff5f5 !important;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12) !important;
}
.stv-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 520px) { .stv-grid2 { grid-template-columns: 1fr; } }

/* Preview */
.stv-preview {
  position: relative;
  padding: 16px 18px;
  border-radius: 14px;
  background: linear-gradient(135deg, #fff7f8, #fdeef0);
  border: 1px dashed #f3c2c7;
}
.stv-preview__tag {
  position: absolute; top: -10px; left: 16px;
  font-size: 10.5px; font-weight: 800; letter-spacing: 0.5px;
  padding: 3px 10px; border-radius: 999px;
  background: #9b000e; color: #fff;
}
.stv-preview__line { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.stv-preview__line strong { font-size: 15.5px; color: #1e293b; }
.stv-preview__fmt {
  font-size: 11px; font-weight: 800;
  padding: 2px 8px; border-radius: 6px;
  background: #fee2e2; color: #b91c1c;
}
.stv-preview__time { font-size: 13.5px; font-weight: 600; color: #475569; }
.stv-preview__dur { color: #9b000e; font-weight: 700; }

.stv-form__footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 6px;
  border-top: 1px solid #f4eef0;
  margin-top: 2px;
  padding-top: 18px;
}
.stv-btn {
  display: inline-flex; align-items: center; gap: 8px;
  border: none; cursor: pointer;
  padding: 13px 24px; border-radius: 12px;
  font-size: 14.5px; font-weight: 800;
  transition: all 0.2s;
}
.stv-btn--ghost { background: #fff; color: #475569; border: 1.5px solid #e2e8f0; }
.stv-btn--ghost:hover { background: #f8fafc; border-color: #cbd5e1; }
.stv-btn--solid {
  background: linear-gradient(135deg, #e50914, #9b000e);
  color: #fff;
  box-shadow: 0 8px 20px rgba(229, 9, 20, 0.3);
}
.stv-btn--solid:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(229, 9, 20, 0.4); }
.stv-btn--solid:disabled { opacity: 0.7; cursor: not-allowed; }
.stv-btn__spin {
  width: 15px; height: 15px; border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.4); border-top-color: #fff;
  animation: spin 0.7s linear infinite;
}

/* ===================== DETAIL MODAL ===================== */
.stv-detail {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.dt-head { display: flex; gap: 16px; align-items: flex-start; }
.dt-poster {
  width: 76px; height: 110px;
  object-fit: cover;
  border-radius: 12px;
  flex-shrink: 0;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
}
.dt-head__info { flex: 1; min-width: 0; }
.dt-title { margin: 0 0 10px; font-size: 20px; font-weight: 800; color: #1e293b; line-height: 1.25; }
.dt-badges { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.dt-sub { margin: 10px 0 0; font-size: 13.5px; font-weight: 600; color: #64748b; }

.dt-rows {
  display: flex;
  flex-direction: column;
  border: 1px solid #f0e2e4;
  border-radius: 14px;
  overflow: hidden;
}
.dt-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 13px 16px;
}
.dt-row:not(:last-child) { border-bottom: 1px solid #f6eaeb; }
.dt-row__label { font-size: 13.5px; font-weight: 700; color: #64748b; }
.dt-row__value { font-size: 14.5px; font-weight: 800; color: #1e293b; text-align: right; }
.dt-dur { font-style: normal; font-weight: 700; color: #9b000e; }

.dt-seats {
  padding: 16px 18px;
  border-radius: 14px;
  background: linear-gradient(135deg, #fff7f8, #fdeef0);
  border: 1px dashed #f3c2c7;
}
.dt-seats__head {
  display: flex; justify-content: space-between; align-items: baseline;
  font-size: 14px; font-weight: 700; color: #475569; margin-bottom: 10px;
}
.dt-seats__head strong { font-size: 15.5px; color: #9b000e; }
.dt-seats__bar {
  height: 10px; border-radius: 999px;
  background: #f1d8db; overflow: hidden;
}
.dt-seats__fill {
  height: 100%; border-radius: 999px;
  background: linear-gradient(90deg, #e50914, #9b000e);
  transition: width 0.4s ease;
}
.dt-seats__foot {
  display: flex; justify-content: space-between;
  margin-top: 8px; font-size: 12.5px; font-weight: 600; color: #64748b;
}

/* ===================== TOAST "VÉ VỪA IN" ===================== */
.cg-toast {
  position: fixed;
  top: 26px;
  right: 26px;
  z-index: 1200;
  display: grid;
  grid-template-columns: 64px 14px 1fr;
  width: 380px;
  max-width: calc(100vw - 40px);
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow:
    0 22px 50px rgba(15, 6, 8, 0.32),
    0 0 0 1px rgba(229, 9, 20, 0.08);
}

/* Cuống vé màu đỏ + dấu mộc đóng */
.cg-toast__stub {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  background: linear-gradient(165deg, #e50914 0%, #9b000e 100%);
  color: #fff;
  overflow: hidden;
}
.cg-toast--danger .cg-toast__stub {
  background: linear-gradient(165deg, #475569 0%, #1e293b 100%);
}
/* lỗ sprocket chạy dọc cuống vé */
.cg-toast__stub-sprockets {
  position: absolute;
  left: 5px; top: 0; bottom: 0;
  width: 5px;
  background: repeating-linear-gradient(to bottom, transparent 0 7px, rgba(0, 0, 0, 0.22) 7px 12px);
}
.cg-toast__stamp {
  display: grid;
  place-items: center;
  width: 38px; height: 38px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.16);
  border: 2px solid rgba(255, 255, 255, 0.55);
  box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.08);
  animation: stamp-pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
.cg-toast__stamp svg { animation: stamp-draw 0.45s ease 0.18s both; }
.cg-toast__stub-brand {
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 1.5px;
  opacity: 0.9;
}

/* đường xé giữa cuống và thân */
.cg-toast__perf {
  position: relative;
  background: #fff;
}
.cg-toast__perf::before {
  content: '';
  position: absolute;
  top: 6px; bottom: 6px; left: 50%;
  transform: translateX(-50%);
  border-left: 2px dashed #f3c9cd;
}
.cg-toast--danger .cg-toast__perf::before { border-left-color: #cbd5e1; }

/* thân thông báo */
.cg-toast__body {
  padding: 14px 36px 16px 6px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.cg-toast__kicker {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1.2px;
  color: #e50914;
}
.cg-toast--danger .cg-toast__kicker { color: #475569; }
.cg-toast__title {
  font-size: 15.5px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cg-toast__msg {
  margin: 0;
  font-size: 12.8px;
  font-weight: 600;
  color: #64748b;
  line-height: 1.45;
}
.cg-toast__close {
  position: absolute;
  top: 9px; right: 10px;
  width: 22px; height: 22px;
  border: none;
  border-radius: 7px;
  background: #f1f5f9;
  color: #94a3b8;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.18s;
}
.cg-toast__close:hover { background: #fee2e2; color: #e50914; }

/* thanh đếm ngược tự ẩn */
.cg-toast__timer {
  position: absolute;
  left: 64px; right: 0; bottom: 0;
  height: 3px;
  transform-origin: left;
  background: linear-gradient(90deg, #e50914, #ff6b73);
  animation: toast-timer 4.2s linear forwards;
}
.cg-toast--danger .cg-toast__timer { background: linear-gradient(90deg, #475569, #94a3b8); }

@keyframes toast-timer { from { transform: scaleX(1); } to { transform: scaleX(0); } }
@keyframes stamp-pop {
  0% { transform: scale(0) rotate(-25deg); opacity: 0; }
  100% { transform: scale(1) rotate(-8deg); opacity: 1; }
}
@keyframes stamp-draw {
  from { stroke-dasharray: 40; stroke-dashoffset: 40; }
  to { stroke-dasharray: 40; stroke-dashoffset: 0; }
}

/* hiệu ứng vé "in trượt ra" */
.ticket-print-enter-active { transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease; }
.ticket-print-leave-active { transition: transform 0.35s ease, opacity 0.3s ease; }
.ticket-print-enter-from { opacity: 0; transform: translateX(40px) translateY(-14px) rotate(2deg); }
.ticket-print-leave-to { opacity: 0; transform: translateX(40px) scale(0.96); }

@media (max-width: 520px) {
  .cg-toast { top: auto; bottom: 18px; right: 50%; transform: translateX(50%); }
  .ticket-print-enter-from { transform: translateX(50%) translateY(20px); }
  .ticket-print-leave-to { transform: translateX(50%) translateY(20px); }
}

/* ===================== CONFIRM XÓA ===================== */
.cg-confirm-backdrop { align-items: center; }
.cg-confirm {
  position: relative;
  width: 100%;
  max-width: 420px;
  padding: 40px 28px 26px;
  border-radius: 22px;
  background: #fff;
  text-align: center;
  box-shadow: 0 30px 70px rgba(15, 6, 8, 0.45);
  overflow: hidden;
}
/* dải ruy băng cảnh báo chéo góc */
.cg-confirm__ribbon {
  position: absolute;
  top: 16px; right: -42px;
  transform: rotate(45deg);
  background: linear-gradient(90deg, #e50914, #9b000e);
  color: #fff;
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 0.8px;
  padding: 5px 48px;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.4);
}
.cg-confirm__icon {
  position: relative;
  display: grid;
  place-items: center;
  width: 78px; height: 78px;
  margin: 0 auto 16px;
  font-size: 34px;
  border-radius: 50%;
  background: radial-gradient(circle at 50% 40%, #fff1f2, #ffe4e6);
  border: 2px dashed #f3a8ae;
}
.cg-confirm__icon-glow {
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  background: rgba(229, 9, 20, 0.18);
  animation: confirm-glow 1.6s ease-in-out infinite;
  z-index: -1;
}
@keyframes confirm-glow {
  0%, 100% { transform: scale(0.92); opacity: 0.5; }
  50% { transform: scale(1.08); opacity: 0; }
}
.cg-confirm__title {
  margin: 0 0 8px;
  font-size: 20px;
  font-weight: 800;
  color: #1e293b;
}
.cg-confirm__desc {
  margin: 0 0 16px;
  font-size: 13.8px;
  font-weight: 600;
  color: #64748b;
  line-height: 1.5;
}
/* thẻ vé minh họa suất sắp xóa */
.cg-confirm__ticket {
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: left;
  padding: 12px 14px;
  margin-bottom: 22px;
  border-radius: 14px;
  background: #faf9fa;
  border: 1px solid #f3dde0;
  border-left: 4px solid #e50914;
}
.cg-confirm__ticket-time {
  font-size: 18px;
  font-weight: 800;
  font-family: 'Courier New', monospace;
  color: #9b000e;
  flex-shrink: 0;
}
.cg-confirm__ticket-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.cg-confirm__ticket-info strong {
  font-size: 14.5px;
  font-weight: 800;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cg-confirm__ticket-info span { font-size: 12px; font-weight: 600; color: #94a3b8; }
.cg-confirm__ticket-id {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 999px;
  background: #fee2e2;
  color: #b91c1c;
  flex-shrink: 0;
}
.cg-confirm__actions {
  display: flex;
  gap: 12px;
}
.cg-confirm__actions .stv-btn--ghost { flex: 1; justify-content: center; }
.cg-confirm__del {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  border: none;
  cursor: pointer;
  padding: 13px 20px;
  border-radius: 12px;
  font-size: 14.5px;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, #e50914, #9b000e);
  box-shadow: 0 8px 20px rgba(229, 9, 20, 0.32);
  transition: all 0.2s;
}
.cg-confirm__del:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(229, 9, 20, 0.45); }

/* ===================== ERROR BANNER ===================== */
.error-banner {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
  border: 1px solid #fca5a5;
  border-left: 5px solid #e50914;
  box-shadow: 0 8px 24px rgba(229, 9, 20, 0.18);
  position: relative;
  overflow: hidden;
  animation: banner-shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}
.error-banner::after {
  content: '';
  position: absolute;
  top: 0; left: -120%;
  width: 60%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.55), transparent);
  animation: banner-sheen 2.2s ease-in-out 0.4s infinite;
}
.error-banner__icon {
  font-size: 24px; line-height: 1.2;
  filter: drop-shadow(0 2px 3px rgba(229, 9, 20, 0.35));
  animation: icon-pulse 1.3s ease-in-out infinite;
}
.error-banner__body { flex: 1; min-width: 0; }
.error-banner__title {
  display: block; font-size: 15px; font-weight: 800;
  color: #9b000e; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 3px;
}
.error-banner__msg { margin: 0; font-size: 14px; font-weight: 600; color: #b91c1c; line-height: 1.5; white-space: pre-line; }
.error-banner__close {
  background: transparent; border: none; color: #ef4444;
  font-size: 16px; font-weight: 700; cursor: pointer;
  padding: 2px 6px; border-radius: 6px; transition: all 0.18s; z-index: 1;
}
.error-banner__close:hover { background: rgba(229, 9, 20, 0.12); color: #9b000e; }

/* ===================== TRANSITIONS ===================== */
.error-pop-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.error-pop-leave-active { transition: all 0.25s ease; }
.error-pop-enter-from, .error-pop-leave-to { opacity: 0; transform: translateY(-10px) scale(0.97); }

.preview-pop-enter-active { transition: all 0.3s ease; }
.preview-pop-enter-from { opacity: 0; transform: translateY(8px); }

.modal-fade-enter-active { transition: opacity 0.25s ease; }
.modal-fade-enter-active .stv-modal { transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-fade-enter-from { opacity: 0; }
.modal-fade-enter-from .stv-modal { transform: translateY(-30px) scale(0.95); }
.modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-leave-to { opacity: 0; }

@keyframes banner-shake {
  10%, 90% { transform: translateX(-1px); }
  20%, 80% { transform: translateX(2px); }
  30%, 50%, 70% { transform: translateX(-4px); }
  40%, 60% { transform: translateX(4px); }
}
@keyframes banner-sheen { 0% { left: -120%; } 60%, 100% { left: 130%; } }
@keyframes icon-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.18); } }
</style>
