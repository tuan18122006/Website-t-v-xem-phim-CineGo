<template>
  <div class="blog-editor-container">
    <div class="editor-header">
      <div class="header-title-zone">
        <h2 class="page-title">
          <span class="title-icon">{{ isEditing ? "📝" : "✍️" }}</span>
          {{ isEditing ? "Chỉnh sửa bài viết" : "Viết bài mới" }}
        </h2>
        <p class="page-subtitle">
          Biên tập nội dung tin tức, review phim và quản lý chiến dịch
          marketing.
        </p>
      </div>
      <div class="header-actions">
        <button
          type="button"
          @click="savePost('draft')"
          class="btn-secondary-cine"
          :disabled="submitting"
        >
          Lưu bản nháp
        </button>
        <button
          type="button"
          @click="savePost('published')"
          class="btn-primary-cine"
          :disabled="submitting"
        >
          {{ isEditing ? "Cập nhật bài viết" : "Xuất bản" }}
        </button>
      </div>
    </div>

    <div class="wordpress-layout">
      <div class="main-content-column">
        <div class="glass-panel content-box">
          <div class="input-group">
            <label class="form-label">Tiêu đề bài viết *</label>
            <input
              v-model="form.title"
              type="text"
              class="form-input-title"
              placeholder="Nhập tiêu đề tại đây..."
            />
            <span v-if="errors?.title" class="error-msg">{{
              errors.title[0]
            }}</span>
          </div>

          <div class="slug-permalink-zone">
            <span class="permalink-label">🔗 Liên kết tĩnh (Slug):</span>
            <span class="permalink-url"
              >cinego.com/blog/<strong class="slug-text">{{
                generateSlug(form.title)
              }}</strong></span
            >
          </div>
        </div>

        <div class="glass-panel content-box">
          <div class="input-group">
            <label class="form-label"
              >Đoạn tóm tắt ngắn (Excerpt)
              <span class="label-hint"
                >(Dưới 150 chữ hiển thị ngoài trang chủ)</span
              ></label
            >
            <textarea
              v-model="form.excerpt"
              class="form-textarea"
              rows="3"
              placeholder="Nhập một đoạn mô tả ngắn cuốn hút để mồi độc giả click..."
            ></textarea>
          </div>
        </div>

        <div class="glass-panel content-box">
          <div class="input-group">
            <label class="form-label">Nội dung bài viết *</label>
            <div class="rich-editor-wrapper">
              <div class="editor-toolbar">
                <button type="button" class="toolbar-btn"><b>B</b></button>
                <button type="button" class="toolbar-btn"><i>I</i></button>
                <button type="button" class="toolbar-btn"><u>U</u></button>
                <button type="button" class="toolbar-btn">🔗 Thêm Link</button>
                <button type="button" class="toolbar-btn">
                  🎬 Nhúng Video Youtube
                </button>
                <button type="button" class="toolbar-btn">🖼️ Thêm Ảnh</button>
              </div>
              <textarea
                v-model="form.content"
                class="editor-textarea"
                rows="16"
                placeholder="Bắt đầu viết nội dung bài viết của bạn tại đây..."
              ></textarea>
            </div>
            <span v-if="errors?.content" class="error-msg">{{
              errors.content[0]
            }}</span>
          </div>
        </div>

        <div class="glass-panel content-box marketing-card">
          <h4 class="box-sub-title">
            🎯 Gắn kết phim bán vé nhanh (Call to Action)
          </h4>
          <p class="box-desc">
            Hệ thống tự động hiển thị Poster và nút "ĐẶT VÉ XEM PHIM NÀY NGAY" ở
            cuối bài viết ngoài web.
          </p>
          <div class="input-group">
            <select v-model="form.movie_id" class="form-select">
              <option :value="null">
                -- Không đính kèm phim (Bài viết tin tức chung) --
              </option>
              <option
                v-for="movie in activeMovies"
                :key="movie.id"
                :value="movie.id"
              >
                🎬 Phim: {{ movie.title }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="sidebar-column">
        <div class="glass-panel sidebar-box">
          <h4 class="sidebar-box-title">⚙️ Trạng thái & Lên lịch</h4>
          <div class="sidebar-box-content">
            <div class="radio-group-vertical">
              <label class="radio-label">
                <input type="radio" v-model="form.status" value="published" />
                Đăng công khai ngay
              </label>
              <label class="radio-label">
                <input type="radio" v-model="form.status" value="scheduled" />
                Hẹn giờ xuất bản
              </label>
            </div>

            <div
              v-if="form.status === 'scheduled'"
              class="scheduling-input-zone"
            >
              <label class="form-label-small"
                >Thời gian tự động phát sóng:</label
              >
              <input
                type="datetime-local"
                v-model="form.published_at"
                class="form-input-small"
              />
            </div>
          </div>
        </div>

        <div class="glass-panel sidebar-box">
          <h4 class="sidebar-box-title">📁 Chuyên mục</h4>
          <div class="sidebar-box-content">
            <div class="input-group">
              <select v-model="form.blog_category_id" class="form-select">
                <option value="">-- Chọn chuyên mục --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
              <span v-if="errors?.category_id" class="error-msg">{{
                errors.category_id[0]
              }}</span>
            </div>
          </div>
        </div>

        <div class="glass-panel sidebar-box">
          <h4 class="sidebar-box-title">🖼️ Ảnh bìa bài viết (16:9)</h4>
          <div class="sidebar-box-content">
            <div class="thumbnail-uploader-box" @click="triggerUpload">
              <div v-if="!form.thumbnail_url" class="uploader-placeholder">
                <span class="upload-icon">📸</span>
                <p class="upload-text">Tải ảnh bìa lên</p>
                <span class="upload-hint">Tỷ lệ khuyên dùng 1920x1080</span>
              </div>
              <div v-else class="uploader-preview">
                <img
                  :src="form.thumbnail_url"
                  alt="Thumbnail Preview"
                  class="preview-img"
                />
                <div class="change-layer">Thay ảnh khác</div>
              </div>
            </div>
            <span v-if="errors?.thumbnail_url" class="error-msg">{{
              errors.thumbnail_url[0]
            }}</span>
          </div>
        </div>

        <div class="glass-panel sidebar-box">
          <h4 class="sidebar-box-title">🔍 Cấu hình SEO Google</h4>
          <div class="sidebar-box-content seo-fields">
            <div class="input-group">
              <label class="form-label-small">SEO Title</label>
              <input
                v-model="form.meta_title"
                type="text"
                class="form-input-small"
                placeholder="Mặc định lấy tiêu đề bài viết"
              />
            </div>
            <div class="input-group">
              <label class="form-label-small">SEO Meta Description</label>
              <textarea
                v-model="form.meta_description"
                class="form-textarea-small"
                rows="3"
                placeholder="Nhập mô tả ngắn giúp trang dễ lọt top 1 tìm kiếm..."
              ></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../../../api/axios";
import { toast } from "../../../utils/alert";

const route = useRoute();
const router = useRouter();

const isEditing = ref(false);
const editingId = ref(null);
const submitting = ref(false);

const categories = ref([]);
const activeMovies = ref([]);

// 💡 ĐÃ ĐỔI: category_id -> blog_category_id cho đồng bộ với Laravel
const form = ref({
  title: "",
  excerpt: "",
  content: "",
  thumbnail_url: "",
  blog_category_id: "", // Sửa ở đây
  movie_id: null,
  status: "published",
  published_at: "",
  meta_title: "",
  meta_description: "",
});

const errors = ref({});

// Thuật toán sinh Slug URL tự động chuẩn SEO
const generateSlug = (title) => {
  if (!title) return "";
  let slug = title.toLowerCase();
  slug = slug.replace(/á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/gi, "a");
  slug = slug.replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, "e");
  slug = slug.replace(/i|í|ì|ỉ|ĩ|ị/gi, "i");
  slug = slug.replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, "o");
  slug = slug.replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, "u");
  slug = slug.replace(/ý|ỳ|ỷ|ỹ|ị/gi, "y");
  slug = slug.replace(/đ/gi, "d");
  slug = slug.replace(/\s+/g, "-");
  slug = slug.replace(/[^a-z0-9\-]/g, "");
  slug = slug.replace(/\-+/g, "-");
  slug = slug.replace(/^-+|-+$/g, "");
  return slug;
};

// 💡 ĐÃ GOM: Gom việc gọi API danh mục và phim vào đúng hàm fetchInitialData
const fetchInitialData = async () => {
  try {
    const catResponse = await api.get("/admin/blog-categories");
    categories.value = catResponse.data.data || catResponse.data;
    console.log("Danh sách chuyên mục tải thành công:", categories.value);

    const movieResponse = await api.get("/admin/movies");
    activeMovies.value = movieResponse.data.data || movieResponse.data;
  } catch (error) {
    console.error("Lỗi khi tải dữ liệu ban đầu:", error);
    toast("Không thể tải danh sách chuyên mục hoặc phim!", "error");
  }
};

// Kiểm tra xem trang ở trạng thái Edit hay Create mới
const checkEditState = async () => {
  const id = route.params.id;
  if (id) {
    isEditing.value = true;
    editingId.value = id;
    try {
      const response = await api.get(`/admin/blogs/${id}`);
      const data = response.data.data || response.data;
      form.value = { ...data };
    } catch (error) {
      toast("Không tìm thấy bài viết cần sửa!", "error");
      router.push("/admin/blogs");
    }
  }
};

const validateForm = () => {
  errors.value = {};
  let isValid = true;

  if (!form.value.title || form.value.title.trim() === "") {
    errors.value.title = ["Vui lòng nhập tiêu đề bài viết."];
    isValid = false;
  }
  if (!form.value.content || form.value.content.trim() === "") {
    errors.value.content = ["Vui lòng nhập nội dung chi tiết bài viết."];
    isValid = false;
  }
  // 💡 ĐÃ SỬA: Kiểm tra theo đúng biến blog_category_id mới
  if (!form.value.blog_category_id) {
    errors.value.blog_category_id = ["Vui lòng lựa chọn chuyên mục phân loại."];
    isValid = false;
  }

  return isValid;
};

// Xử lý Lưu bài viết (Hỗ trợ cả chế độ Published lẫn Draft)
const savePost = async (targetStatus) => {
  if (targetStatus === "draft") {
    form.value.status = "draft";
  } else if (form.value.status === "draft") {
    form.value.status = "published";
  }

  if (!validateForm()) {
    toast("Vui lòng kiểm tra lại dữ liệu nhập vào!", "warning");
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      ...form.value,
      slug: generateSlug(form.value.title),
    };

    if (isEditing.value) {
      await api.put(`/admin/blogs/${editingId.value}`, payload);
      toast("Cập nhật bài viết thành công!");
    } else {
      await api.post("/admin/blogs", payload);
      toast("Đã xuất bản bài viết mới thành công!");
    }
    router.push("/admin/blogs");
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
    } else {
      toast("Có lỗi hệ thống xảy ra!", "error");
    }
  } finally {
    submitting.value = false;
  }
};

// Giả lập uploader ảnh
const triggerUpload = () => {
  const url = prompt("Nhập tạm URL ảnh bìa để kiểm tra giao diện:");
  if (url) form.value.thumbnail_url = url;
};

onMounted(async () => {
  await fetchInitialData(); // Bây giờ hàm này đã tồn tại và chạy chuẩn xác!
  await checkEditState();
});
</script>

<style scoped>
.blog-editor-container {
  padding: 25px;
  background-color: #f8fafc;
  min-height: 100vh;
  color: #1e293b;
  font-family: "Inter", sans-serif;
}

/* Header vùng quản lý */
.editor-header {
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

.header-actions {
  display: flex;
  gap: 12px;
}

/* Cấu trúc Layout 2 cột phân vùng */
.wordpress-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

@media (min-width: 1024px) {
  .wordpress-layout {
    grid-template-columns: 7fr 3fr;
  }
}

/* Khối Panel nền trắng */
.glass-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  margin-bottom: 24px;
}

.main-content-column .content-box {
  border-left: 4px solid #e2e8f0;
}

.main-content-column .content-box:focus-within {
  border-left-color: #e50914;
}

.marketing-card {
  border-left: 4px solid #f43f5e !important;
  background: #fff1f2;
}

.box-sub-title {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 700;
  color: #9f1239;
}

.box-desc {
  margin: 0 0 16px 0;
  font-size: 13px;
  color: #e11d48;
}

/* Các thành phần Form & Input */
.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.form-label {
  font-size: 14px;
  font-weight: 700;
  color: #334155;
}

.label-hint {
  font-weight: 400;
  color: #64748b;
  font-size: 12.5px;
}

.form-input-title {
  width: 100%;
  border: 1px solid #cbd5e1;
  padding: 14px 18px;
  border-radius: 8px;
  outline: none;
  font-size: 18px;
  font-weight: 700;
  background-color: #ffffff;
  color: #0f172a;
  box-sizing: border-box;
}

.form-input-title:focus {
  border-color: #e50914;
  box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.08);
}

.slug-permalink-zone {
  margin-top: 12px;
  font-size: 13.5px;
  color: #64748b;
}

.permalink-url {
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 4px;
  margin-left: 6px;
  font-family: monospace;
  color: #334155;
}

.slug-text {
  color: #e50914;
  font-weight: 700;
}

.form-textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  padding: 12px 16px;
  border-radius: 8px;
  outline: none;
  font-size: 14.5px;
  font-family: inherit;
  resize: vertical;
  box-sizing: border-box;
}

.form-textarea:focus,
.form-select:focus {
  border-color: #e50914;
}

.form-select {
  width: 100%;
  border: 1px solid #cbd5e1;
  padding: 12px 16px;
  border-radius: 8px;
  outline: none;
  font-size: 14.5px;
  background-color: white;
  box-sizing: border-box;
}

/* Trình soạn thảo Rich Editor */
.rich-editor-wrapper {
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
}

.editor-toolbar {
  background: #f8fafc;
  padding: 10px 14px;
  border-bottom: 1px solid #cbd5e1;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.toolbar-btn {
  background: white;
  border: 1px solid #cbd5e1;
  padding: 5px 12px;
  border-radius: 4px;
  font-size: 13px;
  color: #475569;
  cursor: pointer;
  font-weight: 600;
}

.toolbar-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #94a3b8;
}

.editor-textarea {
  width: 100%;
  border: none;
  padding: 16px;
  font-size: 15px;
  line-height: 1.6;
  font-family: inherit;
  outline: none;
  resize: vertical;
  box-sizing: border-box;
  background: white;
}

/* SIDEBAR CỘT PHẢI */
.sidebar-box {
  padding: 20px;
  margin-bottom: 20px;
}

.sidebar-box-title {
  margin: 0 0 16px 0;
  font-size: 14.5px;
  font-weight: 800;
  color: #1e293b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 10px;
}

.radio-group-vertical {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.radio-label {
  font-size: 14px;
  color: #334155;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.scheduling-input-zone {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px dashed #e2e8f0;
}

.form-label-small {
  font-size: 12.5px;
  font-weight: 700;
  color: #475569;
  margin-bottom: 6px;
  display: block;
}

.form-input-small,
.form-textarea-small {
  width: 100%;
  border: 1px solid #cbd5e1;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  background: white;
  box-sizing: border-box;
}

.form-textarea-small {
  font-family: inherit;
  resize: none;
}

/* Bộ tải ảnh Featured Image */
.thumbnail-uploader-box {
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  padding: 24px 16px;
  text-align: center;
  cursor: pointer;
  background: #f8fafc;
  position: relative;
  overflow: hidden;
  transition: all 0.2s ease;
}

.thumbnail-uploader-box:hover {
  border-color: #e50914;
  background: #fffafb;
}

.upload-icon {
  font-size: 28px;
  display: block;
  margin-bottom: 6px;
}

.upload-text {
  margin: 0;
  font-size: 13.5px;
  font-weight: 700;
  color: #334155;
}

.upload-hint {
  font-size: 11px;
  color: #94a3b8;
  display: block;
  margin-top: 2px;
}

.uploader-preview {
  width: 100%;
  height: auto;
  aspect-ratio: 16 / 9;
  position: relative;
}

.preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

.change-layer {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
  opacity: 0;
  transition: opacity 0.2s ease;
  border-radius: 6px;
}

.thumbnail-uploader-box:hover .change-layer {
  opacity: 1;
}

.seo-fields {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* Nút bấm của hệ thống CineGo */
.btn-primary-cine {
  background: linear-gradient(135deg, #e50914 0%, #9b000e 100%);
  color: #ffffff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.15);
  transition: all 0.2s ease;
}

.btn-primary-cine:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(229, 9, 20, 0.25);
}

.btn-primary-cine:disabled,
.btn-secondary-cine:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary-cine {
  background-color: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary-cine:hover {
  background-color: #f1f5f9;
  border-color: #94a3b8;
  color: #1e293b;
}

.error-msg {
  color: #dc2626;
  font-size: 12px;
  margin-top: 4px;
  display: block;
  font-weight: 600;
}
</style>
