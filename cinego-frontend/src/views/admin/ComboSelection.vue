<template>
    <div class="admin-movies-view-container">
        <div class="glass-panel list-card">
            <div class="header-row">
                <h2 class="title-cine">🍿 Quản Lý Combo và Đồ Ăn</h2>
                <button @click="openCreateModal" class="btn-primary-cine">+ Thêm Combo Mới</button>
            </div>



            <div class="table-responsive-wrapper">

                <table class="movies-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên Combo</th>
                            <th>Loại</th>
                            <th>Giá</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="combo in combos" :key="combo.id" class="table-row">
                            <td>#{{ combo.id }}</td>
                            <td>
                                <img :src="combo.image_url" alt="Combo" class="combo-img-thumb" />
                            </td>
                            <td class="cell-title">{{ combo.name }}</td>
                            <td>
                                <span :class="[
                                    'type-badge',
                                    combo.type
                                ]">
                                    {{
                                        combo.type === 'combo'
                                            ? '🎁 Combo'
                                            : combo.type === 'drink'
                                                ? '🥤 Nước'
                                                : '🍿 Đồ ăn'
                                    }}
                                </span>
                            </td>
                            <td>{{ formatPrice(combo.price) }}</td>
                            <td>
                                <span :class="['status-pill-cine', combo.stock > 0 ? 'active' : 'inactive']">
                                    {{ combo.stock > 0 ? 'Còn hàng (' + combo.stock + ')' : 'Hết hàng' }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons-group">
                                    <button class="btn-action edit" @click="openEditModal(combo)">✏️ Sửa</button>
                                    <button class="btn-action delete" @click="deleteCombo(combo.id)">🗑️ Xóa</button>


                                    <div v-if="isItemsModalOpen" class="modal-overlay-cine">

                                        <div class="glass-panel modal-content-cine">

                                            <div class="modal-header">
                                                <h3>
                                                    Quản lý thành phần
                                                    {{ currentCombo.name }}
                                                </h3>

                                                <button class="close-btn" @click="isItemsModalOpen = false">
                                                    ×
                                                </button>
                                            </div>

                                            <div class="modal-body">

                                                <div class="form-group">
                                                    <label>Đồ ăn / Nước</label>

                                                    <select v-model="itemForm.item_id" class="form-control">

                                                        <option value="">-- Chọn sản phẩm --</option>

                                                        <option v-for="item in availableItems" :key="item.id"
                                                            :value="item.id">
                                                            {{ item.name }}
                                                        </option>

                                                    </select>
                                                    <span v-if="itemFormError && itemFormError.item_id"
                                                        class="error-text">
                                                        {{ itemFormError.item_id[0] }}
                                                    </span>
                                                </div>

                                                <div class="form-group">
                                                    <label>Số lượng</label>
                                                    <input type="number" min="1" class="form-control"
                                                        v-model.number="itemForm.quantity" @keydown="filterNegative">
                                                    <span v-if="itemFormError && itemFormError.quantity"
                                                        class="error-text">
                                                        {{ itemFormError.quantity[0] }}
                                                    </span>
                                                </div>

                                                <button class="btn-primary-cine" @click="addComboItem">
                                                    Thêm thành phần
                                                </button>


                                                <hr>

                                                <table class="movies-table">

                                                    <thead>

                                                        <tr>

                                                            <th>Tên</th>

                                                            <th>Số lượng</th>

                                                            <th></th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <tr v-for="item in comboItems" :key="item.id">

                                                            <td>{{ item.item.name }}</td>

                                                            <td>{{ item.quantity }}</td>

                                                            <td>

                                                                <button class="btn-action delete"
                                                                    @click="deleteComboItem(item.id)">
                                                                    Xóa
                                                                </button>

                                                            </td>

                                                        </tr>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>
                                    <button v-if="combo.type === 'combo'" class="btn-action setting"
                                        @click="openItemsModal(combo)">⚙️ Thành phần
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="loading" class="loading-container">
                <div class="loader"></div>
                <p>Đang lấy dữ liệu từ database...</p>
            </div>
        </div>



        <div v-if="isCreateModalOpen" class="modal-overlay-cine">
            <div class="glass-panel modal-content-cine">
                <div class="modal-header">
                    <h3>{{ isEditing ? 'Chỉnh sửa Combo' : 'Thêm Combo Mới' }}</h3>
                    <button class="close-btn" @click="isCreateModalOpen = false">×</button>
                </div>

                <div class="modal-body" style="padding: 20px 0;">
                    <div class="form-group">
                        <label>Tên Combo</label>
                        <input v-model="comboForm.name" type="text" class="form-control"
                            placeholder="Ví dụ: Combo Bắp Nước...">
                        <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea v-model="comboForm.description" class="form-control" rows="3"
                            placeholder="Nhập mô tả combo..."></textarea>
                        <span v-if="errors.description" class="error-text">{{ errors.description[0] }}</span>
                    </div>
                    <div class="form-group">
                        <label>Loại sản phẩm <span style="color:red">*</span></label>

                        <select v-model="comboForm.type" class="form-control">
                            <option value="">-- Chọn loại sản phẩm --</option>
                            <option value="combo">🎁 Combo</option>
                            <option value="drink">🥤 Nước uống</option>
                            <option value="food">🍿 Đồ ăn</option>
                        </select>

                        <span v-if="errors.type" class="error-text">
                            {{ errors.type[0] }}
                        </span>
                    </div>
                    <div class="form-group">
                        <label>Giá tiền (VNĐ)</label>
                        <input v-model="comboForm.price" type="number" class="form-control" placeholder="0">
                        <span v-if="errors.price" class="error-text">{{ errors.price[0] }}</span>
                    </div>

                    <div class="form-group">
                        <label>Số lượng kho</label>
                        <input v-model="comboForm.stock" type="number" class="form-control" placeholder="0">
                        <span v-if="errors.stock" class="error-text">{{ errors.stock[0] }}</span>
                    </div>

                    <div class="form-group">
                        <label>Hình ảnh (File)</label>
                        <input type="file" ref="fileInput" @change="handleFileChange" class="form-control" />

                        <img v-if="previewUrl" :src="previewUrl" alt="Preview"
                            style="max-width: 150px; margin-top: 10px; border-radius: 8px;" />

                        <span v-if="errors.image" class="error-text">{{ errors.image[0] }}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="modal-footer">
                        <button @click="isCreateModalOpen = false" class="btn-secondary-cine">Hủy</button>
                        <button @click="saveCombo" class="btn-primary-cine">
                            {{ isEditing ? 'Cập nhật Combo' : 'Thêm Combo Mới' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/axios';

const combos = ref([]);
const isCreateModalOpen = ref(false);
const isEditing = ref(false);
const fileInput = ref(null);
const previewUrl = ref(null);
const comboForm = ref({
    id: null,
    name: '',
    type: '',
    price: '',
    stock: 0,
    image: null,
    description: ''
});

const errors = ref({});

const formatPrice = (val) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const loading = ref(false);

const fetchCombos = async () => {
    loading.value = true;

    try {
        const response = await api.get('admin/combos');
        combos.value = response.data.data;
    } catch (error) {
        console.error("Lỗi khi tải combo:", error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    isEditing.value = false;

    comboForm.value = {
        name: '',
        price: '',
        type: '',
        stock: 0,
        description: '',
        image: null
    };

    previewUrl.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    errors.value = {};
    isCreateModalOpen.value = true;
};


const openEditModal = (combo) => {
    isEditing.value = true;

    comboForm.value = { ...combo };


    previewUrl.value = combo.image_url ? combo.image_url : null;

    errors.value = {};
    isCreateModalOpen.value = true;
};

const saveCombo = async () => {
    errors.value = {};
    let formData = new FormData();

    formData.append('name', comboForm.value.name);
    formData.append('description', comboForm.value.description || '');
    formData.append('type', comboForm.value.type);
    formData.append('price', comboForm.value.price);
    formData.append('stock', comboForm.value.stock);

    if (fileInput.value && fileInput.value.files[0]) {
        formData.append('image', fileInput.value.files[0]);
    }

    try {
        let response;
        if (isEditing.value) {
            formData.append('_method', 'PUT');
            response = await api.post(`admin/combos/${comboForm.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } else {
            response = await api.post('admin/combos', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }

        await fetchCombos();
        isCreateModalOpen.value = false;
        alert(isEditing.value ? "Cập nhật thành công!" : "Thêm mới thành công!");
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            alert("Có lỗi xảy ra, vui lòng thử lại!");
        }
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }
};

const handleImageUpload = (e) => {

    const file = e.target.files[0];

    if (!file) return;

    comboForm.value.image = file;

    previewImage.value = URL.createObjectURL(file);

}

const deleteCombo = async (id) => {
    if (!confirm('Bạn có chắc muốn xóa combo này?')) return;

    try {
        await api.delete(`admin/combos/${id}`);
        alert("Xóa thành công!");
        fetchCombos();
    } catch (error) {
        if (error.response?.status === 409) {
            alert(error.response.data.message);
        } else {
            alert("Có lỗi xảy ra khi xóa.");
            console.error(error);
        }
    }
};

const itemFormError = ref('');

const isItemsModalOpen = ref(false);

const currentCombo = ref(null);

const comboItems = ref([]);

const availableItems = ref([]);

const itemForm = ref({
    item_id: "",
    quantity: 1
});

const openItemsModal = async (combo) => {
    currentCombo.value = combo;
    isItemsModalOpen.value = true;
    itemFormError.value = null; // Reset về null khi mở modal

    itemForm.value = {
        item_id: "",
        quantity: 1
    };
    await fetchComboItems(combo.id);
}

const fetchComboItems = async (comboId) => {

    const res = await api.get(`admin/combos/${comboId}/items`);

    comboItems.value = res.data;

}

const fetchAvailableItems = async () => {

    const res = await api.get("admin/combos");

    availableItems.value =
        res.data.data.filter(item => item.type !== "combo");

}


onMounted(async () => {

    await fetchCombos();

    await fetchAvailableItems();

})


const addComboItem = async () => {
    try {
        itemFormError.value = null;

        const localErrors = {};

        if (!itemForm.value.item_id) {
            localErrors.item_id = ["Vui lòng chọn 1 loại đồ."];
        }

        if (!itemForm.value.quantity || itemForm.value.quantity < 1) {
            localErrors.quantity = ["Số lượng không được là số âm hoặc bằng 0."];
        }

        if (Object.keys(localErrors).length > 0) {
            itemFormError.value = localErrors;
            return; 
        }

        await api.post("admin/combo-items", {
            combo_id: currentCombo.value.id,
            item_id: itemForm.value.item_id,
            quantity: itemForm.value.quantity
        });

        itemForm.value = {
            item_id: "",
            quantity: 1
        };

        await fetchComboItems(currentCombo.value.id);
        alert("Thêm thành phần vào combo thành công!");

    } catch (error) {
        if (error.response && error.response.status === 422) {
            const resData = error.response.data;
            if (resData.errors) {
                itemFormError.value = resData.errors;
            } else if (resData.message) {
                alert(resData.message);
            }
        } else {
            alert("Đã có lỗi hệ thống xảy ra.");
            console.error(error);
        }
    }
}

const deleteComboItem = async (id) => {

    if (!confirm("Xóa thành phần?")) return;

    await api.delete(`admin/combo-items/${id}`);

    await fetchComboItems(currentCombo.value.id);

}


</script>

<style scoped>
.grid-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 10px;
}

.modal-body {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>
<style scoped>
.admin-movies-view-container {
    padding: 20px;
}

.list-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    padding: 20px;
}

.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.table-responsive-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}



.combo-img-thumb {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    display: block;
}

.movies-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.movies-table th,
.movies-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.cell-title {
    max-width: 180px;
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
}

.cell-title::-webkit-scrollbar {
    height: 3px;
}

.cell-title::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
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

.input-cine {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    box-sizing: border-box;
}

.table-row:hover {
    background-color: #f9fafb;
    transition: 0.3s;
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

.modal-overlay-cine {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.6) !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    z-index: 9999 !important;
}

.modal-content-cine {
    background: white !important;
    display: block !important;
    padding: 25px;
    border-radius: 15px;
    width: 450px;
    visibility: visible !important;
    opacity: 1 !important;
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

.error-msg {
    color: #dc2626;
    font-size: 0.85rem;
    margin-top: 5px;
    display: block;
    font-weight: 600;
}

.form-group {
    margin-bottom: 15px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-sizing: border-box;
}

.error-text {
    color: #dc2626;
    font-size: 0.8rem;
    margin-top: 5px;
    display: block;
}

.combo-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 20px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 0;
    color: #555;
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

.type-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.type-badge.combo {
    background: #fee2e2;
    color: #dc2626;
}

.type-badge.drink {
    background: #dbeafe;
    color: #2563eb;
}

.type-badge.food {
    background: #dcfce7;
    color: #15803d;
}

.modal-content-cine {
    width: 700px;
    max-width: 90%;
}
</style>