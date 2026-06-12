<template>
  <div class="user-management">
    <!-- THANH ĐIỀU KHIỂN: sub-tabs + nút thêm -->
    <div class="controls">
      <div class="section-tabs">
        <button
          v-for="s in sections"
          :key="s.key"
          class="section-tab"
          :class="{ active: activeSection === s.key }"
          @click="switchSection(s.key)"
        >
          <component :is="s.icon" />
          <span>{{ s.label }}</span>
          <span class="st-count">{{ roleCount(s.role) }}</span>
        </button>
      </div>
      <button class="btn-add" @click="openCreate">
        <IconPlus /> Thêm {{ currentSection.addLabel }}
      </button>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">
      <div class="search-wrap">
        <span class="search-ic"><IconSearch /></span>
        <input
          v-model="search"
          type="text"
          class="search-input"
          placeholder="Tìm theo tên, email hoặc số điện thoại..."
        />
      </div>
      <div class="seg" role="tablist">
        <button :class="{ on: statusFilter === '' }" @click="statusFilter = ''">Tất cả</button>
        <button :class="{ on: statusFilter === 'active' }" @click="statusFilter = 'active'">Hoạt động</button>
        <button :class="{ on: statusFilter === 'locked' }" @click="statusFilter = 'locked'">Đã khóa</button>
      </div>
    </div>

    <div v-if="error" class="alert-error">{{ error }}</div>

    <!-- ============================================================= -->
    <!-- TAB KHÁCH HÀNG & NHÂN VIÊN (bảng) -->
    <!-- ============================================================= -->
    <template v-if="activeSection !== 'admin'">
      <div v-if="activeSection === 'staff'" class="stats-row">
        <div class="stat-card">
          <span class="stat-ic ic-violet"><IconUsers /></span>
          <div><span class="sp-value">{{ roleCount('staff') }}</span><span class="sp-label">Tổng nhân viên</span></div>
        </div>
        <div class="stat-card">
          <span class="stat-ic ic-mint"><IconCheck /></span>
          <div><span class="sp-value">{{ sectionCount('active') }}</span><span class="sp-label">Đang hoạt động</span></div>
        </div>
        <div class="stat-card">
          <span class="stat-ic ic-red"><IconLock /></span>
          <div><span class="sp-value">{{ sectionCount('locked') }}</span><span class="sp-label">Đã khóa</span></div>
        </div>
      </div>

      <div class="panel">
        <div v-if="selectedIds.length" class="bulk-bar">
          <span class="bulk-count">{{ selectedIds.length }} đã chọn</span>
          <div class="bulk-actions">
            <button class="btn-soft" @click="bulkSetLocked(false)"><IconUnlock /> Mở khóa</button>
            <button class="btn-soft red" @click="bulkSetLocked(true)"><IconLock /> Khóa</button>
            <button class="btn-ghost" @click="clearSelection">Bỏ chọn</button>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th class="ta-center cb-col">
                <input type="checkbox" :checked="allChecked" :indeterminate.prop="someChecked" @change="toggleAll" />
              </th>
              <th>{{ activeSection === 'staff' ? 'Nhân viên' : 'Khách hàng' }}</th>
              <th>Liên hệ</th>
              <th v-if="activeSection === 'staff'">Ngày vào làm</th>
              <th>Vai trò</th>
              <th>Trạng thái</th>
              <th class="ta-right">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in filteredUsers" :key="u.id" :class="{ 'row-sel': isSelected(u.id) }">
              <td class="ta-center">
                <input type="checkbox" :checked="isSelected(u.id)" @change="toggleOne(u.id)" />
              </td>
              <td>
                <div class="id-cell">
                  <span class="avatar" :style="{ background: avatarColor(u.name) }">{{ initials(u.name) }}</span>
                  <div class="id-meta">
                    <strong>{{ u.name }}</strong>
                    <small>{{ u.email }}</small>
                  </div>
                </div>
              </td>
              <td class="td-muted">{{ u.phone || '—' }}</td>
              <td v-if="activeSection === 'staff'" class="td-muted">{{ formatDate(u.created_at) }}</td>
              <td><span class="badge" :class="'role-' + u.role">{{ roleLabel(u.role) }}</span></td>
              <td>
                <span class="badge" :class="u.status === 'locked' ? 'status-locked' : 'status-active'">
                  <i class="dot"></i>{{ u.status === 'locked' ? 'Đã khóa' : 'Hoạt động' }}
                </span>
              </td>
              <td class="actions ta-right">
                <button class="btn-icon" title="Chi tiết" @click="openDetail(u)"><IconEye /></button>
                <button class="btn-icon" title="Sửa" @click="openEdit(u)"><IconEdit /></button>
                <button v-if="activeSection === 'customer'" class="btn-soft mint" @click="promoteToStaff(u)">
                  <IconUserPlus /> Nâng cấp NV
                </button>
                <template v-else>
                  <button class="btn-soft red" @click="changeRole(u, 'admin')"><IconArrowUp /> Admin</button>
                  <button class="btn-soft" @click="changeRole(u, 'customer')"><IconArrowDown /> Khách</button>
                </template>
                <button class="btn-icon" :title="u.status === 'locked' ? 'Mở khóa' : 'Khóa'" @click="toggleStatus(u)">
                  <component :is="u.status === 'locked' ? IconUnlock : IconLock" />
                </button>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td :colspan="activeSection === 'staff' ? 7 : 6">
                <div class="empty-state">
                  <span class="empty-ic"><IconUsers /></span>
                  <p>Không có {{ activeSection === 'staff' ? 'nhân viên' : 'khách hàng' }} nào phù hợp.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="table-foot">Hiển thị {{ filteredUsers.length }} / {{ roleCount(currentSection.role) }} tài khoản</div>
      </div>
    </template>

    <!-- ============================================================= -->
    <!-- TAB ADMIN (card grid cao cấp) -->
    <!-- ============================================================= -->
    <template v-else>
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-ic ic-red"><IconCrown /></span>
          <div><span class="sp-value">{{ roleCount('admin') }}</span><span class="sp-label">Tổng quản trị viên</span></div>
        </div>
        <div class="stat-card">
          <span class="stat-ic ic-mint"><IconCheck /></span>
          <div><span class="sp-value">{{ sectionCount('active') }}</span><span class="sp-label">Đang hoạt động</span></div>
        </div>
        <div class="stat-card">
          <span class="stat-ic ic-red"><IconLock /></span>
          <div><span class="sp-value">{{ sectionCount('locked') }}</span><span class="sp-label">Đã khóa</span></div>
        </div>
      </div>

      <div class="security-note">
        <span class="sn-ic"><IconShield /></span>
        <span>Khu vực nhạy cảm — không thể tự khóa hoặc hạ quyền tài khoản đang đăng nhập của chính bạn.</span>
      </div>

      <div class="admin-grid">
        <div v-for="u in filteredUsers" :key="u.id" class="admin-card">
          <div class="ac-banner"></div>
          <div class="ac-body">
            <span class="avatar xl" :style="{ background: avatarColor(u.name) }">{{ initials(u.name) }}</span>
            <h4 class="ac-name">{{ u.name }}</h4>
            <p class="ac-email">{{ u.email }}</p>
            <div class="ac-badges">
              <span class="badge role-admin"><IconCrown /> Quản trị</span>
              <span class="badge" :class="u.status === 'locked' ? 'status-locked' : 'status-active'">
                <i class="dot"></i>{{ u.status === 'locked' ? 'Đã khóa' : 'Hoạt động' }}
              </span>
              <span v-if="isSelf(u)" class="badge badge-self">Bạn</span>
            </div>
            <ul class="ac-info">
              <li><span><IconPhone /> SĐT</span><strong>{{ u.phone || '—' }}</strong></li>
              <li><span><IconCalendar /> Ngày tạo</span><strong>{{ formatDate(u.created_at) }}</strong></li>
            </ul>
            <div class="ac-actions">
              <button class="btn-soft" @click="openEdit(u)"><IconEdit /> Sửa</button>
              <button class="btn-soft" :disabled="isSelf(u)" @click="changeRole(u, 'staff')"><IconArrowDown /> NV</button>
              <button class="btn-soft red" :disabled="isSelf(u)" @click="toggleStatus(u)">
                <component :is="u.status === 'locked' ? IconUnlock : IconLock" />
              </button>
            </div>
          </div>
        </div>
        <div v-if="filteredUsers.length === 0" class="empty-state wide">
          <span class="empty-ic"><IconCrown /></span>
          <p>Chưa có quản trị viên nào.</p>
        </div>
      </div>
    </template>

    <!-- MODAL FORM THÊM/SỬA -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-box">
        <h3>{{ isEdit ? 'Sửa Tài Khoản' : 'Thêm ' + currentSection.addLabel }}</h3>
        <form @submit.prevent="submitForm" class="modal-form">
          <label>Tên người dùng</label>
          <input v-model="form.name" type="text" required placeholder="Nguyễn Văn A" />

          <label>Email</label>
          <input v-model="form.email" type="email" required placeholder="email@example.com" />

          <label>Mật khẩu {{ isEdit ? '(để trống nếu không đổi)' : '' }}</label>
          <input v-model="form.password" type="password" :required="!isEdit" placeholder="••••••••" />

          <label>Số điện thoại</label>
          <input v-model="form.phone" type="text" placeholder="0987xxxxxx" />

          <div class="form-row">
            <div>
              <label>Vai trò</label>
              <select v-model="form.role" required>
                <option value="admin">Admin (Quản trị)</option>
                <option value="staff">Staff (Nhân viên)</option>
                <option value="customer">User (Khách hàng)</option>
              </select>
            </div>
            <div>
              <label>Trạng thái</label>
              <select v-model="form.status" required>
                <option value="active">Hoạt động</option>
                <option value="locked">Khóa</option>
              </select>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="closeModal">Hủy</button>
            <button type="submit" class="btn-save" :disabled="loading">
              {{ loading ? 'Đang lưu...' : 'Lưu thay đổi' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CHI TIẾT -->
    <div v-if="showDetail" class="modal-overlay" @click.self="closeDetail">
      <div class="modal-box detail-box">
        <h3>Chi Tiết Tài Khoản</h3>
        <div v-if="detailLoading" class="loading-text">Đang tải dữ liệu...</div>

        <template v-else-if="detail">
          <div class="detail-hero">
            <span class="avatar xl" :style="{ background: avatarColor(detail.user.name) }">{{ initials(detail.user.name) }}</span>
            <div>
              <h4 class="dh-name">{{ detail.user.name }}</h4>
              <p class="dh-email">{{ detail.user.email }}</p>
              <div class="ac-badges">
                <span class="badge" :class="'role-' + detail.user.role">{{ roleLabel(detail.user.role) }}</span>
                <span class="badge" :class="detail.user.status === 'locked' ? 'status-locked' : 'status-active'">
                  <i class="dot"></i>{{ detail.user.status === 'locked' ? 'Đã khóa' : 'Hoạt động' }}
                </span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Thống kê lịch sử mua vé</h4>
            <div class="stats-grid">
              <div class="mini-stat">
                <span class="stat-value">{{ detail.stats.total_bookings }}</span>
                <span class="stat-label">Đơn đặt vé</span>
              </div>
              <div class="mini-stat">
                <span class="stat-value">{{ detail.stats.total_tickets }}</span>
                <span class="stat-label">Vé đã mua</span>
              </div>
              <div class="mini-stat">
                <span class="stat-value">{{ formatCurrency(detail.stats.total_spent) }}</span>
                <span class="stat-label">Tổng chi tiêu</span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Đánh giá & bình luận ({{ detail.reviews.length }})</h4>
            <div v-if="detail.reviews.length === 0" class="muted">Chưa có đánh giá nào.</div>
            <ul v-else class="review-list">
              <li v-for="r in detail.reviews" :key="r.id">
                <div class="review-head">
                  <strong>{{ r.movie?.title || 'Phim #' + r.movie_id }}</strong>
                  <span class="review-rating">⭐ {{ r.rating }}/5</span>
                </div>
                <p class="review-comment">{{ r.comment || '(không có bình luận)' }}</p>
              </li>
            </ul>
          </div>
        </template>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" @click="closeDetail">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import api from '../../api/axios';
import { useAuthStore } from '../../stores/auth';

/* ---------- Icon SVG (kiểu Lucide, stroke mảnh) ---------- */
const mk = (children) => ({
  render() {
    return h('svg', {
      class: 'ico', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
      'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
    }, children.map(([t, a]) => h(t, a)));
  },
});
const IconUsers   = mk([['path',{d:'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'}],['circle',{cx:9,cy:7,r:4}],['path',{d:'M22 21v-2a4 4 0 0 0-3-3.87'}],['path',{d:'M16 3.13a4 4 0 0 1 0 7.75'}]]);
const IconUser    = mk([['path',{d:'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2'}],['circle',{cx:12,cy:7,r:4}]]);
const IconBriefcase = mk([['rect',{x:2,y:7,width:20,height:14,rx:2}],['path',{d:'M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16'}]]);
const IconCrown   = mk([['path',{d:'m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7z'}],['path',{d:'M5 20h14'}]]);
const IconPlus    = mk([['path',{d:'M12 5v14'}],['path',{d:'M5 12h14'}]]);
const IconSearch  = mk([['circle',{cx:11,cy:11,r:8}],['path',{d:'m21 21-4.3-4.3'}]]);
const IconEye     = mk([['path',{d:'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z'}],['circle',{cx:12,cy:12,r:3}]]);
const IconEdit    = mk([['path',{d:'M12 20h9'}],['path',{d:'M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z'}]]);
const IconLock    = mk([['rect',{x:3,y:11,width:18,height:11,rx:2}],['path',{d:'M7 11V7a5 5 0 0 1 10 0v4'}]]);
const IconUnlock  = mk([['rect',{x:3,y:11,width:18,height:11,rx:2}],['path',{d:'M7 11V7a5 5 0 0 1 9.9-1'}]]);
const IconShield  = mk([['path',{d:'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'}]]);
const IconPhone   = mk([['path',{d:'M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z'}]]);
const IconCalendar= mk([['rect',{x:3,y:4,width:18,height:18,rx:2}],['path',{d:'M16 2v4'}],['path',{d:'M8 2v4'}],['path',{d:'M3 10h18'}]]);
const IconCheck   = mk([['path',{d:'M20 6 9 17l-5-5'}]]);
const IconUserPlus= mk([['path',{d:'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'}],['circle',{cx:9,cy:7,r:4}],['path',{d:'M19 8v6'}],['path',{d:'M22 11h-6'}]]);
const IconArrowUp = mk([['path',{d:'M12 19V5'}],['path',{d:'m5 12 7-7 7 7'}]]);
const IconArrowDown=mk([['path',{d:'M12 5v14'}],['path',{d:'m19 12-7 7-7-7'}]]);

const authStore = useAuthStore();

const allUsers = ref([]);
const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const loading = ref(false);
const error = ref(null);

const search = ref('');
const statusFilter = ref('');

const sections = [
  { key: 'customer', label: 'Khách hàng', icon: IconUser,      role: 'customer', addLabel: 'Khách Hàng' },
  { key: 'staff',    label: 'Nhân viên',  icon: IconBriefcase, role: 'staff',    addLabel: 'Nhân Viên' },
  { key: 'admin',    label: 'Quản trị',   icon: IconCrown,     role: 'admin',    addLabel: 'Quản Trị Viên' },
];
const activeSection = ref('customer');
const currentSection = computed(() => sections.find(s => s.key === activeSection.value));

const selectedIds = ref([]);

const showDetail = ref(false);
const detailLoading = ref(false);
const detail = ref(null);

const form = ref({ name: '', email: '', password: '', phone: '', role: 'customer', status: 'active' });

const roleLabel = (role) => ({ admin: 'Admin', staff: 'Staff', customer: 'User' }[role] || role);

const formatCurrency = (val) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);

const formatDate = (val) => {
  if (!val) return '—';
  const d = new Date(val);
  return isNaN(d) ? '—' : d.toLocaleDateString('vi-VN');
};

const initials = (name = '') =>
  name.trim().split(/\s+/).slice(-2).map(w => w[0]?.toUpperCase() || '').join('') || '?';

const avatarColor = (name = '') => {
  const palette = [
    'linear-gradient(135deg,#e50914,#9b000e)',
    'linear-gradient(135deg,#7c4dff,#512da8)',
    'linear-gradient(135deg,#00bcd4,#00838f)',
    'linear-gradient(135deg,#009688,#00695c)',
    'linear-gradient(135deg,#ff9800,#e65100)',
    'linear-gradient(135deg,#3f51b5,#1a237e)',
  ];
  let sum = 0;
  for (const ch of name) sum += ch.charCodeAt(0);
  return palette[sum % palette.length];
};

const roleCount = (role) => allUsers.value.filter(u => u.role === role).length;

const filteredUsers = computed(() => {
  const kw = search.value.trim().toLowerCase();
  return allUsers.value.filter(u => {
    if (u.role !== currentSection.value.role) return false;
    if (statusFilter.value && u.status !== statusFilter.value) return false;
    if (kw) {
      const hay = `${u.name} ${u.email} ${u.phone || ''}`.toLowerCase();
      if (!hay.includes(kw)) return false;
    }
    return true;
  });
});

const sectionCount = (status) => filteredUsers.value.filter(u => u.status === status).length;

const isSelf = (u) => authStore.user?.id === u.id;

const isSelected = (id) => selectedIds.value.includes(id);
const toggleOne = (id) => {
  selectedIds.value = isSelected(id) ? selectedIds.value.filter(x => x !== id) : [...selectedIds.value, id];
};
const allChecked = computed(() => filteredUsers.value.length > 0 && filteredUsers.value.every(u => isSelected(u.id)));
const someChecked = computed(() => filteredUsers.value.some(u => isSelected(u.id)) && !allChecked.value);
const toggleAll = () => { selectedIds.value = allChecked.value ? [] : filteredUsers.value.map(u => u.id); };
const clearSelection = () => { selectedIds.value = []; };

const bulkSetLocked = async (locked) => {
  const targets = filteredUsers.value.filter(u =>
    selectedIds.value.includes(u.id) && (u.status === 'locked') !== locked && !isSelf(u)
  );
  if (!targets.length) { clearSelection(); return; }
  try {
    await Promise.all(targets.map(u => api.patch(`/admin/users/${u.id}/status`)));
    clearSelection();
    await fetchUsers();
  } catch (err) {
    error.value = 'Có lỗi khi cập nhật hàng loạt.';
  }
};

const resetForm = () => {
  form.value = { name: '', email: '', password: '', phone: '', role: currentSection.value.role, status: 'active' };
};

const fetchUsers = async () => {
  try {
    const res = await api.get('/admin/users');
    allUsers.value = res.data.data;
  } catch (err) {
    error.value = 'Không tải được danh sách tài khoản.';
  }
};

const switchSection = (key) => {
  if (activeSection.value === key) return;
  activeSection.value = key;
  search.value = '';
  statusFilter.value = '';
  error.value = null;
  clearSelection();
};

const openCreate = () => {
  isEdit.value = false; editingId.value = null; resetForm(); showModal.value = true;
};

const openEdit = (u) => {
  isEdit.value = true; editingId.value = u.id;
  form.value = { name: u.name, email: u.email, password: '', phone: u.phone || '', role: u.role, status: u.status };
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; error.value = null; };

const submitForm = async () => {
  loading.value = true; error.value = null;
  try {
    if (isEdit.value) await api.put(`/admin/users/${editingId.value}`, form.value);
    else await api.post('/admin/users', form.value);
    closeModal();
    await fetchUsers();
  } catch (err) {
    error.value = err.response?.data?.message
      || Object.values(err.response?.data?.errors || {})[0]?.[0]
      || 'Có lỗi xảy ra!';
  } finally {
    loading.value = false;
  }
};

const toggleStatus = async (u) => {
  if (isSelf(u)) { error.value = 'Không thể tự khóa tài khoản của chính bạn.'; return; }
  try { await api.patch(`/admin/users/${u.id}/status`); await fetchUsers(); }
  catch (err) { error.value = 'Không đổi được trạng thái.'; }
};

const promoteToStaff = async (u) => {
  if (!confirm(`Nâng cấp "${u.name}" lên Nhân viên (Staff)?`)) return;
  await changeRole(u, 'staff');
};

const changeRole = async (u, role) => {
  if (isSelf(u)) { error.value = 'Không thể tự đổi vai trò của chính bạn.'; return; }
  if (u.role === role) return;
  if (!confirm(`Đổi vai trò "${u.name}" thành ${roleLabel(role)}?`)) return;
  try { await api.patch(`/admin/users/${u.id}/role`, { role }); await fetchUsers(); }
  catch (err) { error.value = 'Không đổi được vai trò.'; }
};

const openDetail = async (u) => {
  showDetail.value = true; detailLoading.value = true; detail.value = null;
  try { const res = await api.get(`/admin/users/${u.id}`); detail.value = res.data.data; }
  catch (err) { error.value = 'Không tải được chi tiết tài khoản.'; showDetail.value = false; }
  finally { detailLoading.value = false; }
};

const closeDetail = () => { showDetail.value = false; detail.value = null; };

onMounted(fetchUsers);
</script>

<style scoped>
.user-management { color: var(--text-primary); }
.ico { width: 16px; height: 16px; flex-shrink: 0; display: block; }

/* CONTROL BAR */
.controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 16px; flex-wrap: wrap; }
.btn-add {
  display: inline-flex; align-items: center; gap: 7px;
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: #fff; border: none; padding: 12px 22px; border-radius: var(--radius-full);
  font-weight: 700; font-size: 13px; cursor: pointer; white-space: nowrap;
  box-shadow: var(--shadow-neon-pink); transition: var(--transition-smooth);
}
.btn-add:hover { transform: translateY(-1px); filter: brightness(1.06); }

/* SUB-TABS */
.section-tabs { display: inline-flex; gap: 5px; background: var(--bg-secondary); padding: 5px; border-radius: var(--radius-full); border: 1px solid var(--border-glass); }
.section-tab { display: flex; align-items: center; gap: 8px; background: transparent; border: none; color: var(--text-secondary); font-weight: 700; font-size: 13px; padding: 10px 20px; cursor: pointer; border-radius: var(--radius-full); transition: var(--transition-smooth); }
.section-tab:hover { color: var(--accent-pink); }
.section-tab.active { background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); color: #fff; box-shadow: 0 6px 16px rgba(229,9,20,.3); }
.st-count { background: var(--bg-tertiary); color: var(--text-secondary); font-size: 11px; font-weight: 800; min-width: 20px; height: 20px; padding: 0 6px; border-radius: var(--radius-full); display: inline-flex; align-items: center; justify-content: center; }
.section-tab.active .st-count { background: rgba(255,255,255,.25); color: #fff; }

/* TOOLBAR */
.toolbar { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }
.search-wrap { position: relative; flex: 1; min-width: 240px; }
.search-ic { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); display: flex; }
.search-input { width: 100%; padding: 11px 14px 11px 42px; border-radius: var(--radius-full); border: 1px solid var(--border-glass); background: #fff; color: var(--text-primary); font-size: 14px; transition: var(--transition-smooth); }
.search-input:focus { outline: none; border-color: var(--accent-pink); box-shadow: 0 0 0 3px var(--accent-pink-glow); }
.seg { display: inline-flex; background: var(--bg-secondary); border: 1px solid var(--border-glass); border-radius: var(--radius-full); padding: 4px; }
.seg button { border: none; background: transparent; color: var(--text-secondary); font-weight: 700; font-size: 12px; padding: 7px 14px; border-radius: var(--radius-full); cursor: pointer; transition: var(--transition-smooth); }
.seg button.on { background: #fff; color: var(--accent-pink); box-shadow: 0 1px 4px rgba(0,0,0,.08); }

/* PANEL + TABLE */
.panel { background: #fff; border: 1px solid var(--border-glass); border-radius: var(--radius-md); box-shadow: 0 1px 3px rgba(0,0,0,.03), 0 10px 30px rgba(0,0,0,.04); overflow: hidden; }
.bulk-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 18px; background: rgba(229,9,20,.05); border-bottom: 1px solid var(--border-glass); }
.bulk-count { font-weight: 800; font-size: 13px; color: var(--accent-pink); }
.bulk-actions { display: flex; gap: 8px; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table thead th { background: var(--bg-secondary); color: var(--text-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 13px 18px; text-align: left; }
.data-table tbody td { padding: 15px 18px; border-top: 1px solid var(--border-glass); font-size: 14px; vertical-align: middle; }
.data-table tbody tr { transition: var(--transition-smooth); box-shadow: inset 3px 0 0 transparent; }
.data-table tbody tr:hover { background: linear-gradient(90deg, rgba(229,9,20,.05), transparent 60%); box-shadow: inset 3px 0 0 var(--accent-pink); }
.data-table tbody tr.row-sel { background: rgba(229,9,20,.05); box-shadow: inset 3px 0 0 var(--accent-pink); }
.cb-col { width: 44px; }
.ta-right { text-align: right; }
.ta-center { text-align: center; }
.td-muted { color: var(--text-secondary); }
input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--accent-pink); cursor: pointer; }

.id-cell { display: flex; align-items: center; gap: 12px; }
.id-meta { display: flex; flex-direction: column; line-height: 1.35; }
.id-meta strong { color: var(--text-primary); font-weight: 700; }
.id-meta small { color: var(--text-muted); font-size: 12px; }
.table-foot { padding: 13px 18px; border-top: 1px solid var(--border-glass); color: var(--text-muted); font-size: 12px; font-weight: 600; }

/* EMPTY STATE */
.empty-state { text-align: center; padding: 44px 20px; }
.empty-state.wide { grid-column: 1 / -1; }
.empty-ic { display: inline-flex; color: var(--text-muted); margin-bottom: 10px; }
.empty-ic .ico { width: 40px; height: 40px; }
.empty-state p { color: var(--text-muted); font-size: 14px; }

/* AVATAR */
.avatar { width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 13px; flex-shrink: 0; box-shadow: 0 0 0 3px rgba(255,255,255,.9), 0 3px 8px rgba(0,0,0,.18); }
.avatar.xl { width: 66px; height: 66px; font-size: 20px; }

/* BADGES */
.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: var(--radius-full); font-size: 12px; font-weight: 700; }
.badge .ico { width: 13px; height: 13px; }
.badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.role-admin { background: rgba(229,9,20,.1); color: var(--accent-pink); }
.role-staff { background: rgba(0,131,143,.1); color: #00838f; }
.role-customer { background: rgba(100,116,139,.12); color: var(--text-secondary); }
.status-active { background: rgba(0,143,93,.12); color: var(--accent-mint); }
.status-locked { background: rgba(229,9,20,.1); color: var(--accent-pink); }
.badge-self { background: rgba(124,77,255,.12); color: #7c4dff; }

/* ACTIONS */
.actions { white-space: nowrap; }
.actions > * { margin-left: 6px; }
.btn-icon { display: inline-flex; align-items: center; justify-content: center; background: var(--bg-secondary); border: 1px solid var(--border-glass); width: 34px; height: 34px; border-radius: 9px; cursor: pointer; color: var(--text-secondary); transition: var(--transition-smooth); vertical-align: middle; }
.btn-icon:hover { border-color: var(--accent-pink); color: var(--accent-pink); transform: translateY(-1px); }
.btn-soft { display: inline-flex; align-items: center; gap: 5px; background: var(--bg-secondary); border: 1px solid var(--border-glass); color: var(--text-secondary); padding: 7px 12px; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer; transition: var(--transition-smooth); vertical-align: middle; }
.btn-soft:hover { transform: translateY(-1px); border-color: var(--accent-pink); color: var(--accent-pink); }
.btn-soft .ico { width: 14px; height: 14px; }
.btn-soft.mint { color: var(--accent-mint); }
.btn-soft.mint:hover { border-color: var(--accent-mint); }
.btn-soft.red { color: var(--accent-pink); }
.btn-ghost { background: transparent; border: none; color: var(--text-secondary); font-weight: 700; font-size: 12px; cursor: pointer; }
.btn-ghost:hover { color: var(--accent-pink); }
button:disabled { opacity: .4; cursor: not-allowed; transform: none !important; }

/* STAT CARDS */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 22px; }
.stat-card { position: relative; display: flex; align-items: center; gap: 16px; background: #fff; border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 20px 22px; box-shadow: 0 1px 3px rgba(0,0,0,.03), 0 10px 28px rgba(0,0,0,.04); overflow: hidden; }
.stat-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(var(--accent-pink), var(--accent-violet)); opacity: .9; }
.stat-ic { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 6px 16px rgba(0,0,0,.12); }
.stat-ic .ico { width: 24px; height: 24px; }
.ic-violet { background: linear-gradient(135deg, #7c4dff, #512da8); }
.ic-mint { background: linear-gradient(135deg, #00b377, #007a4f); }
.ic-red { background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); }
.sp-value { display: block; font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1.05; }
.sp-label { font-size: 12px; color: var(--text-muted); font-weight: 600; }

/* SECURITY NOTE */
.security-note { display: flex; align-items: center; gap: 10px; background: rgba(255,152,0,.08); color: #b26a00; border: 1px solid rgba(255,152,0,.25); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 13px; font-weight: 600; }
.sn-ic { display: flex; }

/* ADMIN CARDS */
.admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 18px; }
.admin-card { background: #fff; border: 1px solid var(--border-glass); border-radius: var(--radius-md); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.03), 0 10px 30px rgba(0,0,0,.04); transition: var(--transition-smooth); }
.admin-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-neon-pink); }
.ac-banner { position: relative; height: 60px; background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); overflow: hidden; }
.ac-banner::before { content: ''; position: absolute; top: 9px; left: 0; right: 0; height: 8px; background-image: radial-gradient(circle, rgba(255,255,255,.55) 2px, transparent 2.4px); background-size: 15px 8px; }
.ac-banner::after { content: ''; position: absolute; bottom: 9px; left: 0; right: 0; height: 8px; background-image: radial-gradient(circle, rgba(255,255,255,.35) 2px, transparent 2.4px); background-size: 15px 8px; }
.ac-body { padding: 0 18px 18px; text-align: center; margin-top: -32px; }
.ac-body .avatar { border: 3px solid #fff; }
.ac-name { color: var(--text-primary); margin: 10px 0 2px; font-size: 16px; font-weight: 800; }
.ac-email { color: var(--text-muted); font-size: 12px; margin: 0 0 12px; word-break: break-all; }
.ac-badges { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-bottom: 14px; }
.ac-info { list-style: none; padding: 0; margin: 0 0 14px; text-align: left; }
.ac-info li { display: flex; justify-content: space-between; align-items: center; font-size: 13px; padding: 7px 0; border-bottom: 1px dashed var(--border-glass); }
.ac-info li span { color: var(--text-muted); display: inline-flex; align-items: center; gap: 6px; }
.ac-info li span .ico { width: 14px; height: 14px; }
.ac-info li strong { color: var(--text-primary); }
.ac-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.ac-actions .btn-soft { flex: 1; justify-content: center; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(30,41,59,.45); backdrop-filter: blur(3px); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-box { background: #fff; color: var(--text-primary); padding: 26px; border-radius: var(--radius-lg); width: 440px; max-width: 92%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
.modal-box h3 { font-family: var(--font-display); color: var(--text-primary); margin-bottom: 18px; font-size: 19px; font-weight: 800; }
.detail-box { width: 560px; }
.modal-form { display: flex; flex-direction: column; gap: 5px; }
.modal-form label { font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-top: 6px; }
.modal-form input, .modal-form select { padding: 11px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); background: #fff; color: var(--text-primary); font-size: 14px; }
.modal-form input:focus, .modal-form select:focus { outline: none; border-color: var(--accent-pink); box-shadow: 0 0 0 3px var(--accent-pink-glow); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-row > div { display: flex; flex-direction: column; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.btn-cancel { background: var(--bg-tertiary); color: var(--text-secondary); border: none; padding: 10px 18px; border-radius: var(--radius-full); cursor: pointer; font-weight: 700; }
.btn-save { background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); color: #fff; border: none; padding: 10px 22px; border-radius: var(--radius-full); cursor: pointer; font-weight: 700; box-shadow: var(--shadow-neon-pink); }
.alert-error { background: rgba(229,9,20,.08); color: var(--accent-pink); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; font-weight: 600; border: 1px solid rgba(229,9,20,.2); }

/* DETAIL */
.detail-hero { display: flex; gap: 16px; align-items: center; padding-bottom: 18px; margin-bottom: 18px; border-bottom: 1px solid var(--border-glass); }
.dh-name { font-size: 18px; font-weight: 800; color: var(--text-primary); margin-bottom: 2px; }
.dh-email { color: var(--text-muted); font-size: 13px; margin-bottom: 8px; }
.detail-section { margin-bottom: 20px; }
.detail-section h4 { margin-bottom: 12px; color: var(--text-primary); font-size: 14px; font-weight: 800; }
.stats-grid { display: flex; gap: 12px; }
.mini-stat { flex: 1; background: var(--bg-secondary); border-radius: var(--radius-md); padding: 16px; text-align: center; border: 1px solid var(--border-glass); }
.stat-value { display: block; font-size: 18px; font-weight: 800; color: var(--accent-pink); }
.stat-label { font-size: 12px; color: var(--text-muted); }
.review-list { list-style: none; padding: 0; margin: 0; }
.review-list li { background: var(--bg-secondary); border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 8px; }
.review-head { display: flex; justify-content: space-between; }
.review-head strong { color: var(--text-primary); }
.review-rating { color: #f59e0b; }
.review-comment { margin: 6px 0 0; color: var(--text-secondary); font-size: 14px; }
.muted { color: var(--text-muted); }
.loading-text { color: var(--text-muted); padding: 24px 0; text-align: center; }
</style>
