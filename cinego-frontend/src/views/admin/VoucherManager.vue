<template>
    <div class="admin-vouchers-view-container">
        <div class="glass-panel list-card">
            <div class="header-row">
                <h2 class="title-cine">🎟️ Quản Lý Mã Giảm Giá</h2>
                <button @click="openCreateModal" class="btn-primary-cine">+ Thêm Mã Mới</button>
            </div>

            <div class="table-responsive-wrapper">
                <table class="vouchers-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Loại giảm</th>
                            <th>Giá trị</th>
                            <th>Đơn tối thiểu</th>
                            <th>Đối tượng</th>
                            <th>Điều kiện áp dụng</th>
                            <th>Hết hạn</th>
                            <th>Giảm tối đa</th>
                            <th>số lần dùng</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="voucher in vouchers" :key="voucher.id">
                            <td><strong>{{ voucher.code }}</strong></td>
                            <td>
                                {{ voucher.discount_type === 'percentage'
                                    ? voucher.discount_value + '%'
                                    : formatCurrency(voucher.discount_value)
                                }}
                            </td>
                            <td>{{ voucher.discount_value }}{{ voucher.discount_type === 'percentage' ? '%' : 'đ' }}
                            </td>
                            <td>{{ formatCurrency(voucher.min_spend) }}</td>

                            <td>
                                <span v-if="voucher.target_limit === 'all'">Tất cả</span>
                                <span v-else-if="voucher.target_limit === 'new_user'" class="status-pill active">Tân
                                    binh</span>
                                <span v-else-if="voucher.target_limit === 'birthday'" class="status-pill active"
                                    style="background:#fef08a; color:#854d0e;">Sinh nhật</span>
                            </td>

                            <td>
                                <div v-if="voucher.usage_condition">
                                    <small v-if="voucher.usage_condition?.day_of_week"
                                        style="display: block; margin-bottom: 4px;">
                                        📅 Thứ {{ voucher.usage_condition.day_of_week == 7 ? 'Chủ Nhật' :
                                            voucher.usage_condition.day_of_week + 1 }}
                                    </small>
                                    <small v-if="voucher.usage_condition?.movie_id"
                                        style="display: block; color: #e11d48; font-weight: 500;">
                                        🎬 {{ getMovieTitle(voucher.usage_condition.movie_id) }}
                                    </small>
                                </div>
                                <span v-else>-</span>
                            </td>

                            <td>{{ formatDate(voucher.expires_at) }}</td>
                            <td>{{ voucher.max_discount ? formatCurrency(voucher.max_discount) : 'Không giới hạn' }}
                            </td>
                            <td>{{ voucher.usage_limit ? voucher.usage_limit : 'Không giới hạn' }}</td>
                            <td>
                                <span
                                    :class="['status-pill', voucher.is_active && !isVoucherExpired(voucher.expires_at) ? 'active' : 'inactive']">
                                    {{ voucher.is_active && !isVoucherExpired(voucher.expires_at) ? 'Đang hoạt động' :
                                        'Đã hết hạn' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn-action edit" @click="editVoucher(voucher)">Sửa</button>
                                <button class="btn-action delete" @click="deleteVoucher(voucher.id)">Xóa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="loading" class="loading-container">
                    <div class="loader"></div>
                    <p>Đang lấy dữ liệu từ database...</p>
                </div>
            </div>
        </div>

        <div v-if="isModalOpen" class="modal-overlay-cine">
            <div class="glass-panel modal-content-cine">
                <div class="modal-header">
                    <h3 style="margin-top: 0; margin-bottom: 0;">{{ isEditing ? 'Cập nhật Voucher' : 'Tạo Voucher mới'
                        }}</h3>
                    <button class="close-btn" @click="isModalOpen = false">×</button>
                </div>

                <div class="form-group">
                    <label>Mã Code (VD: SALE50)</label>
                    <input v-model="voucherForm.code" :class="{ 'is-invalid': errors.code }" class="form-control"
                        placeholder="Nhập mã...">
                    <span v-if="errors.code" class="error-text">{{ errors.code[0] }}</span>
                </div>

                <div class="grid-inputs" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Loại giảm</label>
                        <select v-model="voucherForm.discount_type" class="form-control">
                            <option value="fixed">Số tiền cố định (đ)</option>
                            <option value="percentage">Phần trăm (%)</option>
                        </select>
                        <span v-if="errors.discount_type" class="error-text">{{ errors.discount_type[0] }}</span>
                    </div>
                    <div class="form-group">
                        <label>Giá trị giảm</label>
                        <input v-model="voucherForm.discount_value" type="number"
                            :class="{ 'is-invalid': errors.discount_value }" class="form-control">
                        <span v-if="errors.discount_value" class="error-text">{{ errors.discount_value[0] }}</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Đơn tối thiểu (đ)</label>
                    <input v-model="voucherForm.min_spend" type="number" :class="{ 'is-invalid': errors.min_spend }"
                        class="form-control">
                    <span v-if="errors.min_spend" class="error-text">{{ errors.min_spend[0] }}</span>
                </div>

                <div class="form-group">
                    <label>Giảm tối đa (đ) - Để trống nếu không giới hạn</label>
                    <input v-model="voucherForm.max_discount" type="number" class="form-control"
                        placeholder="VD: 50000">
                </div>

                <!-- 1. TRƯỜNG MỚI: GIỚI HẠN ĐỐI TƯỢNG -->
                <div class="form-group">
                    <label>Giới hạn đối tượng áp dụng</label>
                    <select v-model="voucherForm.target_limit" class="form-control">
                        <option value="all">Tất cả khách hàng</option>
                        <option value="new_user">Tân binh (Đăng ký mới)</option>
                        <option value="birthday">Voucher sinh nhật (Áp dụng đúng tháng sinh)</option>
                    </select>
                </div>

                <!-- 2. TRƯỜNG MỚI: GIỚI HẠN KHUNG GIỜ / PHIM -->
                <div class="form-group border-box-limit"
                    style="border: 1px dashed #ccc; padding: 12px; border-radius: 8px;">
                    <label style="font-size: 0.95rem; margin-bottom: 10px;">🛡️ Điều kiện thời gian hoặc phim</label>

                    <div class="grid-inputs" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 0.85rem; font-weight: normal;">Chỉ áp dụng vào Thứ:</label>
                            <select v-model="voucherForm.usage_condition.day_of_week" class="form-control">
                                <option value="">Không giới hạn ngày</option>
                                <option value="1">Thứ 2</option>
                                <option value="2">Thứ 3</option>
                                <option value="3">Thứ 4</option>
                                <option value="4">Thứ 5</option>
                                <option value="5">Thứ 6</option>
                                <option value="6">Thứ Bảy</option>
                                <option value="7">Chủ Nhật</option>
                            </select>
                        </div>



                        <div>
                            <label>Áp dụng riêng cho bộ phim</label>
                            <div class="custom-select-search" style="position: relative;">
                                <!-- Ô input để gõ tìm kiếm -->
                                <input type="text" class="form-control" placeholder="Gõ ID phim để tìm kiếm..."
                                    v-model="movieSearchQuery" @focus="showMovieDropdown = true"
                                    @blur="hideMovieDropdown" />

                                <!-- Dropdown hiển thị danh sách lọc -->
                                <ul v-if="showMovieDropdown && filteredMovies.length > 0"
                                    style="position: absolute; top: 100%; left: 0; right: 0; z-index: 999; 
                   background: #fff; border: 1px solid #ccc; max-height: 200px; 
                   overflow-y: auto; list-style: none; padding: 0; margin: 0; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

                                    <!-- Lựa chọn mặc định không áp dụng phim nào -->
                                    <li @mousedown="selectMovie(null)"
                                        style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; color: #888;">
                                        -- Không áp dụng riêng cho phim nào --
                                    </li>

                                    <!-- Danh sách phim tìm được -->
                                    <li v-for="movie in filteredMovies" :key="movie.id" @mousedown="selectMovie(movie)"
                                        style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;"
                                        class="movie-item-option">
                                        ID: {{ movie.id }} - {{ movie.title }}
                                    </li>
                                </ul>

                                <ul v-else-if="showMovieDropdown" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 999; 
                   background: #fff; border: 1px solid #ccc; padding: 10px; color: #999; text-align: center;">
                                    Không tìm thấy phim phù hợp
                                </ul>
                            </div>

                            <!-- Hiển thị badge bộ phim đang được chọn ở dưới để Admin dễ nhận biết -->
                            <div v-if="voucherForm.usage_condition.movie_id" style="margin-top: 8px; font-size: 13px;">
                                Đang chọn: <span
                                    style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-weight: bold; color: #2d3748;">
                                    ID {{ voucherForm.usage_condition.movie_id }} - {{ selectedMovieTitle }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>







                <div class="grid-inputs"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Ngày bắt đầu áp dụng</label>
                        <input v-model="voucherForm.starts_at" type="datetime-local"
                            :class="{ 'is-invalid': errors.starts_at }" class="form-control">
                        <span v-if="errors.starts_at" class="error-text">{{ errors.starts_at[0] }}</span>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Ngày hết hạn</label>
                        <input v-model="voucherForm.expires_at" type="datetime-local"
                            :class="{ 'is-invalid': errors.expires_at }" class="form-control">
                        <span v-if="errors.expires_at" class="error-text">{{ errors.expires_at[0] }}</span>
                    </div>
                </div>
                <div class="grid-inputs"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Tổng lượt dùng hệ thống</label>
                        <input v-model="voucherForm.usage_limit" type="number" class="form-control"
                            placeholder="Để trống = Không GH">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Lượt dùng / 1 tài khoản</label>
                        <input v-model="voucherForm.user_limit" type="number"
                            :class="{ 'is-invalid': errors.user_limit }" class="form-control" placeholder="Mặc định: 1">
                        <span v-if="errors.user_limit" class="error-text">{{ errors.user_limit[0] }}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="isModalOpen = false" class="btn-secondary-cine">Hủy</button>
                    <button @click="saveVoucher" class="btn-primary-cine">Lưu Voucher</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';

const vouchers = ref([]);
const isModalOpen = ref(false);
const isEditing = ref(false);
const errors = ref({});
const moviesList = ref([]); // Đồng nhất dùng duy nhất biến moviesList này
const movieSearchQuery = ref('');
const showMovieDropdown = ref(false);

const initForm = () => ({
    code: '',
    discount_type: 'fixed',
    discount_value: 0,
    min_spend: 0,
    max_discount: '',
    usage_limit: '',
    user_limit: 1,
    starts_at: '',
    expires_at: '',
    is_active: true,
    target_limit: 'all',
    usage_condition: {
        day_of_week: '',
        movie_id: ''
    }
});

const voucherForm = ref(initForm());
const loading = ref(false);

const fetchVouchers = async () => {
    loading.value = true;
    try {
        const res = await api.get('admin/vouchers');
        vouchers.value = res.data.map(v => {
            if (v.usage_condition && typeof v.usage_condition === 'string') {
                try {
                    v.usage_condition = JSON.parse(v.usage_condition);
                } catch (e) {
                    v.usage_condition = null;
                }
            }
            return v;
        });
    } catch (error) {
        console.error("Lỗi khi tải voucher:", error);
    } finally {
        loading.value = false;
    }
};

const formatCurrency = (value) => {
    if (!value) return '0đ';
    const number = parseFloat(value);
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0,
    }).format(number);
};

const fetchMoviesList = async () => {
    try {
        const res = await api.get('admin/movies/list');
        moviesList.value = res.data;
    } catch (error) {
        console.error("Lỗi khi tải danh sách phim:", error);
    }
};

const getMovieTitle = (movieId) => {
    if (!movieId) return '';
    if (!moviesList.value || moviesList.value.length === 0) {
        return `ID Phim: ${movieId}`;
    }
    const movie = moviesList.value.find(m => Number(m.id) === Number(movieId));
    return movie ? movie.title : `ID Phim: ${movieId}`;
};

const openCreateModal = () => {
    isEditing.value = false;
    voucherForm.value = initForm();
    movieSearchQuery.value = ''; 
    isModalOpen.value = true;
    fetchMoviesList();
};

const saveVoucher = async () => {
    errors.value = {};
    const payload = { ...voucherForm.value };

    const condition = {};
    if (payload.usage_condition) {
        if (payload.usage_condition.day_of_week !== '' && payload.usage_condition.day_of_week !== null) {
            condition.day_of_week = parseInt(payload.usage_condition.day_of_week);
        }
        if (payload.usage_condition.movie_id !== '' && payload.usage_condition.movie_id !== null) {
            condition.movie_id = parseInt(payload.usage_condition.movie_id);
        }
    }

    if (!voucherForm.value.starts_at) {
        errors.value.starts_at = ["Vui lòng chọn ngày bắt đầu áp dụng mã."];
    }

    if (!voucherForm.value.expires_at) {
        errors.value.expires_at = ["Vui lòng chọn ngày hết hạn mã."];
    }

    if (errors.value.starts_at || errors.value.expires_at) {
        return;
    }

    const start = new Date(voucherForm.value.starts_at);
    const expire = new Date(voucherForm.value.expires_at);

    if (start >= expire) {
        errors.value.expires_at = ["Ngày hết hạn phải lớn hơn ngày bắt đầu"];
        return;
    }

    payload.usage_condition = Object.keys(condition).length > 0 ? condition : null;
    payload.max_discount = payload.max_discount ? parseFloat(payload.max_discount) : null;
    payload.usage_limit = payload.usage_limit ? parseInt(payload.usage_limit) : null;
    payload.user_limit = payload.user_limit ? parseInt(payload.user_limit) : null;

    try {
        if (isEditing.value) {
            await api.put(`admin/vouchers/${payload.id}`, payload);
        } else {
            await api.post('admin/vouchers', payload);
        }
        isModalOpen.value = false;
        fetchVouchers();
    } catch (error) {
        if (error.response?.status === 422 && error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            alert("Lỗi: " + (error.response?.data?.message || "Có lỗi kết nối!"));
        }
    }

};

// Gộp lại làm duy nhất 1 hàm editVoucher chuẩn
const editVoucher = (voucher) => {
    errors.value = {};
    isEditing.value = true;
    
    fetchMoviesList().then(() => {
        if (voucher.usage_condition?.movie_id) {
            const movie = moviesList.value.find(m => m.id == voucher.usage_condition.movie_id);
            movieSearchQuery.value = movie ? movie.title : '';
        } else {
            movieSearchQuery.value = '';
        }
    });

    voucherForm.value = {
        ...voucher,
        usage_condition: voucher.usage_condition ? {
            day_of_week: voucher.usage_condition.day_of_week || '',
            movie_id: voucher.usage_condition.movie_id || ''
        } : { day_of_week: '', movie_id: '' }
    };

    if (voucher.starts_at) {
        const date = new Date(voucher.starts_at);
        voucherForm.value.starts_at = date.toISOString().slice(0, 16);
    }
    if (voucher.expires_at) {
        const date = new Date(voucher.expires_at);
        voucherForm.value.expires_at = date.toISOString().slice(0, 16);
    }
    
    isModalOpen.value = true;
};

const isVoucherExpired = (expires_at) => {
    return new Date(expires_at) < new Date();
};

const deleteVoucher = async (id) => {
    if (confirm('Bạn có chắc chắn muốn xóa mã giảm giá này? Hành động này không thể hoàn tác.')) {
        try {
            await api.delete(`admin/vouchers/${id}`);
            fetchVouchers();
        } catch (error) {
            alert("Có lỗi xảy ra khi xóa!");
        }
    }
};

// Sửa biến movies -> moviesList
const filteredMovies = computed(() => {
    const query = movieSearchQuery.value.toLowerCase().trim();
    if (!query) return moviesList.value; 
    return moviesList.value.filter(movie => 
        movie.title.toLowerCase().includes(query) || 
        movie.id.toString() === query
    );
});

// Sửa biến movies -> moviesList
const selectedMovieTitle = computed(() => {
    if (!voucherForm.value.usage_condition.movie_id) return '';
    const movie = moviesList.value.find(m => m.id == voucherForm.value.usage_condition.movie_id);
    return movie ? movie.title : 'Không xác định';
});

const selectMovie = (movie) => {
    if (movie) {
        voucherForm.value.usage_condition.movie_id = movie.id;
        movieSearchQuery.value = movie.title;
    } else {
        voucherForm.value.usage_condition.movie_id = '';
        movieSearchQuery.value = '';
    }
    showMovieDropdown.value = false;
};

// Sửa biến movies -> moviesList
const hideMovieDropdown = () => {
    setTimeout(() => {
        showMovieDropdown.value = false;
        if (voucherForm.value.usage_condition.movie_id) {
            const movie = moviesList.value.find(m => m.id == voucherForm.value.usage_condition.movie_id);
            movieSearchQuery.value = movie ? movie.title : '';
        } else {
            movieSearchQuery.value = '';
        }
    }, 200);
};

const formatDate = (date) => new Date(date).toLocaleDateString('vi-VN');

onMounted(fetchVouchers);
</script>

<style scoped>
.glass-panel {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.vouchers-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 10px;
}

.vouchers-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}


.vouchers-table tbody td {
    padding: 16px 12px;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
}


.vouchers-table tbody tr:hover {
    background-color: #fcfcfc;
}

.vouchers-table thead th {
    background-color: #f9fafb;
    padding: 14px 12px;
    font-weight: 600;
    color: #4b5563;
    border-bottom: 2px solid #e5e7eb;
}


.btn-primary-cine {
    background-color: #e11d48;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.status-pill {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.85em;
}

.btn-primary-cine {
    background-color: #ef4444;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-left: 5px;
}

.btn-secondary-cine {
    background-color: #f3f4f6 !important;
    color: #374151 !important;
    padding: 10px 20px !important;
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
}

.btn-primary-cine:hover {
    background-color: #dc2626;
}

.action-buttons-group {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
}

.btn-action {
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 0.9em;
    white-space: nowrap;
}

.edit {
    background: #e0f2fe;
    color: #0284c7;
}

.delete {
    background: #fee2e2;
    color: #dc2626;
}


.status-pill.active {
    background: #dcfce7;
    color: #166534;
}

.status-pill.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.btn-action {
    margin-right: 5px;
    cursor: pointer;
}

.modal-overlay-cine {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 40px 20px;
    overflow-y: auto;
    z-index: 1000;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
    color: #9ca3af;
    transition: color 0.3s;
    padding: 0;
    margin-top: -5px;
}

.close-btn:hover {
    color: #ef4444;
}

vouchers-table img {
    width: 120px;
    /* Tăng từ kích thước cũ lên 60px hoặc hơn */
    height: 120px;
    object-fit: cover;
    /* Giúp ảnh không bị méo */
    border-radius: 8px;
    /* Bo góc cho đẹp */
}

.modal-content-cine {
    width: 600px;
    max-width: 90%;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
    text-align: left;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #f3f4f6;
}

.error-text {
    color: #dc2626;
    font-size: 0.85em;
    margin-top: 5px;
    display: block;
}

.is-invalid {
    border-color: #dc2626 !important;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 0;
    color: #555;
}

.table-responsive-wrapper {
    width: 100%;
    max-height: calc(100vh - 250px);
    overflow-y: auto;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.loader {
    width: 45px;
    height: 45px;
    border: 4px solid #ddd;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin .8s linear infinite;
    margin-bottom: 15px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>