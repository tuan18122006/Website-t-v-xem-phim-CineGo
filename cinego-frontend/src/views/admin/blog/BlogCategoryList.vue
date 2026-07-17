<template>
  <div class="blog-category-container">
    <div class="category-grid">
      <div class="form-column">
        <div class="glass-panel form-card">
          <div class="card-header-cine">
            <span class="header-icon">{{ isEditing ? "📝" : "✨" }}</span>
            <div>
              <h3 class="panel-title">
                {{ isEditing ? "Cập Nhật Thể Loại" : "Thêm Thể Loại Mới" }}
              </h3>
              <p class="panel-desc">
                {{
                  isEditing
                    ? "Chỉnh sửa thông tin thể loại hiện tại"
                    : "Tạo một phân loại bài viết mới"
                }}
              </p>
            </div>
          </div>

          <form @submit.prevent="handleSubmit" class="cine-form">
            <div class="form-group">
              <label class="form-label"
                >Tên thể loại <span class="required">*</span></label
              >
              <input
                v-model="form.name"
                type="text"
                class="form-input"
                placeholder="Ví dụ: Review Phim, Tin Điện Ảnh..."
                required
              />
            </div>

            <div class="form-actions">
              <button
                v-if="isEditing"
                type="button"
                @click="resetForm"
                class="btn-secondary-cine"
              >
                Hủy bỏ
              </button>
              <button
                type="submit"
                class="btn-primary-cine"
                :disabled="submitting"
              >
                <span v-if="submitting" class="mini-spinner"></span>
                <span>{{ isEditing ? "Lưu thay đổi" : "Tạo thể loại" }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="table-column">
        <div class="glass-panel table-card">
          <div class="table-toolbar">
            <div class="search-box">
              <span class="search-icon">🔍</span>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Tìm kiếm thể loại..."
                class="search-input"
              />
            </div>
            <div class="badge-total">
              Tổng số: <strong>{{ filteredCategories.length }}</strong> thể loại
            </div>
          </div>

          <div v-if="loading" class="loading-overlay">
            <div class="spinner-cine"></div>
            <p>Đang tải danh sách thể loại...</p>
          </div>

          <div v-else class="table-responsive">
            <table class="cine-table">
              <thead>
                <tr>
                  <th style="width: 100px">ID</th>
                  <th>Tên Thể Loại</th>
                  <th style="width: 180px" class="text-center">Số bài viết</th>
                  <th style="width: 180px" class="text-right">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="cat in filteredCategories"
                  :key="cat.id"
                  :class="{ 'editing-row': editingId === cat.id }"
                >
                  <td class="cat-id">#{{ cat.id }}</td>
                  <td class="font-bold text-highlight">{{ cat.name }}</td>
                  <td class="text-center">
                    <span
                      class="count-badge"
                      :class="{ 'has-posts': cat.blog_posts_count > 0 }"
                    >
                      {{ cat.blog_posts_count || 0 }} bài
                    </span>
                  </td>
                  <td class="text-right actions-cell">
                    <button
                      @click="editCategory(cat)"
                      class="action-btn btn-edit-icon"
                      title="Sửa"
                    >
                      ✏️ Sửa
                    </button>
                    <button
                      @click="deleteCategory(cat)"
                      class="action-btn btn-delete-icon"
                      title="Xóa"
                    >
                      🗑️ Xóa
                    </button>
                  </td>
                </tr>
                <tr v-if="filteredCategories.length === 0">
                  <td colspan="4" class="empty-state">
                    <span class="empty-icon">📂</span>
                    <p>Không tìm thấy thể loại blog nào phù hợp.</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../../api/axios";
import { toast, confirmDialog } from "../../../utils/alert";

const categories = ref([]);
const loading = ref(true);
const submitting = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const searchQuery = ref("");

const form = ref({
  name: "",
});

// Tìm kiếm danh sách thời gian thực theo tên
const filteredCategories = computed(() => {
  if (!searchQuery.value) return categories.value;
  const q = searchQuery.value.toLowerCase();
  return categories.value.filter((cat) => cat.name.toLowerCase().includes(q));
});

// Gọi API lấy danh sách Thể Loại
const fetchCategories = async () => {
  loading.value = true;

  try {
    const response = await api.get("/admin/blog-categories");

    console.log(response.data);

    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error("Lỗi tải danh sách thể loại:", error);
  } finally {
    loading.value = false;
  }
};

// Gửi Form (Lưu hoặc Thêm mới)
const handleSubmit = async () => {
  if (!form.value.name.trim()) return;

  submitting.value = true;

  const payload = {
    name: form.value.name.trim(),
  };

  try {
    if (isEditing.value) {
      await api.put(`/admin/blog-categories/${editingId.value}`, payload);
      toast("Cập nhật thể loại thành công!");
    } else {
      await api.post("/admin/blog-categories", payload);
      toast("Thêm thể loại mới thành công!");
    }

    resetForm();
    await fetchCategories();
  } catch (error) {
    console.error(error);

    if (error.response?.status === 422) {
      toast(
        error.response.data.errors?.name?.[0] || "Dữ liệu không hợp lệ!",
        "error",
      );
    } else {
      toast(
        error.response?.data?.message || "Đã xảy ra lỗi hệ thống!",
        "error",
      );
    }
  } finally {
    submitting.value = false;
  }
};

// Kích hoạt trạng thái sửa đổi thông tin
const editCategory = (category) => {
  isEditing.value = true;
  editingId.value = category.id;
  form.value.name = category.name;
};

// Xóa trắng Form quay lại chế độ Thêm Mới
const resetForm = () => {
  isEditing.value = false;
  editingId.value = null;
  form.value.name = "";
};

// Gửi yêu cầu xóa Thể Loại
const deleteCategory = async (category) => {
  const confirmed = await confirmDialog(
    "Bạn có chắc chắn muốn xóa thể loại này?",
    `Thể loại "${category.name}" sẽ bị xóa và có thể ảnh hưởng đến các bài viết liên quan.`,
  );

  if (!confirmed) return;

  try {
    await api.delete(`/admin/blog-categories/${category.id}`);

    toast("Đã xóa thể loại thành công!");

    await fetchCategories();
  } catch (error) {
    console.error(error);

    toast(
      error.response?.data?.message || "Không thể xóa thể loại này!",
      "error",
    );
  }
};

onMounted(() => {
  fetchCategories();
});
</script>

<style scoped>
.blog-category-container {
  padding: 1.5rem;
  color: #334155;
}
.category-grid {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 1.5rem;
  align-items: start;
}
.glass-panel {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
}
.form-card {
  padding: 1.75rem;
}
.table-card {
  padding: 1.5rem;
}
.card-header-cine {
  display: flex;
  gap: 12px;
  margin-bottom: 1.5rem;
  align-items: center;
}
.header-icon {
  font-size: 1.75rem;
  padding: 8px;
  background: linear-gradient(
    135deg,
    rgba(236, 72, 153, 0.1),
    rgba(139, 92, 246, 0.1)
  );
  border-radius: 12px;
}
.panel-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}
.panel-desc {
  font-size: 0.8rem;
  color: #64748b;
  margin: 2px 0 0 0;
}
.cine-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}
.required {
  color: #ef4444;
}
.form-input {
  padding: 10px 14px;
  border-radius: 10px;
  border: 1.5px solid #cbd5e1;
  font-size: 0.9rem;
  outline: none;
  background: #f8fafc;
}
.form-input:focus {
  border-color: #ec4899;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.15);
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.btn-primary-cine {
  background: linear-gradient(135deg, #ec4899, #8b5cf6);
  color: white;
  border: none;
  padding: 11px 20px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}
.btn-secondary-cine {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 11px 20px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}
.search-box {
  position: relative;
  width: 280px;
}
.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
}
.search-input {
  width: 100%;
  padding: 9px 12px 9px 36px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  outline: none;
  background: #f8fafc;
}
.search-input:focus {
  border-color: #8b5cf6;
  background: #fff;
}
.badge-total {
  font-size: 0.85rem;
  color: #64748b;
}
.table-responsive {
  width: 100%;
  overflow-x: auto;
}
.cine-table {
  width: 100%;
  border-collapse: collapse;
}
.cine-table th {
  padding: 12px 16px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #475569;
  border-bottom: 2px solid #f1f5f9;
}
.cine-table td {
  padding: 14px 16px;
  font-size: 0.9rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}
.cine-table tr:hover td {
  background-color: rgba(248, 250, 252, 0.8);
}
.editing-row td {
  background-color: rgba(236, 72, 153, 0.05) !important;
}
.cat-id {
  font-family: monospace;
  color: #94a3b8;
}
.font-bold {
  font-weight: 600;
}
.text-highlight {
  color: #0f172a;
}
.count-badge {
  background: #f1f5f9;
  color: #64748b;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.775rem;
  font-weight: 600;
}
.count-badge.has-posts {
  background: #ecfdf5;
  color: #059669;
}
.actions-cell {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}
.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
}
.btn-edit-icon {
  color: #3b82f6;
}
.btn-edit-icon:hover {
  background: rgba(59, 130, 246, 0.08);
}
.btn-delete-icon {
  color: #ef4444;
}
.btn-delete-icon:hover {
  background: rgba(239, 68, 68, 0.08);
}
.text-center {
  text-align: center;
}
.text-right {
  text-align: right;
}
.empty-state {
  text-align: center;
  padding: 3.5rem !important;
  color: #94a3b8;
}
.empty-icon {
  font-size: 2.5rem;
  display: block;
  margin-bottom: 8px;
}
.loading-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 0;
}
.spinner-cine {
  width: 40px;
  height: 40px;
  border: 3.5px solid rgba(236, 72, 153, 0.1);
  border-top-color: #ec4899;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
@media (max-width: 992px) {
  .category-grid {
    grid-template-columns: 1fr;
  }
}
</style>
