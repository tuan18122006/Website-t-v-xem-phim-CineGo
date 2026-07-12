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
                    <h3 style="margin-top: 0; margin-bottom: 0;">{{ isEditing ? 'Cập nhật Voucher' : 'Tạo Voucher mới' }}</h3>
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

                <div class="form-group">
                    <label>Giới hạn lượt dùng - Để trống nếu không giới hạn</label>
                    <input v-model="voucherForm.usage_limit" type="number" class="form-control" placeholder="VD: 100">
                </div>

                <div class="form-group">
                    <label>Ngày hết hạn</label>
                    <input v-model="voucherForm.expires_at" type="datetime-local"
                        :class="{ 'is-invalid': errors.expires_at }" class="form-control">
                    <span v-if="errors.expires_at" class="error-text">{{ errors.expires_at[0] }}</span>
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
import { ref, onMounted } from 'vue';
import api from '../../api/axios';

const vouchers = ref([]);
const isModalOpen = ref(false);
const isEditing = ref(false);
const errors = ref({});
const voucherForm = ref({
    code: '',
    discount_type: 'fixed',
    discount_value: 0,
    min_spend: 0,
    max_discount: '',
    usage_limit: '',
    expires_at: '',
    is_active: true
});

const loading = ref(false);

const fetchVouchers = async () => {
    loading.value = true;

    try {
        const res = await api.get('admin/vouchers');
        vouchers.value = res.data;
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

const openCreateModal = () => {
    isEditing.value = false;
    voucherForm.value = {
        code: '',
        discount_type: 'fixed',
        discount_value: 0,
        min_spend: 0,
        expires_at: '',
        is_active: true
    };
    isModalOpen.value = true;
};
const saveVoucher = async () => {
    errors.value = {};
    try {
        if (isEditing.value) {
            await api.put(`admin/vouchers/${voucherForm.value.id}`, voucherForm.value);
        } else {
            await api.post('admin/vouchers', voucherForm.value);
        }
        isModalOpen.value = false;
        fetchVouchers();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            alert("Có lỗi kết nối server!");
        }
    }
};

const editVoucher = (voucher) => {
    errors.value = {};
    isEditing.value = true;
    voucherForm.value = { ...voucher };
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
    width: 120px;       /* Tăng từ kích thước cũ lên 60px hoặc hơn */
    height: 120px;
    object-fit: cover; /* Giúp ảnh không bị méo */
    border-radius: 8px; /* Bo góc cho đẹp */
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