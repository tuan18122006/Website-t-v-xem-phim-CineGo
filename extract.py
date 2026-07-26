import re

with open('cuong_dashboard.vue', 'r', encoding='utf-16le') as f:
    content = f.read()

# Extract template
match_template = re.search(r'<div v-if="activeTab === \'orders\'" class="orders-tab-content">(.*?)(</div>\s*</div>\s*</main>)', content, re.DOTALL)
if match_template:
    template_str = '<template>\n  <div class="orders-tab-content">\n' + match_template.group(1) + '\n  </div>\n</template>\n\n'
else:
    print('Template not found!')
    exit(1)

# Extract script
match_script = re.search(r'(const orders = ref.*?)(const showOrderModal)', content, re.DOTALL)
match_script2 = re.search(r'(const loadOrders = async.*?)(const fetchOverview = async)', content, re.DOTALL)
script_str = '''<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';
import TicketPrintable from '../../components/TicketPrintable.vue';

''' + match_script.group(1) + 'const showOrderModal = ref(false);\nconst orderDetailLoading = ref(false);\nconst orderStatusUpdating = ref(false);\nconst selectedOrder = ref(null);\nconst selectedOrderDetail = ref(null);\nconst selectedOrderStatus = ref(\'\');\nconst movieOptions = ref([]);\n\n' + '''
const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};
const initials = (name) => {
  if (!name) return '?';
  const parts = name.split(' ');
  return (parts[0][0] + (parts[parts.length - 1][0] || '')).toUpperCase();
};
const statusLabel = (status) => {
  switch (status) {
    case 'pending': return 'Chờ thanh toán';
    case 'paid': return 'Đã thanh toán';
    case 'cancelled': return 'Đã hủy';
    case 'refunded': return 'Đã hoàn tiền';
    default: return 'Không xác định';
  }
};
const statusClass = (status) => {
  switch (status) {
    case 'pending': return 'is-pending';
    case 'paid': return 'is-paid';
    case 'cancelled': return 'is-cancelled';
    case 'refunded': return 'is-refunded';
    default: return '';
  }
};

const viewOrderDetail = async (order) => {
  showOrderModal.value = true;
  orderDetailLoading.value = true;
  selectedOrder.value = order;
  selectedOrderDetail.value = null;
  selectedOrderStatus.value = order.order_status || 'pending';
  try {
    const res = await api.get(`/admin/orders/${order.id}`);
    selectedOrderDetail.value = res.data;
    selectedOrderStatus.value = res.data.order_status || 'pending';
  } catch (err) {
    console.error('Load order detail error:', err);
  } finally {
    orderDetailLoading.value = false;
  }
};
''' + match_script2.group(1) + '''
onMounted(() => {
  loadOrders();
});
</script>

'''

# Extract style
match_style = re.search(r'(/\* ORDERS TAB STYLES \*/.*?)(/\* SIDEBAR STYLES \*/)', content, re.DOTALL)
style_str = '<style scoped>\n' + match_style.group(1) + '''
/* Lookup/modal styles */
.lookup-state { text-align: center; padding: 40px; color: #64748b; }
.lookup-spinner { width: 40px; height: 40px; border: 3px solid #f3f4f6; border-top-color: var(--accent-pink); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px; }
@keyframes spin { to { transform: rotate(360deg); } }
.lookup-state__art { font-size: 48px; display: block; margin-bottom: 16px; }
.report-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.report-table th, .report-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; }
.report-table th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 13px; text-transform: uppercase; }
.text-pink { color: var(--accent-pink); }
.font-bold { font-weight: 700; }
.muted { color: #94a3b8; }
.btn-ghost { background: transparent; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; cursor: pointer; color: #475569; font-weight: 600; transition: all 0.2s; }
.btn-ghost:hover { border-color: var(--accent-pink); color: var(--accent-pink); }
.status-pill-small { padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.status-pill-small.is-pending { background: #fffbeb; color: #d97706; }
.status-pill-small.is-paid { background: #ecfdf5; color: #059669; }
.status-pill-small.is-cancelled { background: #fef2f2; color: #dc2626; }
.status-pill-small.is-refunded { background: #f1f5f9; color: #475569; }

/* Modal styles */
.lk-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.lk-modal { background: #fff; border-radius: 16px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
.lk-modal__head { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px; }
.lk-modal__head h3 { margin: 0; font-size: 20px; color: #0f172a; }
.lk-modal__close { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; }
.lk-section { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px dashed #e2e8f0; }
.lk-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.lk-section__title { font-size: 15px; color: #475569; margin: 0 0 12px; }
.lk-kv { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 14px; }
.lk-kv span { color: #64748b; }
.lk-movie { display: flex; gap: 16px; align-items: center; }
.lk-movie img { width: 60px; height: 85px; object-fit: cover; border-radius: 8px; }
.status-select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; }
.btn-primary { width: 100%; padding: 12px; border: none; border-radius: 8px; background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); color: white; font-weight: 700; cursor: pointer; margin-top: 16px; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }

.lk-fade-enter-active, .lk-fade-leave-active { transition: opacity 0.3s ease; }
.lk-fade-enter-from, .lk-fade-leave-to { opacity: 0; }
</style>
'''

full_file = template_str + script_str + style_str

with open('cinego-frontend/src/views/admin/OrderManagementView.vue', 'w', encoding='utf-8') as f:
    f.write(full_file)

print('File created successfully!')
