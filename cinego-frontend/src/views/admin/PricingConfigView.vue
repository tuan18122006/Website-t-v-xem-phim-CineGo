<template>
  <section class="pricing-config">
    <div class="pricing-config__header">
      <h3>Cấu Hình Giá Hệ Thống</h3>
    </div>

    <form v-if="!loadingPricing" @submit.prevent="savePricingConfig" class="pricing-config__form">
      <div class="pricing-panel">
        <div class="pricing-panel__title">1. Giá cơ bản</div>
        <div class="pricing-grid pricing-grid--base">
          <div class="pricing-field">
            <label>Ghế thường</label>
            <div class="money-input-wrap">
              <input
                type="text"
                inputmode="numeric"
                :value="moneyInputs.standard_price"
                @input="updateMoneyInput('standard_price', $event)"
                @blur="formatMoneyInput('standard_price')"
                placeholder="50.000"
              />
            </div>
            <span v-if="pricingErrors.standard_price" class="error-msg">{{ pricingErrors.standard_price[0] }}</span>
          </div>
          <div class="pricing-field">
            <label>Ghế VIP</label>
            <div class="money-input-wrap">
              <input
                type="text"
                inputmode="numeric"
                :value="moneyInputs.vip_price"
                @input="updateMoneyInput('vip_price', $event)"
                @blur="formatMoneyInput('vip_price')"
                placeholder="50.000"
              />
            </div>
            <span v-if="pricingErrors.vip_price" class="error-msg">{{ pricingErrors.vip_price[0] }}</span>
          </div>
          <div class="pricing-field pricing-field--full">
            <label>Ghế đôi</label>
            <div class="money-input-wrap">
              <input
                type="text"
                inputmode="numeric"
                :value="moneyInputs.couple_price"
                @input="updateMoneyInput('couple_price', $event)"
                @blur="formatMoneyInput('couple_price')"
                placeholder="50.000"
              />
            </div>
            <span v-if="pricingErrors.couple_price" class="error-msg">{{ pricingErrors.couple_price[0] }}</span>
          </div>
        </div>

        <div class="pricing-actions pricing-actions--base">
          <button type="submit" class="btn-primary" :disabled="savingPricing">
            {{ savingPricing ? 'Đang lưu...' : 'Lưu cấu hình' }}
          </button>
        </div>
      </div>

      <div class="pricing-panel">
        <div class="pricing-panel__title" style="display: flex; justify-content: space-between; align-items: center;">
          2. QUY TẮC GIÁ ĐẶC BIỆT
          <button type="button" class="btn-primary" @click="openCreateRule" style="font-size: 13px; padding: 6px 12px; border-radius: 6px;">+ Thêm quy tắc</button>
        </div>

        <div class="rule-list">
            <div v-if="pricingForm.pricing_rules && pricingForm.pricing_rules.length" v-for="(rule, index) in pricingForm.pricing_rules" :key="index" class="rule-list__item">
              <div class="rule-list__content">
                <strong>{{ rule.name || 'Quy tắc chưa đặt tên' }}</strong>
                <small>{{ rule.scope === 'movie' ? 'Theo phim' : 'Toàn hệ thống' }} • {{ formatSeatType(rule.seat_type) }} • {{ rule.status === 'active' ? 'Bật' : 'Tắt' }}</small>
              </div>
              <div class="rule-list__meta">
                <span>{{ rule.adjustment_type === 'free' ? 'Miễn phí' : rule.value ? formatMoney(rule.value) + (rule.adjustment_type === 'percentage' ? '%' : 'đ') : '0đ' }}</span>
                <div class="rule-list__actions">
                  <button type="button" class="mini-btn" @click="viewRule(index)">Xem chi tiết</button>
                  <button type="button" class="mini-btn" @click="editRule(index)">Sửa</button>
                  <button type="button" class="mini-btn mini-btn--danger" @click="removeRule(index)">Xóa</button>
                </div>
              </div>
            </div>
            <div v-else class="rule-list__empty">Chưa có quy tắc nào được thêm.</div>
          </div>
      </div>

      <!-- Modal Add/Edit Rule -->
      <div v-if="showRuleModal && (rulePageMode === 'create' || rulePageMode === 'edit')" class="rule-modal-overlay" @click.self="backToRuleList">
        <div class="rule-modal-container">
          <div class="rule-modal-header">
            <h3>{{ rulePageMode === 'create' ? 'THÊM QUY TẮC GIÁ' : 'SỬA QUY TẮC GIÁ' }}</h3>
            <button type="button" class="rule-modal-close" @click="backToRuleList">×</button>
          </div>
          <div class="rule-modal-body">
            <div class="pricing-grid" style="grid-template-columns: repeat(2, 1fr);">
              <div class="pricing-field">
                <label>Tên quy tắc <span class="required-mark">*</span></label>
                <input v-model="pricingRuleDraft.name" type="text" placeholder="Giá cuối tuần" />
                <span v-if="pricingRuleErrors.name" class="error-msg">{{ pricingRuleErrors.name[0] }}</span>
              </div>
              <div class="pricing-field">
                <label>Phạm vi <span class="required-mark">*</span></label>
                <select v-model="pricingRuleDraft.scope">
                  <option value="system">Toàn hệ thống</option>
                  <option value="movie">Theo phim</option>
                </select>
              </div>
              <div class="pricing-field">
                <label>Phim</label>
                <div v-if="pricingRuleDraft.scope === 'movie'" class="movie-picker">
                  <input v-model="movieSearch" type="search" placeholder="Tìm kiếm phim..." autocomplete="off" />
                  <select v-model="pricingRuleDraft.movie_id">
                    <option value="">Chọn phim</option>
                    <option v-for="movie in filteredMovies" :key="movie.id" :value="movie.id">{{ movie.title }}</option>
                    <option v-if="movieSearch.trim() && !filteredMovies.length" disabled value="">Không tìm thấy phim</option>
                  </select>
                </div>
                <div v-if="pricingRuleDraft.scope === 'movie' && movieSearch.trim()" class="movie-picker__results">
                  <button v-for="movie in filteredMovies" :key="movie.id" type="button" class="movie-picker__result" @click="selectMovie(movie)">
                    {{ movie.title }}
                  </button>
                  <span v-if="!filteredMovies.length" class="movie-picker__empty">Không tìm thấy phim</span>
                </div>
                <select v-if="pricingRuleDraft.scope !== 'movie'" disabled>
                  <option value="">Tất cả phim</option>
                </select>
                <span v-if="pricingRuleDraft.scope === 'movie' && pricingRuleDraft.movie_id" class="movie-picker__selected">Đã chọn: {{ getMovieName(pricingRuleDraft.movie_id) }}</span>
                <span v-if="pricingRuleErrors.movie_id" class="error-msg">{{ pricingRuleErrors.movie_id[0] }}</span>
              </div>
              <div class="pricing-field">
                <label>Loại ghế</label>
                <select v-model="pricingRuleDraft.seat_type">
                  <option value="all">Tất cả loại ghế</option>
                  <option value="standard">Ghế thường</option>
                  <option value="vip">Ghế VIP</option>
                  <option value="couple">Ghế đôi</option>
                </select>
              </div>
              <div class="pricing-field">
                <label>Ngày bắt đầu <span class="required-mark">*</span></label>
                <input v-model="pricingRuleDraft.start_date" type="date" :min="todayDate" />
                <span v-if="pricingRuleErrors.start_date" class="error-msg">{{ pricingRuleErrors.start_date[0] }}</span>
              </div>
              <div class="pricing-field">
                <label>Ngày kết thúc</label>
                <input v-model="pricingRuleDraft.end_date" type="date" :min="pricingRuleDraft.start_date || todayDate" />
                <span v-if="pricingRuleErrors.end_date" class="error-msg">{{ pricingRuleErrors.end_date[0] }}</span>
              </div>
              <div class="pricing-field pricing-field--full">
                <label>Thứ áp dụng</label>
                <div class="weekday-picker">
                  <label class="weekday-picker__item"><input type="checkbox" :checked="pricingRuleDraft.days.includes('T2')" @change="toggleWeekday('T2')" /><span>T2</span></label>
                  <label class="weekday-picker__item"><input type="checkbox" :checked="pricingRuleDraft.days.includes('T3')" @change="toggleWeekday('T3')" /><span>T3</span></label>
                  <label class="weekday-picker__item"><input type="checkbox" :checked="pricingRuleDraft.days.includes('T4')" @change="toggleWeekday('T4')" /><span>T4</span></label>
                  <label class="weekday-picker__item"><input type="checkbox" :checked="pricingRuleDraft.days.includes('T5')" @change="toggleWeekday('T5')" /><span>T5</span></label>
                  <label class="weekday-picker__item"><input type="checkbox" :checked="pricingRuleDraft.days.includes('T6')" @change="toggleWeekday('T6')" /><span>T6</span></label>
                  <label class="weekday-picker__item"><input type="checkbox" :checked="pricingRuleDraft.days.includes('T7')" @change="toggleWeekday('T7')" /><span>T7</span></label>
                  <label class="weekday-picker__item"><input type="checkbox" :checked="pricingRuleDraft.days.includes('CN')" @change="toggleWeekday('CN')" /><span>CN</span></label>
                  <label class="weekday-picker__item weekday-picker__item--all"><input type="checkbox" :checked="pricingRuleDraft.all_weekdays" @change="toggleAllWeekdays" /><span>Tất cả</span></label>
                </div>
              </div>
              <div class="pricing-field pricing-field--full date-exceptions">
                <label>Ngày ngoại trừ</label>
                <div class="date-exceptions__input">
                  <input v-model="excludedDateInput" type="date" :min="pricingRuleDraft.start_date || todayDate" :max="pricingRuleDraft.end_date || undefined" />
                  <button type="button" class="btn-secondary" @click="addExcludedDate">+ Thêm ngày</button>
                </div>
                <div v-if="pricingRuleDraft.excluded_dates.length" class="date-exceptions__list">
                  <span v-for="date in pricingRuleDraft.excluded_dates" :key="date" class="date-exceptions__item">{{ formatDisplayDate(date) }} <button type="button" @click="removeExcludedDate(date)" aria-label="Xóa">X</button></span>
                </div>
                <span v-if="pricingRuleErrors.excluded_dates" class="error-msg">{{ pricingRuleErrors.excluded_dates[0] }}</span>
              </div>
              <div class="pricing-field pricing-field--full">
                <label>Phạm vi giờ áp dụng</label>
                <div class="time-scope-selector">
                  <button type="button" class="time-scope-selector__item" :class="{ 'is-active': !pricingRuleDraft.use_time_filter }" @click="pricingRuleDraft.use_time_filter = false">Tất cả suất chiếu trong ngày</button>
                  <button type="button" class="time-scope-selector__item" :class="{ 'is-active': pricingRuleDraft.use_time_filter }" @click="pricingRuleDraft.use_time_filter = true">Chỉ áp dụng trong khung giờ</button>
                </div>
                <div v-if="pricingRuleDraft.use_time_filter" class="time-range-grid" style="margin-top: 12px; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                  <div class="time-range-grid__field">
                    <label>Từ giờ</label>
                    <input v-model="pricingRuleDraft.time_from" type="time" />
                    <span v-if="pricingRuleErrors.time_from" class="error-msg">{{ pricingRuleErrors.time_from[0] }}</span>
                  </div>
                  <div class="time-range-grid__field">
                    <label>Đến giờ</label>
                    <input v-model="pricingRuleDraft.time_to" type="time" />
                    <span v-if="pricingRuleErrors.time_to" class="error-msg">{{ pricingRuleErrors.time_to[0] }}</span>
                  </div>
                </div>
              </div>
              <div class="pricing-field">
                <label>Trạng thái</label>
                <div class="status-options">
                  <label class="status-option"><input v-model="pricingRuleDraft.status" type="radio" value="active" /><span>Bật</span></label>
                  <label class="status-option"><input v-model="pricingRuleDraft.status" type="radio" value="inactive" /><span>Tắt</span></label>
                </div>
              </div>
              <div class="pricing-field">
                <label>Kiểu điều chỉnh</label>
                <select v-model="pricingRuleDraft.adjustment_type">
                  <option value="surcharge">Cộng tiền</option>
                  <option value="percentage">Tăng %</option>
                  <option value="free">Miễn phí</option>
                </select>
              </div>
              <div class="pricing-field">
                <label>Giá trị</label>
                <input v-model.number="pricingRuleDraft.value" type="number" placeholder="10.000" :disabled="pricingRuleDraft.adjustment_type === 'free'" />
                <span v-if="pricingRuleErrors.value" class="error-msg">{{ pricingRuleErrors.value[0] }}</span>
              </div>
            </div>
          </div>
          <div class="rule-modal-footer">
            <button type="button" class="btn-secondary" @click="backToRuleList">Hủy</button>
            <button type="button" class="btn-primary" @click="rulePageMode === 'create' ? addPricingRule() : saveEditedRule()">Lưu quy tắc</button>
          </div>
        </div>
      </div>

      <!-- Modal View Rule -->
      <div v-if="showRuleModal && rulePageMode === 'view'" class="rule-modal-overlay" @click.self="backToRuleList">
        <div class="rule-modal-container">
          <div class="rule-modal-header">
            <h3>CHI TIẾT QUY TẮC GIÁ</h3>
            <button type="button" class="rule-modal-close" @click="backToRuleList">×</button>
          </div>
          <div class="rule-modal-body">
            <div class="detail-grid">
              <div class="detail-row"><span class="detail-label">Tên quy tắc</span><span class="detail-value">{{ pricingRuleDraft.name || '—' }}</span></div>
              <div class="detail-row"><span class="detail-label">Phạm vi</span><span class="detail-value">{{ pricingRuleDraft.scope === 'movie' ? 'Theo phim' : 'Toàn hệ thống' }}</span></div>
              <div class="detail-row"><span class="detail-label">Phim</span><span class="detail-value">{{ getMovieName(pricingRuleDraft.movie_id) || 'Tất cả' }}</span></div>
              <div class="detail-row"><span class="detail-label">Loại ghế</span><span class="detail-value">{{ formatSeatType(pricingRuleDraft.seat_type) }}</span></div>
              <div class="detail-row"><span class="detail-label">Ngày bắt đầu</span><span class="detail-value">{{ formatDisplayDate(pricingRuleDraft.start_date) || '—' }}</span></div>
              <div class="detail-row"><span class="detail-label">Ngày kết thúc</span><span class="detail-value">{{ formatDisplayDate(pricingRuleDraft.end_date) || '—' }}</span></div>
              <div class="detail-row"><span class="detail-label">Thứ áp dụng</span><span class="detail-value">{{ formatWeekdays(pricingRuleDraft.days) || 'Tất cả' }}</span></div>
              <div class="detail-row"><span class="detail-label">Ngày ngoại trừ</span><span class="detail-value">{{ pricingRuleDraft.excluded_dates.length ? pricingRuleDraft.excluded_dates.map(formatDisplayDate).join(', ') : 'Không có' }}</span></div>
              <div class="detail-row"><span class="detail-label">Khung giờ</span><span class="detail-value">{{ pricingRuleDraft.use_time_filter ? `${pricingRuleDraft.time_from || '--:--'} - ${pricingRuleDraft.time_to || '--:--'}` : 'Tất cả suất chiếu trong ngày' }}</span></div>
              <div class="detail-row"><span class="detail-label">Kiểu điều chỉnh</span><span class="detail-value">{{ formatAdjustmentType(pricingRuleDraft.adjustment_type) }}</span></div>
              <div class="detail-row"><span class="detail-label">Giá trị</span><span class="detail-value">{{ pricingRuleDraft.adjustment_type === 'free' ? 'Miễn phí' : `${formatMoney(pricingRuleDraft.value) || 0}${pricingRuleDraft.adjustment_type === 'percentage' ? '%' : 'đ'}` }}</span></div>
              <div class="detail-row"><span class="detail-label">Trạng thái</span><span class="detail-value">{{ pricingRuleDraft.status === 'active' ? 'Bật' : 'Tắt' }}</span></div>
            </div>
          </div>
          <div class="rule-modal-footer">
            <button type="button" class="btn-secondary" @click="backToRuleList">Đóng</button>
            <button type="button" class="btn-primary" @click="editRule(selectedRuleIndex)">Sửa</button>
          </div>
        </div>
      </div>

    </form>
    <p v-else class="pricing-loading">Đang tải dữ liệu...</p>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import api from '../../api/axios';
import { confirmDialog, toast } from '../../utils/alert';
import { validatePricingConfig, validatePricingRule } from '../../utils/pricingValidation';

const movies = ref([]);
const loadingPricing = ref(true);
const savingPricing = ref(false);
const pricingForm = ref({ pricing_rules: [] });
const moneyInputs = ref({ standard_price: '', vip_price: '', couple_price: '' });
const pricingErrors = ref({});
const pricingRuleErrors = ref({});
const editingRuleIndex = ref(null);
const selectedRuleIndex = ref(null);
const rulePageMode = ref('list');
const weekdayOptions = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
const today = new Date();
const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

const showRuleModal = ref(false);

const buildDefaultRule = () => ({
  name: '', scope: 'system', seat_type: 'all', adjustment_type: 'surcharge', value: 0,
  start_date: '', end_date: '', movie_id: '', use_time_filter: false,
  time_from: '', time_to: '', all_weekdays: true, days: [...weekdayOptions], excluded_dates: [], status: 'active'
});

const pricingRuleDraft = ref(buildDefaultRule());
const excludedDateInput = ref('');
const movieSearch = ref('');

const filteredMovies = computed(() => {
  const search = movieSearch.value.trim().toLowerCase();
  return !search
    ? movies.value
    : movies.value.filter(movie => String(movie.title || '').toLowerCase().includes(search));
});

const selectMovie = (movie) => {
  pricingRuleDraft.value.movie_id = movie.id;
  movieSearch.value = '';
  pricingRuleErrors.value.movie_id = undefined;
};

const formatDisplayDate = (date) => {
  if (!date) return '';
  const [year, month, day] = String(date).split('-');
  return `${day}/${month}/${year}`;
};

const addExcludedDate = () => {
  if (!excludedDateInput.value) return;
  if (pricingRuleDraft.value.excluded_dates.includes(excludedDateInput.value)) return;

  pricingRuleDraft.value.excluded_dates = [...pricingRuleDraft.value.excluded_dates, excludedDateInput.value].sort();
  excludedDateInput.value = '';
  pricingRuleErrors.value.excluded_dates = undefined;
};

const removeExcludedDate = (date) => {
  pricingRuleDraft.value.excluded_dates = pricingRuleDraft.value.excluded_dates.filter(item => item !== date);
};

const toggleWeekday = (day) => {
  const currentDays = [...pricingRuleDraft.value.days];
  const nextDays = currentDays.includes(day)
    ? currentDays.filter(item => item !== day)
    : [...currentDays, day];

  pricingRuleDraft.value.days = nextDays;
  pricingRuleDraft.value.all_weekdays = nextDays.length === weekdayOptions.length;
};

const toggleAllWeekdays = () => {
  const shouldSelectAll = !pricingRuleDraft.value.all_weekdays;
  pricingRuleDraft.value.days = shouldSelectAll ? [...weekdayOptions] : [];
  pricingRuleDraft.value.all_weekdays = shouldSelectAll;
};

const formatMoney = (value) => {
  if (value === '' || value === null || value === undefined) return '';
  const number = Number(value);
  if (!Number.isFinite(number) || number <= 0) return '';
  const thousandUnits = Math.round(number / 1000);
  return `${new Intl.NumberFormat('vi-VN').format(thousandUnits)}.000`;
};

const parseMoneyInput = (value) => {
  if (value === '' || value === null || value === undefined) return '';

  const rawValue = String(value).trim();
  if (!rawValue) return '';

  const digitsOnly = rawValue.replace(/\D/g, '');
  if (!digitsOnly) return '';

  const parsed = Number(digitsOnly);
  if (!Number.isFinite(parsed) || !Number.isInteger(parsed) || parsed <= 0) return '';

  const hasThousandsSeparator = /[.,]/.test(rawValue);
  return hasThousandsSeparator ? parsed : parsed * 1000;
};

const updateMoneyInput = (field, event) => {
  const rawValue = event.target.value;
  const caretPosition = event.target.selectionStart ?? rawValue.length;
  const digitsBeforeCaret = rawValue.slice(0, caretPosition).replace(/\D/g, '').length;
  const parsedValue = parseMoneyInput(rawValue);

  moneyInputs.value[field] = rawValue;
  pricingForm.value[field] = parsedValue;

  if (parsedValue !== '') {
    const formattedValue = formatMoney(parsedValue);
    moneyInputs.value[field] = formattedValue;
    event.target.value = formattedValue;

    const integerPart = formattedValue.split('.')[0];
    const nextCaretPosition = Math.min(digitsBeforeCaret, integerPart.length);
    event.target.setSelectionRange(nextCaretPosition, nextCaretPosition);
  }

  if (pricingErrors.value[field]) delete pricingErrors.value[field];
};

const formatMoneyInput = (field) => {
  moneyInputs.value[field] = formatMoney(pricingForm.value[field]);
};

const resetPricingRuleDraft = () => {
  pricingRuleErrors.value = {};
  editingRuleIndex.value = null;
  selectedRuleIndex.value = null;
  rulePageMode.value = 'list';
  showRuleModal.value = false;
  pricingRuleDraft.value = buildDefaultRule();
  excludedDateInput.value = '';
  movieSearch.value = '';
};

const backToRuleList = () => {
  resetPricingRuleDraft();
};

const openCreateRule = () => {
  pricingRuleErrors.value = {};
  editingRuleIndex.value = null;
  selectedRuleIndex.value = null;
  rulePageMode.value = 'create';
  showRuleModal.value = true;
  pricingRuleDraft.value = buildDefaultRule();
  pricingRuleDraft.value.status = 'active';
  movieSearch.value = '';
};

const loadRuleIntoForm = (rule, index) => {
  const safeDays = Array.isArray(rule.days) ? rule.days : [];
  pricingRuleDraft.value = {
    name: rule.name || '',
    scope: rule.scope || 'system',
    seat_type: rule.seat_type || 'all',
    adjustment_type: rule.adjustment_type || 'surcharge',
    value: rule.adjustment_type === 'free' ? 0 : Number(rule.value || 0),
    start_date: rule.start_date || '',
    end_date: rule.end_date || '',
    movie_id: rule.movie_id || '',
    use_time_filter: Boolean(rule.use_time_filter),
    time_from: rule.time_from || '',
    time_to: rule.time_to || '',
    all_weekdays: safeDays.length === weekdayOptions.length || rule.all_weekdays === true,
    days: [...safeDays],
    excluded_dates: Array.isArray(rule.excluded_dates) ? [...rule.excluded_dates].sort() : [],
    status: rule.status || 'active'
  };
  editingRuleIndex.value = index;
  selectedRuleIndex.value = index;
  pricingRuleErrors.value = {};
  movieSearch.value = '';
};

const viewRule = async (index) => {
  const rule = pricingForm.value.pricing_rules[index];
  if (!rule) return;

  rulePageMode.value = 'view';
  showRuleModal.value = true;
  loadRuleIntoForm(rule, index);
};

const editRule = async (index) => {
  const rule = pricingForm.value.pricing_rules[index];
  if (!rule) return;

  rulePageMode.value = 'edit';
  showRuleModal.value = true;
  loadRuleIntoForm(rule, index);
};

const removeRule = async (index) => {
  const confirmed = await confirmDialog('Xóa quy tắc?', 'Bạn có chắc chắn muốn xóa quy tắc này?');
  if (!confirmed) return;

  const removedRule = pricingForm.value.pricing_rules.splice(index, 1)[0];
  try {
    await api.put('/admin/pricing-rules', pricingForm.value);
  } catch (error) {
    pricingForm.value.pricing_rules.splice(index, 0, removedRule);
    toast('Không thể xóa quy tắc. Vui lòng thử lại!', 'error');
    return;
  }

  toast('Xóa quy tắc thành công!');

  if (selectedRuleIndex.value === index || editingRuleIndex.value === index) {
    resetPricingRuleDraft();
  }
};

const toggleRuleStatus = (index) => {
  const rule = pricingForm.value.pricing_rules[index];
  if (!rule) return;
  rule.status = rule.status === 'active' ? 'inactive' : 'active';
};

const addPricingRule = async () => {
  const rule = { ...pricingRuleDraft.value };
  const validationIndex = selectedRuleIndex.value !== null ? selectedRuleIndex.value : pricingForm.value.pricing_rules?.length ?? 0;
  const validation = validatePricingRule(rule, validationIndex);

  if (!validation.isValid) {
    pricingRuleErrors.value = {
      name: validation.errors[`rule_${validationIndex}_name`],
      movie_id: validation.errors[`rule_${validationIndex}_movie_id`],
      value: validation.errors[`rule_${validationIndex}_value`],
      start_date: validation.errors[`rule_${validationIndex}_date`],
      end_date: validation.errors[`rule_${validationIndex}_date`],
      excluded_dates: validation.errors[`rule_${validationIndex}_excluded_dates`],
      time_from: validation.errors[`rule_${validationIndex}_time_from`],
      time_to: validation.errors[`rule_${validationIndex}_time_to`]
    };
    return;
  }

  const payload = {
    ...rule,
    value: rule.adjustment_type === 'free' ? 0 : Number(rule.value || 0),
    status: rule.status || 'active',
    days: Array.isArray(rule.days) ? [...rule.days] : [...weekdayOptions],
    all_weekdays: rule.all_weekdays !== false
  };

  const isEditing = selectedRuleIndex.value !== null && rulePageMode.value === 'edit';

  if (isEditing) {
    pricingForm.value.pricing_rules.splice(selectedRuleIndex.value, 1, payload);
  } else {
    pricingForm.value.pricing_rules.push(payload);
  }

  try {
    await api.put('/admin/pricing-rules', pricingForm.value);
    toast(isEditing ? 'Cập nhật quy tắc thành công!' : 'Thêm quy tắc thành công!');
  } catch (error) {
    if (isEditing) {
      pricingForm.value.pricing_rules.splice(selectedRuleIndex.value, 1, rule);
    } else {
      pricingForm.value.pricing_rules.pop();
    }
    toast('Không thể lưu quy tắc. Vui lòng thử lại!', 'error');
    return;
  }

  resetPricingRuleDraft();
  pricingErrors.value = {};
  pricingRuleErrors.value = {};
};

const saveEditedRule = () => {
  addPricingRule();
};

const getMovieName = (movieId) => {
  const movie = movies.value.find(item => String(item.id) === String(movieId));
  return movie ? movie.title : '';
};



const formatSeatType = (value) => {
  const map = { all: 'Tất cả', standard: 'Ghế thường', vip: 'Ghế VIP', couple: 'Ghế đôi' };
  return map[value] || 'Tất cả';
};

const formatAdjustmentType = (value) => {
  const map = { surcharge: 'Cộng tiền', percentage: 'Tăng %', free: 'Miễn phí' };
  return map[value] || 'Cộng tiền';
};

const formatWeekdays = (days) => {
  if (!Array.isArray(days) || !days.length) return '';
  return days.join(', ');
};

const validatePricingForm = () => {
  const validation = validatePricingConfig(pricingForm.value);
  pricingErrors.value = validation.errors;
  return validation.isValid;
};

const savePricingConfig = async () => {
  if (!validatePricingForm()) return;
  savingPricing.value = true;
  try {
    const basePricing = {
      standard_price: pricingForm.value.standard_price,
      vip_price: pricingForm.value.vip_price,
      couple_price: pricingForm.value.couple_price
    };
    const response = await api.put('/admin/pricing-rules', basePricing);
    if (response.data.success) {
      pricingForm.value = { ...pricingForm.value, ...response.data.data };
      toast('Cập nhật giá cơ bản thành công!');
    }
  } catch (error) {
    pricingErrors.value = error.response?.data?.errors || {};
    toast('Có lỗi xảy ra khi lưu!', 'error');
  } finally {
    savingPricing.value = false;
  }
};

const resetPricingForm = () => {
  pricingErrors.value = {};
  resetPricingRuleDraft();
};

onMounted(async () => {
  try {
    const [pricingResponse, moviesResponse] = await Promise.all([
      api.get('/admin/pricing-rules'),
      api.get('/movies')
    ]);
    pricingForm.value = {
      ...pricingResponse.data.data,
      pricing_rules: Array.isArray(pricingResponse.data.data?.pricing_rules) ? [...pricingResponse.data.data.pricing_rules] : []
    };
    moneyInputs.value = {
      standard_price: formatMoney(pricingForm.value.standard_price),
      vip_price: formatMoney(pricingForm.value.vip_price),
      couple_price: formatMoney(pricingForm.value.couple_price)
    };
    movies.value = moviesResponse.data.data || moviesResponse.data || [];
  } catch (error) {
    toast('Không thể tải cấu hình giá.', 'error');
  } finally {
    loadingPricing.value = false;
  }
});
</script>

<style scoped>
:global(*) {
  box-sizing: border-box;
}

.pricing-config {
  background: linear-gradient(180deg, #fff 0%, #fffaf9 100%);
  border-radius: 18px;
  padding: 0 22px 22px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
  border: 1px solid #f1f3f5;
}

.pricing-config__header {
  background: linear-gradient(135deg, #d9001b 0%, #b30018 100%);
  color: #fff;
  margin: 0 -22px 18px;
  padding: 18px 22px;
  border-radius: 18px 18px 0 0;
  box-shadow: inset 0 -1px 0 rgba(255,255,255,0.15);
}

.pricing-config__header h3 {
  margin: 0;
  font-size: 20px;
  line-height: 1.2;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.pricing-config__form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.pricing-panel {
  border: 1px solid #edf2f6;
  background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
  border-radius: 16px;
  padding: 20px 20px 18px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}

.pricing-panel__title {
  color: #d9001b;
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 18px;
  position: relative;
  padding-left: 12px;
}

.pricing-panel__title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 1px;
  width: 4px;
  height: 18px;
  background: linear-gradient(180deg, #d9001b, #f43f5e);
  border-radius: 999px;
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px 18px;
}

.pricing-grid--base {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.pricing-field {
  display: flex;
  flex-direction: column;
  gap: 7px;
  min-width: 0;
}

.pricing-field--full,
.pricing-field--time-scope,
.pricing-field--status {
  grid-column: 1 / -1;
}

.pricing-field label {
  color: #374151;
  font-size: 12px;
  font-weight: 700;
}

.money-input-wrap {
  position: relative;
}

.money-input-wrap input {
  width: 100%;
  box-sizing: border-box;
  padding-right: 64px;
}

.money-input-wrap__suffix {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-size: 12px;
  font-weight: 700;
}

.pricing-field input,
.pricing-field select,
.preview-form__row select,
.preview-form__row input {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #dbe1e8;
  border-radius: 10px;
  padding: 10px 12px;
  color: #1f2937;
  background: #fff;
  font-size: 13px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.pricing-field input:hover,
.pricing-field select:hover,
.preview-form__row select:hover,
.preview-form__row input:hover {
  border-color: #c8d1dc;
}

.pricing-field input:focus,
.pricing-field select:focus,
.preview-form__row select:focus,
.preview-form__row input:focus {
  outline: none;
  border-color: #d9001b;
  box-shadow: 0 0 0 3px rgba(217, 0, 27, 0.09);
  background: #fff;
}

.pricing-field input:disabled {
  background: #f3f4f6;
  color: #9ca3af;
}

.required-mark {
  color: #d9001b;
}

.status-options {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 18px;
  margin-top: 2px;
}

.status-option,
.time-scope__option,
.time-scope-option,
.radio-option {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #374151;
  font-size: 13px;
  cursor: pointer;
}

.status-option input,
.time-scope__option input,
.time-scope-option input,
.radio-option input,
.weekday-picker__item input {
  accent-color: #d9001b;
}

.rule-section {
  border: 1px solid #ebf0f3;
  background: linear-gradient(180deg, #fafbfd 0%, #fff 100%);
  border-radius: 12px;
  padding: 18px;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
}

.rule-section + .rule-section {
  margin-top: 18px;
}

.rule-section__header,
.rule-section__topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.rule-section__title {
  color: #d9001b;
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 14px;
}

.back-link {
  border: none;
  background: transparent;
  color: #4b5563;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  padding: 0;
}

.back-link:hover {
  color: #d9001b;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px 18px;
  padding-top: 6px;
}

.detail-row {
  display: grid;
  grid-template-columns: 170px 1fr;
  gap: 12px;
  padding: 11px 0;
  border-bottom: 1px solid #edf1f4;
}

.detail-label {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.detail-value {
  font-size: 13px;
  color: #111827;
  word-break: break-word;
}

.weekday-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 12px;
  margin-top: 4px;
}

.weekday-picker__item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #374151;
  font-size: 12px;
  font-weight: 700;
  min-width: 52px;
  padding: 6px 8px;
  border-radius: 8px;
  background: #f9fafb;
  border: 1px solid #e5ebf2;
}

.weekday-picker__item input {
  width: 14px;
  height: 14px;
  margin: 0;
}

.weekday-picker__item--all {
  margin-left: 4px;
}

.time-scope-selector {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  width: 100%;
}

.time-scope-selector__item {
  flex: 1;
  border: none;
  background: transparent;
  color: #4b5563;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}

.time-scope-selector__item.is-active {
  background: #fff;
  color: #d9001b;
  box-shadow: 0 2px 10px rgba(217, 0, 27, 0.12);
  border: 1px solid rgba(217, 0, 27, 0.12);
}

.time-range-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 14px;
  margin-top: 12px;
}

.time-range-grid__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.time-range-grid__field label {
  color: #374151;
  font-size: 12px;
  font-weight: 700;
}

.date-exceptions__input {
  display: flex;
  align-items: center;
  gap: 10px;
}

.date-exceptions__input input {
  max-width: 220px;
}

.date-exceptions__list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 4px;
}

.date-exceptions__item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 9px;
  border: 1px solid #fecdd3;
  border-radius: 8px;
  background: #fff1f2;
  color: #9f1239;
  font-size: 12px;
  font-weight: 700;
}

.date-exceptions__item button {
  border: 0;
  padding: 0;
  background: transparent;
  color: #be123c;
  font-weight: 800;
  cursor: pointer;
}

.movie-picker {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 8px;
  position: relative;
}

.movie-picker input,
.movie-picker select {
  min-width: 0;
}

.movie-picker__results {
  position: relative;
  z-index: 10;
  max-height: 240px;
  overflow-y: auto;
  padding: 4px;
  border: 1px solid #dbe1e8;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
}

.movie-picker__result {
  display: block;
  width: 100%;
  border: 0;
  border-radius: 7px;
  padding: 10px 12px;
  background: transparent;
  color: #1f2937;
  font-size: 13px;
  text-align: left;
  cursor: pointer;
}

.movie-picker__result:hover,
.movie-picker__result:focus {
  outline: none;
  background: #fff1f2;
  color: #b10017;
}

.movie-picker__empty,
.movie-picker__selected {
  display: block;
  color: #6b7280;
  font-size: 11px;
}

.movie-picker__empty {
  padding: 10px 12px;
}

.movie-picker__selected {
  margin-top: 4px;
  color: #166534;
  font-weight: 700;
}

.btn-primary--full {
  width: 100%;
  justify-content: center;
}

.rule-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 4px;
}

.rule-list__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  border: 1px solid #e8edf4;
  background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
  border-radius: 12px;
  padding: 16px 20px;
  transition: all 0.2s ease;
}

.rule-list__item:hover {
  border-color: #d5ddea;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
  transform: translateY(-1px);
}

.rule-list__content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.rule-list__content strong {
  color: #111827;
  font-size: 14px;
}

.rule-list__content small {
  color: #6b7280;
  font-size: 12px;
}

.rule-list__meta {
  display: flex;
  align-items: center;
  gap: 14px;
  color: #374151;
  font-weight: 700;
}

.rule-list__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.mini-btn {
  border: 1px solid #dfe7ef;
  background: #f8fafc;
  color: #374151;
  border-radius: 9px;
  padding: 7px 11px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.mini-btn:hover {
  background: #eef2f7;
  border-color: #c7d2df;
}

.mini-btn--danger {
  color: #b91c1c;
  border-color: rgba(185, 28, 28, 0.2);
  background: #fff5f5;
}

.mini-btn--danger:hover {
  background: #ffeaea;
}

.rule-list__empty {
  border: 1px dashed #d1d5db;
  border-radius: 10px;
  padding: 18px;
  color: #6b7280;
  background: #fff;
  text-align: center;
}

.pricing-actions,
.pricing-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}

.pricing-footer {
  border-top: 1px solid #eef2f6;
  padding-top: 18px;
}

.btn-primary,
.btn-secondary {
  border: 0;
  border-radius: 10px;
  padding: 10px 18px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #d9001b 0%, #b10017 100%);
  color: #fff;
  box-shadow: 0 12px 22px rgba(217, 0, 27, 0.16);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #c6001a 0%, #9d0015 100%);
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.62;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-secondary {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  color: #374151;
  border: 1px solid #dfe7ef;
}

.btn-secondary:hover {
  background: linear-gradient(180deg, #f1f5f9 0%, #e7edf5 100%);
}

.error-msg {
  color: #d9001b;
  font-size: 11px;
  line-height: 1.45;
}

.pricing-panel--split {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.mini-panel {
  border: 1px solid #edf0f3;
  border-radius: 10px;
  background: #fafafb;
  padding: 14px;
}

.mini-panel__body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.preview-form {
  border: 1px solid #edf0f3;
  background: #fafafb;
  border-radius: 10px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.preview-form__row {
  display: grid;
  grid-template-columns: 90px 1fr;
  align-items: center;
  gap: 8px;
}

.preview-form__row label {
  font-size: 12px;
  color: #374151;
  font-weight: 700;
}

.preview-calc-btn {
  margin-top: 6px;
  border: 0;
  border-radius: 8px;
  background: #d9001b;
  color: white;
  font-weight: 700;
  padding: 10px 14px;
  cursor: pointer;
}

.preview-summary {
  border: 1px solid #edf0f3;
  background: #fafafb;
  border-radius: 10px;
  padding: 12px 14px;
}

.preview-summary__row,
.preview-summary__total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 7px 0;
  color: #374151;
  font-size: 13px;
}

.preview-summary__divider {
  border-top: 1px solid #e5e7eb;
  margin: 6px 0;
}

.preview-summary__total {
  font-weight: 700;
  color: #111827;
}

.preview-summary__total strong {
  color: #d9001b;
}

.history-box {
  border: 1px solid #edf0f3;
  background: #fafafb;
  border-radius: 10px;
  padding: 12px 14px;
}

.history-box__item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid #eef1f4;
  flex-direction: column;
  align-items: flex-start;
}

.history-box__item:last-child {
  border-bottom: 0;
}

.history-box__item span {
  color: #374151;
  font-size: 13px;
}

.history-box__item small {
  color: #6b7280;
  font-size: 11px;
}

.pricing-loading {
  color: #6b7280;
  padding: 25px 0;
  text-align: center;
}

@media (max-width: 700px) {
  .pricing-config {
    padding: 0 14px 16px;
  }

  .pricing-config__header {
    margin: 0 -14px 14px;
    padding: 16px 16px;
  }

  .pricing-grid,
  .pricing-grid--base,
  .pricing-panel--split,
  .detail-grid,
  .time-range-grid {
    grid-template-columns: 1fr;
  }
  
  .rule-modal-body .pricing-grid {
    grid-template-columns: 1fr !important;
  }

  .detail-row {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .pricing-actions,
  .pricing-footer {
    flex-direction: column;
  }

  .pricing-actions > *,
  .pricing-footer > * {
    width: 100%;
  }

  .date-exceptions__input {
    align-items: stretch;
    flex-direction: column;
  }

  .date-exceptions__input input {
    max-width: none;
  }

  .movie-picker {
    grid-template-columns: 1fr;
  }

  .rule-list__item {
    flex-direction: column;
    align-items: flex-start;
  }

  .rule-list__meta {
    width: 100%;
    justify-content: space-between;
    flex-wrap: wrap;
  }
}

/* Modal CSS */
.rule-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.rule-modal-container {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.rule-modal-container--large {
  max-width: 800px;
}

.rule-modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.rule-modal-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #dc2626;
  text-transform: uppercase;
}

.rule-modal-close {
  background: none;
  border: none;
  font-size: 24px;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.rule-modal-close:hover {
  color: #111827;
}

.rule-modal-body {
  padding: 24px;
  overflow-y: auto;
}

.rule-modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: #f9fafb;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}
</style>
