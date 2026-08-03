import re

with open('cinego-frontend/src/views/client/ProfileView.vue', 'r', encoding='utf-8') as f:
    text = f.read()

# 1. State Variables
new_vars = """const loadingLoyalty = ref(false);
const loadingMyVouchers = ref(false);
const redeemableVouchers = ref([]);
const redeemableCombos = ref([]);
const myVouchers = ref([]);

const availableVouchersCount = computed(() => redeemableVouchers.value.length);
const availableCombosCount = computed(() => redeemableCombos.value.length);

const filteredMyVouchers = computed(() => {
  if (voucherFilter.value === 'unused') {
    return myVouchers.value.filter(v => v.pivot.status === 'unused');
  } else if (voucherFilter.value === 'used') {
    return myVouchers.value.filter(v => v.pivot.status === 'used' || v.pivot.status === 'expired');
  }
  return myVouchers.value;
});

const unusedVoucherCount = computed(() => {
  return myVouchers.value.filter(v => v.pivot.status === 'unused').length;
});"""

# Find the place to insert variables, right after const loyaltyData = ref({ ... });
if 'const loadingLoyalty' not in text:
    text = text.replace('const loyaltySubTab = ref(\'vouchers\');', new_vars + '\nconst loyaltySubTab = ref(\'vouchers\');')

# 2. Add API Fetch Functions
api_funcs = """const fetchLoyaltyItems = async () => {
  try {
    loadingLoyalty.value = true;
    const [voucherRes, comboRes] = await Promise.all([
      api.get('/loyalty/vouchers'),
      api.get('/loyalty/combos')
    ]);
    if (voucherRes.data.success) {
      redeemableVouchers.value = voucherRes.data.data;
    }
    if (comboRes.data.success) {
      redeemableCombos.value = comboRes.data.data;
    }
  } catch (error) {
    console.error("Lỗi lấy ưu đãi:", error);
  } finally {
    loadingLoyalty.value = false;
  }
};

const fetchMyVouchers = async () => {
  try {
    loadingMyVouchers.value = true;
    const res = await api.get('/client/my-vouchers');
    if (res.data.success) {
      myVouchers.value = res.data.data;
    }
  } catch (error) {
    console.error("Lỗi lấy ví voucher:", error);
  } finally {
    loadingMyVouchers.value = false;
  }
};

const redeemVoucher = async (voucherId) => {
  const result = await Swal.fire({
    title: 'Xác nhận đổi ưu đãi?',
    text: "Số điểm tương ứng sẽ bị trừ đi.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#e71a0f',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Đồng ý',
    cancelButtonText: 'Hủy'
  });

  if (result.isConfirmed) {
    try {
      const res = await api.post(`/loyalty/redeem-voucher/${voucherId}`);
      if (res.data.success) {
        toast('Đổi voucher thành công!', 'success');
        fetchLoyaltyProgress();
        fetchMyVouchers();
      } else {
        toast(res.data.message || 'Đổi voucher thất bại', 'error');
      }
    } catch (error) {
      toast(error.response?.data?.message || 'Lỗi hệ thống khi đổi voucher', 'error');
    }
  }
};

const redeemCombo = async (comboId) => {
  const result = await Swal.fire({
    title: 'Xác nhận đổi combo?',
    text: "Số điểm tương ứng sẽ bị trừ đi.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#e71a0f',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Đồng ý',
    cancelButtonText: 'Hủy'
  });

  if (result.isConfirmed) {
    try {
      const res = await api.post(`/loyalty/redeem-combo`, { combo_id: comboId });
      if (res.data.success) {
        toast('Đổi combo thành công!', 'success');
        fetchLoyaltyProgress();
      } else {
        toast(res.data.message || 'Đổi combo thất bại', 'error');
      }
    } catch (error) {
      toast(error.response?.data?.message || 'Lỗi hệ thống khi đổi combo', 'error');
    }
  }
};"""

if 'const fetchLoyaltyItems' not in text:
    text = text.replace('const fetchLoyaltyProgress = async () => {', api_funcs + '\n\nconst fetchLoyaltyProgress = async () => {')


# 3. Add to watch and onMounted
text = text.replace("watch(activeTab, (newTab) => {", """watch(activeTab, (newTab) => {
  if (newTab === 'loyalty') {
    fetchLoyaltyItems();
  }
  if (newTab === 'my_vouchers') {
    fetchMyVouchers();
  }""")

text = text.replace("fetchLoyaltyProgress();", "fetchLoyaltyProgress();\n  fetchLoyaltyItems();\n  fetchMyVouchers();")


# 4. Fix template hardcodings
text = text.replace('<p class="stat-value">{{ 0 }}</p>', '<p class="stat-value">{{ availableVouchersCount }}</p>', 1)
text = text.replace('<p class="stat-value">{{ 0 }}</p>', '<p class="stat-value">{{ availableCombosCount }}</p>', 1)
text = text.replace('@click="redeemVoucher(item.id)"', '@click="redeemVoucher(item.id)"')
text = text.replace('@click="redeemCombo(item.id)"', '@click="redeemCombo(item.id)"')


with open('cinego-frontend/src/views/client/ProfileView.vue', 'w', encoding='utf-8') as f:
    f.write(text)

print("Updates written successfully!")
