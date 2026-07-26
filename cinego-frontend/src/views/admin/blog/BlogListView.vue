<template>
  <div class="blog-list-container">
    <div class="list-page-header">
      <div class="header-title-zone">
        <h2 class="page-title">
          <span class="title-icon">📰</span>
          Quản Lý Bài Viết Blog
        </h2>
        <p class="page-subtitle">
          Xem, tìm kiếm, lọc danh sách bài viết và theo dõi hiệu quả chiến dịch
          Marketing.
        </p>
      </div>
      <router-link to="/admin/blogs/create" class="btn-primary-cine-link">
        ✨ Viết Bài Mới
      </router-link>
    </div>

    <div class="glass-panel filter-card">
      <div class="filter-grid">
        <div class="input-group">
          <label class="form-label-small">Tìm kiếm bài viết</label>
          <input
            v-model="filter.search"
            type="text"
            class="form-input-filter"
            placeholder="Nhập tiêu đề hoặc từ khóa..."
            @input="handleFilter"
          />
        </div>

        <div class="input-group">
          <label class="form-label-small">Chuyên mục</label>
          <select
            v-model="filter.category_id"
            class="form-select-filter"
            @change="handleFilter"
          >
            <option value="">-- Tất cả chuyên mục --</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <div class="input-group">
          <label class="form-label-small">Trạng thái</label>
          <select
            v-model="filter.status"
            class="form-select-filter"
            @change="handleFilter"
          >
            <option value="">-- Tất cả trạng thái --</option>
            <option value="published">🟢 Đã xuất bản</option>
            <option value="draft">🟡 Bản nháp</option>
            <option value="scheduled">🔵 Hẹn giờ đăng</option>
          </select>
        </div>
      </div>
    </div>

    <div class="glass-panel list-table-card">
      <div v-if="loading" class="loading-state">
        <div class="spinner-cine"></div>
        <p>Đang tải danh sách bài viết...</p>
      </div>

      <div v-else class="table-responsive">
        <table class="cine-blog-table">
          <thead>
            <tr>
              <th style="width: 70px">ID</th>
              <th style="width: 120px">Hình ảnh</th>
              <th>Tiêu đề bài viết</th>
              <th style="width: 160px">Chuyên mục</th>
              <th style="width: 170px">Trạng thái</th>
              <th style="width: 170px">Thao tác</th>
            </tr>
          </thead>
          <tr v-for="post in blogPosts" :key="post.id">
            <td class="cell-id">
              {{ post.id }}
            </td>

            <td class="cell-thumb">
              <div class="thumb-wrapper">
                <img
                  :src="
                    post.thumbnail_url ||
                    'https://placehold.co/160x90?text=No+Image'
                  "
                  class="table-thumb-img"
                />
              </div>
            </td>

            <td class="cell-title">
              <div class="post-title-main">
                {{ post.title }}
              </div>

              <div class="post-permalink">
                Slug:
                <span>{{ post.slug }}</span>
              </div>

              <div class="post-date">📅 {{ formatDate(post.updated_at) }}</div>
            </td>

            <td class="cell-category">
              <span class="category-badge">
                {{ post.category?.name || "Chưa phân loại" }}
              </span>
            </td>

            <td class="cell-status">
              <span :class="['status-dot-badge', post.status]">
                {{ getStatusText(post.status, post.published_at) }}
              </span>
            </td>

            <td class="text-right actions-cell">
              <button
                @click="viewPost(post.id)"
                class="action-btn btn-view-icon"
                title="Chi tiết"
              >
                👁️ Chi tiết
              </button>

              <button
                @click="editPost(post.id)"
                class="action-btn btn-edit-icon"
                title="Sửa"
              >
                ✏️ Sửa
              </button>

              <button
                @click="deletePost(post.id)"
                class="action-btn btn-delete-icon"
                title="Xóa"
              >
                🗑️ Xóa
              </button>
            </td>
            
          </tr>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "../../../api/axios";
import { toast, confirmDialog } from "../../../utils/alert"; // Import bộ Alert tùy biến của bạn

const router = useRouter();
const loading = ref(false);
const blogPosts = ref([]);
const categories = ref([]);

// State quản lý bộ lọc dữ liệu
const filter = ref({
  search: "",
  category_id: "",
  status: "",
});

// Định dạng ngày hiển thị tiếng Việt
const formatDate = (dateString) => {
  if (!dateString) return "Chưa cập nhật";
  const date = new Date(dateString);
  return date.toLocaleString("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

// Lấy nhãn hiển thị trực quan cho trạng thái
const getStatusText = (status, publishedAt) => {
  if (status === "draft") return "📝 Bản nháp";
  if (status === "scheduled")
    return `⏳ Hẹn giờ (${formatDate(publishedAt).split(" ")[0]})`;
  return "🟢 Đã đăng";
};

// Gọi danh sách Chuyên mục ban đầu phục vụ thẻ select lọc dữ liệu
const fetchCategories = async () => {
  try {
    const response = await api.get("/admin/blog-categories");
    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error("Lỗi tải chuyên mục:", error);
  }
};

// Gọi danh sách bài viết từ API (Có truyền kèm query bộ lọc)
const fetchBlogPosts = async () => {
  loading.value = true;
  try {
    const response = await api.get("/admin/blogs", {
      params: {
        search: filter.value.search,
        category_id: filter.value.category_id,
        status: filter.value.status,
      },
    });
    blogPosts.value = response.data.data || response.data;
  } catch (error) {
    console.error("Lỗi tải danh sách bài viết:", error);
    toast("Không thể tải danh sách bài viết!", "error");
  } finally {
    loading.value = false;
  }
};

// Kích hoạt tìm kiếm/lọc dữ liệu
let filterTimeout = null;
const handleFilter = () => {
  // Tránh việc gọi API liên tục khi người dùng đang gõ phím liên tiếp (Debounce)
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    fetchBlogPosts();
  }, 300);
};

//Di chuyển sang trang xem chi tiết bài viết
const viewPost = (id) => {
  router.push(`/admin/blogs/preview/${id}`);
};

// Điều hướng sang trang sửa
const editPost = (id) => {
  router.push(`/admin/blogs/edit/${id}`);
};

// Xử lý Xóa bài viết với cơ chế khôi phục nhanh (Optimistic UI)
const deletePost = async (id) => {
  if (
    await confirmDialog(
      "Bạn có chắc chắn muốn xóa bài viết này không?",
      "Bài viết này sẽ biến mất khỏi trang chủ rạp phim.",
    )
  ) {
    const originalList = [...blogPosts.value];
    blogPosts.value = blogPosts.value.filter((p) => p.id !== id);

    try {
      await api.delete(`/admin/blogs/${id}`);
      toast("Đã xóa bài viết thành công!");
    } catch (error) {
      blogPosts.value = originalList; // Khôi phục danh sách cũ nếu lỗi mạng/server
      console.error("Lỗi xóa bài viết:", error);
      toast(
        error.response?.data?.message || "Lỗi hệ thống, không thể xóa!",
        "error",
      );
    }
  }
};

onMounted(() => {
  fetchCategories();
  fetchBlogPosts();
});
</script>

<style scoped>
.blog-list-container {
  padding: 25px;
  background-color: #f8fafc;
  min-height: 100vh;
  color: #1e293b;
  font-family: "Inter", sans-serif;
}

/* Header phân vùng quản trị */
.list-page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  background: white;
  padding: 20px 24px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.page-title {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-subtitle {
  margin: 4px 0 0 0;
  color: #64748b;
  font-size: 14px;
}

.btn-primary-cine-link {
  background: linear-gradient(135deg, #e50914 0%, #9b000e 100%);
  color: #ffffff;
  text-decoration: none;
  padding: 12px 22px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.2);
  transition: all 0.2s ease;
}

.btn-primary-cine-link:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(229, 9, 20, 0.3);
}

/* Các khối panel wrapper */
.glass-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  margin-bottom: 24px;
}

/* Khối bộ lọc thông minh */
.filter-card {
  padding: 18px 24px;
}

.filter-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

@media (min-width: 768px) {
  .filter-grid {
    grid-template-columns: 2fr 1fr 1fr;
  }
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label-small {
  font-size: 12.5px;
  font-weight: 700;
  color: #475569;
}

.form-input-filter,
.form-select-filter {
  width: 100%;
  border: 1px solid #cbd5e1;
  padding: 10px 14px;
  border-radius: 6px;
  font-size: 14px;
  outline: none;
  background-color: #ffffff;
  box-sizing: border-box;
  color: #1e293b;
}

.form-input-filter:focus,
.form-select-filter:focus {
  border-color: #e50914;
}

/* Thiết kế kiến trúc bảng danh sách hiển thị */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.cine-blog-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.cine-blog-table th {
  padding: 14px 16px;
  background-color: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
  color: #475569;
  font-size: 13.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.cine-blog-table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.table-row:hover {
  background-color: #fffafb; /* Hover có vệt hồng đỏ nhẹ thương hiệu rạp */
}

/* Tùy chỉnh chi tiết từng cột */
.col-thumb {
  width: 140px;
}
.col-category {
  width: 150px;
}
.col-status {
  width: 160px;
}
.col-marketing {
  width: 220px;
}
.col-actions {
  width: 150px;
  text-align: center;
}

/* Wrapper xử lý ảnh tỷ lệ 16:9 trong bảng */
.thumb-wrapper {
  width: 110px;
  aspect-ratio: 16 / 9;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #cbd5e1;
  background: #f1f5f9;
}

.table-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Khu vực cột thông tin chính của bài viết */
.post-title-main {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 4px;
  line-height: 1.4;
}

.post-permalink {
  font-size: 12px;
  color: #64748b;
  font-family: monospace;
}

.post-permalink span {
  color: #334155;
  font-weight: 600;
}

.post-date {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

/* Badge Chuyên mục */
.category-badge {
  background-color: #f1f5f9;
  color: #475569;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid #e2e8f0;
  display: inline-block;
}

/* Badge Trạng thái thông minh */
.status-dot-badge {
  font-size: 13px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  display: inline-block;
}

.status-dot-badge.published {
  background-color: #dcfce7;
  color: #15803d;
}

.status-dot-badge.draft {
  background-color: #fef3c7;
  color: #b45309;
}

.status-dot-badge.scheduled {
  background-color: #e0f2fe;
  color: #0369a1;
}

/* Khu vực hiển thị Chốt đơn Marketing (Gắn kết phim) */
.attached-movie-zone {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff1f2;
  border: 1px solid #ffe4e6;
  padding: 6px 10px;
  border-radius: 8px;
  max-width: 200px;
}

.movie-icon {
  font-size: 18px;
}

.movie-title {
  font-size: 12.5px;
  font-weight: 700;
  color: #9f1239;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 140px;
}

.conversion-tag {
  font-size: 10px;
  background: #f43f5e;
  color: white;
  padding: 1px 4px;
  border-radius: 4px;
  font-weight: 700;
  text-transform: uppercase;
}

.text-muted-small {
  font-size: 12.5px;
  color: #94a3b8;
  font-style: italic;
}

.actions-cell {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  align-items: center;
}

.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
  transition: 0.2s;
}

.btn-view-icon {
  color: #10b981;
}

.btn-view-icon:hover {
  background: rgba(16, 185, 129, 0.08);
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

/* State trống & Loading Spinner */
.empty-state {
  text-align: center;
  padding: 40px;
  color: #94a3b8;
  font-size: 14.5px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
  gap: 12px;
}

.spinner-cine {
  width: 36px;
  height: 36px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #e50914;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.loading-state p {
  font-size: 14px;
  color: #64748b;
  font-weight: 600;
}

.cell-id {
  text-align: center;
  font-weight: 700;
  color: #334155;
  width: 70px;
}

.cell-thumb {
  width: 120px;
}

.cell-category {
  width: 160px;
  text-align: center;
}

.cell-status {
  width: 170px;
  text-align: center;
}

.cell-actions {
  width: 170px;
  text-align: center;
}

.cine-blog-table {
  width: 100%;
  table-layout: fixed;
}

.cine-blog-table th,
.cine-blog-table td {
  vertical-align: middle;
}
</style>
