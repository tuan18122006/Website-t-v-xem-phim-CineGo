<template>
  <div class="pricing-container">
    <!-- Header -->
    <div class="header-section">
      <h2 class="title">⏰ Điều Chỉnh Giá Theo Thời Gian</h2>
      <p class="subtitle">Quản lý quy tắc giá động theo ngày và giờ</p>
    </div>

    <!-- Form Card -->
    <div class="form-card">
      <h3 class="form-title">Thêm Quy Tắc Giá Mới</h3>
      
      <form @submit.prevent="addRule">
        <!-- Row 1: Tên quy tắc | Phạm vi -->
        <div class="form-row">
          <div class="form-group">
            <label for="ruleName">Tên quy tắc</label>
            <input
              id="ruleName"
              v-model="newRule.name"
              type="text"
              placeholder="VD: Ngày lễ 30/4, Phim bán quyền cao"
              class="input-field"
              required
            />
          </div>

          <div class="form-group">
            <label for="scope">Phạm vi</label>
            <select v-model="newRule.scope" id="scope" class="select-field" required>
              <option value="">-- Chọn phạm vi --</option>
              <option value="system">Toàn hệ thống</option>
              <option value="movie">Theo phim</option>
            </select>
          </div>
        </div>

        <!-- Row 2: Chọn phim (khi scope=movie) | Áp dụng cho ghế -->
        <div class="form-row">
          <div v-if="newRule.scope === 'movie'" class="form-group">
            <label for="movieId">Chọn phim</label>
            <select v-model.number="newRule.movie_id" id="movieId" class="select-field" required>
              <option value="">-- Chọn phim --</option>
              <option v-for="movie in movies" :key="movie.id" :value="movie.id">
                {{ movie.title }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="seatType">Áp dụng cho ghế</label>
            <select v-model="newRule.seat_type" id="seatType" class="select-field" required>
              <option value="">Tất cả</option>
              <option value="standard">Ghế Thường</option>
              <option value="vip">Ghế VIP</option>
              <option value="couple">Ghế Đôi</option>
            </select>
          </div>
        </div>

        <!-- Copyright Badge (when movie selected) -->
        <div v-if="newRule.scope === 'movie' && selectedMovie" class="copyright-badge-row">
          <div class="copyright-badge" :class="`copyright-${selectedMovie.copyright_fee_level}`">
            <span v-if="selectedMovie.copyright_fee_level === 'high'">⭐ Bản quyền CAO</span>
            <span v-else-if="selectedMovie.copyright_fee_level === 'medium'">📌 Bản quyền TRUNG BÌNH</span>
            <span v-else>✅ Bản quyền THẤP</span>
            <p v-if="selectedMovie.copyright_notes" class="notes">{{ selectedMovie.copyright_notes }}</p>
          </div>
        </div>

        <!-- Row 3: Loại điều chỉnh | Giá trị -->
        <div class="form-row">
          <div class="form-group">
            <label for="adjustType">Loại điều chỉnh</label>
            <select v-model="newRule.adjustment_type" id="adjustType" class="select-field" required>
              <option value="">-- Chọn loại --</option>
              <option value="surcharge">Cộng tiền</option>
              <option value="percentage">Tăng %</option>
              <option value="free">Miễn phí</option>
            </select>
          </div>

          <div class="form-group">
            <label for="priceAdjustment">
              Giá trị
              <span v-if="newRule.adjustment_type === 'percentage'"> (%)</span>
              <span v-else-if="newRule.adjustment_type === 'free'"> (bỏ qua)</span>
              <span v-else> (VND)</span>
            </label>
            <input
              id="priceAdjustment"
              v-model.number="newRule.price_adjustment"
              type="number"
              placeholder="0"
              class="input-field"
              :disabled="newRule.adjustment_type === 'free'"
              required
            />
          </div>
        </div>

        <!-- Row 4: Ngày bắt đầu | Ngày kết thúc -->
        <div class="form-row">
          <div class="form-group">
            <label for="startDate">Ngày bắt đầu</label>
            <input
              id="startDate"
              v-model="newRule.start_date"
              type="date"
              class="input-field"
              required
            />
          </div>

          <div class="form-group">
            <label for="endDate">Ngày kết thúc</label>
            <input
              id="endDate"
              v-model="newRule.end_date"
              type="date"
              class="input-field"
              required
            />
          </div>
        </div>

        <!-- Help Text -->
        <p class="help-text">
          💡 <strong>Hướng dẫn:</strong><br>
          • <strong>Cộng tiền</strong>: Thêm giá trị cố định (VND) vào giá vé<br>
          • <strong>Tăng %</strong>: Tăng giá theo phần trăm (%)  <br>
          • <strong>Miễn phí</strong>: Miễn phí vé cho loại ghế được chọn<br>
          • <strong>Toàn hệ thống</strong>: Áp dụng cho tất cả phim<br>
          • <strong>Theo phim</strong>: Áp dụng cho phim cụ thể
        </p>

        <!-- Buttons -->
        <div class="form-actions">
          <button type="button" @click="resetForm" class="btn btn-secondary">
            🔄 Làm mới
          </button>
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            <span v-if="!isSubmitting">➕ Thêm quy tắc</span>
            <span v-else>Đang tạo...</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Rules List -->
    <div class="rules-section">
      <h3 class="section-title">Danh Sách Quy Tắc ({{ rules.length }})</h3>

      <div v-if="rules.length === 0" class="empty-state">
        <p>📭 Chưa có quy tắc nào</p>
      </div>

      <div v-else class="rules-grid">
        <div v-for="rule in rules" :key="rule.id" class="rule-card">
          <div class="rule-header">
            <h4 class="rule-name">{{ rule.name }}</h4>
            <span :class="['badge', rule.is_active ? 'active' : 'inactive']">
              {{ rule.is_active ? '✅ Kích hoạt' : '⚫ Vô hiệu' }}
            </span>
          </div>

          <div class="rule-info">
            <p><strong>Phạm vi:</strong> {{ getScopeName(rule.scope, rule.movie_id) }}</p>
            <p><strong>Ghế:</strong> {{ getSeatTypeName(rule.seat_type) }}</p>
            <p><strong>Giá trị:</strong> {{ rule.price_adjustment > 0 ? '+' : '' }}{{ formatPrice(rule.price_adjustment) }} VNĐ</p>
            <p v-if="rule.start_date"><strong>Ngày:</strong> {{ formatDate(rule.start_date) }} - {{ formatDate(rule.end_date) }}</p>
            <p v-if="rule.start_time"><strong>Giờ:</strong> {{ rule.start_time }} - {{ rule.end_time }}</p>
          </div>

          <div class="rule-actions">
            <button @click="toggleRule(rule)" class="btn-action">
              {{ rule.is_active ? '⚫ Vô hiệu' : '✅ Kích hoạt' }}
            </button>
            <button @click="editRule(rule)" class="btn-action">✏️ Sửa</button>
            <button @click="deleteRule(rule.id)" class="btn-action btn-danger">🗑️ Xóa</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editingRule" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Sửa Quy Tắc Giá</h3>
          <button @click="closeEditModal" class="close-btn">✕</button>
        </div>

        <form @submit.prevent="saveEdit" class="modal-form">
          <div class="form-group">
            <label>Tên quy tắc</label>
            <input v-model="editingRule.name" type="text" class="input-field" required />
          </div>

          <div class="form-group">
            <label>Phạm vi</label>
            <select v-model="editingRule.scope" class="select-field" required>
              <option value="system">Toàn hệ thống</option>
              <option value="movie">Theo phim</option>
            </select>
          </div>

          <div v-if="editingRule.scope === 'movie'" class="form-group">
            <label>Chọn phim</label>
            <select v-model.number="editingRule.movie_id" class="select-field" required>
              <option value="">-- Chọn phim --</option>
              <option v-for="movie in movies" :key="movie.id" :value="movie.id">
                {{ movie.title }}
              </option>
            </select>

            <!-- Hiển thị copyright info trong modal edit -->
            <div v-if="getMovieById(editingRule.movie_id)" class="copyright-badge" :class="`copyright-${getMovieById(editingRule.movie_id).copyright_fee_level}`">
              <span v-if="getMovieById(editingRule.movie_id).copyright_fee_level === 'high'">⭐ Bản quyền CAO</span>
              <span v-else-if="getMovieById(editingRule.movie_id).copyright_fee_level === 'medium'">📌 Bản quyền TRUNG BÌNH</span>
              <span v-else>✅ Bản quyền THẤP</span>
            </div>
          </div>

          <div class="form-group">
            <label>Áp dụng cho ghế</label>
            <select v-model="editingRule.seat_type" class="select-field" required>
              <option value="">Tất cả</option>
              <option value="standard">Ghế Thường</option>
              <option value="vip">Ghế VIP</option>
              <option value="couple">Ghế Đôi</option>
            </select>
          </div>

          <div class="form-group">
            <label>Giá trị (VNĐ)</label>
            <input v-model.number="editingRule.price_adjustment" type="number" class="input-field" required />
          </div>

          <div class="form-group">
            <label>Ngày bắt đầu</label>
            <input v-model="editingRule.start_date" type="date" class="input-field" required />
          </div>

          <div class="form-group">
            <label>Ngày kết thúc</label>
            <input v-model="editingRule.end_date" type="date" class="input-field" required />
          </div>

          <div class="form-group">
            <label>Giờ bắt đầu (Tùy chọn)</label>
            <input v-model="editingRule.start_time" type="time" class="input-field" />
          </div>

          <div class="form-group">
            <label>Giờ kết thúc (Tùy chọn)</label>
            <input v-model="editingRule.end_time" type="time" class="input-field" />
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeEditModal" class="btn btn-secondary">Hủy</button>
            <button type="submit" class="btn btn-primary">💾 Lưu</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div v-if="toast.message" :class="['toast', toast.type]">
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'TimeBasedPricingView',
  data() {
    return {
      rules: [],
      movies: [],
      newRule: {
        name: '',
        scope: '',
        movie_id: null,
        seat_type: '',
        adjustment_type: '',
        price_adjustment: 0,
        start_date: '',
        end_date: '',
        start_time: '',
        end_time: '',
        is_active: true
      },
      editingRule: null,
      isSubmitting: false,
      toast: {
        message: '',
        type: ''
      }
    };
  },
  computed: {
    selectedMovie() {
      return this.movies.find(m => m.id === this.newRule.movie_id) || null;
    }
  },
  mounted() {
    this.fetchRules();
    this.fetchMovies();
  },
  methods: {
    async fetchRules() {
      try {
        const response = await axios.get('/api/admin/time-based-pricing');
        this.rules = response.data.data || [];
      } catch (error) {
        console.error('Lỗi:', error);
        this.showToast('Lỗi khi tải dữ liệu', 'error');
      }
    },
    async fetchMovies() {
      try {
        const response = await axios.get('/api/movies');
        this.movies = response.data.data || [];
      } catch (error) {
        console.error('Lỗi tải danh sách phim:', error);
      }
    },
    async addRule() {
      if (!this.validateForm()) return;

      this.isSubmitting = true;
      try {
        const payload = {
          name: this.newRule.name,
          scope: this.newRule.scope,
          movie_id: this.newRule.scope === 'movie' ? this.newRule.movie_id : null,
          seat_type: this.newRule.seat_type || null,
          adjustment_type: this.newRule.adjustment_type,
          price_adjustment: this.newRule.adjustment_type === 'free' ? 0 : this.newRule.price_adjustment,
          start_date: this.newRule.start_date,
          end_date: this.newRule.end_date,
          start_time: this.newRule.start_time || null,
          end_time: this.newRule.end_time || null,
          use_date: true,
          use_time: this.newRule.start_time && this.newRule.end_time ? true : false,
          is_active: this.newRule.is_active
        };

        const response = await axios.post('/api/admin/time-based-pricing', payload);
        this.rules.push(response.data.data);
        this.resetForm();
        this.showToast('✅ Quy tắc được tạo thành công!', 'success');
      } catch (error) {
        const message = error.response?.data?.message || 'Lỗi khi tạo quy tắc';
        this.showToast(message, 'error');
      } finally {
        this.isSubmitting = false;
      }
    },
    async toggleRule(rule) {
      try {
        await axios.patch(`/api/admin/time-based-pricing/${rule.id}/toggle`);
        rule.is_active = !rule.is_active;
        this.showToast('✅ Cập nhật thành công!', 'success');
      } catch (error) {
        this.showToast('Lỗi cập nhật', 'error');
      }
    },
    editRule(rule) {
      this.editingRule = { ...rule };
    },
    closeEditModal() {
      this.editingRule = null;
    },
    async saveEdit() {
      try {
        const response = await axios.put(`/api/admin/time-based-pricing/${this.editingRule.id}`, {
          ...this.editingRule,
          movie_id: this.editingRule.scope === 'movie' ? this.editingRule.movie_id : null,
          use_date: true,
          use_time: this.editingRule.start_time && this.editingRule.end_time ? true : false
        });
        const index = this.rules.findIndex(r => r.id === this.editingRule.id);
        if (index >= 0) {
          this.rules[index] = response.data.data;
        }
        this.closeEditModal();
        this.showToast('✅ Cập nhật thành công!', 'success');
      } catch (error) {
        this.showToast('Lỗi cập nhật', 'error');
      }
    },
    async deleteRule(id) {
      if (!window.confirm('Xóa quy tắc này?')) return;
      try {
        await axios.delete(`/api/admin/time-based-pricing/${id}`);
        this.rules = this.rules.filter(r => r.id !== id);
        this.showToast('✅ Xóa thành công!', 'success');
      } catch (error) {
        this.showToast('Lỗi xóa', 'error');
      }
    },
    resetForm() {
      this.newRule = {
        name: '',
        scope: '',
        movie_id: null,
        seat_type: '',
        adjustment_type: '',
        price_adjustment: 0,
        start_date: '',
        end_date: '',
        start_time: '',
        end_time: '',
        is_active: true
      };
    },
    validateForm() {
      if (!this.newRule.name || !this.newRule.scope) {
        this.showToast('⚠️ Điền đầy đủ tên quy tắc và chọn phạm vi', 'warning');
        return false;
      }

      if (this.newRule.scope === 'movie' && !this.newRule.movie_id) {
        this.showToast('⚠️ Vui lòng chọn phim', 'warning');
        return false;
      }

      if (!this.newRule.adjustment_type) {
        this.showToast('⚠️ Vui lòng chọn loại điều chỉnh', 'warning');
        return false;
      }

      if (this.newRule.adjustment_type !== 'free' && !this.newRule.price_adjustment) {
        this.showToast('⚠️ Vui lòng nhập giá trị', 'warning');
        return false;
      }

      if (!this.newRule.start_date || !this.newRule.end_date) {
        this.showToast('⚠️ Điền đầy đủ ngày bắt đầu và kết thúc', 'warning');
        return false;
      }

      const startDate = new Date(this.newRule.start_date);
      const endDate = new Date(this.newRule.end_date);
      if (startDate > endDate) {
        this.showToast('⚠️ Ngày kết thúc phải sau ngày bắt đầu', 'warning');
        return false;
      }

      if (this.newRule.start_time && this.newRule.end_time) {
        if (this.newRule.start_time >= this.newRule.end_time) {
          this.showToast('⚠️ Giờ kết thúc phải sau giờ bắt đầu', 'warning');
          return false;
        }
      }

      return true;
    },
    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString('vi-VN');
    },
    formatPrice(price) {
      return new Intl.NumberFormat('vi-VN').format(price);
    },
    getScopeName(scope, movieId) {
      if (scope === 'system') {
        return '🌍 Toàn hệ thống';
      } else if (scope === 'movie') {
        const movie = this.movies.find(m => m.id === movieId);
        return movie ? `🎬 ${movie.title}` : 'Theo phim';
      }
      return scope;
    },
    getMovieById(movieId) {
      return this.movies.find(m => m.id === movieId) || null;
    },
    getSeatTypeName(type) {
      const names = {
        standard: '🎫 Ghế Thường',
        vip: '👑 Ghế VIP',
        couple: '💑 Ghế Đôi',
        '': 'Tất cả'
      };
      return names[type] || type;
    },
    showToast(message, type = 'info') {
      this.toast = { message, type };
      setTimeout(() => {
        this.toast = { message: '', type: '' };
      }, 3000);
    }
  }
};
</script>

<style scoped lang="scss">
.pricing-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 30px 20px;
  background: #f5f5f5;
  min-height: 100vh;
}

.header-section {
  text-align: center;
  margin-bottom: 40px;

  .title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 10px;
  }

  .subtitle {
    font-size: 14px;
    color: #666;
    margin: 0;
  }
}

.form-card {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 40px;

  .form-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
  }
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 25px;
  margin-bottom: 25px;

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }

  // Full-width single item row
  &:has(> .form-group:only-child) {
    grid-template-columns: 1fr;
  }
}

.form-group {
  display: flex;
  flex-direction: column;

  label {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    text-transform: capitalize;
  }

  .input-field,
  .select-field {
    padding: 12px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background-color: white;
    transition: all 0.3s ease;

    &:focus {
      outline: none;
      border-color: #dc3545;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    &:disabled {
      background-color: #f5f5f5;
      color: #999;
      cursor: not-allowed;
    }
  }

  .select-field {
    cursor: pointer;
  }
}

.help-text {
  font-size: 12px;
  color: #666;
  margin: 20px 0;
  padding: 12px;
  background-color: #f9f9f9;
  border-left: 3px solid #dc3545;
  line-height: 1.6;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 25px;
  border-top: 1px solid #f0f0f0;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;

  &:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  &.btn-primary {
    background-color: #dc3545;
    color: white;

    &:hover:not(:disabled) {
      background-color: #c82333;
    }
  }

  &.btn-secondary {
    background-color: #6c757d;
    color: white;

    &:hover:not(:disabled) {
      background-color: #5a6268;
    }
  }
}

.rules-section {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

  .section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
  }
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #999;

  p {
    font-size: 16px;
    margin: 0;
  }
}

.rules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.rule-card {
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 20px;
  background: #fafafa;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #dc3545;
  }
}

.rule-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 15px;
  gap: 10px;

  .rule-name {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
    flex: 1;
  }

  .badge {
    font-size: 11px;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 4px;
    white-space: nowrap;

    &.active {
      background-color: #d4edda;
      color: #155724;
    }

    &.inactive {
      background-color: #e2e3e5;
      color: #383d41;
    }
  }
}

.rule-info {
  border-top: 1px solid #e0e0e0;
  border-bottom: 1px solid #e0e0e0;
  padding: 12px 0;
  margin-bottom: 15px;
  font-size: 13px;
  color: #666;

  p {
    margin: 6px 0;

    strong {
      color: #333;
      font-weight: 600;
    }
  }
}

.rule-actions {
  display: flex;
  gap: 8px;

  .btn-action {
    flex: 1;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
      border-color: #dc3545;
      color: #dc3545;
    }

    &.btn-danger {
      color: #dc3545;

      &:hover {
        background-color: #fff5f5;
      }
    }
  }
}

.copyright-info {
  grid-column: 1 / -1;
}

.copyright-badge-row {
  margin-bottom: 20px;
  padding: 0 5px;
}

.copyright-badge {
  padding: 12px 14px;
  border-radius: 6px;
  border-left: 4px solid;
  font-size: 13px;
  font-weight: 600;

  &.copyright-high {
    background-color: #ffe5e5;
    border-left-color: #dc3545;
    color: #c82333;
  }

  &.copyright-medium {
    background-color: #fff4e5;
    border-left-color: #ff9800;
    color: #e67e22;
  }

  &.copyright-low {
    background-color: #e5f8e5;
    border-left-color: #28a745;
    color: #1e7e34;
  }

  .notes {
    font-size: 12px;
    font-weight: 400;
    margin: 6px 0 0 0;
    opacity: 0.9;
  }
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 450px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;

  h3 {
    font-size: 17px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
      color: #1a1a1a;
    }
  }
}

.modal-form {
  padding: 20px;

  .form-group {
    margin-bottom: 15px;

    label {
      font-size: 13px;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }

    .input-field,
    .select-field {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;

      &:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
      }
    }
  }
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  padding: 14px 20px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: white;
  z-index: 2000;
  max-width: 300px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);

  &.success {
    background-color: #28a745;
  }

  &.error {
    background-color: #dc3545;
  }

  &.warning {
    background-color: #ffc107;
    color: #333;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
