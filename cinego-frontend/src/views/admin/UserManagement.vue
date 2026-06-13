<template>
  <div class="um-container">
    <!-- CỘT TRÁI: SIDEBAR ĐIỀU HƯỚNG VẬN HÀNH (ĐÃ KHÔI PHỤC THEO YÊU CẦU) -->
    <div class="um-sidebar">
      <div class="sidebar-header">
        <IconShield class="header-logo" />
        <div>
          <h3>CineGo Hub</h3>
          <small>Hệ thống vận hành rạp</small>
        </div>
      </div>

      <div class="sidebar-divider"></div>

      <div class="menu-group">
        <span class="group-title">Quản lý Tài khoản</span>
        <button
          v-for="s in sections.slice(0, 3)"
          :key="s.key"
          class="menu-item"
          :class="{ active: activeSection === s.key }"
          @click="switchSection(s.key)"
        >
          <component :is="s.icon" class="menu-icon" />
          <span>{{ s.label }}</span>
          <span class="menu-badge">{{ roleCount(s.role) }}</span>
        </button>
      </div>

      <div class="menu-group">
        <span class="group-title">Đối soát & Phê duyệt</span>
        <button
          v-for="s in sections.slice(3)"
          :key="s.key"
          class="menu-item"
          :class="{ active: activeSection === s.key }"
          @click="switchSection(s.key)"
        >
          <component :is="s.icon" class="menu-icon" />
          <span>{{ s.label }}</span>
          <span class="menu-badge warning-badge" v-if="s.key === 'shift' && shiftAudits.length > 0">
            {{ shiftAudits.length }}
          </span>
          <span class="menu-badge alert-badge" v-if="s.key === 'refund' && pendingRefunds.length > 0">
            {{ pendingRefunds.length }}
          </span>
          <span class="menu-badge empty-badge" v-else-if="s.key === 'shift'">0</span>
          <span class="menu-badge empty-badge" v-else-if="s.key === 'refund'">0</span>
        </button>
      </div>
    </div>

    <!-- CỘT PHẢI: KHÔNG GIAN LÀM VIỆC CHÍNH -->
    <div class="um-main">
      <!-- HEADER TIÊU ĐỀ PHÂN KHU -->
      <div class="main-header">
        <div class="header-titles">
          <h2>{{ currentSectionTitle }}</h2>
          <p class="header-desc">{{ currentSectionDesc }}</p>
        </div>
        <button v-if="currentSection.addLabel" class="btn-add-premium" @click="openCreate">
          <IconPlus /> Thêm {{ currentSection.addLabel }}
        </button>
      </div>

      <div v-if="error" class="alert-error">{{ error }}</div>

      <!-- CHỈ SỐ THỐNG KÊ RỰC RỠ (ANALYTICS CARDS) -->
      <div class="stats-grid-premium">
        <div class="analytics-card" v-for="(card, index) in dashboardCards" :key="index">
          <div class="card-icon-wrap" :class="card.colorClass">
            <component :is="card.icon" />
          </div>
          <div class="card-info">
            <span class="card-label">{{ card.label }}</span>
            <span class="card-value">{{ card.value }}</span>
          </div>
        </div>
      </div>

      <!-- THANH CÔNG CỤ TÌM KIẾM & LỌC -->
      <div v-if="['customer', 'staff', 'admin'].includes(activeSection)" class="toolbar-card">
        <div class="search-box-premium">
          <IconSearch class="search-ic" />
          <input
            v-model="search"
            type="text"
            placeholder="Tìm kiếm theo họ tên, email hoặc số điện thoại..."
            class="search-input-premium"
          />
        </div>

        <div class="filters-wrap">
          <!-- Lọc hạng thành viên (Tab Khách hàng) -->
          <div v-if="activeSection === 'customer'" class="filter-select-wrap">
            <IconFilter class="filter-ic" />
            <select v-model="tierFilter" class="filter-select">
              <option value="">Tất cả hạng thành viên</option>
              <option value="Bronze">🏆 Bronze (Đồng)</option>
              <option value="Silver">🏆 Silver (Bạc)</option>
              <option value="Gold">🏆 Gold (Vàng)</option>
              <option value="Diamond">🏆 Diamond (Kim cương)</option>
            </select>
          </div>

          <!-- Lọc trạng thái hoạt động -->
          <div class="status-seg">
            <button :class="{ active: statusFilter === '' }" @click="statusFilter = ''">Tất cả</button>
            <button :class="{ active: statusFilter === 'active' }" @click="statusFilter = 'active'">Đang hoạt động</button>
            <button :class="{ active: statusFilter === 'locked' }" @click="statusFilter = 'locked'">Đã khóa</button>
          </div>
        </div>
      </div>

      <!-- BẢNG DỮ LIỆU: KHÁCH HÀNG & NHÂN VIÊN -->
      <template v-if="['customer', 'staff'].includes(activeSection)">
        <div class="panel-premium">
          <table class="data-table-premium">
            <thead>
              <tr>
                <th>{{ activeSection === 'staff' ? 'Nhân viên' : 'Khách hàng' }}</th>
                <th>Liên hệ</th>
                <th>{{ activeSection === 'staff' ? 'Ca trực POS' : 'Hạng thành viên' }}</th>
                <th>Vai trò</th>
                <th>Trạng thái hoạt động</th>
                <th class="ta-right">Hành động</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in filteredUsers" :key="u.id">
                <td>
                  <div class="user-identity-cell">
                    <span
                      class="avatar-premium"
                      :style="{ background: avatarColor(u.name), cursor: 'pointer' }"
                      title="Click xem hồ sơ chi tiết"
                      @click="openDetail(u)"
                    >{{ initials(u.name) }}</span>
                    <div class="identity-meta">
                      <strong class="click-name" @click="openDetail(u)">{{ u.name }}</strong>
                      <small>{{ u.email }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="phone-link"><IconPhone class="inline-ic" /> {{ u.phone || '—' }}</span>
                </td>
                <td>
                  <template v-if="u.role === 'customer'">
                    <span class="premium-badge tier" :class="'tier-' + (u.membership_tier || 'Bronze').toLowerCase()">
                      🏆 Hạng {{ u.membership_tier || 'Bronze' }}
                    </span>
                  </template>
                  <template v-else>
                    <span class="premium-badge" :class="u.work_status === 'on_shift' ? 'status-active' : 'role-customer'">
                      💼 {{ u.work_status === 'on_shift' ? 'Đang trực POS' : 'Chưa vào ca' }}
                    </span>
                  </template>
                </td>
                <td><span class="premium-badge role" :class="'role-' + u.role">{{ roleLabel(u.role) }}</span></td>
                <td>
                  <span class="premium-badge" :class="u.status === 'locked' ? 'status-locked' : 'status-active'">
                    <i class="dot"></i>{{ u.status === 'locked' ? 'Đã khóa' : 'Hoạt động' }}
                  </span>
                  <div v-if="u.status === 'locked' && u.lock_reason" class="lock-reason-banner" :title="u.lock_reason">
                    Lý do: {{ u.lock_reason }}
                  </div>
                </td>
                <td class="ta-right action-buttons-cell">
                  <button class="btn-action-icon" title="Hồ sơ 360" @click="openDetail(u)"><IconEye /></button>
                  <button class="btn-action-icon" title="Chỉnh sửa" @click="openEdit(u)"><IconEdit /></button>
                  <button v-if="activeSection === 'customer'" class="btn-action-text mint" @click="promoteToStaff(u)">
                    <IconUserPlus /> Lên Nhân viên
                  </button>
                  <template v-else>
                    <button class="btn-action-text red" @click="changeRole(u, 'admin')"><IconArrowUp /> Lên Admin</button>
                    <button class="btn-action-text" @click="changeRole(u, 'customer')"><IconArrowDown /> Xuống Khách</button>
                  </template>
                  <button class="btn-action-icon" :title="u.status === 'locked' ? 'Mở khóa' : 'Khóa tài khoản'" @click="toggleStatus(u)">
                    <component :is="u.status === 'locked' ? IconUnlock : IconLock" />
                  </button>
                </td>
              </tr>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="6">
                  <div class="empty-state-card">
                    <IconUsers class="empty-icon" />
                    <p>Không tìm thấy danh sách hợp lệ.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="panel-footer">Đang hiển thị {{ filteredUsers.length }} / {{ roleCount(currentSection.role) }} tài khoản</div>
        </div>
      </template>

      <!-- KHÔNG GIAN DỮ LIỆU: ADMIN GRID -->
      <template v-else-if="activeSection === 'admin'">
        <div class="security-info-banner">
          <IconShield class="sib-icon" />
          <span>Khu vực bảo mật cấp cao: Không được phép tự khóa hoặc hạ quyền tài khoản của chính mình.</span>
        </div>

        <div class="admin-cards-grid">
          <div v-for="u in filteredUsers" :key="u.id" class="admin-profile-card">
            <div class="apc-header-gradient"></div>
            <div class="apc-body">
              <span
                class="avatar-premium xl"
                :style="{ background: avatarColor(u.name), cursor: 'pointer' }"
                title="Click xem hồ sơ chi tiết"
                @click="openDetail(u)"
              >{{ initials(u.name) }}</span>
              <h4 class="click-name" @click="openDetail(u)">{{ u.name }}</h4>
              <p class="apc-email">{{ u.email }}</p>
              
              <div class="apc-badges">
                <span class="premium-badge role role-admin"><IconCrown /> Quản trị</span>
                <span class="premium-badge" :class="u.status === 'locked' ? 'status-locked' : 'status-active'">
                  <i class="dot"></i>{{ u.status === 'locked' ? 'Đã khóa' : 'Hoạt động' }}
                </span>
                <span v-if="isSelf(u)" class="premium-badge badge-self">Bạn đang trực</span>
              </div>

              <div class="apc-details">
                <div class="apcd-row"><span><IconPhone /> SĐT</span><strong>{{ u.phone || '—' }}</strong></div>
                <div class="apcd-row"><span>🧑 Tuổi</span><strong>{{ u.age || '—' }}</strong></div>
                <div class="apcd-row"><span><IconCalendar /> Ngày tham gia</span><strong>{{ formatDate(u.created_at) }}</strong></div>
              </div>

              <div class="apc-actions">
                <button class="btn-card-action" @click="openEdit(u)"><IconEdit /> Sửa</button>
                <button class="btn-card-action" :disabled="isSelf(u)" @click="changeRole(u, 'staff')"><IconArrowDown /> Hạ NV</button>
                <button class="btn-card-action danger" :disabled="isSelf(u)" @click="toggleStatus(u)">
                  <component :is="u.status === 'locked' ? IconUnlock : IconLock" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- KHÔNG GIAN DỮ LIỆU: ĐỐI SOÁT CA TRỰC -->
      <template v-else-if="activeSection === 'shift'">
        <div class="panel-premium">
          <table class="data-table-premium">
            <thead>
              <tr>
                <th>Nhân sự quầy</th>
                <th>Ca làm</th>
                <th>Vị trí quầy</th>
                <th>Giờ Check-in</th>
                <th>Giờ Check-out</th>
                <th>Khai báo thực tế</th>
                <th>Hệ thống đối soát</th>
                <th>Lệch tiền mặt</th>
                <th class="ta-right">Hành động chốt đối soát</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in shiftAudits" :key="s.id">
                <td>
                  <div class="user-identity-cell">
                    <span
                      class="avatar-premium"
                      :style="{ background: avatarColor(s.user.name), cursor: 'pointer' }"
                      @click="openDetail(s.user)"
                    >{{ initials(s.user.name) }}</span>
                    <div class="identity-meta">
                      <strong class="click-name" @click="openDetail(s.user)">{{ s.user.name }}</strong>
                      <small>{{ s.user.email }}</small>
                    </div>
                  </div>
                </td>
                <td><strong>{{ s.shift_name }}</strong></td>
                <td><span class="premium-badge role-staff">📍 {{ s.workstation }}</span></td>
                <td class="td-muted">{{ formatDateTime(s.checkin_time) }}</td>
                <td class="td-muted">{{ formatDateTime(s.checkout_time) }}</td>
                <td>
                  <div class="shift-reported-wrap">
                    <div>💵 Tiền mặt: <strong>{{ formatCurrency(s.reported_cash) }}</strong></div>
                    <div>💳 C.Khoản: <strong>{{ formatCurrency(s.reported_transfer) }}</strong></div>
                  </div>
                </td>
                <td>
                  <strong class="text-violet">{{ formatCurrency(s.system_revenue) }}</strong>
                </td>
                <td>
                  <span class="diff-amount" :class="calculateDiff(s) !== 0 ? 'text-red font-bold' : 'text-mint'">
                    {{ calculateDiff(s) > 0 ? '+' : '' }}{{ formatCurrency(calculateDiff(s)) }}
                  </span>
                </td>
                <td class="ta-right action-buttons-cell">
                  <button class="btn-action-text mint" @click="auditShift(s.id, 'closed', 'Khớp số liệu chốt ca')">
                    <IconCheck /> Duyệt chốt ca
                  </button>
                  <button class="btn-action-text red" @click="auditShift(s.id, 'rejected', 'Sai lệch số liệu đối soát')">
                    <IconLock /> Trả lại ca
                  </button>
                </td>
              </tr>
              <tr v-if="shiftAudits.length === 0">
                <td colspan="9">
                  <div class="empty-state-card">
                    <IconClock class="empty-icon" />
                    <p>Không có ca trực nào đang chờ duyệt chốt đối soát.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- KHÔNG GIAN DỮ LIỆU: DUYỆT HOÀN VÉ -->
      <template v-else-if="activeSection === 'refund'">
        <div class="panel-premium">
          <table class="data-table-premium">
            <thead>
              <tr>
                <th>Mã hóa đơn</th>
                <th>Nhân viên đề xuất</th>
                <th style="width: 250px;">Lý do hoàn vé</th>
                <th>Hình thức</th>
                <th>Số tiền hoàn</th>
                <th>Thời gian yêu cầu</th>
                <th class="ta-right">Duyệt yêu cầu</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in pendingRefunds" :key="r.id">
                <td><strong class="text-violet">#{{ r.booking?.booking_code || r.booking_id }}</strong></td>
                <td>
                  <div class="user-identity-cell">
                    <span
                      class="avatar-premium"
                      :style="{ background: avatarColor(r.requester?.name), cursor: 'pointer' }"
                      @click="openDetail(r.requester)"
                    >{{ initials(r.requester?.name) }}</span>
                    <div class="identity-meta">
                      <strong class="click-name" @click="openDetail(r.requester)">{{ r.requester?.name }}</strong>
                      <small>{{ r.requester?.email }}</small>
                    </div>
                  </div>
                </td>
                <td><p class="refund-reason-paragraph" :title="r.reason">{{ r.reason }}</p></td>
                <td><span class="premium-badge role-customer">{{ r.booking?.payment_method?.toUpperCase() }}</span></td>
                <td><strong class="text-red">{{ formatCurrency(r.booking?.total_amount) }}</strong></td>
                <td class="td-muted">{{ formatDateTime(r.created_at) }}</td>
                <td class="ta-right action-buttons-cell">
                  <button class="btn-action-text mint" @click="approveRefund(r.id, 'approved')">
                    <IconCheck /> Xác nhận hoàn vé
                  </button>
                  <button class="btn-action-text red" @click="approveRefund(r.id, 'rejected')">
                    <IconLock /> Từ chối hoàn
                  </button>
                </td>
              </tr>
              <tr v-if="pendingRefunds.length === 0">
                <td colspan="7">
                  <div class="empty-state-card">
                    <IconTicket class="empty-icon" />
                    <p>Không có yêu cầu hoàn tiền vé nào đang chờ phê duyệt.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- MODAL FORM THÊM/SỬA TÀI KHOẢN -->
    <div v-if="showModal" class="modal-overlay-premium" @click.self="closeModal">
      <div class="modal-box-premium">
        <div class="modal-header-premium">
          <h3>{{ isEdit ? 'Cập nhật thông tin' : 'Tạo mới ' + currentSection.addLabel }}</h3>
          <button class="btn-close-modal" @click="closeModal">✕</button>
        </div>
        <form @submit.prevent="submitForm" class="form-premium">
          <div class="form-group-premium">
            <label>Họ và tên</label>
            <input v-model="form.name" type="text" required placeholder="Nguyễn Văn A" class="input-form-premium" />
          </div>

          <div class="form-group-premium">
            <label>Địa chỉ Email</label>
            <input v-model="form.email" type="email" required placeholder="email@example.com" class="input-form-premium" />
          </div>

          <div class="form-group-premium">
            <label>Mật khẩu đăng nhập {{ isEdit ? '(để trống nếu không thay đổi)' : '' }}</label>
            <input v-model="form.password" type="password" :required="!isEdit" placeholder="••••••••" class="input-form-premium" />
          </div>

          <div class="form-row-premium">
            <div class="form-group-premium">
              <label>Số điện thoại liên hệ</label>
              <input v-model="form.phone" type="text" placeholder="0987xxxxxx" class="input-form-premium" />
            </div>
            <div class="form-group-premium">
              <label>Tuổi</label>
              <input v-model="form.age" type="number" min="0" max="120" placeholder="Nhập số tuổi" class="input-form-premium" />
            </div>
          </div>

          <div class="form-row-premium">
            <div class="form-group-premium">
              <label>Vai trò truy cập</label>
              <select v-model="form.role" required class="select-form-premium">
                <option value="admin">Quản trị viên (Admin)</option>
                <option value="staff">Nhân viên rạp (Staff)</option>
                <option value="customer">Khách hàng rạp (User)</option>
              </select>
            </div>
            <div class="form-group-premium">
              <label>Trạng thái kích hoạt</label>
              <select v-model="form.status" required class="select-form-premium">
                <option value="active">Hoạt động bình thường</option>
                <option value="locked">Khóa tài khoản</option>
              </select>
            </div>
          </div>

          <div class="modal-actions-premium">
            <button type="button" class="btn-cancel-premium" @click="closeModal">Hủy bỏ</button>
            <button type="submit" class="btn-save-premium" :disabled="loading">
              {{ loading ? 'Đang lưu trữ...' : 'Lưu thông tin' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CRM HỒ SƠ CHI TIẾT 360 -->
    <div v-if="showDetail" class="modal-overlay-premium" @click.self="closeDetail">
      <div class="modal-box-premium crm-detail-box">
        <div class="modal-header-premium">
          <h3>Hồ sơ khách hàng 360°</h3>
          <button class="btn-close-modal" @click="closeDetail">✕</button>
        </div>

        <div v-if="detailLoading" class="loading-wrap-premium">
          <div class="spinner-premium"></div>
          <p>Đang liên kết dữ liệu hồ sơ...</p>
        </div>

        <div v-else-if="detail" class="crm-layout-grid">
          <!-- CỘT TRÁI: THẺ THÀNH VIÊN VÀ THÔNG TIN NHANH -->
          <div class="crm-sidebar-card">
            <!-- THẺ THÀNH VIÊN GRADIENT 3D TILT -->
            <div
              ref="cardRef"
              class="gilded-member-card"
              :class="'tier-bg-' + (detail.user.membership_tier || 'Bronze').toLowerCase()"
              @mousemove="handleMouseMove"
              @mouseleave="handleMouseLeave"
            >
              <div class="gmc-glow"></div>
              <div class="gmc-chip-wrap">
                <span class="gmc-chip-icon">💳</span>
              </div>
              <div class="gmc-header">
                <IconShield class="gmc-logo" />
                <span class="gmc-brand">CineGo Card</span>
              </div>
              <div class="gmc-body">
                <span class="gmc-title">{{ detail.user.name }}</span>
                <span class="gmc-email">{{ detail.user.email }}</span>
              </div>
              <div class="gmc-footer">
                <span class="gmc-tier">🏆 {{ detail.user.membership_tier || 'Bronze' }} MEMBER</span>
                <span class="gmc-points">{{ detail.user.loyalty_points }} PTS</span>
              </div>
            </div>

            <!-- CHỈNH HẠNG THÀNH VIÊN -->
            <div v-if="detail.user.role === 'customer'" class="crm-quick-control">
              <label class="control-label">Thay đổi hạng thành viên</label>
              <select v-model="detail.user.membership_tier" class="select-crm-premium" @change="updateUserTier(detail.user.id, detail.user.membership_tier)">
                <option value="Bronze">Bronze (Đồng)</option>
                <option value="Silver">Silver (Bạc)</option>
                <option value="Gold">Gold (Vàng)</option>
                <option value="Diamond">Diamond (Kim cương)</option>
              </select>
            </div>

            <!-- THÔNG TIN CHI TIẾT CÁ NHÂN -->
            <div class="crm-quick-stats">
              <div class="quick-stat-row"><span>Họ và tên:</span><strong>{{ detail.user.name }}</strong></div>
              <div class="quick-stat-row"><span>Số điện thoại:</span><strong>{{ detail.user.phone || 'Chưa cập nhật' }}</strong></div>
              <div class="quick-stat-row"><span>Tuổi:</span><strong>{{ detail.user.age || 'Chưa cập nhật' }}</strong></div>
              <div class="quick-stat-row"><span>Tổng chi tiêu:</span><strong>{{ formatCurrency(detail.stats.total_spent) }}</strong></div>
              <div class="quick-stat-row"><span>Vé đã mua:</span><strong>{{ detail.stats.total_tickets }} vé</strong></div>
              <div class="quick-stat-row"><span>Hóa đơn đặt:</span><strong>{{ detail.stats.total_bookings }} đơn</strong></div>
            </div>

            <!-- DANGER ZONE (ẨN DANH GDPR) -->
            <div v-if="detail.user.role !== 'admin' && !detail.user.is_anonymized" class="crm-danger-zone">
              <span class="danger-title">🗑️ Bảo mật dữ liệu (GDPR)</span>
              <p>Ẩn hoàn toàn thông tin cá nhân của khách hàng vĩnh viễn (giữ lại hóa đơn phục vụ tài chính).</p>
              <button type="button" class="btn-anonymize-premium" @click="anonymizeUser(detail.user.id)">
                Kích hoạt ẩn danh tính
              </button>
            </div>
          </div>

          <!-- CỘT PHẢI: TABS LỊCH SỬ HOẠT ĐỘNG -->
          <div class="crm-main-tabs">
            <div class="crm-tabs-header">
              <button :class="{ active: crmTab === 'bookings' }" @click="crmTab = 'bookings'">Lịch sử vé</button>
              <button v-if="detail.user.role === 'customer'" :class="{ active: crmTab === 'vouchers' }" @click="crmTab = 'vouchers'">Ví Voucher</button>
              <button :class="{ active: crmTab === 'devices' }" @click="crmTab = 'devices'">Log đăng nhập</button>
              <button :class="{ active: crmTab === 'reviews' }" @click="crmTab = 'reviews'">Bình luận</button>
            </div>

            <div class="crm-tabs-content">
              <!-- TAB: BOOKINGS -->
              <div v-if="crmTab === 'bookings'">
                <h4 class="content-title">Danh sách giao dịch mua vé</h4>
                <div v-if="detail.stats.total_bookings === 0" class="empty-tab-text">Chưa thực hiện mua vé nào.</div>
                <div v-else class="crm-table-wrapper">
                  <table class="crm-table">
                    <thead>
                      <tr>
                        <th>Mã đơn</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="b in detail.user.bookings" :key="b.id">
                        <td><strong>#{{ b.booking_code }}</strong></td>
                        <td>{{ formatCurrency(b.total_amount) }}</td>
                        <td><span class="badge" :class="b.payment_status === 'paid' ? 'status-active' : 'status-locked'">{{ b.payment_status }}</span></td>
                        <td>{{ b.booking_status }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- TAB: VOUCHERS -->
              <div v-if="crmTab === 'vouchers' && detail.user.role === 'customer'">
                <h4 class="content-title">Quản lý mã ưu đãi trong ví</h4>
                <div class="crm-gift-voucher-box">
                  <input v-model="newVoucherCode" type="text" placeholder="Nhập mã voucher (VD: CINECO50)" class="crm-voucher-input" />
                  <button type="button" class="btn-gift-premium" @click="giftVoucherToUser(detail.user.id)">Tặng mã</button>
                </div>

                <div v-if="!detail.vouchers || detail.vouchers.length === 0" class="empty-tab-text">Ví voucher hiện đang trống.</div>
                <div v-else class="crm-voucher-grid">
                  <div v-for="v in detail.vouchers" :key="v.id" class="crm-voucher-ticket">
                    <span class="cvt-val">{{ v.discount_type === 'percentage' ? v.discount_value + '%' : formatCurrency(v.discount_value) }}</span>
                    <div class="cvt-meta">
                      <strong>{{ v.code }}</strong>
                      <small>Đơn từ: {{ formatCurrency(v.min_spend) }}</small>
                    </div>
                    <button type="button" class="btn-revoke-mini" title="Thu hồi voucher" @click="revokeVoucherFromUser(detail.user.id, v.id)">
                      ✕
                    </button>
                  </div>
                </div>
              </div>

              <!-- TAB: DEVICES LOG -->
              <div v-if="crmTab === 'devices'">
                <h4 class="content-title">Lịch sử thiết bị & địa chỉ IP đăng nhập</h4>
                <div v-if="!detail.device_logs || detail.device_logs.length === 0" class="empty-tab-text">Không có lịch sử thiết bị.</div>
                <div v-else class="crm-device-list">
                  <div v-for="log in detail.device_logs" :key="log.id" class="crm-device-item">
                    <div class="cdi-header">
                      <span>🌐 IP: <strong>{{ log.ip_address }}</strong></span>
                      <span class="td-muted">🕒 {{ formatDateTime(log.last_login_at) }}</span>
                    </div>
                    <p class="cdi-ua">{{ log.user_agent }}</p>
                  </div>
                </div>
              </div>

              <!-- TAB: REVIEWS -->
              <div v-if="crmTab === 'reviews'">
                <h4 class="content-title">Bình luận & Đánh giá rạp phim</h4>
                <div v-if="detail.reviews.length === 0" class="empty-tab-text">Chưa thực hiện bình luận nào.</div>
                <ul class="crm-review-list">
                  <li v-for="r in detail.reviews" :key="r.id">
                    <div class="crl-head">
                      <strong>{{ r.movie?.title || 'Phim #' + r.movie_id }}</strong>
                      <span class="review-stars">⭐ {{ r.rating }}/5</span>
                    </div>
                    <p class="crl-comment">{{ r.comment || '(Không có nội dung đánh giá)' }}</p>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- HỘP THOẠI KHÁCH HÀNG (CUSTOM DIALOG & PROMPT OVERLAY) -->
    <div v-if="dialog.visible" class="custom-dialog-overlay" @click.self="closeDialog(false)">
      <div class="custom-dialog-box animate-scale-up">
        <div class="dialog-header-premium">
          <h3>{{ dialog.title }}</h3>
        </div>
        <div class="dialog-body-premium">
          <p class="dialog-message">{{ dialog.message }}</p>
          <div v-if="dialog.type === 'prompt'" class="dialog-prompt-field">
            <input
              v-model="dialog.inputValue"
              type="text"
              :placeholder="dialog.placeholder"
              class="input-form-premium prompt-input"
              @keyup.enter="closeDialog(true)"
              ref="dialogInputRef"
              autofocus
            />
          </div>
        </div>
        <div class="dialog-actions-premium">
          <button class="btn-cancel-premium" @click="closeDialog(false)">Hủy bỏ</button>
          <button
            class="btn-save-premium"
            :class="{ 'btn-danger-premium': dialog.isDanger }"
            @click="closeDialog(true)"
          >
            {{ dialog.confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h, watch } from 'vue';
import api from '../../api/axios';
import { useAuthStore } from '../../stores/auth';

/* ---------- Icon SVG (Lucide custom render) ---------- */
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
const IconClock    = mk([['circle',{cx:12,cy:12,r:10}],['polyline',{points:'12 6 12 12 16 14'}]]);
const IconTicket   = mk([['path',{d:'M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z'}],['path',{d:'M13 5v14'}],['path',{d:'M9 9h0'}],['path',{d:'M9 13h0'}],['path',{d:'M9 17h0'}]]);
const IconFilter   = mk([['polygon',{points:'22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3'}]]);

const authStore = useAuthStore();

const allUsers = ref([]);
const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const loading = ref(false);
const error = ref(null);

const search = ref('');
const statusFilter = ref('');
const tierFilter = ref('');
const crmTab = ref('bookings');

const shiftAudits = ref([]);
const pendingRefunds = ref([]);
const newVoucherCode = ref('');

const sections = [
  { key: 'customer', label: 'Khách hàng', icon: IconUser,      role: 'customer', addLabel: 'Khách Hàng', title: 'Danh mục Khách hàng', desc: 'Quản lý thông tin, phân hạng thành viên, khóa/mở khóa tài khoản khách hàng.' },
  { key: 'staff',    label: 'Nhân viên',  icon: IconBriefcase, role: 'staff',    addLabel: 'Nhân Viên', title: 'Nhân sự vận hành', desc: 'Danh sách nhân sự trực tuyến đầu, theo dõi ca làm việc và quầy trực.' },
  { key: 'admin',    label: 'Quản trị',   icon: IconCrown,     role: 'admin',    addLabel: 'Quản Trị Viên', title: 'Quản trị viên hệ thống', desc: 'Tài khoản quản lý cấp cao có toàn quyền quản trị và thiết lập cấu hình rạp.' },
  { key: 'shift',    label: 'Đối soát ca', icon: IconClock,     role: null,       addLabel: null, title: 'Đối soát ca trực POS', desc: 'Kiểm soát doanh thu chốt ca của nhân viên quầy, đối sánh giữa tiền thực tế khai báo và hệ thống.' },
  { key: 'refund',   label: 'Duyệt hoàn vé', icon: IconTicket,   role: null,       addLabel: null, title: 'Duyệt yêu cầu hoàn vé', desc: 'Danh sách các yêu cầu hoàn tiền vé và hủy ghế ngồi từ nhân viên gửi lên.' },
];

const activeSection = ref('customer');
const currentSection = computed(() => sections.find(s => s.key === activeSection.value));

const currentSectionTitle = computed(() => currentSection.value.title);
const currentSectionDesc = computed(() => currentSection.value.desc);

const showDetail = ref(false);
const detailLoading = ref(false);
const detail = ref(null);

const form = ref({ name: '', email: '', password: '', phone: '', role: 'customer', status: 'active', age: '' });

/* ---------- Custom Dialog State ---------- */
const dialog = ref({
  visible: false,
  type: 'confirm', // confirm | prompt
  title: '',
  message: '',
  placeholder: '',
  inputValue: '',
  confirmLabel: 'Xác nhận',
  isDanger: false,
  resolve: null
});

const showCustomConfirm = (title, message, isDanger = false, confirmLabel = 'Xác nhận') => {
  return new Promise((resolve) => {
    dialog.value = {
      visible: true,
      type: 'confirm',
      title,
      message,
      placeholder: '',
      inputValue: '',
      confirmLabel,
      isDanger,
      resolve
    };
  });
};

const showCustomPrompt = (title, message, placeholder = '', confirmLabel = 'Đồng ý') => {
  return new Promise((resolve) => {
    dialog.value = {
      visible: true,
      type: 'prompt',
      title,
      message,
      placeholder,
      inputValue: '',
      confirmLabel,
      isDanger: false,
      resolve
    };
  });
};

const closeDialog = (confirmed) => {
  if (!dialog.value.visible) return;
  dialog.value.visible = false;
  if (dialog.value.resolve) {
    if (confirmed) {
      if (dialog.value.type === 'prompt') {
        dialog.value.resolve(dialog.value.inputValue);
      } else {
        dialog.value.resolve(true);
      }
    } else {
      dialog.value.resolve(null);
    }
  }
};

/* ---------- 3D Card Interactive Tilt Effect ---------- */
const cardRef = ref(null);
const handleMouseMove = (e) => {
  const card = cardRef.value;
  if (!card) return;
  const rect = card.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const xc = rect.width / 2;
  const yc = rect.height / 2;
  const angleX = (yc - y) / 10;
  const angleY = (x - xc) / 10;
  card.style.transform = `perspective(500px) rotateX(${angleX}deg) rotateY(${angleY}deg)`;
};
const handleMouseLeave = () => {
  const card = cardRef.value;
  if (card) {
    card.style.transform = 'perspective(500px) rotateX(0deg) rotateY(0deg)';
  }
};

/* ---------- Thẻ chỉ số phân tích trên Dashboard (Analytics Cards) ---------- */
const dashboardCards = computed(() => {
  if (activeSection.value === 'customer') {
    const total = roleCount('customer');
    const vipCount = allUsers.value.filter(u => u.role === 'customer' && ['Gold', 'Diamond'].includes(u.membership_tier)).length;
    const lockedCount = allUsers.value.filter(u => u.role === 'customer' && u.status === 'locked').length;
    return [
      { label: 'Tổng số khách hàng', value: total + ' người', icon: IconUsers, colorClass: 'card-blue' },
      { label: 'Thành viên VIP (Vàng/Kim Cương)', value: vipCount + ' khách', icon: IconCrown, colorClass: 'card-gold' },
      { label: 'Khách hàng bị khóa', value: lockedCount + ' tài khoản', icon: IconLock, colorClass: 'card-red' },
    ];
  } else if (activeSection.value === 'staff') {
    const total = roleCount('staff');
    const onShift = allUsers.value.filter(u => u.role === 'staff' && u.work_status === 'on_shift').length;
    const locked = allUsers.value.filter(u => u.role === 'staff' && u.status === 'locked').length;
    return [
      { label: 'Tổng nhân viên', value: total + ' nhân sự', icon: IconBriefcase, colorClass: 'card-violet' },
      { label: 'Nhân viên đang làm ca', value: onShift + ' quầy POS', icon: IconCheck, colorClass: 'card-mint' },
      { label: 'Tài khoản nhân sự bị khóa', value: locked + ' acc', icon: IconLock, colorClass: 'card-red' },
    ];
  } else if (activeSection.value === 'admin') {
    const total = roleCount('admin');
    return [
      { label: 'Tổng số quản trị viên', value: total + ' người', icon: IconCrown, colorClass: 'card-gold' },
      { label: 'Quyền hạn bảo mật', value: 'Cấp cao nhất', icon: IconShield, colorClass: 'card-blue' },
      { label: 'Hệ thống rạp CineGo', value: '1 Cơ sở duy nhất', icon: IconUsers, colorClass: 'card-violet' },
    ];
  } else if (activeSection.value === 'shift') {
    const totalPending = shiftAudits.value.length;
    const totalSystemRevenue = shiftAudits.value.reduce((sum, s) => sum + parseFloat(s.system_revenue || 0), 0);
    const totalDiff = shiftAudits.value.reduce((sum, s) => sum + calculateDiff(s), 0);
    return [
      { label: 'Ca trực chờ chốt đối soát', value: totalPending + ' ca trực', icon: IconClock, colorClass: 'card-violet' },
      { label: 'Doanh thu ca hệ thống ghi nhận', value: formatCurrency(totalSystemRevenue), icon: IconCheck, colorClass: 'card-mint' },
      { label: 'Tổng chênh lệch thực tế', value: formatCurrency(totalDiff), icon: totalDiff !== 0 ? 'card-red' : 'card-blue' },
    ];
  } else if (activeSection.value === 'refund') {
    const totalRefundPending = pendingRefunds.value.length;
    const totalCashToRefund = pendingRefunds.value.reduce((sum, r) => sum + parseFloat(r.booking?.total_amount || 0), 0);
    return [
      { label: 'Yêu cầu hoàn vé chờ duyệt', value: totalRefundPending + ' yêu cầu', icon: IconTicket, colorClass: 'card-gold' },
      { label: 'Tổng giá trị hoàn trả', value: formatCurrency(totalCashToRefund), icon: IconPlus, colorClass: 'card-red' },
      { label: 'Trạng thái giải phóng ghế', value: 'Tự động 100%', icon: IconShield, colorClass: 'card-blue' },
    ];
  }
  return [];
});

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
    if (activeSection.value === 'customer' && tierFilter.value && u.membership_tier !== tierFilter.value) return false;
    if (kw) {
      const hay = `${u.name} ${u.email} ${u.phone || ''}`.toLowerCase();
      if (!hay.includes(kw)) return false;
    }
    return true;
  });
});

const isSelf = (u) => authStore.user?.id === u.id;

const resetForm = () => {
  form.value = { name: '', email: '', password: '', phone: '', role: currentSection.value.role, status: 'active', age: '' };
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
  tierFilter.value = '';
  error.value = null;
  
  if (key === 'shift') {
    fetchShiftAudits();
  } else if (key === 'refund') {
    fetchPendingRefunds();
  } else {
    fetchUsers();
  }
};

const openCreate = () => {
  isEdit.value = false; editingId.value = null; resetForm(); showModal.value = true;
};

const openEdit = (u) => {
  isEdit.value = true; editingId.value = u.id;
  form.value = { name: u.name, email: u.email, password: '', phone: u.phone || '', role: u.role, status: u.status, age: u.age || '' };
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
  let lockReason = '';
  if (u.status !== 'locked') {
    const res = await showCustomPrompt(
      'Khóa tài khoản',
      `Vui lòng nhập lý do khóa cho tài khoản "${u.name}":`,
      'Lý do khóa tài khoản...'
    );
    if (res === null) return;
    lockReason = res.trim() || 'Bị khóa bởi quản lý';
  } else {
    const confirmed = await showCustomConfirm(
      'Mở khóa tài khoản',
      `Bạn có chắc chắn muốn mở khóa tài khoản "${u.name}"?`
    );
    if (!confirmed) return;
  }
  try { 
    await api.patch(`/admin/users/${u.id}/status`, { lock_reason: lockReason }); 
    await fetchUsers(); 
  }
  catch (err) { error.value = 'Không đổi được trạng thái.'; }
};

const promoteToStaff = async (u) => {
  const confirmed = await showCustomConfirm('Nâng cấp tài khoản', `Nâng cấp "${u.name}" lên Nhân viên (Staff)?`);
  if (!confirmed) return;
  await changeRole(u, 'staff');
};

const changeRole = async (u, role) => {
  if (isSelf(u)) { error.value = 'Không thể tự đổi vai trò của chính bạn.'; return; }
  if (u.role === role) return;
  const confirmed = await showCustomConfirm('Thay đổi quyền truy cập', `Đổi vai trò "${u.name}" thành ${roleLabel(role)}?`);
  if (!confirmed) return;
  try { await api.patch(`/admin/users/${u.id}/role`, { role }); await fetchUsers(); }
  catch (err) { error.value = 'Không đổi được vai trò.'; }
};

const openDetail = async (u) => {
  showDetail.value = true; detailLoading.value = true; detail.value = null;
  newVoucherCode.value = '';
  crmTab.value = 'bookings';
  try { const res = await api.get(`/admin/users/${u.id}`); detail.value = res.data.data; }
  catch (err) { error.value = 'Không tải được chi tiết tài khoản.'; showDetail.value = false; }
  finally { detailLoading.value = false; }
};

const closeDetail = () => { showDetail.value = false; detail.value = null; };

const fetchShiftAudits = async () => {
  try {
    const res = await api.get('/admin/shifts/pending-audits');
    shiftAudits.value = res.data.data;
  } catch (err) {
    error.value = 'Không tải được danh sách ca trực đối soát.';
  }
};

const fetchPendingRefunds = async () => {
  try {
    const res = await api.get('/admin/refunds/pending');
    pendingRefunds.value = res.data.data;
  } catch (err) {
    error.value = 'Không tải được danh sách yêu cầu hoàn vé.';
  }
};

const auditShift = async (id, status, defaultNote) => {
  const note = await showCustomPrompt(
    status === 'closed' ? 'Duyệt chốt ca làm việc' : 'Trả lại ca làm việc',
    `Nhập ghi chú chốt đối soát ca trực:`,
    defaultNote
  );
  if (note === null) return;
  try {
    await api.post(`/admin/shifts/${id}/audit`, { status, audit_note: note });
    await fetchShiftAudits();
  } catch (err) {
    error.value = 'Có lỗi khi duyệt đối soát ca trực.';
  }
};

const approveRefund = async (id, status) => {
  const actionText = status === 'approved' ? 'ĐỒNG Ý HOÀN VÉ' : 'TỪ CHỐI HOÀN VÉ';
  const confirmed = await showCustomConfirm('Xác nhận phê duyệt', `Bạn có chắc chắn muốn ${actionText} yêu cầu này?`, status === 'rejected');
  if (!confirmed) return;
  try {
    await api.post(`/admin/refunds/${id}/approve`, { status });
    await fetchPendingRefunds();
  } catch (err) {
    error.value = 'Có lỗi khi duyệt yêu cầu hoàn vé.';
  }
};

const updateUserTier = async (userId, tier) => {
  try {
    await api.patch(`/admin/users/${userId}/tier`, { membership_tier: tier });
    await fetchUsers();
  } catch (err) {
    error.value = 'Không cập nhật được hạng thành viên.';
  }
};

const giftVoucherToUser = async (userId) => {
  if (!newVoucherCode.value.trim()) {
    error.value = 'Vui lòng nhập mã voucher!';
    return;
  }
  try {
    await api.post(`/admin/users/${userId}/gift-voucher`, { voucher_code: newVoucherCode.value.trim() });
    newVoucherCode.value = '';
    const res = await api.get(`/admin/users/${userId}`);
    detail.value = res.data.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Có lỗi xảy ra khi tặng voucher.';
  }
};

const revokeVoucherFromUser = async (userId, voucherId) => {
  const confirmed = await showCustomConfirm(
    'Thu hồi Voucher',
    'Bạn có chắc chắn muốn thu hồi voucher này của khách hàng?',
    true,
    'Thu hồi'
  );
  if (!confirmed) return;
  try {
    await api.post(`/admin/users/${userId}/revoke-voucher`, { voucher_id: voucherId });
    const res = await api.get(`/admin/users/${userId}`);
    detail.value = res.data.data;
  } catch (err) {
    error.value = 'Không thu hồi được voucher.';
  }
};

const anonymizeUser = async (userId) => {
  const confirmed = await showCustomConfirm(
    'CẢNH BÁO BẢO MẬT (GDPR)',
    'Bạn có chắc chắn muốn ẩn danh tính tài khoản này? Tên, SĐT, Email sẽ bị xóa vĩnh viễn và tài khoản sẽ bị khóa vĩnh viễn.',
    true,
    'Ẩn danh tính'
  );
  if (!confirmed) return;
  try {
    await api.post(`/admin/users/${userId}/anonymize`);
    showDetail.value = false;
    await fetchUsers();
  } catch (err) {
    error.value = err.response?.data?.message || 'Không thể ẩn danh tính.';
  }
};

const calculateDiff = (s) => {
  const totalReported = parseFloat(s.reported_cash || 0) + parseFloat(s.reported_transfer || 0);
  return totalReported - parseFloat(s.system_revenue || 0);
};

const formatDateTime = (val) => {
  if (!val) return '—';
  const d = new Date(val);
  if (isNaN(d)) return '—';
  return d.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(fetchUsers);
</script>

<style scoped>
/* BỐ CỤC HAI CỘT HIỆN ĐẠI (SPLIT DASHBOARD PANELS) */
.um-container {
  display: flex;
  min-height: calc(100vh - 64px);
  background: #fdfdfd;
  font-family: 'Inter', sans-serif;
  color: #1e293b;
  width: 100%;
}

.ico {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  display: inline-block;
}

/* 1. SIDEBAR TRÁI */
.um-sidebar {
  width: 260px;
  background: #111111;
  color: #ffffff;
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex-shrink: 0;
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
}

.header-logo {
  width: 32px;
  height: 32px;
  color: #e50914;
}

.sidebar-header h3 {
  font-size: 16px;
  font-weight: 800;
  margin: 0;
  letter-spacing: 0.5px;
}

.sidebar-header small {
  color: #94a3b8;
  font-size: 11px;
}

.sidebar-divider {
  height: 1px;
  background: rgba(255,255,255,0.08);
  margin: 8px 0;
}

.menu-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.group-title {
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding-left: 12px;
  margin-bottom: 4px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  background: transparent;
  border: none;
  color: #cbd5e1;
  font-weight: 600;
  font-size: 13px;
  padding: 12px 14px;
  border-radius: 10px;
  cursor: pointer;
  text-align: left;
  transition: all 0.25s ease;
  width: 100%;
}

.menu-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.menu-item.active {
  background: #e50914;
  color: #ffffff;
  box-shadow: 0 4px 14px rgba(229, 9, 20, 0.35);
}

.menu-icon {
  width: 18px;
  height: 18px;
  opacity: 0.8;
}

.menu-item.active .menu-icon {
  opacity: 1;
}

.menu-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 800;
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
  padding: 2px 7px;
  border-radius: 20px;
  min-width: 22px;
  text-align: center;
}

.menu-item.active .menu-badge {
  background: rgba(255, 255, 255, 0.25);
}

.warning-badge {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #f59e0b !important;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.alert-badge {
  background: rgba(229, 9, 20, 0.2) !important;
  color: #ff3344 !important;
  border: 1px solid rgba(229, 9, 20, 0.3);
}

.empty-badge {
  opacity: 0.4;
}

/* 2. KHÔNG GIAN MAIN PHẢI */
.um-main {
  flex: 1;
  padding: 32px;
  background: #f8fafc;
  overflow-y: auto;
}

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
}

.header-titles h2 {
  font-size: 22px;
  font-weight: 900;
  margin: 0 0 6px;
  letter-spacing: -0.5px;
}

.header-desc {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
}

.btn-add-premium {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #e50914;
  color: #ffffff;
  border: none;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.25);
}

.btn-add-premium:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(229, 9, 20, 0.35);
  background: #ff121f;
}

/* THÈ CHỈ SỐ */
.stats-grid-premium {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
  margin-bottom: 28px;
}

.analytics-card {
  display: flex;
  align-items: center;
  gap: 20px;
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 16px;
  padding: 22px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 4px 12px rgba(0,0,0,0.03);
}

.card-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
}

.card-icon-wrap .ico {
  width: 22px;
  height: 22px;
}

.card-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.card-gold { background: linear-gradient(135deg, #d4af37, #b2902b); }
.card-red { background: linear-gradient(135deg, #e50914, #9b000e); }
.card-violet { background: linear-gradient(135deg, #7c4dff, #512da8); }
.card-mint { background: linear-gradient(135deg, #10b981, #047857); }

.card-info {
  display: flex;
  flex-direction: column;
}

.card-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 4px;
}

.card-value {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
}

/* THANH TÌM KIẾM */
.toolbar-card {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 16px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.search-box-premium {
  position: relative;
  flex: 1;
  min-width: 280px;
}

.search-ic {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input-premium {
  width: 100%;
  padding: 12px 14px 12px 42px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #0f172a;
  font-size: 13.5px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.search-input-premium:focus {
  outline: none;
  background: #ffffff;
  border-color: #e50914;
  box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.15);
}

.filters-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-select-wrap {
  position: relative;
}

.filter-ic {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
}

.filter-select {
  padding: 12px 14px 12px 34px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  font-size: 13px;
  font-weight: 700;
  color: #475569;
  cursor: pointer;
  outline: none;
  transition: all 0.2s;
}

.filter-select:focus {
  border-color: #e50914;
}

.status-seg {
  display: inline-flex;
  background: #f1f5f9;
  padding: 4px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.status-seg button {
  border: none;
  background: transparent;
  color: #64748b;
  font-weight: 700;
  font-size: 12px;
  padding: 8px 16px;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.2s;
}

.status-seg button.active {
  background: #ffffff;
  color: #e50914;
  box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

/* BẢNG DỮ LIỆU */
.panel-premium {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 10px 24px rgba(0,0,0,0.03);
  overflow: hidden;
}

.data-table-premium {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.data-table-premium thead th {
  background: #f8fafc;
  color: #64748b;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
}

.data-table-premium tbody td {
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13.5px;
  vertical-align: middle;
}

.data-table-premium tbody tr {
  transition: all 0.2s ease;
}

.data-table-premium tbody tr:hover {
  background: #f8fafc;
}

.ta-center { text-align: center; }
.ta-right { text-align: right; }
.td-muted { color: #64748b; font-size: 12.5px; }

.text-red { color: #e50914; }
.text-mint { color: #10b981; }
.text-violet { color: #7c4dff; }
.font-bold { font-weight: 800; }

.user-identity-cell {
  display: flex;
  align-items: center;
  gap: 14px;
}

.identity-meta {
  display: flex;
  flex-direction: column;
  line-height: 1.4;
}

.identity-meta strong {
  color: #0f172a;
  font-size: 14px;
  font-weight: 700;
}

.click-name {
  cursor: pointer;
  transition: color 0.2s;
}

.click-name:hover {
  color: #e50914;
  text-decoration: underline;
}

.identity-meta small {
  color: #64748b;
  font-size: 11.5px;
}

.avatar-premium {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 12.5px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  flex-shrink: 0;
  transition: transform 0.2s;
}

.avatar-premium:hover {
  transform: scale(1.08);
}

.avatar-premium.xl {
  width: 60px;
  height: 60px;
  font-size: 18px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.phone-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #475569;
  font-weight: 500;
}

.inline-ic {
  width: 14px;
  height: 14px;
  opacity: 0.6;
}

.premium-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.premium-badge .dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.role-admin { background: rgba(229, 9, 20, 0.08); color: #e50914; }
.role-staff { background: rgba(0, 131, 143, 0.08); color: #00838f; }
.role-customer { background: #f1f5f9; color: #475569; }

.status-active { background: rgba(16, 185, 129, 0.08); color: #10b981; }
.status-locked { background: rgba(229, 9, 20, 0.08); color: #e50914; }
.badge-self { background: rgba(124, 77, 255, 0.08); color: #7c4dff; }

.tier-bronze { background: rgba(139, 69, 19, 0.12); color: #8b4513; border: 1px solid rgba(139, 69, 19, 0.15); }
.tier-silver { background: rgba(148, 163, 184, 0.12); color: #475569; border: 1px solid rgba(148, 163, 184, 0.15); }
.tier-gold { background: rgba(212, 175, 55, 0.12); color: #b2902b; border: 1px solid rgba(212, 175, 55, 0.2); }
.tier-diamond { background: rgba(6, 182, 212, 0.08); color: #0891b2; border: 1px solid rgba(6, 182, 212, 0.15); }

.lock-reason-banner {
  font-size: 11px;
  color: #ef4444;
  margin-top: 5px;
  background: rgba(239, 68, 68, 0.05);
  padding: 2px 6px;
  border-radius: 4px;
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  border: 1px dashed rgba(239, 68, 68, 0.15);
}

.action-buttons-cell > * {
  margin-left: 6px;
}

.btn-action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  color: #64748b;
  transition: all 0.2s;
  vertical-align: middle;
}

.btn-action-icon:hover {
  border-color: #e50914;
  color: #e50914;
  box-shadow: 0 2px 8px rgba(229, 9, 20, 0.1);
  transform: translateY(-1px);
}

.btn-action-text {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  vertical-align: middle;
}

.btn-action-text:hover {
  background: #ffffff;
  border-color: #e50914;
  color: #e50914;
  transform: translateY(-1px);
}

.btn-action-text.mint { color: #10b981; }
.btn-action-text.mint:hover { border-color: #10b981; }
.btn-action-text.red { color: #e50914; }

.panel-footer {
  padding: 16px 24px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
}

.security-info-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(245, 158, 11, 0.06);
  border: 1px solid rgba(245, 158, 11, 0.15);
  color: #d97706;
  border-radius: 12px;
  padding: 14px 18px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 24px;
}

.sib-icon {
  width: 20px;
  height: 20px;
}

.empty-state-card {
  text-align: center;
  padding: 48px 20px;
}

.empty-icon {
  width: 44px;
  height: 44px;
  color: #cbd5e1;
  margin-bottom: 12px;
}

.empty-state-card p {
  color: #94a3b8;
  font-size: 13.5px;
  margin: 0;
}

/* KHỐI ADMIN */
.admin-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.admin-profile-card {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(0,0,0,0.02);
  transition: all 0.25s ease;
}

.admin-profile-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(229, 9, 20, 0.12);
  border-color: rgba(229, 9, 20, 0.15);
}

.apc-header-gradient {
  height: 55px;
  background: linear-gradient(135deg, #e50914, #9b000e);
}

.apc-body {
  padding: 0 20px 20px;
  text-align: center;
  margin-top: -30px;
}

.apc-body .avatar-premium {
  border: 3px solid #ffffff;
}

.apc-body h4 {
  margin: 12px 0 2px;
  font-size: 15.5px;
  font-weight: 800;
  color: #0f172a;
}

.apc-email {
  color: #64748b;
  font-size: 12px;
  margin-bottom: 12px;
  word-break: break-all;
}

.apc-badges {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.apc-details {
  background: #f8fafc;
  padding: 10px 14px;
  border-radius: 10px;
  margin-bottom: 16px;
}

.apcd-row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  padding: 6px 0;
  border-bottom: 1px dashed #e2e8f0;
}

.apcd-row:last-child {
  border-bottom: none;
}

.apcd-row span {
  color: #64748b;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.apcd-row strong {
  color: #334155;
}

.apc-actions {
  display: flex;
  gap: 6px;
}

.btn-card-action {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 8px 10px;
  border-radius: 8px;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-card-action:hover:not(:disabled) {
  border-color: #e50914;
  color: #e50914;
  background: #ffffff;
}

.btn-card-action.danger:hover:not(:disabled) {
  background: rgba(229, 9, 20, 0.05);
  border-color: #e50914;
  color: #e50914;
}

.btn-card-action:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.shift-reported-wrap {
  font-size: 12px;
  line-height: 1.45;
  color: #475569;
}

.diff-amount {
  font-weight: 800;
  font-size: 14px;
}

.refund-reason-paragraph {
  max-width: 230px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 13px;
  color: #475569;
}

.refund-reason-paragraph:hover {
  white-space: normal;
  word-break: break-all;
}

/* DIALOG MODALS */
.modal-overlay-premium {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-box-premium {
  background: #ffffff;
  border-radius: 16px;
  width: 480px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 48px rgba(15, 23, 42, 0.15);
  display: flex;
  flex-direction: column;
}

.modal-header-premium {
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header-premium h3 {
  font-size: 17px;
  font-weight: 800;
  margin: 0;
  color: #0f172a;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 16px;
  color: #94a3b8;
  cursor: pointer;
  transition: color 0.2s;
}

.btn-close-modal:hover {
  color: #e50914;
}

.form-premium {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group-premium {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group-premium label {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.input-form-premium, .select-form-premium {
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  font-size: 13.5px;
  outline: none;
  transition: all 0.2s;
  color: #0f172a;
}

.input-form-premium:focus, .select-form-premium:focus {
  border-color: #e50914;
  box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.12);
}

.form-row-premium {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.modal-actions-premium {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}

.btn-cancel-premium {
  background: #f1f5f9;
  color: #475569;
  border: none;
  padding: 10px 20px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel-premium:hover {
  background: #e2e8f0;
}

.btn-save-premium {
  background: #e50914;
  color: #ffffff;
  border: none;
  padding: 10px 24px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(229, 9, 20, 0.2);
  transition: all 0.2s;
}

.btn-save-premium:hover {
  background: #ff121f;
  transform: translateY(-1px);
}

/* HỘP THOẠI CUSTOM DIALOG OVERLAYS */
.custom-dialog-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(6px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
}

.custom-dialog-box {
  background: #ffffff;
  border-radius: 16px;
  width: 420px;
  max-width: 90%;
  padding: 24px;
  box-shadow: 0 20px 48px rgba(15, 23, 42, 0.2);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.dialog-header-premium h3 {
  font-size: 17px;
  font-weight: 800;
  margin: 0;
  color: #0f172a;
}

.dialog-message {
  font-size: 14px;
  color: #475569;
  line-height: 1.5;
  margin: 0 0 4px 0;
}

.dialog-prompt-field {
  margin-top: 8px;
}

.prompt-input {
  width: 100%;
}

.dialog-actions-premium {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}

.btn-danger-premium {
  background: #e50914 !important;
  color: #ffffff !important;
}

.btn-danger-premium:hover {
  background: #ff121f !important;
}

.animate-scale-up {
  animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scaleUp {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

/* CRM MODAL 2 CỘT */
.crm-detail-box {
  width: 900px;
  max-width: 95%;
}

.loading-wrap-premium {
  padding: 60px 20px;
  text-align: center;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.spinner-premium {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #e50914;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.crm-layout-grid {
  display: grid;
  grid-template-columns: 310px 1fr;
  padding: 24px;
  gap: 24px;
  min-height: 480px;
}

.crm-sidebar-card {
  border-right: 1px solid #e2e8f0;
  padding-right: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.gilded-member-card {
  position: relative;
  height: 170px;
  border-radius: 14px;
  padding: 20px;
  color: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  box-shadow: 0 10px 20px rgba(0,0,0,0.15);
  transition: transform 0.1s ease, box-shadow 0.25s ease;
  transform-style: preserve-3d;
}

.gmc-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, transparent 60%);
  transform: rotate(30deg);
  pointer-events: none;
}

.gmc-chip-wrap {
  position: absolute;
  top: 18px;
  right: 18px;
  z-index: 1;
}

.gmc-chip-icon {
  font-size: 24px;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
  opacity: 0.95;
}

.tier-bg-bronze { background: linear-gradient(135deg, #a15525, #5c2f13); }
.tier-bg-silver { background: linear-gradient(135deg, #94a3b8, #475569); }
.tier-bg-gold { background: linear-gradient(135deg, #e5c158, #b8860b); }
.tier-bg-diamond { background: linear-gradient(135deg, #22d3ee, #0891b2); }

.gmc-header {
  display: flex;
  align-items: center;
  gap: 8px;
  z-index: 1;
}

.gmc-logo {
  width: 20px;
  height: 20px;
}

.gmc-brand {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

.gmc-body {
  z-index: 1;
  display: flex;
  flex-direction: column;
}

.gmc-title {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.gmc-email {
  font-size: 11px;
  opacity: 0.8;
}

.gmc-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 1;
  border-top: 1px solid rgba(255,255,255,0.15);
  padding-top: 10px;
}

.gmc-tier {
  font-size: 11px;
  font-weight: 800;
}

.gmc-points {
  font-size: 12px;
  font-weight: 950;
  background: rgba(255,255,255,0.2);
  padding: 1px 6px;
  border-radius: 4px;
}

.crm-quick-control {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.control-label {
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
}

.select-crm-premium {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  color: #1e293b;
  font-weight: 700;
  font-size: 13px;
  outline: none;
  cursor: pointer;
}

.crm-quick-stats {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: #f8fafc;
  padding: 14px;
  border-radius: 12px;
}

.quick-stat-row {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
}

.quick-stat-row span {
  color: #64748b;
}

.quick-stat-row strong {
  color: #0f172a;
}

.crm-danger-zone {
  border: 1px dashed rgba(239, 68, 68, 0.3);
  background: rgba(239, 68, 68, 0.03);
  padding: 14px;
  border-radius: 10px;
}

.danger-title {
  font-size: 12px;
  font-weight: 800;
  color: #ef4444;
  display: block;
  margin-bottom: 4px;
}

.crm-danger-zone p {
  font-size: 10.5px;
  color: #64748b;
  margin: 0 0 10px 0;
  line-height: 1.4;
}

.btn-anonymize-premium {
  width: 100%;
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  padding: 8px;
  font-weight: 700;
  font-size: 11.5px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-anonymize-premium:hover {
  background: #ef4444;
  color: #ffffff;
}

/* CRM TABS */
.crm-main-tabs {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.crm-tabs-header {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid #f1f5f9;
}

.crm-tabs-header button {
  background: transparent;
  border: none;
  color: #64748b;
  font-weight: 700;
  font-size: 13.5px;
  padding: 10px 16px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
}

.crm-tabs-header button:hover {
  color: #e50914;
}

.crm-tabs-header button.active {
  color: #e50914;
}

.crm-tabs-header button.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 2px;
  background: #e50914;
}

.crm-tabs-content {
  flex: 1;
}

.content-title {
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 14px 0;
}

.empty-tab-text {
  padding: 40px 10px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}

.crm-table-wrapper {
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.crm-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
}

.crm-table thead th {
  background: #f8fafc;
  color: #64748b;
  font-weight: 800;
  padding: 10px 14px;
  border-bottom: 1px solid #e2e8f0;
}

.crm-table tbody td {
  padding: 10px 14px;
  border-bottom: 1px solid #f1f5f9;
}

/* VOUCHER TICKET DESIGN */
.crm-gift-voucher-box {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.crm-voucher-input {
  flex: 1;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 13px;
  outline: none;
}

.crm-voucher-input:focus {
  border-color: #e50914;
}

.btn-gift-premium {
  background: #10b981;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 0 16px;
  font-weight: 700;
  font-size: 12.5px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-gift-premium:hover {
  background: #059669;
}

.crm-voucher-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
  gap: 12px;
}

.crm-voucher-ticket {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: radial-gradient(circle at left, transparent 8px, #fff5f6 8px) left,
              radial-gradient(circle at right, transparent 8px, #fff5f6 8px) right;
  background-size: 51% 100%;
  background-repeat: no-repeat;
  border: 1px dashed rgba(229, 9, 20, 0.25);
  border-radius: 8px;
  padding: 10px 12px;
  position: relative;
  box-shadow: 0 2px 6px rgba(0,0,0,0.01);
}

.cvt-val {
  background: #e50914;
  color: #ffffff;
  font-size: 9px;
  font-weight: 900;
  padding: 2px 4px;
  border-radius: 4px;
  white-space: nowrap;
}

.cvt-meta {
  display: flex;
  flex-direction: column;
  margin-left: 6px;
  flex: 1;
}

.cvt-meta strong {
  font-size: 11px;
  color: #1e293b;
}

.cvt-meta small {
  font-size: 8px;
  color: #64748b;
}

.btn-revoke-mini {
  background: transparent;
  border: none;
  color: #ef4444;
  font-size: 11px;
  cursor: pointer;
  padding: 0 4px;
}

.btn-revoke-mini:hover {
  background: rgba(239, 68, 68, 0.1);
  border-radius: 4px;
}

.crm-device-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 280px;
  overflow-y: auto;
}

.crm-device-item {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 14px;
  background: #f8fafc;
}

.cdi-header {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  margin-bottom: 4px;
}

.cdi-ua {
  font-size: 11px;
  color: #475569;
  margin: 0;
  word-break: break-all;
}

.crm-review-list {
  list-style: none;
  padding: 0;
  margin: 0;
  max-height: 280px;
  overflow-y: auto;
}

.crm-review-list li {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 8px;
}

.crl-head {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
}

.review-stars {
  color: #f59e0b;
}

.crl-comment {
  margin: 4px 0 0 0;
  font-size: 12.5px;
  color: #475569;
  line-height: 1.4;
}

.alert-error {
  background: rgba(229, 9, 20, 0.06);
  border: 1px solid rgba(229, 9, 20, 0.15);
  color: #e50914;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 16px;
  font-size: 13px;
  font-weight: 600;
}
</style>
