<template>
  <div class="rvm">
    <!-- Bộ lọc -->
    <div class="rvm-toolbar glass-panel">
      <label class="rvm-search">
        <span>🔍</span>
        <input
          v-model="filters.q"
          type="text"
          placeholder="Tìm từ khóa trong bình luận / tên / email khách…"
          @keyup.enter="applyFilters"
        />
        <button v-if="filters.q" class="rvm-search__clear" @click="filters.q = ''; applyFilters()">✕</button>
      </label>

      <select v-model="filters.rating" class="rvm-select" @change="applyFilters">
        <option value="">Tất cả sao</option>
        <option v-for="s in [5,4,3,2,1]" :key="s" :value="s">{{ s }} ★</option>
      </select>

      <select v-model="filters.movie_id" class="rvm-select" @change="applyFilters">
        <option value="">Tất cả phim</option>
        <option v-for="m in movies" :key="m.id" :value="m.id">{{ m.title }}</option>
      </select>

      <button class="rvm-btn-danger-soft" @click="quickBad" title="Xem ngay khách 1–2 sao đang bức xúc">
        😠 Khách bức xúc (1–2★)
      </button>
    </div>

    <!-- Loading / Empty -->
    <div v-if="loading" class="rvm-state"><div class="rvm-spinner"></div><p>Đang tải đánh giá…</p></div>
    <div v-else-if="reviews.length === 0" class="rvm-state">
      <span style="font-size:42px">🗳️</span>
      <p>Không có đánh giá nào khớp bộ lọc.</p>
    </div>

    <!-- Bảng -->
    <div v-else class="rvm-table-wrap glass-panel">
      <table class="rvm-table">
        <thead>
          <tr>
            <th>Người đánh giá</th>
            <th>Phim</th>
            <th>Đánh giá</th>
            <th>Nội dung</th>
            <th>Thời gian</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in reviews" :key="r.id" :class="{ 'is-hidden': r.is_hidden, 'is-featured': r.is_featured }">
            <td>
              <div class="rvm-user">
                <span class="rvm-avatar">{{ initials(r.user?.name) }}</span>
                <div class="rvm-user__info">
                  <strong>{{ r.user?.name || 'Ẩn danh' }}</strong>
                  <span class="muted">{{ r.user?.email || '—' }}</span>
                </div>
              </div>
            </td>
            <td class="rvm-movie">{{ r.movie?.title || '—' }}</td>
            <td>
              <span class="rvm-stars">
                <span v-for="i in 5" :key="i" :class="i <= r.rating ? 'on' : 'off'">★</span>
              </span>
            </td>
            <td class="rvm-comment">
              <span v-if="r.is_featured" class="tag-featured">📌 Ghim</span>
              <span v-if="r.is_hidden" class="tag-hidden">🚫 Đã ẩn</span>
              <p>{{ r.comment || '(không có nội dung)' }}</p>
              <p v-if="r.admin_reply" class="rvm-reply">
                <b>CineGo Official:</b> {{ r.admin_reply }}
              </p>
            </td>
            <td class="muted rvm-time">{{ formatTime(r.created_at) }}</td>
            <td>
              <div class="rvm-actions">
                <button class="ic" :class="{ active: r.is_featured }" title="Ghim nổi bật" @click="toggleFeature(r)">📌</button>
                <button class="ic" :class="{ active: r.is_hidden }" :title="r.is_hidden ? 'Hiện lại' : 'Ẩn'" @click="toggleHide(r)">
                  {{ r.is_hidden ? '👁️' : '🙈' }}
                </button>
                <button class="ic" title="Phản hồi" @click="openReply(r)">💬</button>
                <button class="ic ic-del" title="Xóa vĩnh viễn" @click="removeReview(r)">🗑️</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Phân trang -->
      <div v-if="lastPage > 1" class="rvm-pager">
        <button :disabled="page === 1" @click="goPage(page - 1)">← Trước</button>
        <span>Trang {{ page }} / {{ lastPage }} • {{ total }} đánh giá</span>
        <button :disabled="page === lastPage" @click="goPage(page + 1)">Sau →</button>
      </div>
    </div>

    <!-- Modal phản hồi -->
    <transition name="rvm-fade">
      <div v-if="replyTarget" class="rvm-backdrop" @click.self="replyTarget = null">
        <div class="rvm-modal">
          <h3>💬 Phản hồi với tư cách <span class="rvm-official">CineGo Official</span></h3>
          <p class="muted">Trả lời bình luận của <b>{{ replyTarget.user?.name }}</b> · phim {{ replyTarget.movie?.title }}</p>
          <blockquote>"{{ replyTarget.comment }}"</blockquote>
          <textarea v-model="replyText" rows="4" placeholder="VD: Cảm ơn bạn đã ủng hộ CineGo! / Rạp xin lỗi vì sự cố…"></textarea>
          <div class="rvm-modal__foot">
            <button class="btn-ghost" @click="replyTarget = null">Hủy</button>
            <button v-if="replyTarget.admin_reply" class="btn-ghost" @click="saveReply('')">Xóa phản hồi</button>
            <button class="btn-solid" @click="saveReply(replyText)">Gửi phản hồi</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../../api/axios';

const loading = ref(false);
const reviews = ref([]);
const movies = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

const filters = reactive({ q: '', rating: '', movie_id: '' });

const replyTarget = ref(null);
const replyText = ref('');

const initials = (name) => {
  if (!name) return '👤';
  const p = name.trim().split(/\s+/);
  return (p[0][0] + (p[p.length - 1][0] || '')).toUpperCase();
};

const formatTime = (dt) => {
  if (!dt) return '—';
  const d = new Date(dt);
  return d.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
};

const fetchMovies = async () => {
  try {
    const res = await api.get('/movies');
    movies.value = res.data.data || res.data || [];
  } catch (e) { console.error('movies error', e); }
};

const fetchReviews = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/reviews', {
      params: { page: page.value, rating: filters.rating || undefined, movie_id: filters.movie_id || undefined, q: filters.q || undefined },
    });
    reviews.value = res.data.data || [];
    lastPage.value = res.data.last_page || 1;
    total.value = res.data.total || 0;
  } catch (e) {
    console.error('reviews error', e);
    alert('Không tải được danh sách đánh giá.');
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => { page.value = 1; fetchReviews(); };
const goPage = (p) => { page.value = p; fetchReviews(); };
const quickBad = () => { filters.rating = ''; filters.movie_id = ''; page.value = 1; fetchReviewsBad(); };
const fetchReviewsBad = async () => {
  // Lọc nhanh 1–2 sao: gọi 2 lần rồi gộp (đơn giản, không đổi backend)
  loading.value = true;
  try {
    const [r1, r2] = await Promise.all([
      api.get('/admin/reviews', { params: { rating: 1 } }),
      api.get('/admin/reviews', { params: { rating: 2 } }),
    ]);
    reviews.value = [...(r1.data.data || []), ...(r2.data.data || [])];
    lastPage.value = 1; total.value = reviews.value.length;
  } finally { loading.value = false; }
};

const toggleFeature = async (r) => {
  try { const res = await api.patch(`/admin/reviews/${r.id}/feature`); r.is_featured = res.data.is_featured; }
  catch { alert('Lỗi khi ghim.'); }
};
const toggleHide = async (r) => {
  try { const res = await api.patch(`/admin/reviews/${r.id}/hide`); r.is_hidden = res.data.is_hidden; }
  catch { alert('Lỗi khi ẩn/hiện.'); }
};
const removeReview = async (r) => {
  if (!confirm(`Xóa vĩnh viễn bình luận của ${r.user?.name || 'khách'}? Không thể hoàn tác.`)) return;
  try { await api.delete(`/admin/reviews/${r.id}`); reviews.value = reviews.value.filter(x => x.id !== r.id); }
  catch { alert('Lỗi khi xóa.'); }
};

const openReply = (r) => { replyTarget.value = r; replyText.value = r.admin_reply || ''; };
const saveReply = async (text) => {
  try {
    const res = await api.post(`/admin/reviews/${replyTarget.value.id}/reply`, { admin_reply: text });
    replyTarget.value.admin_reply = res.data.data.admin_reply;
    replyTarget.value = null;
  } catch { alert('Lỗi khi gửi phản hồi.'); }
};

onMounted(() => { fetchMovies(); fetchReviews(); });
</script>

<style scoped>
.rvm { display: flex; flex-direction: column; gap: 20px; }
.muted { color: var(--text-muted); }

.rvm-toolbar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding: 16px; background: #fff; border: 1px solid rgba(0,0,0,.05); }
.rvm-search { flex: 1; min-width: 240px; display: flex; align-items: center; gap: 8px; padding: 0 12px; height: 44px; background: #faf8fa; border: 1.5px solid #ececf1; border-radius: 10px; }
.rvm-search:focus-within { border-color: var(--accent-pink); background: #fff; }
.rvm-search input { flex: 1; border: none; outline: none; background: transparent; font-size: 14px; }
.rvm-search__clear { border: none; background: #f1f5f9; width: 22px; height: 22px; border-radius: 50%; cursor: pointer; }
.rvm-select { height: 44px; padding: 0 14px; border: 1.5px solid #ececf1; border-radius: 10px; background: #fff; font-size: 13.5px; font-weight: 600; cursor: pointer; }
.rvm-btn-danger-soft { height: 44px; padding: 0 16px; border: 1.5px solid #fecaca; background: #fff5f5; color: #dc2626; font-weight: 700; font-size: 13px; border-radius: 10px; cursor: pointer; }
.rvm-btn-danger-soft:hover { background: #fee2e2; }

.rvm-state { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 60px; color: var(--text-muted); }
.rvm-spinner { width: 40px; height: 40px; border-radius: 50%; border: 4px solid #f1e3ec; border-top-color: var(--accent-pink); animation: rvm-spin .8s linear infinite; }
@keyframes rvm-spin { to { transform: rotate(360deg); } }

.rvm-table-wrap { background: #fff; border: 1px solid rgba(0,0,0,.05); overflow-x: auto; padding: 4px; }
.rvm-table { width: 100%; border-collapse: collapse; }
.rvm-table th { text-align: left; padding: 12px 14px; font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid rgba(0,0,0,.06); white-space: nowrap; }
.rvm-table td { padding: 14px; border-bottom: 1px solid rgba(0,0,0,.04); font-size: 13.5px; vertical-align: top; }
.rvm-table tr.is-hidden { background: #fafafa; opacity: .7; }
.rvm-table tr.is-featured { background: #fffdf5; }

.rvm-user { display: flex; gap: 10px; align-items: center; }
.rvm-avatar { width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center; color: #fff; font-size: 12px; font-weight: 800; background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); }
.rvm-user__info { display: flex; flex-direction: column; }
.rvm-user__info strong { color: #1e293b; }
.rvm-movie { font-weight: 700; color: #1e293b; min-width: 120px; }
.rvm-stars { white-space: nowrap; font-size: 15px; }
.rvm-stars .on { color: #f59e0b; }
.rvm-stars .off { color: #e2e8f0; }
.rvm-comment { max-width: 340px; }
.rvm-comment p { margin: 4px 0 0; color: #334155; line-height: 1.5; }
.rvm-reply { background: #f1f5f9; border-left: 3px solid var(--accent-pink); padding: 6px 10px; border-radius: 6px; font-size: 12.5px; }
.rvm-reply b { color: var(--accent-pink); }
.rvm-time { white-space: nowrap; }

.tag-featured, .tag-hidden { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 999px; margin-right: 6px; }
.tag-featured { background: #fef3c7; color: #b45309; }
.tag-hidden { background: #fee2e2; color: #b91c1c; }

.rvm-actions { display: flex; gap: 6px; }
.ic { width: 32px; height: 32px; border: 1px solid #e2e8f0; background: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; transition: all .15s; }
.ic:hover { background: #f8fafc; border-color: var(--accent-pink); }
.ic.active { background: #fef3c7; border-color: #f59e0b; }
.ic-del:hover { background: #fee2e2; border-color: #dc2626; }

.rvm-pager { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; font-size: 13px; color: var(--text-secondary); }
.rvm-pager button { border: 1px solid #e2e8f0; background: #fff; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
.rvm-pager button:disabled { opacity: .5; cursor: not-allowed; }

.rvm-backdrop { position: fixed; inset: 0; z-index: 1000; background: rgba(15,6,8,.5); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 20px; }
.rvm-modal { width: 100%; max-width: 500px; background: #fff; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; gap: 12px; }
.rvm-modal h3 { font-size: 17px; font-weight: 800; color: #1e293b; }
.rvm-official { color: var(--accent-pink); }
.rvm-modal blockquote { margin: 0; padding: 10px 14px; background: #f8fafc; border-left: 3px solid #cbd5e1; border-radius: 6px; font-style: italic; color: #475569; font-size: 13px; }
.rvm-modal textarea { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px; font-size: 14px; resize: vertical; outline: none; font-family: inherit; }
.rvm-modal textarea:focus { border-color: var(--accent-pink); }
.rvm-modal__foot { display: flex; justify-content: flex-end; gap: 10px; }
.btn-ghost { border: 1.5px solid #e2e8f0; background: #fff; color: #475569; padding: 10px 18px; border-radius: 10px; font-weight: 700; cursor: pointer; }
.btn-solid { border: none; background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; }

.rvm-fade-enter-active { transition: opacity .2s; }
.rvm-fade-enter-from { opacity: 0; }
</style>
