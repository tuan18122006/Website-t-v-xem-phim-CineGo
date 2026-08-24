<template>
  <div class="stv">
    <!-- ===== HERO: BẢNG ĐIỀU PHỐI (cinematic dark marquee) ===== -->
    <header class="stv-hero">
      <div class="stv-hero__grain"></div>
      <div class="stv-hero__filmstrip"></div>

      <div class="stv-hero__row">
        <div class="stv-hero__intro">
          <span class="stv-hero__kicker"><Clapperboard :size="15" style="vertical-align:-2px" /> PHÒNG ĐIỀU PHỐI</span>
          <h2 class="stv-hero__title">Lịch Chiếu &amp; Suất Phim</h2>
          <p class="stv-hero__desc">
            Xếp phim vào phòng đúng giờ — hệ thống <b>tự động chống trùng lịch</b> trong cùng một phòng.
          </p>
          <div v-if="currentPricing.standard_price" class="stv-hero__pricing-note" style="margin-top: 15px; padding: 12px 15px; background: rgba(0,0,0,0.4); border-radius: 8px; border-left: 4px solid var(--accent-pink); font-size: 0.95rem; color: #eee; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
              <strong style="color: white; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Giá cơ sở:</strong> 
              <span style="background: rgba(229,9,20,0.2); border: 1px solid rgba(229,9,20,0.5); padding: 3px 8px; border-radius: 6px; color: #fff;">Thường <b style="margin-left:4px; font-size: 1.05rem;">{{ formatPrice(currentPricing.standard_price) }}</b></span>
              <span style="background: rgba(255,215,0,0.2); border: 1px solid rgba(255,215,0,0.5); padding: 3px 8px; border-radius: 6px; color: #fff;">VIP <b style="margin-left:4px; color: var(--accent-gold); font-size: 1.05rem;">{{ formatPrice(currentPricing.vip_price) }}</b></span>
              <span style="background: rgba(100,255,218,0.15); border: 1px solid rgba(100,255,218,0.4); padding: 3px 8px; border-radius: 6px; color: #fff;">Couple <b style="margin-left:4px; color: #64ffda; font-size: 1.05rem;">{{ formatPrice(currentPricing.couple_price) }}</b></span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
              <strong style="color: white; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Giá theo thời điểm:</strong>
              <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 6px; color: #ddd; font-size: 0.9rem;">Cuối tuần <b style="color: var(--accent-pink); margin-left:4px;">+{{ formatPrice(currentPricing.weekend_surcharge) }}</b></span>
              <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 6px; color: #ddd; font-size: 0.9rem;">Giờ vàng <b style="color: #64ffda; margin-left:4px;">-{{ formatPrice(currentPricing.happy_hour_discount) }}</b></span>
              <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 6px; color: #ddd; font-size: 0.9rem;">3D <b style="color: var(--accent-pink); margin-left:4px;">+{{ formatPrice(currentPricing.format_3d_surcharge) }}</b></span>
              <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 6px; color: #ddd; font-size: 0.9rem;">Chiếu sớm <b style="color: var(--accent-pink); margin-left:4px;">+{{ formatPrice(currentPricing.sneak_show_surcharge) }}</b></span>
            </div>
          </div>
        </div>

        <div style="display: flex; gap: 10px; flex-shrink: 0;">
          <button class="stv-hero__cta" @click="openCreateModal">
            <span class="stv-hero__cta-plus"><Plus :size="20" /></span>
            <span>Thêm Suất Chiếu</span>
          </button>
        </div>
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

      <div class="stv-hero__timeline-filters">
        <button v-for="t in timelineOptions" :key="t.value" 
          @click="timelineFilter = t.value"
          :class="['stv-timeline-btn', timelineFilter === t.value ? 'stv-timeline-btn--active' : '']">
          {{ t.label }}
        </button>
      </div>


    </header>

    <!-- ===== TOOLBAR: tìm kiếm + lọc định dạng ===== -->
    <div class="stv-toolbar">
      <label class="stv-search">
        <span class="stv-search__icon"><Search :size="20" /></span>
        <input v-model="searchQuery" type="text" placeholder="Tìm theo tên phim hoặc phòng chiếu…" />
        <button v-if="searchQuery" class="stv-search__clear" @click="searchQuery = ''" aria-label="Xóa">✕</button>
      </label>

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
      <div class="stv-empty__art"><Film :size="44" /></div>
      <h3>{{ showtimes.length === 0 ? 'Chưa có suất chiếu nào' : 'Không tìm thấy suất chiếu phù hợp' }}</h3>
      <p>{{ showtimes.length === 0 ? 'Hãy tạo suất chiếu đầu tiên để khởi động phòng vé.' : 'Thử đổi từ khóa hoặc bộ lọc định dạng.' }}</p>
      <button v-if="showtimes.length === 0" class="stv-empty__btn" @click="openCreateModal"><Plus :size="15" style="vertical-align:-2px" /> Tạo suất chiếu</button>
    </div>

    <!-- ===== TICKET GRID ===== -->
    <div v-else class="stv-grid">
      <article
        v-for="st in paginatedShowtimes"
        :key="st.id"
        class="ticket"
        :class="{ 'ticket--off': st.status !== 'active' }"
        @click="openDetailModal(st)"
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
            <span class="tg tg--room"><Building2 :size="15" style="vertical-align:-2px" /> {{ st.room_name }}</span>
            <span class="tg tg--format">{{ st.format }}</span>
            <span class="tg tg--trans"><MessageSquare :size="15" style="vertical-align:-2px" /> {{ st.translation }}</span>
          </div>


        </div>

        <div class="ticket__actions">
          <button class="ticket__btn ticket__btn--edit" @click.stop="openEditModal(st)" title="Sửa suất chiếu"><Pencil :size="20" /></button>
          <button class="ticket__btn ticket__btn--del" @click.stop="deleteShowtime(st.id)" title="Xóa suất chiếu"><Trash2 :size="20" /></button>
        </div>
      </article>
    </div>

    <!-- Phân trang -->
    <div v-if="totalPages > 1" class="pagination-cine">
      <button @click="currentPage--" :disabled="currentPage === 1" class="btn-page">Trước</button>
      <span class="page-info">Trang {{ currentPage }} / {{ totalPages }}</span>
      <button @click="currentPage++" :disabled="currentPage === totalPages" class="btn-page">Sau</button>
    </div>

    <!-- ===== MODAL: CHI TIẾT SUẤT CHIẾU ===== -->
    <transition name="modal-fade">
      <div v-if="showDetailModal" class="stv-backdrop" @click.self="closeDetailModal">
        <div class="stv-modal">
          <div class="stv-modal__marquee">
            <div class="stv-modal__marquee-dots"></div>
            <h3><Search :size="15" style="vertical-align:-2px" /> Chi Tiết Suất Chiếu #{{ selectedDetail?.id }}</h3>
            <button class="stv-modal__close" @click="closeDetailModal" aria-label="Đóng">✕</button>
          </div>
          
          <div class="stv-form" v-if="selectedDetail">
            <div style="margin-bottom: 15px;">
              <span v-if="getShowtimeStatus(selectedDetail.start_time, selectedDetail.end_time)"
                    style="padding: 4px 10px; border-radius: 12px; color: white; font-size: 0.8rem; font-weight: bold; display: inline-block;"
                    :style="{ backgroundColor: getShowtimeStatus(selectedDetail.start_time, selectedDetail.end_time).color }">
                Trạng thái: {{ getShowtimeStatus(selectedDetail.start_time, selectedDetail.end_time).label }}
              </span>
            </div>
            <div class="stv-field">
              <label>Tên phim</label>
              <div class="stv-input stv-input--readonly">{{ selectedDetail.movie_title }}</div>
            </div>
            <div class="stv-grid2">
              <div class="stv-field">
                <label>Phòng chiếu</label>
                <div class="stv-input stv-input--readonly">{{ selectedDetail.room_name }}</div>
              </div>
              <div class="stv-field">
                <label>Trạng thái</label>
                <div class="stv-input stv-input--readonly">{{ selectedDetail.status === 'active' ? 'Đang hoạt động' : 'Đã hủy' }}</div>
              </div>
            </div>
            <div class="stv-grid2">
              <div class="stv-field">
                <label>Giờ chiếu</label>
                <div class="stv-input stv-input--readonly">{{ timeOnly(selectedDetail.start_time) }} - {{ dateOnly(selectedDetail.start_time) }}</div>
              </div>
              <div class="stv-field">
                <label>Định dạng</label>
                <div class="stv-input stv-input--readonly">{{ selectedDetail.format }} • {{ selectedDetail.translation }}</div>
              </div>
            </div>
            <div class="stv-field stv-field--price">
              <label>Cấu hình giá tại thời điểm chiếu</label>
              <div style="font-size: 0.95rem; margin-top: 5px;">
                <p style="margin: 0; color: #555; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 5px; font-weight: bold;">Giá cơ sở</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                  <span style="background: rgba(229,9,20,0.1); padding: 4px 8px; border-radius: 4px; color: var(--accent-pink);">Thường: <b>{{ formatPrice(selectedDetail.pricing_snapshot?.standard_price || 0) }}</b></span>
                  <span style="background: rgba(255,215,0,0.2); border: 1px solid rgba(255,215,0,0.3); padding: 4px 8px; border-radius: 4px; color: #b8860b;">VIP: <b>{{ formatPrice(selectedDetail.pricing_snapshot?.vip_price || 0) }}</b></span>
                  <span style="background: rgba(0,150,136,0.1); padding: 4px 8px; border-radius: 4px; color: #00796b;">Couple: <b>{{ formatPrice(selectedDetail.pricing_snapshot?.couple_price || 0) }}</b></span>
                </div>
              </div>
              <div style="font-size: 0.95rem; margin-top: 10px;">
                <p style="margin: 0; color: #555; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 5px; font-weight: bold;">Giá theo thời điểm đã lưu</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; font-size: 0.85rem;">
                  <span style="color: #444; background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">Cuối tuần: <b style="color: var(--accent-pink);">+{{ formatPrice(selectedDetail.pricing_snapshot?.weekend_surcharge || 0) }}</b></span>
                  <span style="color: #444; background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">Giờ vàng: <b style="color: #00796b;">-{{ formatPrice(selectedDetail.pricing_snapshot?.happy_hour_discount || 0) }}</b></span>
                  <span style="color: #444; background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">3D: <b style="color: var(--accent-pink);">+{{ formatPrice(selectedDetail.pricing_snapshot?.format_3d_surcharge || 0) }}</b></span>
                  <span style="color: #444; background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">Chiếu sớm: <b style="color: var(--accent-pink);">+{{ formatPrice(selectedDetail.pricing_snapshot?.sneak_show_surcharge || 0) }}</b></span>
                </div>
              </div>
            </div>
            <div class="stv-form__footer">
              <button type="button" class="stv-btn stv-btn--ghost" @click="closeDetailModal">Đóng</button>
              <button type="button" class="stv-btn stv-btn--solid" @click="openEditModal(selectedDetail); closeDetailModal()">Sửa Suất Chiếu</button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ===== MODAL: CẤU HÌNH GIÁ HỆ THỐNG ===== -->
    <transition name="modal-fade">
      <div v-if="false && showPricingModal" class="stv-backdrop" @click.self="closePricingModal">
        <div class="stv-modal stv-modal--pricing">
          <div class="stv-modal__marquee">
            <div class="stv-modal__marquee-dots"></div>
            <h3><Settings :size="15" style="vertical-align:-2px" /> Cấu Hình Giá Hệ Thống</h3>
            <button class="stv-modal__close" @click="closePricingModal" aria-label="Đóng">✕</button>
          </div>

          <div v-if="loadingPricing" class="pricing-loading" style="padding: 30px; text-align: center; color: white;">
            Đang tải dữ liệu...
          </div>
          <form v-else @submit.prevent="savePricingConfig" class="stv-form stv-form--pricing">
            <div class="pricing-section">
              <h4>1. Giá Vé Cơ Sở</h4>
              <div class="stv-grid2">
                <div class="stv-field">
                  <label>Ghế Thường (VNĐ)</label>
                  <input type="text" :value="formatInput(pricingForm.standard_price)" @input="pricingForm.standard_price = parseInput($event.target.value)" class="stv-input" />
                  <span v-if="pricingErrors.standard_price" class="error-msg">{{ pricingErrors.standard_price[0] }}</span>
                </div>
                <div class="stv-field">
                  <label>Ghế VIP (VNĐ)</label>
                  <input type="text" :value="formatInput(pricingForm.vip_price)" @input="pricingForm.vip_price = parseInput($event.target.value)" class="stv-input" />
                  <span v-if="pricingErrors.vip_price" class="error-msg">{{ pricingErrors.vip_price[0] }}</span>
                </div>
                <div class="stv-field stv-field--full">
                  <label>Ghế Đôi (VNĐ)</label>
                  <input type="text" :value="formatInput(pricingForm.couple_price)" @input="pricingForm.couple_price = parseInput($event.target.value)" class="stv-input" />
                  <span v-if="pricingErrors.couple_price" class="error-msg">{{ pricingErrors.couple_price[0] }}</span>
                </div>
              </div>
            </div>

            <div class="pricing-section pricing-section--special">
              <h4>2. Giá Vé Theo Trường Hợp Đặc Biệt</h4>

              <div class="stv-grid2 stv-grid2--pricing-rule">
                <div class="stv-field">
                  <label>Tên quy tắc</label>
                  <input v-model="pricingRuleDraft.name" type="text" class="stv-input" placeholder="VD: Ngày lễ 30/4, Phim bản quyền cao" />
                </div>

                <div class="stv-field">
                  <label>Phạm vi</label>
                  <select v-model="pricingRuleDraft.scope" class="stv-input stv-input--select">
                    <option value="system">Toàn hệ thống</option>
                    <option value="movie">Theo phim</option>
                  </select>
                </div>

                <div v-if="pricingRuleDraft.scope === 'movie'" class="stv-field">
                  <label>Chọn phim</label>
                  <select v-model="pricingRuleDraft.movie_id" class="stv-input stv-input--select">
                    <option value="">-- Chọn phim --</option>
                    <option v-for="movie in movies" :key="movie.id" :value="movie.id">
                      {{ movie.title }}
                    </option>
                  </select>
                </div>

                <div v-else class="stv-field" style="visibility: hidden; pointer-events: none;">
                  <label>Chọn phim</label>
                  <select class="stv-input stv-input--select">
                    <option value="">-- Chọn phim --</option>
                  </select>
                </div>

                <div class="stv-field">
                  <label>Áp dụng cho ghế</label>
                  <select v-model="pricingRuleDraft.seat_type" class="stv-input stv-input--select">
                    <option value="all">Tất cả</option>
                    <option value="standard">Ghế thường</option>
                    <option value="vip">Ghế VIP</option>
                    <option value="couple">Ghế đôi</option>
                  </select>
                </div>

                <div class="stv-field">
                  <label>Loại điều chỉnh</label>
                  <select v-model="pricingRuleDraft.adjustment_type" class="stv-input stv-input--select">
                    <option value="surcharge">Cộng tiền</option>
                    <option value="percentage">Tăng %</option>
                    <option value="free">Miễn phí</option>
                  </select>
                </div>

                <div class="stv-field">
                  <label>Giá trị</label>
                  <input v-model.number="pricingRuleDraft.value" type="number" class="stv-input" placeholder="0" :disabled="pricingRuleDraft.adjustment_type === 'free'" />
                </div>

                <div class="stv-field">
                  <label>Ngày bắt đầu</label>
                  <input v-model="pricingRuleDraft.start_date" type="date" class="stv-input" />
                </div>

                <div class="stv-field">
                  <label>Ngày kết thúc</label>
                  <input v-model="pricingRuleDraft.end_date" type="date" class="stv-input" />
                </div>
              </div>

              <div class="rule-time-block">
                <div class="rule-time-block__row">
                  <label class="rule-time-toggle">
                    <input type="checkbox" v-model="pricingRuleDraft.use_time_filter" />
                    <span>Theo giờ</span>
                  </label>
                </div>

                <div v-if="pricingRuleDraft.use_time_filter" class="stv-grid2 rule-time-inner">
                  <div class="stv-field">
                    <label>Từ giờ</label>
                    <input v-model="pricingRuleDraft.time_from" type="time" class="stv-input" />
                  </div>
                  <div class="stv-field">
                    <label>Đến giờ</label>
                    <input v-model="pricingRuleDraft.time_to" type="time" class="stv-input" />
                  </div>
                </div>
              </div>

              <div class="pricing-actions">
                <button type="button" class="stv-btn stv-btn--ghost" @click="resetPricingRuleDraft">Làm mới</button>
                <button type="button" class="stv-btn stv-btn--solid" @click="addPricingRule">Thêm quy tắc</button>
              </div>

            </div>

            <div class="stv-modal__actions" style="margin-top: 10px;">
              <button type="button" class="stv-btn stv-btn--outline" @click="closePricingModal">Hủy bỏ</button>
              <button type="submit" class="stv-btn stv-btn--solid" :disabled="savingPricing">
                {{ savingPricing ? 'Đang lưu...' : 'Lưu Cấu Hình' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- ===== MODAL: TẠO SUẤT CHIẾU ===== -->
    <transition name="modal-fade">
      <div v-if="showModal" class="stv-backdrop" @click.self="closeModal">
        <div class="stv-modal">
          <!-- Marquee header -->
          <div class="stv-modal__marquee">
            <div class="stv-modal__marquee-dots"></div>
            <h3><Sparkles :size="15" style="vertical-align:-2px" /> {{ editingId ? 'Cập Nhật Suất Chiếu' : 'Lên Lịch Suất Chiếu Mới' }}</h3>
            <button class="stv-modal__close" @click="closeModal" aria-label="Đóng">✕</button>
          </div>

          <form @submit.prevent="saveShowtime" class="stv-form">
            <!-- Banner lỗi chống trùng lịch -->
            <transition name="error-pop">
              <div v-if="formError" class="error-banner" role="alert">
                <span class="error-banner__icon"><AlertTriangle :size="20" /></span>
                <div class="error-banner__body">
                  <strong class="error-banner__title">Không thể xếp lịch</strong>
                  <p class="error-banner__msg">{{ formError }}</p>
                </div>
                <button type="button" class="error-banner__close" @click="formError = ''" aria-label="Đóng">✕</button>
              </div>
            </transition>

            <div class="stv-grid2">
              <div class="stv-field" style="position: relative;">
                <label>Chọn phim <i>*</i></label>
                <div class="combobox-wrapper">
                  <input type="text" class="stv-input combobox-input" v-model="movieSearch" @focus="showMovieDropdown = true" placeholder="Gõ tìm phim..." />
                  <span class="combobox-arrow">▼</span>
                </div>
                <ul v-show="showMovieDropdown" class="combobox-dropdown">
                  <li v-for="movie in filteredMovieOptions" :key="movie.id" @click.stop="selectMovie(movie)">
                    {{ movie.title }} • {{ movie.duration }} phút
                  </li>
                  <li v-if="filteredMovieOptions.length === 0" class="combobox-empty">Không tìm thấy</li>
                </ul>
                <span v-if="errors.movie_id" class="error-msg">{{ errors.movie_id[0] }}</span>
              </div>
  
              <div class="stv-field" style="position: relative;">
                <label>Phòng chiếu <i>*</i></label>
                <div class="combobox-wrapper">
                  <input type="text" class="stv-input combobox-input" v-model="roomSearch" @focus="showRoomDropdown = true" placeholder="Gõ tìm phòng..." :class="{ 'is-error': formError }" />
                  <span class="combobox-arrow">▼</span>
                </div>
                <ul v-show="showRoomDropdown" class="combobox-dropdown">
                  <li v-for="room in filteredRoomOptions" :key="room.id" @click.stop="selectRoom(room)">
                    {{ room.name }} • {{ room.total_seats }} ghế
                  </li>
                  <li v-if="filteredRoomOptions.length === 0" class="combobox-empty">Không tìm thấy</li>
                </ul>
                <span v-if="errors.room_id" class="error-msg">{{ errors.room_id[0] }}</span>
              </div>
            </div>
            
            <!-- EDIT MODE -->
            <div v-if="editingId" class="stv-grid2">
              <div class="stv-field">
                <label>Giờ bắt đầu <i>*</i></label>
                <input
                  v-model="form.start_time"
                  type="datetime-local"
                  class="stv-input"
                  :min="minDateTime"
                  :class="{ 'is-error': formError }"
                  @change="onStartTimeChange"
                />
                <span v-if="errors.start_time" class="error-msg">{{ errors.start_time[0] }}</span>
              </div>
              <div class="stv-field">
                <label>Giờ kết thúc <span class="stv-auto">đã chừa 15p dọn phòng</span></label>
                <input v-model="form.end_time" type="datetime-local" readonly class="stv-input stv-input--readonly" />
              </div>
            </div>

            <!-- CREATE MODE -->
            <div v-if="!editingId" class="stv-grid2">
              <div class="stv-field">
                <label>Ngày chiếu <i>*</i></label>
                <input
                  v-model="form.start_date"
                  type="date"
                  class="stv-input"
                  :min="minDate"
                />
                <span v-if="errors.start_date" class="error-msg">{{ errors.start_date[0] }}</span>
              </div>
            </div>
            
            <div v-if="!editingId" class="stv-field">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="margin-bottom: 0;">Các khung giờ chiếu <i>*</i></label>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" @click="form.times = ['']" class="btn-add-time" style="padding: 4px 10px; font-size: 0.85rem; color: #64748b; border-color: #cbd5e1;">Xóa tất cả</button>
                        <button type="button" @click="addTimeSlot" class="btn-add-time" style="padding: 4px 10px; font-size: 0.85rem;">+ Thêm khung giờ</button>
                    </div>
                </div>
                <div class="times-container">
                    <div v-for="(t, idx) in form.times" :key="idx" class="time-slot-row">
                        <input type="time" v-model="form.times[idx]" class="stv-input" style="width: 150px;" required />
                        <button type="button" v-if="form.times.length > 1" @click="removeTimeSlot(idx)" class="btn-remove-time">✕</button>
                    </div>
                    
                </div>
                <span v-if="errors.times" class="error-msg">{{ errors.times[0] }}</span>
            </div>

            <!-- Dải ngày lặp lại -->
            <div class="stv-field" v-if="!editingId">
              <label>Lặp lại hàng ngày đến <i>(Tạo hàng loạt)</i></label>
              <input
                v-model="form.end_date"
                type="date"
                class="stv-input"
                :min="form.start_date ? form.start_date : minDate"
              />
              <span class="help-text" style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: block;">Nếu chọn, hệ thống sẽ tạo lịch chiếu cho mỗi ngày vào cùng khung giờ trên.</span>
              <span v-if="errors.end_date" class="error-msg">{{ errors.end_date[0] }}</span>
            </div>

            <div class="stv-grid2">
              <div class="stv-field">
                <label>Định dạng <i>*</i></label>
                <select v-model="form.format" class="stv-input stv-input--select">
                  <option value="2D">2D</option>
                  <option value="3D">3D</option>
                  <option value="IMAX">IMAX</option>
                </select>
              </div>
              <div class="stv-field">
                <label>Dịch thuật <i>*</i></label>
                <select v-model="form.translation" class="stv-input stv-input--select">
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
                  <Clock :size="15" style="vertical-align:-2px" /> {{ timeOnly(form.start_time) }} → {{ form.end_time ? timeOnly(form.end_time) : '—' }}
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import api from '../../api/axios';
import { toast, confirmDialog } from '../../utils/alert';
import { validatePricingConfig, validatePricingRule } from '../../utils/pricingValidation';
import { AlertTriangle, Building2, Clapperboard, Clock, Film, MessageSquare, Pencil, Plus, Search, Settings, Sparkles, Trash2 } from 'lucide-vue-next';

const showtimes = ref([]);
const movies = ref([]);
const rooms = ref([]);
const loading = ref(false);
const showModal = ref(false);
const showDetailModal = ref(false);
const selectedDetail = ref(null);
const editingId = ref(null);
const submitting = ref(false);
const formError = ref('');
const appliedRules = ref([]);
const isSneakShow = ref(false);

const currentPricing = ref({});
const showPricingModal = ref(false);
const loadingPricing = ref(false);
const savingPricing = ref(false);
const pricingForm = ref({ pricing_rules: [] });
const pricingErrors = ref({});
const pricingRuleDraft = ref({
  name: '',
  scope: 'movie',
  seat_type: 'all',
  adjustment_type: 'surcharge',
  value: 0,
  start_date: '',
  end_date: '',
  holiday_date: '',
  movie_id: '',
  use_time_filter: false,
  time_from: '',
  time_to: ''
});

const searchQuery = ref('');
const formatFilter = ref('Tất cả');
const formatOptions = ['Tất cả', 'Hôm nay', '2D', '3D', 'IMAX'];

const timelineFilter = ref('upcoming');
const timelineOptions = [
  { label: 'Sắp diễn ra', value: 'upcoming' },
  { label: 'Đang diễn ra', value: 'ongoing' },
  { label: 'Đã kết thúc', value: 'past' }
];

const getShowtimeStatus = (startStr, endStr) => {
  if (!startStr || !endStr) return null;
  const st = new Date(startStr).getTime();
  const et = new Date(endStr).getTime();
  const ct = new Date().getTime();
  if (st > ct) return { label: 'Sắp chiếu', color: '#ff9800' };
  if (st <= ct && et >= ct) return { label: 'Đang chiếu', color: '#4caf50' };
  return { label: 'Đã kết thúc', color: '#9e9e9e' };
};

  const form = ref({
    movie_id: '',
    room_id: '',
    start_date: '',
    times: [''],
    start_time: '',
    end_time: '',
    end_date: '',
    format: '2D',
    translation: 'Phụ đề'
  });
  
  const addTimeSlot = () => form.value.times.push('');
  const removeTimeSlot = (idx) => form.value.times.splice(idx, 1);

const movieSearch = ref('');
const roomSearch = ref('');
const showMovieDropdown = ref(false);
const showRoomDropdown = ref(false);

const filteredMovieOptions = computed(() => {
  if (!movieSearch.value) return movies.value;
  const q = movieSearch.value.toLowerCase();
  return movies.value.filter(m => m.title.toLowerCase().includes(q));
});

const filteredRoomOptions = computed(() => {
  if (!roomSearch.value) return rooms.value;
  const q = roomSearch.value.toLowerCase();
  return rooms.value.filter(r => r.name.toLowerCase().includes(q));
});

const selectMovie = (movie) => {
  form.value.movie_id = movie.id;
  movieSearch.value = movie.title;
  showMovieDropdown.value = false;
  onMovieChange();
};

const selectRoom = (room) => {
  form.value.room_id = room.id;
  roomSearch.value = room.name;
  showRoomDropdown.value = false;
  formError.value = '';
};

const minDateTime = computed(() => {
  const now = new Date();
  const tzOffset = now.getTimezoneOffset() * 60000;
  return new Date(now.getTime() - tzOffset).toISOString().slice(0, 16);
});

const minDate = computed(() => minDateTime.value.split('T')[0]);

const closeDropdowns = (e) => {
  if (!e.target.closest('.combobox-wrapper') && !e.target.closest('.combobox-dropdown')) {
    showMovieDropdown.value = false;
    showRoomDropdown.value = false;
  }
};

/* ---------- Computed ---------- */
const activeCount = computed(() => showtimes.value.filter(s => s.status === 'active').length);

const todayCount = computed(() => {
  const now = new Date();
  return showtimes.value.filter(s => {
    if (!s.start_time) return false;
    const d = new Date(s.start_time);
    return d.getDate() === now.getDate() && d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  }).length;
});

const filteredShowtimes = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  const now = new Date();
  
  let result = showtimes.value.filter(s => {
    let matchFormat = false;
    
    if (formatFilter.value === 'Tất cả') {
      matchFormat = true;
    } else if (formatFilter.value === 'Hôm nay') {
      if (s.start_time) {
        const d = new Date(s.start_time);
        matchFormat = d.getDate() === now.getDate() && 
                      d.getMonth() === now.getMonth() && 
                      d.getFullYear() === now.getFullYear();
      }
    } else {
      matchFormat = s.format === formatFilter.value;
    }

    const matchSearch = !q
      || (s.movie_title && s.movie_title.toLowerCase().includes(q))
      || (s.room_name && s.room_name.toLowerCase().includes(q));

    let matchTimeline = true;
    if (s.start_time && s.end_time) {
      const st = new Date(s.start_time).getTime();
      const et = new Date(s.end_time).getTime();
      const ct = now.getTime();
      if (timelineFilter.value === 'upcoming') {
        matchTimeline = st > ct;
      } else if (timelineFilter.value === 'ongoing') {
        matchTimeline = st <= ct && et >= ct;
      } else if (timelineFilter.value === 'past') {
        matchTimeline = et < ct;
      }
    }

    return matchFormat && matchSearch && matchTimeline;
  });

  result.sort((a, b) => {
    if (!a.start_time || !b.start_time) return 0;
    const aStart = new Date(a.start_time).getTime();
    const bStart = new Date(b.start_time).getTime();
    const aEnd = new Date(a.end_time).getTime();
    const bEnd = new Date(b.end_time).getTime();
    
    if (timelineFilter.value === 'upcoming') {
      return aStart - bStart;
    } else if (timelineFilter.value === 'past') {
      return bEnd - aEnd;
    } else if (timelineFilter.value === 'ongoing') {
      return aEnd - bEnd;
    } else {
      return bStart - aStart;
    }
  });

  return result;
});

const previewMovie = computed(() => movies.value.find(m => m.id === form.value.movie_id) || null);

// Pagination
const currentPage = ref(1);
const itemsPerPage = 12;

const totalPages = computed(() => Math.ceil(filteredShowtimes.value.length / itemsPerPage));
const paginatedShowtimes = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredShowtimes.value.slice(start, end);
});

watch([searchQuery, formatFilter], () => {
  currentPage.value = 1;
});

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

const formatPrice = (price) => {
  if (!price) return '0đ';
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
};

const formatInput = (val) => {
  if (!val && val !== 0) return '';
  return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const parseInput = (val) => {
  if (val === '' || val === null || val === undefined) return '';
  const parsed = parseInt(val.toString().replace(/[^\d]/g, ''));
  return isNaN(parsed) ? '' : parsed;
};

/* ---------- Modal ---------- */
  const openCreateModal = () => {
    editingId.value = null;
    form.value = { 
      movie_id: '', room_id: '', start_date: '', times: [''], start_time: '', end_time: '', end_date: '',
      format: '2D', translation: 'Phụ đề'
    };
    movieSearch.value = '';
  roomSearch.value = '';
  formError.value = '';
  appliedRules.value = [];
  isSneakShow.value = false;
  showModal.value = true;
};

const openEditModal = (st) => {
  editingId.value = st.id;
  const tzOffset = new Date().getTimezoneOffset() * 60000;
  const startStr = st.start_time ? new Date(new Date(st.start_time).getTime() - tzOffset).toISOString().slice(0, 16) : '';
  const endStr = st.end_time ? new Date(new Date(st.end_time).getTime() - tzOffset).toISOString().slice(0, 16) : '';
  
  const stMovie = movies.value.find(m => m.id === st.movie_id);
  movieSearch.value = stMovie ? stMovie.title : '';
  
  const stRoom = rooms.value.find(r => r.id === st.room_id);
  roomSearch.value = stRoom ? stRoom.name : '';

    form.value = {
      movie_id: st.movie_id,
      room_id: st.room_id,
      start_date: '',
      times: [''],
      start_time: startStr,
      end_time: endStr,
      end_date: '',
      format: st.format,
      translation: st.translation
    };
  formError.value = '';
  showModal.value = true;
};


const closeModal = () => { 
  showModal.value = false; 
  editingId.value = null;
};

const openDetailModal = (st) => {
  selectedDetail.value = st;
  showDetailModal.value = true;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedDetail.value = null;
};

const getRuleScopeLabel = (scope = 'system') => {
  const map = {
    system: '🌍 Toàn hệ thống',
    movie: '🎬 Theo phim'
  };
  return map[scope] || 'Toàn hệ thống';
};

const getSeatTypeLabel = (seatType = 'all') => {
  const map = {
    all: 'Tất cả ghế',
    standard: 'Ghế thường',
    vip: 'Ghế VIP',
    couple: 'Ghế đôi'
  };
  return map[seatType] || 'Tất cả ghế';
};

const getMovieNameById = (movieId) => {
  const movie = movies.value.find(item => item.id == movieId);
  return movie ? movie.title : 'Phim được chỉ định';
};

const resetPricingRuleDraft = () => {
  pricingRuleDraft.value = {
    name: '',
    scope: 'movie',
    seat_type: 'all',
    adjustment_type: 'surcharge',
    value: 0,
    start_date: '',
    end_date: '',
    holiday_date: '',
    movie_id: '',
    use_time_filter: false,
    time_from: '',
    time_to: ''
  };
};

const addPricingRule = () => {
  const rule = { ...pricingRuleDraft.value };
  if (!rule.name && rule.scope !== 'system') {
    rule.name = `${getRuleScopeLabel(rule.scope)} (${getSeatTypeLabel(rule.seat_type)})`;
  }

  const validation = validatePricingRule(rule, pricingForm.value.pricing_rules?.length ?? 0);
  if (!validation.isValid) {
    pricingErrors.value = {
      ...pricingErrors.value,
      ...validation.errors
    };
    const firstError = Object.values(validation.errors)[0]?.[0] || 'Vui lòng kiểm tra lại thông tin quy tắc.';
    toast(firstError, 'error');
    return;
  }

  const list = Array.isArray(pricingForm.value.pricing_rules) ? [...pricingForm.value.pricing_rules] : [];
  list.push({
    ...rule,
    value: rule.adjustment_type === 'free' ? 0 : Number(rule.value || 0)
  });
  pricingForm.value.pricing_rules = list;
  resetPricingRuleDraft();
  pricingErrors.value = {};
};

const removePricingRule = (index) => {
  const list = [...(pricingForm.value.pricing_rules || [])];
  list.splice(index, 1);
  pricingForm.value.pricing_rules = list;
};

const openPricingModal = () => {
  pricingForm.value = {
    ...currentPricing.value,
    pricing_rules: Array.isArray(currentPricing.value.pricing_rules) ? [...currentPricing.value.pricing_rules] : []
  };
  pricingErrors.value = {};
  showPricingModal.value = true;
};

const closePricingModal = () => {
  showPricingModal.value = false;
};

const validatePricingForm = () => {
  const validation = validatePricingConfig(pricingForm.value);
  pricingErrors.value = validation.errors;

  if (Array.isArray(pricingForm.value.pricing_rules)) {
    pricingForm.value.pricing_rules.forEach((rule, index) => {
      const ruleValidation = validatePricingRule(rule, index);
      pricingErrors.value = {
        ...pricingErrors.value,
        ...ruleValidation.errors
      };
      if (!ruleValidation.isValid) {
        validation.isValid = false;
      }
    });
  }

  if (!validation.isValid) {
    toast('Vui lòng nhập đầy đủ thông tin bắt buộc.', 'error');
  }

  return validation.isValid;
};

const savePricingConfig = async () => {
  if (!validatePricingForm()) return;
  pricingErrors.value = {};
  savingPricing.value = true;
  try {
    const payload = {
      ...pricingForm.value,
      pricing_rules: Array.isArray(pricingForm.value.pricing_rules) ? pricingForm.value.pricing_rules : []
    };

    const res = await api.put('/admin/pricing-rules', payload);
    if (res.data.success) {
      toast('Cập nhật cấu hình giá thành công!');
      currentPricing.value = { ...res.data.data };
      closePricingModal();
    }
  } catch (err) {
    console.error('Lỗi lưu cấu hình:', err);
    if (err.response?.data?.errors) {
      pricingErrors.value = err.response.data.errors;
    } else {
      toast('Có lỗi xảy ra khi lưu!', 'error');
    }
  } finally {
    savingPricing.value = false;
  }
};

const onMovieChange = () => { formError.value = ''; calculateEndTime(); };
const onStartTimeChange = () => { formError.value = ''; calculateEndTime(); };

const calculateEndTime = () => {
  if (!form.value.start_time || !form.value.movie_id) return;
  const movie = movies.value.find(m => m.id === form.value.movie_id);
  if (!movie) return;
  const start = new Date(form.value.start_time);
  // Thêm 15 phút thời gian dọn phòng vào Giờ kết thúc
  const end = new Date(start.getTime() + (movie.duration + 15) * 60 * 1000);
  const tzOffset = end.getTimezoneOffset() * 60000;
  form.value.end_time = new Date(end.getTime() - tzOffset).toISOString().slice(0, 16);
};

/* ---------- API ---------- */
const fetchShowtimes = async (showLoading = true) => {
  if (showLoading) loading.value = true;
  try {
    const res = await api.get('/admin/showtimes');
    showtimes.value = res.data;
  } catch (err) {
    console.error('Fetch showtimes error:', err);
  } finally {
    if (showLoading) loading.value = false;
  }
};

const preloadPricing = async () => {
  try {
    const res = await api.get('/admin/pricing-rules');
    if (res.data.success && res.data.data) {
      currentPricing.value = {
        ...res.data.data,
        pricing_rules: Array.isArray(res.data.data.pricing_rules) ? res.data.data.pricing_rules : []
      };
    }
  } catch (err) {
    console.error(err);
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

const errors = ref({});

const validateForm = () => {
  errors.value = {};
  let isValid = true;

  if (!form.value.movie_id) {
    errors.value.movie_id = ['Vui lòng chọn phim.'];
    isValid = false;
  }
  
  if (!form.value.room_id) {
    errors.value.room_id = ['Vui lòng chọn phòng chiếu.'];
    isValid = false;
  }
    if (editingId.value) {
      if (!form.value.start_time) {
        errors.value.start_time = ['Vui lòng chọn thời gian bắt đầu.'];
        isValid = false;
      }
    } else {
      if (!form.value.start_date) {
        errors.value.start_date = ['Vui lòng chọn ngày chiếu.'];
        isValid = false;
      }
      if (!form.value.times || form.value.times.length === 0 || form.value.times.some(t => !t)) {
        errors.value.times = ['Vui lòng điền đầy đủ các khung giờ.'];
        isValid = false;
      }
    }
  

  return isValid;
};

const saveShowtime = async () => {
  if (!validateForm()) return;

  submitting.value = true;
  formError.value = '';
  try {
    if (editingId.value) {
      await api.put(`/admin/showtimes/${editingId.value}`, form.value);
      toast('Cập nhật suất chiếu thành công!');
    } else {
      await api.post('/admin/showtimes', form.value);
      toast('Thêm suất chiếu mới thành công!');
    }
    showModal.value = false;
    // Cập nhật lại danh sách ngầm (không báo loading để tránh giật lag toàn trang)
    await fetchShowtimes(false);
  } catch (err) {
    console.error('Save showtime error:', err);
    const res = err.response?.data;
    if (res?.message) {
      formError.value = res.message;
    } else {
      toast.error('Đã xảy ra lỗi, vui lòng kiểm tra lại!');
    }
  } finally {
    submitting.value = false;
  }
};

const deleteShowtime = async (id) => {
  if (!(await confirmDialog('Bạn có chắc chắn muốn xóa suất chiếu này?', 'Hành động này không thể hoàn tác!'))) return;
  
  // OPTIMISTIC DELETE: Xóa ngay lập tức trên UI để người dùng thấy phản hồi tức thì
  const originalShowtimes = [...showtimes.value];
  showtimes.value = showtimes.value.filter(s => s.id !== id);

  try {
    await api.delete(`/admin/showtimes/${id}`);
    toast('Xóa suất chiếu thành công!');
  } catch (err) {
    // ROLBACK: Trả lại dữ liệu nếu API xóa thất bại
    showtimes.value = originalShowtimes;
    console.error('Delete showtime error:', err);
    toast('Không thể xóa suất chiếu này!', 'error');
  }
};


onMounted(async () => {
  document.addEventListener('click', closeDropdowns);
  await fetchShowtimes();
  await preloadPricing();
  await fetchMovies();
  await fetchRooms();
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns);
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
  flex-wrap: nowrap;
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

.stv-hero__timeline-filters {
  margin-top: 45px;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.stv-timeline-btn {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.4);
  color: #ffffff;
  padding: 10px 24px;
  border-radius: 25px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.25s ease;
  backdrop-filter: blur(8px);
}
.stv-timeline-btn:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}
.stv-timeline-btn--active {
  background: var(--accent-pink);
  border-color: #ff8fa3;
  color: #fff;
  font-weight: 700;
  box-shadow: 0 4px 15px rgba(255, 90, 100, 0.4);
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

/* Phân trang */
.pagination-cine {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  padding: 24px 16px 0;
}
.btn-page {
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-page:not(:disabled):hover {
  border-color: #e50914;
  color: #e50914;
}
.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.page-info {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
}

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

.ticket__actions {
  position: absolute;
  top: 12px; right: 12px;
  display: flex; gap: 6px;
  opacity: 0;
  transform: translateY(-4px);
  transition: all 0.2s;
}
.ticket:hover .ticket__actions { opacity: 1; transform: translateY(0); }

.ticket__btn {
  width: 32px; height: 32px;
  border: none; border-radius: 9px;
  background: #fff;
  border: 1px solid #fde0e2;
  cursor: pointer; font-size: 14px;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.ticket__btn--edit:hover { background: #f0fdf4; border-color: #22c55e; }
.ticket__btn--del:hover { background: #fee2e2; border-color: #e50914; }

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
  max-width: 700px;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 30px 70px rgba(0, 0, 0, 0.4);
}
.stv-modal__marquee {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  background:
    linear-gradient(90deg, #a80013 0%, #c80018 38%, #d31a27 100%);
  color: #fff;
}
.stv-modal__marquee h3 { margin: 0; font-size: 17px; font-weight: 800; }
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
  padding: 18px 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  background: #fff;
}
.stv-form--pricing {
  padding-top: 16px;
  gap: 12px;
}
.pricing-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.pricing-section h4 {
  margin: 0;
  color: #d71420;
  font-size: 15px;
  font-weight: 800;
  line-height: 1.3;
}
.stv-field { display: flex; flex-direction: column; gap: 6px; }
.stv-field label {
  font-size: 12px; font-weight: 700; color: #2d2d2d;
  display: flex; align-items: center; gap: 6px;
}
.stv-field--full { grid-column: 1 / -1; max-width: 50%; }
.stv-field label i { color: #e50914; font-style: normal; }
.stv-auto {
  font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 999px;
  background: #eef6f1; color: #047857;
}
.stv-input {
  padding: 9px 10px;
  background: #fff;
  border: 1.5px solid #d9d9d9;
  border-radius: 8px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
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
.stv-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; }
.stv-grid2--pricing-rule { margin-top: 4px; }
.pricing-actions {
  display: flex;
  justify-content: flex-start;
  gap: 12px;
  margin-top: 2px;
}
.pricing-note {
  display: block;
  color: #5b6472;
  font-size: 12px;
  line-height: 1.5;
  margin-top: 4px;
}
.pricing-detailed-note {
  margin-top: 4px;
  padding: 10px 12px;
  background: #f8f8f8;
  border: 1px solid #e8e8e8;
  border-radius: 8px;
  color: #475569;
  font-size: 12px;
  line-height: 1.6;
}
.pricing-detailed-note__title {
  margin: 0 0 6px;
  color: #1f2937;
  font-weight: 700;
}
.pricing-detailed-note ul {
  margin: 0;
  padding-left: 18px;
}
.pricing-detailed-note li + li {
  margin-top: 4px;
}
.rule-time-block {
  border-top: 1px solid #ececec;
  padding-top: 12px;
  margin-top: 2px;
}
.rule-time-block__row {
  margin-bottom: 8px;
}
.rule-time-toggle {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #111827;
}
.rule-time-toggle input {
  accent-color: #d71420;
  width: 15px;
  height: 15px;
  margin: 0;
}
.rule-time-inner {
  margin-top: 8px;
}
/* Combobox Styles */
.combobox-wrapper {
  position: relative;
  width: 100%;
}
.combobox-input {
  width: 100%;
  padding-right: 35px;
  cursor: text;
}
.combobox-arrow {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
  font-size: 0.8rem;
}
.combobox-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  width: 100%;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
  list-style: none;
  padding: 0;
  margin: 0;
}
.combobox-dropdown li {
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.95rem;
  color: #1e293b;
  transition: background 0.15s;
}
.combobox-dropdown li:last-child {
  border-bottom: none;
}
.combobox-dropdown li:hover {
  background: #f8fafc;
  color: var(--accent-pink);
  font-weight: 600;
}
.combobox-empty {
  color: #94a3b8 !important;
  text-align: center;
  font-style: italic;
  background: white !important;
  cursor: default !important;
}

@media (max-width: 768px) {
  .stv-grid { grid-template-columns: 1fr; }
}

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

.price-inputs {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.price-col {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.price-col span {
  font-size: 12.5px;
  font-weight: 700;
  color: #475569;
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
.stv-modal__actions {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-top: 12px;
  padding-top: 4px;
}
.stv-btn {
  display: inline-flex; align-items: center; justify-content: center;
  border: none; cursor: pointer;
  padding: 10px 18px; border-radius: 8px;
  font-size: 13px; font-weight: 700;
  transition: all 0.2s;
}
.stv-btn--outline {
  background: #f4f4f4;
  color: #111827;
  border: 1px solid #d4d4d4;
}
.stv-btn--outline:hover { background: #eaeaea; }
.stv-btn--ghost { background: #f0f0f0; color: #1f2937; border: 1px solid #d5d5d5; }
.stv-btn--ghost:hover { background: #e6e6e6; }
.stv-btn--solid {
  background: linear-gradient(90deg, #c90718, #e51c2f);
  color: #fff;
  border: 1px solid #b70014;
  box-shadow: 0 8px 18px rgba(213, 18, 41, 0.2);
}
.stv-btn--solid:hover { filter: brightness(1.04); }
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
.error-banner__msg { margin: 0; font-size: 14px; font-weight: 600; color: #b91c1c; line-height: 1.5; }
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
.error-msg {
  color: #dc2626;
  font-size: 0.85rem;
  margin-top: 5px;
  display: block;
  font-weight: 600;
}
</style>

<style scoped>
.times-container {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 10px;
  max-height: 180px;
  overflow-y: auto;
  padding: 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.time-slot-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.btn-remove-time {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 16px;
}
.btn-add-time {
  background: none;
  border: 1px dashed var(--accent-red);
  color: var(--accent-red);
  padding: 8px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  width: max-content;
}
.btn-add-time:hover {
  background: #fff5f5;
}
</style>
<style scoped>
.times-container {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 10px;
  max-height: 180px;
  overflow-y: auto;
  padding: 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.time-slot-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.btn-remove-time {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 16px;
}
.btn-add-time {
  background: none;
  border: 1px dashed var(--accent-red);
  color: var(--accent-red);
  padding: 8px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  width: max-content;
}
.btn-add-time:hover {
  background: #fff5f5;
}
</style>


