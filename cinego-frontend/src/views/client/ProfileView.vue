<template>
  <div class="cinego-profile-container">
    <div class="cinego-main-header">
      <h2>THÔNG TIN CHUNG</h2>
    </div>

    <div class="cinego-profile-body">
      <aside class="cinego-sidebar">
        <h3 class="sidebar-title">TÀI KHOẢN CINEGO</h3>
                <nav class="cinego-menu">
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'info' }"
            @click="activeTab = 'info'"
          >
            THÔNG TIN CHUNG
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'history' }"
            @click="activeTab = 'history'"
          >
            LỊCH SỬ GIAO DỊCH
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'watched' }"
            @click="activeTab = 'watched'"
          >
            PHIM ĐÃ XEM
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'loyalty' }"
            @click="activeTab = 'loyalty'"
          >
            ĐIỂM & ƯU ĐÃI
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'my_vouchers' }"
            @click="activeTab = 'my_vouchers'"
          >
            VÍ VOUCHER
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'wallet' }"
            @click="activeTab = 'wallet'"
          >
            VÍ TIỀN
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'notifications' }"
            @click="activeTab = 'notifications'"
            style="position: relative;"
          >
            THÔNG BÁO
            <span v-if="unreadNotiCount > 0" style="position: absolute; top: 12px; right: 15px; width: 8px; height: 8px; background-color: #ef4444; border-radius: 50%; box-shadow: 0 0 0 2px white;"></span>
          </button>
          <button
            class="cinego-menu-btn"
            :class="{ active: activeTab === 'password' }"
            @click="activeTab = 'password'"
          >
            ĐỔI MẬT KHẨU
          </button>
        </nav>
      </aside>

      <main class="cinego-content-area">
                <div v-if="activeTab === 'info'" class="cinego-member-summary-box">
          <div class="avatar-block">
            <div class="avatar-frame">
              <img :src="profileForm.avatar_url || '/default-avatar.png'" alt="Avatar" class="avatar-img" />
            </div>
            <label for="avatar-file" class="btn-cinego-small">Thay đổi</label>
            <input type="file" id="avatar-file" @change="handleAvatarUpload" accept="image/*" hidden />
          </div>

          <div class="summary-details">
            <p class="welcome-text">
              Xin chào <strong>{{ profileForm.name }}</strong>,
            </p>
            <p class="welcome-sub">
              Với trang này, bạn sẽ quản lý được tất cả thông tin tài khoản của mình.
            </p>

            <!-- THANH TIẾN TRÌNH THĂNG HẠNG -->
            <div v-if="loyaltyData.next_tier" class="loyalty-progress-bar-wrap" style="margin-bottom: 20px;">
              <div class="loyalty-progress-info">
                <span>Hạng hiện tại: <strong>{{ tierLabel(loyaltyData.current_tier) }}</strong></span>
                <span>Tiếp theo: <strong>{{ tierLabel(loyaltyData.next_tier) }}</strong></span>
              </div>
              <div class="loyalty-progress-track">
                <div class="loyalty-progress-fill" :style="{ width: loyaltyData.progress_percent + '%' }"></div>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                <p class="loyalty-progress-remaining" style="margin: 0;">
                  Còn thiếu <strong>{{ formatCurrency(loyaltyData.remaining_amount || 0) }}</strong> nữa để thăng hạng {{ tierLabel(loyaltyData.next_tier) }}
                </p>
                <button @click="openTierModal" style="background: none; border: none; color: var(--accent-red); font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: underline; padding: 0;">
                  Quyền lợi hạng thẻ
                </button>
              </div>
            </div>
            <div v-else class="loyalty-progress-bar-wrap" style="margin-bottom: 20px;">
              <p class="loyalty-max-rank">🏆 Chúc mừng! Bạn đã đạt hạng cao nhất - <strong>Kim Cương (Diamond)</strong></p>
            </div>

            <div class="member-stats-layout" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 25px;">
              <!-- THẺ THÀNH VIÊN GRADIENT 3D TILT -->
              <div class="tier-card-wrap">
                <div class="tier-card-3d" @click="openTierModal" @mousemove="handleMouseMove" @mouseleave="handleMouseLeave">
                  <div
                    ref="cardRef"
                    class="gilded-member-card"
                    :class="'tier-bg-' + (loyaltyData.current_tier || 'Bronze').toLowerCase()"
                  >
                    <div class="gmc-glow"></div>
                    <div class="gmc-top">
                      <span class="gmc-brand">CineGo <small>Card</small></span>
                      <span class="gmc-chip-icon">💳</span>
                    </div>
                    <div class="gmc-body">
                      <span class="gmc-title">{{ profileForm.name }}</span>
                      <span class="gmc-email">{{ profileForm.email }}</span>
                    </div>
                    <div class="gmc-footer">
                      <div class="gmc-points-group">
                        <span class="gmc-points"><b>{{ loyaltyData.loyalty_points || 0 }}</b> điểm CineGo</span>
                        <span class="gmc-points"><b>{{ formatPrice(loyaltyData.total_spent) }}đ</b> tổng chi tiêu</span>
                      </div>
                      <span class="gmc-tier">🏆 {{ tierLabel(loyaltyData.current_tier || 'Bronze') }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- 3 Box thống kê bên phải -->
              <div style="flex: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-content: start;">
                <div class="stat-col" style="height: 100%; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; background: #ffffff;">
                  <p class="stat-label">Hệ Số Tích Điểm</p>
                  <p class="stat-value">x{{ loyaltyData.multiplier || 1 }}</p>
                  <button class="btn-stat-view" @click="activeTab = 'loyalty'" style="align-self: flex-start; margin-top: auto;">Chi tiết</button>
                </div>
                <div class="stat-col" style="height: 100%; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; background: #ffffff;">
                  <p class="stat-label">Voucher Đổi Được</p>
                  <p class="stat-value">{{ availableVouchersCount }}</p>
                  <button class="btn-stat-view" @click="activeTab = 'loyalty'; loyaltySubTab = 'vouchers'" style="align-self: flex-start; margin-top: auto;">Đổi ngay</button>
                </div>
                <div class="stat-col" style="height: 100%; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; background: #ffffff;">
                  <p class="stat-label">Combo Đổi Được</p>
                  <p class="stat-value">{{ availableCombosCount }}</p>
                  <button class="btn-stat-view" @click="activeTab = 'loyalty'; loyaltySubTab = 'combos'" style="align-self: flex-start; margin-top: auto;">Đổi ngay</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="cinego-tab-dynamic-content">
          <!-- 💰 GIAO DIỆN VÍ TIỀN (WALLET) -->
          <div v-if="activeTab === 'wallet'" class="cinego-section-block">
            <div class="cinego-section-title" style="display: flex; justify-content: space-between; align-items: center;">
              <h3>Ví Tiền Của Tôi</h3>
            </div>
            
            <div v-if="loadingWallet" class="cinego-loading" style="padding: 30px; text-align: center;">Đang tải thông tin ví...</div>
            <div v-else>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                <!-- Ví Tiền -->
                <div class="wallet-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px 28px; color: #1a1a2e; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); min-height: 180px; transition: all 0.3s ease;">
                  <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #e71a0f, #ff6b6b, #e71a0f);"></div>
                  <p style="margin: 0 0 24px; font-size: 11px; color: #1a1a2e; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">Ví Tiền</p>
                  <p style="margin: 0 0 20px; font-size: 28px; font-weight: 700; color: #1a1a2e; letter-spacing: 1px; font-family: 'Courier New', monospace;">{{ formatPrice(walletData.balance || 0) }}<span style="font-size: 14px; font-weight: 700; opacity: 1;">đ</span></p>
                  <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <p style="margin: 0; font-size: 9px; color: #1a1a2e; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Số dư khả dụng</p>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span style="font-size: 18px; font-weight: 800; font-style: italic; font-family: 'Arial', sans-serif;"><span style="color: #e71a0f;">Cine</span><span style="color: #1a1a2e;">go</span></span>
                      <button @click="openWithdrawModal" style="background: #e71a0f; color: white; border: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px;">Rút Tiền</button>
                    </div>
                  </div>
                </div>
                <!-- CinePoints -->
                <div class="wallet-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px 28px; color: #1a1a2e; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); min-height: 180px; transition: all 0.3s ease;">
                  <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #e71a0f, #ff6b6b, #e71a0f);"></div>
                  <p style="margin: 0 0 24px; font-size: 11px; color: #1a1a2e; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">CinePoints</p>
                  <p style="margin: 0 0 20px; font-size: 28px; font-weight: 700; color: #1a1a2e; letter-spacing: 1px; font-family: 'Courier New', monospace;">{{ profileForm.cine_points || 0 }}<span style="font-size: 13px; font-weight: 700; opacity: 1; margin-left: 4px;">điểm</span></p>
                  <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <p style="margin: 0; font-size: 9px; color: #1a1a2e; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Điểm tích lũy</p>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span style="font-size: 18px; font-weight: 800; font-style: italic; font-family: 'Arial', sans-serif;"><span style="color: #e71a0f;">Cine</span><span style="color: #1a1a2e;">go</span></span>
                      <button @click="activeTab = 'loyalty'" style="background: #e71a0f; color: white; border: none; padding: 6px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px;">Đổi Điểm</button>
                    </div>
                  </div>
                </div>
              </div>

              <h4>Lịch sử giao dịch ví</h4>
              <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px;">
                <thead>
                  <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 12px; text-align: left;">Ngày/Giờ</th>
                    <th style="padding: 12px; text-align: left;">Giao dịch</th>
                    <th style="padding: 12px; text-align: right;">Số tiền</th>
                    <th style="padding: 12px; text-align: right;">Số dư sau GD</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="tx in walletData.transactions" :key="tx.id" style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px;">{{ formatDateTime(tx.created_at) }}</td>
                    <td style="padding: 12px;">{{ tx.description }}</td>
                    <td style="padding: 12px; text-align: right;" :style="{ color: tx.type === 'deposit' || tx.type === 'refund' ? '#22c55e' : '#ef4444', fontWeight: 'bold' }">
                      {{ tx.type === 'deposit' || tx.type === 'refund' ? '+' : '-' }}{{ formatPrice(tx.amount < 0 ? -tx.amount : tx.amount) }}đ
                    </td>
                    <td style="padding: 12px; text-align: right; font-weight: 500;">{{ formatPrice(tx.balance_after) }}đ</td>
                  </tr>
                </tbody>
              </table>

              <div v-if="walletData.withdrawals && walletData.withdrawals.length > 0" style="margin-top: 40px;">
                <h4>Lịch sử yêu cầu rút tiền</h4>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px;">
                  <thead>
                    <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                      <th style="padding: 12px; text-align: left;">Ngày yêu cầu</th>
                      <th style="padding: 12px; text-align: left;">Ngân hàng</th>
                      <th style="padding: 12px; text-align: right;">Số tiền</th>
                      <th style="padding: 12px; text-align: center;">Trạng thái</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="wd in walletData.withdrawals" :key="wd.id" style="border-bottom: 1px solid #e2e8f0;">
                      <td style="padding: 12px;">{{ formatDateTime(wd.created_at) }}</td>
                      <td style="padding: 12px;">{{ wd.bank_name }} - {{ wd.bank_account }}</td>
                      <td style="padding: 12px; text-align: right; color: #ef4444; font-weight: bold;">-{{ formatPrice(wd.amount) }}đ</td>
                      <td style="padding: 12px; text-align: center;">
                        <span v-if="wd.status === 'pending'" style="background: #fef08a; color: #854d0e; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Đang xử lý</span>
                        <span v-else-if="wd.status === 'completed'" style="background: #bbf7d0; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Đã duyệt</span>
                        <span v-else-if="wd.status === 'rejected'" style="background: #fecaca; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Từ chối</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 🎟️ GIAO DIỆN VÍ VOUCHER CỦA TÔI -->
          <div v-if="activeTab === 'my_vouchers'" class="cinego-section-block">
            <div class="cinego-section-title"
              style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
              <h3>Ví Voucher Của Tôi</h3>
              <div class="history-filter-toggle">
                <button :class="{ active: voucherFilter === 'unused' }" @click="voucherFilter = 'unused'">
                  Sẵn sàng dùng ({{ unusedVoucherCount }})
                </button>
                <button :class="{ active: voucherFilter === 'used' }" @click="voucherFilter = 'used'">
                  Đã dùng / Hết hạn
                </button>
                <button :class="{ active: voucherFilter === 'all' }" @click="voucherFilter = 'all'">
                  Tất cả
                </button>
              </div>
            </div>

            <!-- 1. Trạng thái đang tải -->
            <div v-if="loadingMyVouchers" class="cinego-loading"
              style="padding: 30px; text-align: center; color: #94a3b8;">
              Đang tải danh sách voucher trong ví...
            </div>

            <!-- 2. Trạng thái có dữ liệu -->
            <div v-else-if="filteredMyVouchers.length > 0" class="my-vouchers-grid">
              <div v-for="item in filteredMyVouchers" :key="item.id"
                class="voucher-wallet-card"
                :class="{ 'is-used': item.is_used || item.is_expired }">
                <div class="voucher-wallet-left">
                  <div class="voucher-wallet-code">{{ item.code }}</div>
                </div>
                <div class="voucher-wallet-center">
                  <p class="voucher-wallet-desc">{{ item.description || (item.type === 'combo' ? 'Ưu đãi Bắp Nước' : 'Voucher giảm giá vé') }}</p>
                  <p v-if="item.min_order_value > 0" class="voucher-wallet-min">Đơn tối thiểu: {{ formatPrice(item.min_order_value) }}đ</p>
                  <p class="voucher-wallet-exp">HSD: {{ item.end_date ? formatDate(item.end_date) : 'Không giới hạn' }}</p>
                </div>
                <div class="voucher-wallet-right">
                  <router-link v-if="!item.is_used && !item.is_expired" to="/quick-booking" class="voucher-wallet-btn">
                    DÙNG NGAY
                  </router-link>
                  <span v-else class="voucher-wallet-btn expired">—</span>
                </div>
              </div>
            </div>

            <!-- 3. Trạng thái không có voucher nào -->
            <div v-else class="text-center empty-msg" style="padding: 40px; color: #94a3b8;">
              Chưa có voucher nào trong danh mục này.
            </div>
          </div>

          <div v-if="activeTab === 'loyalty'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Đổi điểm tích lũy nhận ưu đãi</h3>
              <div class="history-filter-toggle">
                <button :class="{ active: loyaltySubTab === 'vouchers' }" @click="loyaltySubTab = 'vouchers'">
                   Đổi Voucher
                </button>
                <button :class="{ active: loyaltySubTab === 'combos' }" @click="loyaltySubTab = 'combos'">
                   Đổi Combo
                </button>
                <button :class="{ active: loyaltySubTab === 'history' }" @click="loyaltySubTab = 'history'">
                   Lịch Sử Điểm
                </button>
              </div>
            </div>

            <!-- 1. TAB ĐỔI VOUCHER -->
            <div v-if="loyaltySubTab === 'vouchers'">
              <div v-if="redeemableVouchers.length > 0" class="loyalty-grid">
                <div v-for="item in redeemableVouchers" :key="item.id" class="loyalty-card">
                  <div class="loyalty-card-badge">
                    <span class="loyalty-card-points">{{ item.points_required }}</span>
                    <span class="loyalty-card-points-label">điểm</span>
                  </div>
                  <div class="loyalty-card-body">
                    <h4 class="loyalty-card-title">{{ item.code }}</h4>
                    <p class="loyalty-card-desc">{{ item.description || 'Voucher giảm giá vé xem phim' }}</p>
                  </div>
                  <button @click="redeemVoucher(item.id)"
                    :disabled="loyaltyData.loyalty_points < item.points_required || btnLoading"
                    class="loyalty-card-btn"
                    :class="{ 'disabled': loyaltyData.loyalty_points < item.points_required }">
                    {{ loyaltyData.loyalty_points < item.points_required ? 'Chưa đủ điểm' : 'ĐỔI NGAY' }}
                  </button>
                </div>
              </div>
              <div v-else class="text-center empty-msg" style="padding: 40px; color: #94a3b8;">
                <p style="font-size: 32px; margin-bottom: 8px;"></p>
                Hiện chưa có Voucher nào hỗ trợ đổi bằng điểm.
              </div>
            </div>

            <!-- 2. TAB ĐỔI COMBO -->
            <div v-if="loyaltySubTab === 'combos'">
              <div v-if="redeemableCombos.length > 0" class="loyalty-grid">
                <div v-for="item in redeemableCombos" :key="item.id" class="loyalty-card combo-card">
                  <div class="loyalty-card-badge combo-badge">
                    <span class="loyalty-card-points">{{ item.points_required }}</span>
                    <span class="loyalty-card-points-label">điểm</span>
                  </div>
                  <div class="loyalty-card-body">
                    <h4 class="loyalty-card-title">{{ item.name }}</h4>
                    <p class="loyalty-card-desc">{{ item.description }}</p>
                  </div>
                  <button @click="redeemCombo(item.id)"
                    :disabled="loyaltyData.loyalty_points < item.points_required || btnLoading"
                    class="loyalty-card-btn combo-btn"
                    :class="{ 'disabled': loyaltyData.loyalty_points < item.points_required }">
                    {{ btnLoading ? 'Đang xử lý...' : (loyaltyData.loyalty_points < item.points_required ? 'Chưa đủ điểm' : 'ĐỔI NGAY') }}
                  </button>
                </div>
              </div>
              <div v-else class="text-center empty-msg" style="padding: 40px; color: #94a3b8;">
                <p style="font-size: 32px; margin-bottom: 8px;"></p>
                Hiện chưa có Combo bắp nước nào hỗ trợ đổi bằng điểm.
              </div>
            </div>

            <!-- 3. TAB LỊCH SỬ ĐIỂM -->
            <div v-if="loyaltySubTab === 'history'" style="margin-top: 15px; overflow-x: auto;">
              <table class="cinego-table">
                <thead>
                  <tr>
                    <th>Thời gian</th>
                    <th>Loại giao dịch</th>
                    <th>Nội dung</th>
                    <th style="text-align: right;">Điểm</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="log in pointHistories" :key="log.id">
                    <td>{{ formatDate(log.created_at) }}</td>
                    <td style="font-weight: bold; color: #1e293b;">{{ formatLogType(log.type) }}</td>
                    <td>{{ log.description }}</td>
                    <td style="text-align: right; font-weight: 800;"
                      :style="{ color: log.points > 0 ? '#10b981' : '#ef4444' }">
                      {{ log.points > 0 ? '+' : '' }}{{ log.points }} P
                    </td>
                  </tr>
                  <tr v-if="pointHistories.length === 0">
                    <td colspan="4" class="text-center empty-msg">Chưa có lịch sử tích/trừ điểm nào.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- TAB ĐỔI MẬT KHẨU -->
          <div v-if="activeTab === 'password'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Đổi mật khẩu</h3>
            </div>
            <div class="cinego-info-form professional-form" style="max-width: 600px; margin-top: 20px;">
              <div class="form-group-custom">
                <label class="form-label-custom">Mật khẩu hiện tại</label>
                <input type="password" v-model="passwordForm.old_password" class="cinego-input" placeholder="Nhập mật khẩu hiện tại" />
              </div>
              
              <div class="form-group-custom">
                <label class="form-label-custom">Mật khẩu mới</label>
                <input type="password" v-model="passwordForm.new_password" class="cinego-input" placeholder="Nhập mật khẩu mới (ít nhất 8 ký tự)" />
              </div>
              
              <div class="form-group-custom">
                <label class="form-label-custom">Xác nhận mật khẩu</label>
                <input type="password" v-model="passwordForm.confirm_password" class="cinego-input" placeholder="Nhập lại mật khẩu mới" />
              </div>
              
              <div v-if="passwordError" style="color: #dc2626; background: #fef2f2; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid #dc2626;">
                <i class="fi fi-rr-exclamation" style="margin-right: 5px;"></i> {{ passwordError }}
              </div>
              
              <div style="text-align: right; margin-top: 10px;">
                <button class="btn-cinego-main" @click="changePassword" :disabled="passwordLoading" style="padding: 12px 30px; border-radius: 8px; font-weight: bold; background: var(--accent-red); color: white; border: none; cursor: pointer; transition: all 0.2s;">
                  {{ passwordLoading ? 'Đang xử lý...' : 'LƯU MẬT KHẨU' }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'notifications'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Thông báo của tôi</h3>
            </div>
            <div class="notif-page-list">
              <div v-if="notifications.length === 0" style="padding: 30px; text-align: center; color: #64748b;">
                Bạn chưa có thông báo nào.
              </div>
              <div v-for="notif in notifications" :key="notif.id" 
                   class="notif-page-item" 
                   :class="{'unread': notif.read_at === null}"
                   @click="markAsRead(notif.id)">
                <div class="notif-page-icon">
                  {{ notif.data.type === 'booking_confirmed' ? ''
                    : notif.data.type === 'withdrawal_completed' ? ''
                    : notif.data.type === 'withdrawal_rejected' ? ''
                    : notif.data.type === 'wallet_credit' ? ''
                    : notif.data.type === 'seat_incident' ? ''
                    : notif.data.type === 'compensation' ? ''
                    : '' }}
                </div>
                <div class="notif-page-content">
                  <p class="notif-page-title" v-if="notif.data.title" style="font-weight: 700; margin: 0 0 4px 0; font-size: 14px; color: #0f172a;">{{ notif.data.title }}</p>
                  <p class="notif-page-message">{{ notif.data.message }}</p>
                  <span class="notif-page-time">{{ new Date(notif.created_at).toLocaleString('vi-VN') }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'info'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Thông tin tài khoản</h3>
              <button
                type="button"
                @click="isEditingInfo = !isEditingInfo"
                class="btn-cinego-small"
              >
                {{ isEditingInfo ? "Hủy" : "Thay đổi" }}
              </button>
            </div>

            <div class="avatar-block-professional">
              <label for="avatar-file" class="avatar-upload-label" title="Thay đổi Avatar">
                <div class="avatar-frame">
                  <img
                    :src="profileForm.avatar_url"
                    alt="Avatar"
                    class="avatar-img"
                  />
                  <div class="avatar-overlay">
                    <span class="camera-icon">📷</span>
                  </div>
                </div>
              </label>
              <input
                type="file"
                id="avatar-file"
                @change="handleAvatarUpload"
                accept="image/*"
                hidden
              />
              <div class="avatar-info">
                <h4>{{ profileForm.name }}</h4>
                <p class="text-muted">{{ profileForm.email }}</p>
              </div>
            </div>

            <form @submit.prevent="updateProfile" class="cinego-info-form professional-form">
              <div class="form-group-custom">
                <label class="form-label-custom">Tên khách hàng</label>
                <input
                  v-if="isEditingInfo"
                  type="text"
                  v-model="profileForm.name"
                  class="cinego-input"
                  required
                />
                <div v-else class="cinego-input disabled-text" style="padding-top: 13px;">{{ profileForm.name }}</div>
              </div>

              <div class="form-group-custom">
                <label class="form-label-custom">Email đăng nhập</label>
                <div class="cinego-input disabled-text" style="padding-top: 13px;">{{ profileForm.email }}</div>
              </div>

              <div class="form-group-custom">
                <label class="form-label-custom">Số điện thoại</label>
                <input
                  v-if="isEditingInfo"
                  type="text"
                  v-model="profileForm.phone"
                  class="cinego-input"
                />
                <div v-else class="cinego-input disabled-text" style="padding-top: 13px;">{{ profileForm.phone || "Chưa cập nhật" }}</div>
              </div>

              <div class="form-group-custom" v-if="isEditingInfo || profileForm.birthday">
                <label class="form-label-custom">Ngày sinh</label>
                <input
                  v-if="isEditingInfo"
                  type="date"
                  v-model="profileForm.birthday"
                  class="cinego-input"
                />
                <div v-else class="cinego-input disabled-text" style="padding-top: 13px;">{{ formatDate(profileForm.birthday) }}</div>
              </div>

              <div class="form-actions-custom" v-if="isEditingInfo">
                <button
                  type="submit"
                  class="btn-cinego-submit"
                  :disabled="btnLoading"
                >
                  {{ btnLoading ? "Đang lưu..." : "LƯU THÔNG TIN" }}
                </button>
              </div>
            </form>
          </div>


          <div v-if="activeTab === 'history'" class="cinego-section-block">
            <div class="cinego-section-title">
              <h3>Lịch sử giao dịch đặt vé</h3>
              <div class="history-filter-toggle">
                <button
                  :class="{ active: subTab === 'upcoming' }"
                  @click="subTab = 'upcoming'"
                >
                  Vé sắp chiếu
                </button>
                <button
                  :class="{ active: subTab === 'past' }"
                  @click="subTab = 'past'"
                >
                  Vé cũ
                </button>
                <button
                  :class="{ active: subTab === 'affected' }"
                  @click="subTab = 'affected'; fetchAffectedTickets()"
                  style="position: relative;"
                >
                  Vé bị ảnh hưởng
                  <span v-if="affectedTickets.length > 0" style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 10px; min-width: 16px; text-align: center;">{{ affectedTickets.length }}</span>
                </button>
              </div>
            </div>

            <div v-if="loadingHistory" class="cinego-loading">
              Đang quét vé từ hệ thống...
            </div>
            <div v-else-if="subTab !== 'affected'" class="cinego-history-table-wrapper">
              <table class="cinego-table">
                <thead>
                  <tr>
                    <th>Tên Phim</th>
                    <th>Suất Chiếu / Phòng</th>
                    <th>Ghế Ngồi</th>
                    <th>Giá Vé</th>
                    <th>Hành Động</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ticket in paginatedTickets" :key="ticket.id">
                    <td class="bold-text">{{ ticket.movie_title }}</td>
                    <td>
                      {{ ticket.start_time }} <br />
                      {{ formatDate(ticket.date) }} <br />
                      <strong>{{ ticket.room_name }}</strong>
                    </td>
                    <td class="txt-red bold-text">
                      {{
                        ticket.seats
                          ? ticket.seats
                              .map((seat) =>
                                typeof seat === "object"
                                  ? `${seat.row}${seat.number}`
                                  : seat,
                              )
                              .join(", ")
                          : ""
                      }}
                    </td>
                    <td class="bold-text">
                      {{ formatPrice(ticket.total_price) }}đ<br/>
                      <small v-if="ticket.status === 'paid'" style="color: #10b981; font-weight: 500;" title="Điểm CineGo tích lũy được từ đơn hàng đã thanh toán">(+{{ Math.floor(ticket.total_price / 10000) }} P)</small>
                    </td>
                    <td>
                      <div class="table-actions">
                        <template v-if="subTab === 'upcoming'">
                          <button
                            v-if="ticket.status === 'paid'"
                            @click="viewQrCode(ticket)"
                            class="btn-table-action"
                          >
                            Mã QR
                          </button>
                          <button
                            v-if="ticket.status === 'paid'"
                            @click="confirmSelfRefund(ticket)"
                            class="btn-table-action"
                            style="background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5;"
                          >
                            Hoàn vé
                          </button>
                          <span v-else-if="ticket.status === 'waiting_confirmation'" class="badge badge-warning" title="Admin đang xác nhận chuyển khoản của bạn">
                             Chờ xác nhận
                          </span>
                          <!-- Badge hủy thanh toán -->
                          <span v-else-if="ticket.status === 'payment_cancelled'" class="badge badge-danger">
                            Hủy thanh toán
                          </span>
                          <!-- Badge đã hoàn tiền -->
                          <span v-else-if="ticket.status === 'refunded'" style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                            Đã hoàn tiền
                          </span>
                          <!-- Badge đã hủy -->
                          <span v-else-if="ticket.status === 'cancelled'" class="badge badge-danger">
                            Đã hủy
                          </span>
                          <span v-else class="badge badge-pending">
                            Chờ thanh toán
                          </span>
                        </template>
                        <span v-else class="badge badge-success">
                          Đã chiếu
                        </span>
                        <button
                          @click="viewDetails(ticket)"
                          class="btn-table-action"
                        >
                          Chi Tiết
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredTickets.length === 0">
                    <td colspan="5" class="text-center empty-msg">
                      Không có dữ liệu giao dịch đặt vé nào.
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination Controls -->
              <div
                class="pagination-wrapper"
                style="
                  display: flex;
                  justify-content: center;
                  gap: 10px;
                  margin-top: 20px;
                "
              >
                <button
                  class="btn-pagination"
                  :disabled="historyPage === 1"
                  @click="historyPage--"
                >
                  Trước
                </button>
                <span
                  style="font-size: 14px; font-weight: bold; align-self: center"
                >
                  Trang {{ historyPage }} / {{ totalPages }}
                </span>
                <button
                  class="btn-pagination"
                  :disabled="historyPage === totalPages"
                  @click="historyPage++"
                >
                  Sau
                </button>
              </div>
            </div>

            <div v-if="subTab === 'affected'" style="margin-top: 10px;">
              <div v-if="loadingAffected" class="cinego-loading" style="padding: 30px; text-align: center; color: #94a3b8;">
                Đang tải danh sách vé bị ảnh hưởng...
              </div>
              <div v-else-if="affectedTickets.length === 0" style="padding: 40px; text-align: center; color: #94a3b8;">
                <p style="font-size: 40px; margin-bottom: 10px;">🎉</p>
                <p>Bạn không có vé nào bị ảnh hưởng bởi sự cố ghế hỏng.</p>
              </div>
              <div v-else>
                <div v-for="ticket in affectedTickets" :key="ticket.booking_detail_id"
                  style="border: 1px solid #fecaca; border-left: 4px solid #ef4444; border-radius: 8px; padding: 16px; margin-bottom: 12px; background: #fff5f5; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                  <div style="flex: 1; min-width: 200px;">
                    <p style="margin: 0 0 4px 0; font-weight: 700; color: #1e293b; font-size: 15px;">{{ ticket.movie_title }}</p>
                    <p style="margin: 0 0 2px 0; font-size: 13px; color: #64748b;">
                      Suất: <strong>{{ formatDateTime(ticket.showtime_at) }}</strong>
                    </p>
                    <p style="margin: 0 0 2px 0; font-size: 13px; color: #64748b;">
                      Ghế: <strong style="color: #ef4444;">{{ ticket.seat_label }}</strong> (hỏng)
                    </p>
                    <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                      Mã đơn: {{ ticket.booking_code }}
                    </p>
                  </div>
                  <div style="text-align: right;">
                    <p style="margin: 0 0 8px 0; font-weight: 700; color: #ef4444; font-size: 16px;">{{ formatCurrency(ticket.total_amount) }}</p>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                      <button @click="swapAffectedTicket(ticket)" :disabled="ticket._loading"
                        style="background: #334155; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s;">
                        {{ ticket._loading ? 'Đang xử lý...' : 'Đổi ghế' }}
                      </button>
                      <button @click="selfRefundTicket(ticket)" :disabled="ticket._loading"
                        style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s;">
                        {{ ticket._loading ? 'Đang xử lý...' : 'Hoàn Tiền (VNPay/Ví)' }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'watched'" class="cinego-section-block">
            <WatchedMoviesList />
          </div>
        </div>
      </main>
    </div>

    <div
      v-if="isQrModalOpen"
      class="modal-overlay"
      @click.self="isQrModalOpen = false"
    >
      <div
        class="modal-content"
        style="padding: 24px; text-align: center; position: relative"
      >
        <button
          class="btn-close"
          @click="isQrModalOpen = false"
          style="
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e2e8f0;
            color: #1e293b;
          "
        >
          ✕
        </button>
        <h3
          style="
            font-weight: 800;
            color: var(--accent-red);
            margin-bottom: 15px;
          "
        >
          MÃ VÉ CINEGO
        </h3>
        <p class="modal-movie-title" style="font-weight: bold; font-size: 16px">
          {{ selectedTicket?.movie_title }}
        </p>
        <div
          class="qr-img-wrapper"
          style="
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: inline-block;
            margin: 15px 0;
          "
        >
          <img
            :src="getQrUrl(selectedTicket?.booking_code)"
            alt="QR Code"
          />
        </div>
        <p class="modal-code" style="font-size: 14px; margin-bottom: 10px">
          Mã đặt vé:
          <span
            style="font-weight: 800; font-size: 18px; color: var(--accent-red)"
            >{{ selectedTicket?.booking_code }}</span
          >
        </p>
        <div
          class="modal-meta-box"
          style="
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            color: #475569;
          "
        >
          <p style="margin: 0 0 5px 0">
            Phòng: <strong>{{ selectedTicket?.room_name }}</strong> | Ghế:
            <strong>{{ selectedTicket?.seats ? selectedTicket.seats.map((seat) => typeof seat === 'object' ? `${seat.row}${seat.number}` : seat).join(", ") : '' }}</strong>
          </p>
          <p style="margin: 0">
            Suất: <strong>{{ selectedTicket?.start_time }}</strong> - Ngày:
            <strong>{{ formatDate(selectedTicket?.date) }}</strong>
          </p>
        </div>
      </div>
    </div>



    <!-- Modal Chi Tiết Đơn Hàng -->
    <div
      v-if="isDetailModalOpen"
      class="modal-overlay"
      @click.self="isDetailModalOpen = false"
    >
      <div
        class="modal-content detail-modal-wrapper hide-scrollbar"
        style="
          max-width: 650px;
          width: 90%;
          text-align: left;
          padding: 0;
          border-radius: 12px;
          background: white;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);

          max-height: 85vh;
          overflow-y: auto;
          display: flex;
          flex-direction: column;

          scrollbar-width: none;
          -ms-overflow-style: none;
        "
      >
        <div
          style="
            background: linear-gradient(135deg, var(--accent-red), #990000);
            padding: 20px;
            position: relative;
            color: white;
            text-align: center;
            flex-shrink: 0;
          "
        >
          <button
            class="btn-close"
            @click="isDetailModalOpen = false"
            style="
              color: white;
              background: rgba(0, 0, 0, 0.2);
              border-radius: 50%;
              width: 30px;
              height: 30px;
              display: flex;
              align-items: center;
              justify-content: center;
              position: absolute;
              top: 15px;
              right: 15px;
              font-size: 16px;
              border: none;
              cursor: pointer;
            "
          >
            ✕
          </button>
          <h3
            style="
              margin: 0;
              font-size: 19px; /* Tăng nhẹ font chữ tiêu đề */
              font-weight: 800;
              letter-spacing: 1px;
            "
          >
            CHI TIẾT ĐƠN HÀNG
          </h3>
          <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9">
            Mã đơn: <strong>{{ selectedTicket?.booking_code }}</strong>
          </p>
        </div>

        <div style="padding: 25px; background: white; flex: 1">
          <div style="display: flex; gap: 15px; margin-bottom: 20px">
            <div style="flex: 1">
              <h4
                style="
                  font-size: 19px; /* Tăng kích thước tên phim */
                  color: #1e293b;
                  margin: 0 0 8px 0;
                  font-weight: 800;
                "
              >
                {{ selectedTicket?.movie_title }}
              </h4>
              <p style="margin: 0 0 5px 0; color: #475569; font-size: 14.5px">
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Suất chiếu:</span
                >
                <strong
                  >{{ selectedTicket?.start_time }} -
                  {{ formatDate(selectedTicket?.date) }}</strong
                >
              </p>
              <p style="margin: 0 0 5px 0; color: #475569; font-size: 14.5px">
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Phòng chiếu:</span
                >
                <strong>{{ selectedTicket?.room_name }}</strong>
              </p>
              <p style="margin: 0; color: #475569; font-size: 14.5px">
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Thời gian đặt:</span
                >
                {{ selectedTicket?.created_at }}
              </p>
            </div>
          </div>

          <div
            v-if="
              categorizedSeats.standard.length ||
              categorizedSeats.vip.length ||
              categorizedSeats.couple.length
            "
            style="
              margin-bottom: 20px;
              background: #f8fafc;
              padding: 15px;
              border-radius: 8px;
              border: 1px solid #f1f5f9;
            "
          >
            <h5
              style="
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #1e293b;
                font-weight: 700;
              "
            >
              💺 Ghế Ngồi:
            </h5>
            <ul
              style="
                margin: 0;
                padding: 0;
                font-size: 14.5px;
                color: #475569;
                list-style-type: none;
              "
            >
              <li
                v-if="categorizedSeats.standard.length > 0"
                style="margin-bottom: 6px; display: flex; align-items: center"
              >
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Ghế thường:</span
                >
                <strong style="color: var(--accent-pink); font-weight: 700">{{
                  categorizedSeats.standard.join(", ")
                }}</strong>
              </li>
              <li
                v-if="categorizedSeats.vip.length > 0"
                style="margin-bottom: 6px; display: flex; align-items: center"
              >
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Ghế VIP:</span
                >
                <strong style="color: #f59e0b; font-weight: 700">{{
                  categorizedSeats.vip.join(", ")
                }}</strong>
              </li>
              <li
                v-if="categorizedSeats.couple.length > 0"
                style="margin-bottom: 0; display: flex; align-items: center"
              >
                <span style="display: inline-block; width: 95px; color: #94a3b8"
                  >Ghế đôi:</span
                >
                <strong style="color: #ef4444; font-weight: 700">{{
                  categorizedSeats.couple.join(", ")
                }}</strong>
              </li>
            </ul>
          </div>

          <div
            v-if="selectedTicket?.combos && selectedTicket.combos.length > 0"
            style="
              margin-bottom: 20px;
              background: #f8fafc;
              padding: 15px;
              border-radius: 8px;
              border: 1px solid #f1f5f9;
            "
          >
            <h5
              style="
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #1e293b;
                font-weight: 700;
              "
            >
              🍿 Bắp Nước:
            </h5>
            <ul
              style="
                margin: 0;
                padding-left: 20px;
                font-size: 14.5px;
                color: #475569;
              "
            >
              <li
                v-for="(combo, idx) in selectedTicket.combos"
                :key="idx"
                style="margin-bottom: 4px"
              >
                {{ combo }}
              </li>
            </ul>
          </div>

          <div style="border-top: 2px dashed #e2e8f0; padding-top: 20px">
            <div
              style="
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 14.5px;
                color: #475569;
              "
              v-if="selectedTicket?.total_ticket_price > 0"
            >
              <span>Tổng tiền vé:</span>
              <span style="font-weight: 600"
                >{{ formatPrice(selectedTicket?.total_ticket_price) }}đ</span
              >
            </div>
            <div
              style="
                display: flex;
                justify-content: space-between;
                margin-bottom: 12px;
                font-size: 14.5px;
                color: #475569;
              "
              v-if="selectedTicket?.total_combo_price > 0"
            >
              <span>Tổng tiền bắp nước:</span>
              <span style="font-weight: 600"
                >{{ formatPrice(selectedTicket?.total_combo_price) }}đ</span
              >
            </div>
            <div
              style="
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
                font-size: 14.5px;
                color: #10b981;
              "
              v-if="selectedTicket?.discount_amount > 0"
            >
              <span>Mã giảm giá:</span>
              <span style="font-weight: 600"
                >-{{ formatPrice(selectedTicket?.discount_amount) }}đ</span
              >
            </div>

            <div
              style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #fff1f2;
                padding: 15px;
                border-radius: 8px;
                border: 1px solid #ffe4e6;
              "
            >
              <span style="font-weight: 700; color: #1e293b; font-size: 15px"
                >Tổng thanh toán:</span
              >
              <span
                style="
                  color: var(--accent-pink);
                  font-size: 21px;
                  font-weight: 800;
                "
              >
                {{ formatPrice(selectedTicket?.total_price) }}đ
              </span>
            </div>
          </div>

          <div
            style="
              margin-top: 20px;
              display: flex;
              justify-content: space-between;
              align-items: center;
              font-size: 14px;
              padding-top: 15px;
              border-top: 1px solid #f1f5f9;
            "
          >
            <div>
              <span style="color: #94a3b8">Hình thức:</span>
              <strong
                style="
                  text-transform: uppercase;
                  color: #334155;
                  margin-left: 5px;
                "
                >{{ selectedTicket?.payment_method }}</strong
              >
            </div>
            <div
              :style="{
                padding: '4px 12px',
                borderRadius: '20px',
                fontSize: '12.5px',
                fontWeight: '700',
                backgroundColor:
                  selectedTicket?.status === 'paid' ? '#d1fae5' :
                  selectedTicket?.status === 'waiting_confirmation' ? '#fef9c3' : '#fee2e2',
                color:
                  selectedTicket?.status === 'paid' ? '#059669' :
                  selectedTicket?.status === 'waiting_confirmation' ? '#854d0e' : '#dc2626',
              }"
            >
              {{ selectedTicket?.status_label }}
            </div>
          </div>

          <div v-if="selectedTicket?.status && !['paid', 'waiting_confirmation', 'cancelled', 'payment_cancelled'].includes(selectedTicket.status)" style="padding: 0 25px 25px 25px; background: white; text-align: center; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <div v-if="isShowtimePassed" style="padding: 14px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; font-size: 13.5px; font-weight: 600;">
              Suất chiếu đã bắt đầu hoặc kết thúc. Không thể thanh toán lại, vui lòng liên hệ quầy vé.
            </div>
            <div v-else-if="selectedTicket?.status === 'payment_cancelled'" style="padding: 14px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; font-size: 13.5px; font-weight: 600;">
              Bạn đã hủy thanh toán đơn hàng này. Ghế đã được trả lại, vui lòng đặt vé mới nếu vẫn muốn xem phim.
            </div>
            <template v-else>
            <div v-if="isRetryPast24h" style="padding: 14px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; font-size: 13.5px; font-weight: 600;">
              Đơn hàng đã quá 24 giờ kể từ khi đặt. Không thể thanh toán lại, vui lòng đặt vé mới.
            </div>
            <template v-else>
            <div v-if="hasRetried && retryTimeLeft > 0" style="margin-bottom: 12px; font-size: 13px; color: #b45309; display: flex; align-items: center; justify-content: center; gap: 6px;">
              ⏳ Thời gian giữ ghế còn lại:
              <strong style="font-size: 18px; color: #dc2626; letter-spacing: 1px;">{{ retryTimeLeftText }}</strong>
            </div>
            <div v-else-if="hasRetried && retryExpired" style="margin-bottom: 12px; font-size: 13px; color: #b45309;">
             Đã hết thời gian giữ ghế. Vui lòng đặt vé mới.
            </div>
            <template v-if="canRetry && !retryExpired">
              <button
                @click="retryPayment"
                :disabled="isRetrying"
                class="btn-cinego-submit"
                style="width: 100%; background: linear-gradient(135deg, #e50914, #b91c1c); color: white; border: none;"
              >
                {{ isRetrying ? 'ĐANG XỬ LÝ...' : (hasRetried ? 'TIẾP TỤC THANH TOÁN' : 'THANH TOÁN LẠI') }}
              </button>
            </template>
            <div v-else-if="canRetry && retryExpired" style="padding: 14px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; font-size: 13.5px; font-weight: 600;">
              Đã hết thời gian giữ ghế. Vui lòng đặt vé mới để mua lại vé này.
            </div>
            </template>
            </template>
          </div>

          <div v-if="selectedTicket?.payment_status === 'paid' && selectedTicket?.booking_status === 'completed'" style="padding: 0 25px 25px 25px; background: white; text-align: center; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <button @click="isRefundModalOpen = true" class="btn-cinego-submit" style="background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; width: 100%;">
              YÊU CẦU HOÀN VÉ
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Do Ghem voi So Do -->
    <div
      v-if="isSwapModalOpen"
      class="modal-overlay"
      @click.self="isSwapModalOpen = false"
    >
      <div
        class="modal-content"
        style="max-width: 750px; width: 95%; background: #0f172a; border-radius: 16px; overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;"
      >
        <div style="background: linear-gradient(135deg, #334155, #1e293b); padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
          <div>
            <h3 style="margin: 0; color: white; font-size: 18px; font-weight: 800;">ĐỔI GHÉ</h3>
            <p style="margin: 4px 0 0; color: #94a3b8; font-size: 13px;">
              Ghế chọn: <span style="color: #ef4444; font-weight: 700;">{{ swapTicketData?.seat_label }}</span>
              - Chọn ghế trống trong sơ đồ bên dưới
            </p>
          </div>
          <button @click="isSwapModalOpen = false" style="background: none; border: none; color: #94a3b8; font-size: 22px; cursor: pointer; padding: 4px 8px;">X</button>
        </div>

        <div style="flex: 1; overflow-y: auto; padding: 10px; display: flex; justify-content: center;">
          <SeatMap
            :seats="swapSeats.map(s => ({ id: s.id, row: s.row_name, number: s.seat_number, type: s.type, status: s.status, is_booked: s.status === 'sold' || s.status === 'broken' || s.status === 'holding' }))"
            mode="client"
            :allowBookedClick="false"
            :selectedSeatIds="swapSelectedSeat ? [swapSelectedSeat.id] : []"
            @seat-clicked="handleSwapSeatClick"
          />
        </div>

        <div style="padding: 16px 20px; background: #1e293b; border-top: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
          <div style="color: #94a3b8; font-size: 13px;">
            <template v-if="swapSelectedSeat">
              Đã chọn: <strong style="color: #22c55e;">{{ swapSeatLabel(swapSelectedSeat) }}</strong>
              ({{ typeLabel(swapSelectedSeat.type) }})
            </template>
            <template v-else>Nhấn vào ghế trống trong sơ đồ để chọn</template>
          </div>
          <div style="display: flex; gap: 10px;">
            <button @click="isSwapModalOpen = false" style="background: #334155; color: #94a3b8; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Hủy</button>
            <button @click="confirmSwapSeat" :disabled="!swapSelectedSeat || swapLoading" :style="{ background: '#22c55e', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: '700', cursor: 'pointer', opacity: (!swapSelectedSeat || swapLoading) ? 0.5 : 1 }">
              {{ swapLoading ? 'Đang xử lý...' : 'Xác nhận đổi' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Quyen loi Thanh vien -->
    <div v-show="isTierModalOpen" class="modal-overlay" @click.self="closeTierModal" style="z-index: 9999;">
        <div class="modal-content tier-modal-wrapper hide-scrollbar" style="max-width: 700px; padding: 30px;">
          <button class="btn-close" @click="closeTierModal">✕</button>
          
          <h2 class="tier-modal-title" style="text-align: center; margin-bottom: 10px; font-weight: 800; color: #1e293b;">🌟 QUYỀN LỢI HẠNG THÀNH VIÊN</h2>
          <p class="tier-modal-subtitle" style="text-align: center; color: #64748b; margin-bottom: 30px;">Tích điểm đổi quà, nhận ưu đãi đặc quyền và trải nghiệm điện ảnh đỉnh cao cùng CineGo</p>

          <div class="tier-list" style="display: flex; flex-direction: column; gap: 20px;">
            <!-- BRONZE -->
            <div class="tier-item" style="display: flex; gap: 20px; background: #f8fafc; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; align-items: flex-start;">
              <div class="tier-icon" style="background: linear-gradient(135deg, #b06536, #6b3513); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 4px 10px rgba(107, 53, 19, 0.3); color: #fff; flex-shrink: 0;">🥉</div>
              <div class="tier-info" style="flex: 1;">
                <h4 style="color: #92400e; margin: 0 0 12px 0; font-size: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Đồng (Bronze) <span style="font-size: 14px; font-weight: normal; color: #64748b; margin-left: 10px; background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Hạng Mặc định</span></h4>
                <ul style="padding-left: 20px; margin: 0; color: #475569; font-size: 14px; line-height: 1.6;">
                  <li>Tích lũy điểm thưởng: <strong>1.000 VNĐ = 1 điểm CineGo (1 PTS)</strong></li>
                  <li>Dùng điểm thưởng để đổi Voucher giảm giá vé, Bắp nước miễn phí tại quầy.</li>
                  <li>Tham gia các chương trình minigame, bốc thăm may mắn hàng tháng.</li>
                </ul>
              </div>
            </div>

            <!-- SILVER -->
            <div class="tier-item" style="display: flex; gap: 20px; background: #f8fafc; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; align-items: flex-start;">
              <div class="tier-icon" style="background: linear-gradient(135deg, #a4b2c6, #4f5f76); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 4px 10px rgba(79, 95, 118, 0.3); color: #fff; flex-shrink: 0;">🥈</div>
              <div class="tier-info" style="flex: 1;">
                <h4 style="color: #334155; margin: 0 0 12px 0; font-size: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Bạc (Silver) <span style="font-size: 14px; font-weight: normal; color: #64748b; margin-left: 10px; background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Từ 1.000.000 VNĐ</span></h4>
                <ul style="padding-left: 20px; margin: 0; color: #475569; font-size: 14px; line-height: 1.6;">
                  <li>Tích lũy điểm thưởng: <strong>1.000 VNĐ = 1.2 điểm <span style="color:#10b981">(+20%)</span></strong></li>
                  <li><strong>Quà sinh nhật:</strong> Tặng 1 vé xem phim 2D miễn phí + 1 Combo bắp nước size nhỏ vào tháng sinh nhật.</li>
                  <li>Mua combo bắp nước lớn (Extra) chỉ với giá combo thường.</li>
                  <li>Giảm 5% khi mua hàng trực tiếp tại quầy lưu niệm CineGo Store.</li>
                </ul>
              </div>
            </div>

            <!-- GOLD -->
            <div class="tier-item" style="display: flex; gap: 20px; background: #fefce8; padding: 24px; border-radius: 12px; border: 1px solid #fef08a; align-items: flex-start;">
              <div class="tier-icon" style="background: linear-gradient(135deg, #ecc554, #b8860b); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 4px 10px rgba(184, 134, 11, 0.3); color: #fff; flex-shrink: 0;">🥇</div>
              <div class="tier-info" style="flex: 1;">
                <h4 style="color: #b45309; margin: 0 0 12px 0; font-size: 18px; border-bottom: 1px solid #fde047; padding-bottom: 8px;">Vàng (Gold) <span style="font-size: 14px; font-weight: normal; color: #854d0e; margin-left: 10px; background: #fef08a; padding: 2px 8px; border-radius: 4px;">Từ 3.000.000 VNĐ</span></h4>
                <ul style="padding-left: 20px; margin: 0; color: #713f12; font-size: 14px; line-height: 1.6;">
                  <li>Tích điểm tốc độ cao: <strong>1.000 VNĐ = 1.5 điểm <span style="color:#10b981">(+50%)</span></strong></li>
                  <li><strong>Quà sinh nhật:</strong> Tặng 2 vé xem phim 2D miễn phí + 1 Combo Family.</li>
                  <li>Nhận suất chiếu sớm (Sneak Show) riêng biệt cho các bom tấn Hollywood.</li>
                  <li>Giảm 10% toàn bộ dịch vụ ăn uống và mua sắm tại rạp.</li>
                  <li><strong>Đặc quyền:</strong> Lối đi riêng (Fast Track) khi soát vé và mua bắp nước.</li>
                </ul>
              </div>
            </div>

            <!-- DIAMOND -->
            <div class="tier-item" style="display: flex; gap: 20px; background: #f0fdfa; padding: 24px; border-radius: 12px; border: 1px solid #a5f3fc; align-items: flex-start;">
              <div class="tier-icon" style="background: linear-gradient(135deg, #22d3ee, #0891b2); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 4px 10px rgba(8, 145, 178, 0.3); color: #fff; flex-shrink: 0;">💎</div>
              <div class="tier-info" style="flex: 1;">
                <h4 style="color: #0e7490; margin: 0 0 12px 0; font-size: 18px; border-bottom: 1px solid #67e8f9; padding-bottom: 8px;">Kim Cương (Diamond) <span style="font-size: 14px; font-weight: normal; color: #164e63; margin-left: 10px; background: #a5f3fc; padding: 2px 8px; border-radius: 4px;">Từ 10.000.000 VNĐ</span></h4>
                <ul style="padding-left: 20px; margin: 0; color: #164e63; font-size: 14px; line-height: 1.6;">
                  <li>Tích lũy điểm cực khủng: <strong>1.000 VNĐ = 2 điểm <span style="color:#ef4444">(X2 Điểm)</span></strong></li>
                  <li><strong>Quà sinh nhật hạng sang:</strong> Tặng 2 vé IMAX / 4DX + 1 Chai Rượu Vang nhỏ & Bánh kem.</li>
                  <li>Miễn phí 1 vé 2D/3D mỗi tháng, áp dụng cả lễ tết.</li>
                  <li>Tham gia các buổi Premiere phim, thảm đỏ giao lưu cùng Đạo diễn/Diễn viên.</li>
                  <li>Khu vực ghế chờ VIP Lounge miễn phí nước uống và massage.</li>
                  <li>Giảm 20% toàn bộ dịch vụ đi kèm. Đội ngũ CSKH chuyên trách 24/7.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Hoàn Vé -->
      <div v-if="isRefundModalOpen" class="modal-overlay" @click.self="isRefundModalOpen = false">
        <div class="modal-content hide-scrollbar" style="max-width: 500px; padding: 25px; text-align: center;">
          <h3 style="margin-top: 0; color: #ef4444;">YÊU CẦU HOÀN VÉ</h3>
          <p style="color: #475569; font-size: 14px; margin-bottom: 20px;">
            Bạn đang yêu cầu hoàn tiền cho mã vé <strong>{{ selectedTicket?.booking_code }}</strong>. 
            Vui lòng nhập lý do (nhân viên quản lý sẽ xem xét phê duyệt).
          </p>
          <textarea 
            v-model="refundReason" 
            class="cinego-input" 
            placeholder="Ví dụ: Bận việc đột xuất không thể đi xem..."
            style="min-height: 100px; margin-bottom: 15px; resize: none;"
          ></textarea>
          <div style="display: flex; gap: 10px; justify-content: center;">
            <button @click="isRefundModalOpen = false" class="btn-action-text" style="padding: 10px 20px; background: #e2e8f0; color: #475569; border-radius: 8px;">HỦY BỎ</button>
            <button @click="submitRefund" class="btn-cinego-submit" style="max-width: 200px; margin: 0;">GỬI YÊU CẦU</button>
          </div>
        </div>
      </div>

      <!-- Modal Rút Tiền -->
      <div v-if="isWithdrawModalOpen" class="modal-overlay" @click.self="isWithdrawModalOpen = false">
        <div class="modal-content hide-scrollbar" style="max-width: 500px; width: 100%; padding: 25px; text-align: left; max-height: 90vh; overflow-y: auto;">
          <button @click="isWithdrawModalOpen = false" class="btn-close">×</button>
          <h3 style="margin-top: 0; color: #1e293b; text-align: center;">RÚT TIỀN VỀ NGÂN HÀNG</h3>
          <p style="color: #64748b; font-size: 14px; text-align: center; margin-bottom: 20px;">
            Số dư khả dụng: <strong style="color: #0f172a; font-size: 16px;">{{ formatPrice(walletData.balance) }}đ</strong>
          </p>
          
          <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
            <label style="font-weight: 600; font-size: 14px; color: #1e293b;">Số tiền cần rút (Tối thiểu 50.000đ)</label>
            <input type="number" v-model="withdrawForm.amount" class="cinego-input" placeholder="Nhập số tiền..." style="width: 100%;" />
          </div>
          <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
            <label style="font-weight: 600; font-size: 14px; color: #1e293b;">Ngân hàng hưởng thụ</label>
            <select v-model="withdrawForm.bank_name" class="cinego-input" style="width: 100%;">
              <option value="">-- Chọn ngân hàng --</option>
              <option v-for="bank in bankList" :key="bank" :value="bank">{{ bank }}</option>
            </select>
          </div>
          <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
            <label style="font-weight: 600; font-size: 14px; color: #1e293b;">Số tài khoản</label>
            <input type="text" v-model="withdrawForm.bank_account" class="cinego-input" placeholder="Nhập số tài khoản..." style="width: 100%;" />
          </div>
          <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px;">
            <label style="font-weight: 600; font-size: 14px; color: #1e293b;">Tên chủ tài khoản (Viết Hoa không dấu)</label>
            <input type="text" v-model="withdrawForm.bank_holder" class="cinego-input" placeholder="VD: NGUYEN VAN A" style="text-transform: uppercase; width: 100%;" />
          </div>
          
          <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px;">
            <label style="font-weight: 600; font-size: 14px; color: #1e293b;">Hoặc tải lên Mã QR Nhận Tiền (VietQR, Momo...)</label>
            <input type="file" @change="handleQrUpload" accept="image/*" class="cinego-input" style="padding: 10px; width: 100%;" />
            <p style="font-size: 12px; color: #64748b; margin-top: 2px; margin-bottom: 0;">Nếu tải lên QR Code, bạn không bắt buộc phải điền Ngân hàng và Số tài khoản phía trên.</p>
          </div>

          <div style="display: flex; gap: 10px; justify-content: center;">
          <button @click="isWithdrawModalOpen = false" class="btn-action-text" style="padding: 10px 20px; background: #e2e8f0; color: #475569; border-radius: 8px;">HỦY BỎ</button>
          <button @click="submitWithdraw" class="btn-cinego-submit" :disabled="withdrawLoading" style="max-width: 200px; margin: 0; background: #ef4444;">
            {{ withdrawLoading ? 'ĐANG XỬ LÝ...' : 'TẠO YÊU CẦU' }}
          </button>
        </div>
        </div>
      </div>

  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}
.modal-content {
  background: white;
  border-radius: 16px;
  position: relative;
  max-height: 90vh;
  overflow-y: auto;
}
.btn-close {
  position: absolute;
  top: 15px;
  right: 15px;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
}
.tier-modal-title { font-size: 22px; margin-bottom: 5px; color: #1e293b; text-align: center; }
.tier-modal-subtitle { text-align: center; color: #64748b; margin-bottom: 25px; }
.tier-item { display: flex; gap: 15px; margin-bottom: 20px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px; }
.tier-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
.tier-info h4 { margin: 0 0 5px 0; }
.tier-info ul { padding-left: 20px; margin: 0; color: #475569; font-size: 14px; }
</style>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { useAuthStore } from "../../stores/auth";
import api from "../../api/axios";
import Swal from "sweetalert2";
import WatchedMoviesList from "../../components/WatchedMoviesList.vue";
import SeatMap from "../../components/SeatMap.vue";

const toast = (title, icon = 'success') => {
  Swal.fire({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    icon: icon,
    title: title
  });
};

import { useRoute } from "vue-router";

const route = useRoute();
const activeTab = ref(route.query.tab || "info");
const notifications = ref([]);
const unreadNotiCount = computed(() => notifications.value.filter(n => !n.read_at).length);

const fetchNotifications = async () => {
  try {
    const response = await api.get('/notifications');
    notifications.value = response.data.notifications.data || response.data.notifications;
  } catch (err) {
    console.error(err);
  }
};

const markAsRead = async (id) => {
  try {
    await api.post(`/notifications/${id}/read`);
    fetchNotifications();
  } catch (err) {
    console.error(err);
  }
};

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/read-all');
    fetchNotifications();
  } catch (err) {
    console.error(err);
  }
};
const subTab = ref("upcoming");
const isEditingInfo = ref(false);
const btnLoading = ref(false);
const loadingHistory = ref(false);
const loadingAffected = ref(false);
const affectedTickets = ref([]);
const isSwapModalOpen = ref(false);
const swapTicketData = ref(null);
const swapSeats = ref([]);
const swapSelectedSeat = ref(null);
const swapLoading = ref(false);
const isQrModalOpen = ref(false);
const isDetailModalOpen = ref(false);
// Dữ liệu cho Ví Tiền
const loadingWallet = ref(false);
const walletData = ref({ balance: 0, transactions: [], withdrawals: [] });
const isWithdrawModalOpen = ref(false);
const withdrawLoading = ref(false);
const bankList = ref([]);
const withdrawForm = ref({ amount: '', bank_name: '', bank_account: '', bank_holder: '' });
const withdrawQrFile = ref(null);

// Dữ liệu cho Ví Voucher
const loadingMyVouchers = ref(false);
const loadingLoyalty = ref(false);
const unusedVouchers = ref([]);
const usedVouchers = ref([]);
const myVouchers = ref([]);
const pointHistories = ref([]);
const redeemableVouchers = ref([]);
const redeemableCombos = ref([]);

const availableVouchersCount = computed(() => redeemableVouchers.value.length);
const availableCombosCount = computed(() => redeemableCombos.value.length);

const filteredMyVouchers = computed(() => {
  if (voucherFilter.value === 'unused') {
    return myVouchers.value.filter(v => !v.is_used && !v.is_expired);
  } else if (voucherFilter.value === 'used') {
    return myVouchers.value.filter(v => v.is_used || v.is_expired);
  }
  return myVouchers.value;
});

const unusedVoucherCount = computed(() => {
  return myVouchers.value.filter(v => !v.is_used && !v.is_expired).length;
});
const loyaltySubTab = ref('vouchers');
  const voucherFilter = ref('unused');
  const isTierModalOpen = ref(false);

  const openTierModal = () => {
    isTierModalOpen.value = true;
  };

  const closeTierModal = () => {
    isTierModalOpen.value = false;
  };

  const isVoucherModalOpen = ref(false);
const isRefundModalOpen = ref(false);
const refundReason = ref('');
const selectedTicket = ref(null);

const defaultAvatar =
  "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";

const profileForm = ref({
  id: "",
  name: "",
  phone: "",
  email: "",
  birthday: "",
  avatar_url: "",
  cine_points: 0,
  vouchers: [],
});

const bookingHistory = ref([]);
const filteredTickets = computed(() => {
  const todayStr = new Date().toISOString().split("T")[0];
  return bookingHistory.value.filter((ticket) => {
    if (subTab.value === "upcoming") {
      return ticket.date >= todayStr && ticket.status !== "cancelled";
    } else {
      return ticket.date < todayStr || ticket.status === "cancelled";
    }
  });
});
const historyPage = ref(1);
const historyPerPage = 3;

const totalPages = computed(() => {
  return Math.ceil(filteredTickets.value.length / historyPerPage) || 1;
});

const paginatedTickets = computed(() => {
  const start = (historyPage.value - 1) * historyPerPage;
  return filteredTickets.value.slice(start, start + historyPerPage);
});

watch(subTab, () => {
  historyPage.value = 1;
});

const statusClass = (status) => {
  switch (status) {
    case 'paid': return 'status-paid';
    case 'pending': return 'status-pending';
    case 'failed': return 'status-failed';
    default: return '';
  }
};

const getQrUrl = (code) => {
  if (!code) return '';
  const url = `${window.location.origin}/staff/dashboard?scan=${encodeURIComponent(code)}`;
  return `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(url)}`;
};





const fetchUserData = async () => {
  try {
    const response = await api.get("/me");
    const data = response.data?.data || response.data;

    if (data) {
      profileForm.value = {
        id: data.id || "",
        name: data.name || "",
        phone: data.phone || "",
        email: data.email || "",
        birthday: data.birthday || "",
        avatar_url: data.avatar_url || defaultAvatar,
        cine_points: data.cine_points || 0,
      };
    }
  } catch (err) {
    console.error("Lỗi tải thông tin:", err);
  }
};

const updateProfile = async () => {
    if (!profileForm.value.name || profileForm.value.name.trim().length < 2) {
      return alert("Tên khách hàng phải có ít nhất 2 ký tự!");
    }

    if (profileForm.value.phone) {
      const phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/g;
      if (!phoneRegex.test(profileForm.value.phone)) {
        return alert("Số điện thoại không hợp lệ! Vui lòng nhập số điện thoại Việt Nam (10 số).");
      }
    }

    if (profileForm.value.birthday) {
      const selectedDate = new Date(profileForm.value.birthday);
      const today = new Date();
      if (selectedDate >= today) {
        return alert("Ngày sinh không hợp lệ! Vui lòng chọn ngày sinh trong quá khứ.");
      }
    }

    btnLoading.value = true;
    try {
      await api.put(`/profile`, {
        name: profileForm.value.name,
        phone: profileForm.value.phone,
        birthday: profileForm.value.birthday,
      });
      alert("Cập nhật thông tin thành công!");
      isEditingInfo.value = false;
      await authStore.fetchUser();
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || err.response?.data?.error || "Lỗi cập nhật dữ liệu!");
    } finally {
      btnLoading.value = false;
    }
};

watch(() => route.query.tab, (newTab) => {
  if (newTab) {
    activeTab.value = newTab;
  }
});

watch(activeTab, (newTab) => {
  if (newTab === 'wallet') {
    fetchWallet();
  }
});

const fetchWallet = async () => {
  loadingWallet.value = true;
  try {
    const { data } = await api.get('/wallet');
    walletData.value.balance = data.balance;
    walletData.value.transactions = data.transactions?.data || [];
    walletData.value.withdrawals = data.withdrawals || [];
  } catch (err) {
    console.error("Lỗi lấy dữ liệu ví:", err);
  } finally {
    loadingWallet.value = false;
  }
};

const openWithdrawModal = async () => {
  if (bankList.value.length === 0) {
    try {
      const { data } = await api.get('/wallet/banks');
      bankList.value = data.banks || [];
    } catch (err) {
      console.error(err);
    }
  }
  withdrawForm.value = { amount: '', bank_name: '', bank_account: '', bank_holder: '' };
  withdrawQrFile.value = null;
  isWithdrawModalOpen.value = true;
};

const handleQrUpload = (e) => {
  if (e.target.files.length > 0) {
    withdrawQrFile.value = e.target.files[0];
  } else {
    withdrawQrFile.value = null;
  }
};

const submitWithdraw = async () => {
  if (!withdrawForm.value.amount || (!withdrawQrFile.value && (!withdrawForm.value.bank_name || !withdrawForm.value.bank_account || !withdrawForm.value.bank_holder))) {
    return Swal.fire('Thiếu thông tin', 'Vui lòng cung cấp hình ảnh QR hoặc điền đầy đủ thông tin ngân hàng.', 'warning');
  }
  
  if (withdrawForm.value.amount < 50000) {
    return Swal.fire('Không hợp lệ', 'Số tiền rút tối thiểu là 50.000đ', 'warning');
  }

  withdrawLoading.value = true;
  try {
    const formData = new FormData();
    formData.append('amount', withdrawForm.value.amount);
    if (withdrawForm.value.bank_name) formData.append('bank_name', withdrawForm.value.bank_name);
    if (withdrawForm.value.bank_account) formData.append('bank_account', withdrawForm.value.bank_account);
    if (withdrawForm.value.bank_holder) formData.append('bank_holder', withdrawForm.value.bank_holder.toUpperCase());
    if (withdrawQrFile.value) {
      formData.append('qr_image', withdrawQrFile.value);
    }

    const { data } = await api.post('/wallet/withdraw', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    Swal.fire('Thành công', data.message || 'Đã tạo yêu cầu rút tiền thành công.', 'success');
    isWithdrawModalOpen.value = false;
    fetchWallet(); // Reload balance and history
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể tạo yêu cầu rút tiền.', 'error');
  } finally {
    withdrawLoading.value = false;
  }
};

const fetchBookingHistory = async () => {
  loadingHistory.value = true;
  try {
    const response = await api.get("/user/bookings");
    bookingHistory.value = response.data?.data || response.data || [];
  } catch (err) {
    console.error("Lỗi lấy lịch sử vé:", err);
  } finally {
    loadingHistory.value = false;
  }
};



const formatDateTime = (dateStr) => {
  if (!dateStr) return "";
  return new Date(dateStr).toLocaleString("vi-VN");
};

const fetchAffectedTickets = async () => {
  loadingAffected.value = true;
  try {
    const { data } = await api.get("/my-affected-tickets");
    affectedTickets.value = (data.data || []).map(t => ({ ...t, _loading: false }));
  } catch (err) {
    console.error("Lỗi lấy vé bị ảnh hưởng:", err);
  } finally {
    loadingAffected.value = false;
  }
};

const selfRefundTicket = async (ticket) => {
  const result = await Swal.fire({
    title: 'Hoàn tiền vào ví?',
    text: `Ghế ${ticket.seat_label} - ${ticket.movie_title}. Số tiền ${formatCurrency(ticket.total_amount)} sẽ được hoàn vào ví tiền của bạn.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Xác nhận hoàn tiền',
    cancelButtonText: 'Hủy'
  });

  if (!result.isConfirmed) return;

  ticket._loading = true;
  try {
    const { data } = await api.post("/self-refund", { booking_detail_id: ticket.booking_detail_id });
    if (data.success) {
      Swal.fire('Thành công', data.message, 'success');
      fetchAffectedTickets();
      fetchBookingHistory();
    }
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể hoàn tiền.', 'error');
  } finally {
    ticket._loading = false;
  }
};

const confirmSelfRefund = async (ticket) => {
  const totalAmount = ticket.total_price || ticket.total_amount || 0;
  const refundAmount = Math.round(totalAmount * 0.9);
  const fee = totalAmount - refundAmount;
  const seats = ticket.seats
    ? ticket.seats.map(s => typeof s === 'object' ? `${s.row}${s.number}` : s).join(', ')
    : '';

  const result = await Swal.fire({
    title: 'Xác nhận hoàn vé',
    html: `<div style="text-align:left;font-size:14px;">
      <p><strong>Phim:</strong> ${ticket.movie_title}</p>
      <p><strong>Ghế:</strong> ${seats}</p>
      <p><strong>Suất:</strong> ${ticket.start_time} - ${formatDate(ticket.date)}</p>
      <hr style="margin:10px 0;border-color:#e2e8f0"/>
      <p>Tổng tiền: <strong style="color:#0f172a">${formatPrice(totalAmount)}đ</strong></p>
      <p>Phí hủy (10%): <strong style="color:#dc2626">-${formatPrice(fee)}đ</strong></p>
      <p>Số tiền hoàn: <strong style="color:#16a34a">+${formatPrice(refundAmount)}đ</strong></p>
      <p style="color:#64748b;font-size:13px;margin-top:8px;">Số tiền hoàn sẽ được cộng vào ví tiền của bạn.</p>
    </div>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Xác nhận hoàn vé',
    cancelButtonText: 'Giữ nguyên'
  });

  if (!result.isConfirmed) return;

  try {
    const { data } = await api.post('/bookings/self-refund-paid', { booking_id: ticket.booking_id });
    if (data.success) {
      Swal.fire({
        title: 'Hoàn vé thành công!',
        html: `<p>${data.message}</p><p style="margin-top:8px;">Số dư ví mới: <strong>${formatPrice(data.wallet_balance)}đ</strong></p>`,
        icon: 'success'
      });
      fetchBookingHistory();
    }
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể hoàn vé.', 'error');
  }
};

const swapAffectedTicket = async (ticket) => {
  try {
    const { data } = await api.get(`/showtimes/${ticket.showtime_id}/seats`);
    const seats = (data.seats || []).filter(s =>
      s.type !== 'hidden' && s.type !== 'deleted' && s.type !== 'couple_hidden'
    );

    const availableCount = seats.filter(s => s.status === 'available').length;
    if (availableCount === 0) {
      Swal.fire('Thông báo', 'Không còn ghế trống trong phòng này.', 'info');
      return;
    }

    swapTicketData.value = ticket;
    swapSeats.value = seats;
    swapSelectedSeat.value = null;
    isSwapModalOpen.value = true;
  } catch (err) {
    Swal.fire('Lỗi', 'Không thể tải sơ đồ ghế.', 'error');
  }
};

const handleSwapSeatClick = (seat) => {
  if (seat.status !== 'available') return;
  swapSelectedSeat.value = seat;
};

const swapSeatLabel = (seat) => {
  if (!seat) return '';
  const original = swapSeats.value.find(s => s.id === seat.id) || seat;
  const seatsByRow = {};
  swapSeats.value.forEach(s => {
    if (!seatsByRow[s.row_name]) seatsByRow[s.row_name] = [];
    seatsByRow[s.row_name].push(s);
  });
  const sorted = (seatsByRow[original.row_name] || []).sort((a, b) => a.seat_number - b.seat_number);
  let displayNum = 1;
  for (const s of sorted) {
    if (s.id === original.id) break;
    if (s.type !== 'hidden' && s.type !== 'deleted' && s.type !== 'couple_hidden') displayNum++;
  }
  return `${original.row_name}${displayNum}`;
};

const confirmSwapSeat = async () => {
  if (!swapSelectedSeat.value || !swapTicketData.value) return;

  const seat = swapSelectedSeat.value;
  const ticket = swapTicketData.value;

  const result = await Swal.fire({
    title: 'Xác nhận đổi ghế?',
    html: `
      <div style="font-size: 14px;">
        <p>Ghế hỏng: <strong style="color: #ef4444;">${ticket.seat_label}</strong></p>
        <p>Đổi sang: <strong style="color: #22c55e;">${swapSeatLabel(seat)}</strong> (${seat.type === 'vip' ? 'VIP' : seat.type === 'couple' ? 'Ghế đôi' : 'Ghế thường'})</p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#22c55e',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Xác nhận đổi',
    cancelButtonText: 'Hủy'
  });

  if (!result.isConfirmed) return;

  swapLoading.value = true;
  try {
    const { data } = await api.post("/self-swap", {
      booking_detail_id: ticket.booking_detail_id,
      new_seat_id: seat.id
    });
    if (data.success) {
      isSwapModalOpen.value = false;
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `Đã đổi ghế ${ticket.seat_label} → ${swapSeatLabel(seat)} thành công!`,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
      });
      fetchAffectedTickets();
      fetchBookingHistory();
    }
  } catch (err) {
    Swal.fire('Lỗi', err.response?.data?.message || 'Không thể đổi ghế.', 'error');
  } finally {
    swapLoading.value = false;
  }
};

const handleAvatarUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append("avatar", file);
  try {
    const response = await api.post("/profile/avatar", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    profileForm.value.avatar_url = response.data.avatar_url;
    await authStore.fetchUser();
    alert("Thay đổi ảnh đại diện thành công!");
  } catch (err) {
    console.error(err);
  }
};





const viewQrCode = (ticket) => {
  selectedTicket.value = ticket;
  isQrModalOpen.value = true;
};

const viewDetails = (ticket) => {
  selectedTicket.value = ticket;
  isDetailModalOpen.value = true;
  startRetryTimerFromTicket(ticket);
};

const isRetrying = ref(false);

const retryExpiresAt = ref(null);
const retryExpired = ref(false);
const retryNow = ref(Date.now());
let retryTimerInterval = null;

const startRetryTimerFromTicket = (ticket) => {
  stopRetryTimer();
  retryExpiresAt.value = null;
  retryExpired.value = false;
  const holdExpires = ticket?.hold_expires_at;
  if (holdExpires) {
    const ts = Date.parse(holdExpires);
    if (!isNaN(ts) && ts > Date.now()) {
      retryExpiresAt.value = ts;
      startRetryTimer();
    } else {
      retryExpired.value = true;
    }
  } else if (
    ticket?.status &&
    !['paid', 'waiting_confirmation', 'cancelled', 'payment_cancelled'].includes(ticket.status)
  ) {
    retryExpired.value = true;
  }
};

const canRetry = computed(() => {
  const ticket = selectedTicket?.value;
  if (!ticket) return false;
  if (['paid', 'waiting_confirmation', 'cancelled', 'payment_cancelled'].includes(ticket.status)) return false;
  if (isShowtimePassed.value) return false;
  if (isRetryPast24h.value) return false;
  return true;
});

const hasRetried = computed(() => {
  const ticket = selectedTicket?.value;
  return ticket ? (Number(ticket.retry_count) || 0) > 0 : false;
});

const isShowtimePassed = computed(() => {
  const ticket = selectedTicket?.value;
  if (!ticket?.date || !ticket?.start_time) return false;
  const start = new Date(`${ticket.date}T${ticket.start_time}:00`);
  return !isNaN(start.getTime()) && start.getTime() <= Date.now();
});

const isRetryPast24h = computed(() => {
  const ticket = selectedTicket?.value;
  if (!ticket?.created_at) return false;
  const created = new Date(ticket.created_at);
  return !isNaN(created.getTime()) && Date.now() - created.getTime() > 24 * 3600 * 1000;
});

const retryTimeLeft = computed(() => {
  if (!retryExpiresAt.value) return 0;
  return Math.max(0, retryExpiresAt.value - retryNow.value);
});

const retryTimeLeftText = computed(() => {
  const seconds = Math.floor(retryTimeLeft.value / 1000);
  return `${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(
    seconds % 60
  ).padStart(2, "0")}`;
});

const startRetryTimer = () => {
  stopRetryTimer();
  retryExpired.value = false;
  retryNow.value = Date.now();
  retryTimerInterval = setInterval(() => {
    retryNow.value = Date.now();
    if (retryTimeLeft.value <= 0) {
      retryExpired.value = true;
      stopRetryTimer();
      fetchBookingHistory();
    }
  }, 1000);
};

const stopRetryTimer = () => {
  if (retryTimerInterval) {
    clearInterval(retryTimerInterval);
    retryTimerInterval = null;
  }
};

const retryPayment = async () => {
  if (isRetrying.value || !selectedTicket.value) return;
  isRetrying.value = true;

  try {
    const response = await api.post(
      `/payments/retry/${selectedTicket.value.id}`
    );
    const data = response.data;

    if (data?.expires_at) {
      retryExpiresAt.value = Date.parse(data.expires_at);
      startRetryTimer();
    }

    if (data?.payment_url) {
      window.location.href = data.payment_url;
      return;
    }
  } catch (err) {
    Swal.fire({
      title: "Không thể thanh toán lại",
      text:
        err.response?.data?.message ||
        "Đã có lỗi xảy ra. Vui lòng thử lại sau!",
      icon: "error",
      confirmButtonColor: "#e50914",
    });
    fetchBookingHistory();
  } finally {
    isRetrying.value = false;
  }
};

watch(isDetailModalOpen, (open) => {
  if (!open) {
    stopRetryTimer();
    retryExpiresAt.value = null;
    retryExpired.value = false;
  }
});

const formatLogType = (type) => {
  const types = {
    'redeem': 'Đổi quà',
    'redemption': 'Đổi quà',
    'admin_adjustment': 'Admin điều chỉnh',
    'earn': 'Tích điểm',
    'booking_earning': 'Điểm từ đặt vé',
    'refunded': 'Hoàn tiền',
    'payment_cancelled': 'Đã hủy giao dịch',
    'bonus': 'Thưởng điểm'
  };
  return types[type] || type;
};

const typeLabel = (t) => ({ standard: 'Ghế thường', vip: 'VIP', couple: 'Ghế đôi' }[t] || t);

const formatDate = (dateStr) => {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return d.toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

const formatPrice = (price) =>
  price ? Number(price).toLocaleString("vi-VN") : "0";

const categorizedSeats = computed(() => {
  const result = { standard: [], vip: [], couple: [] };

  const ticket = selectedTicket?.value ? selectedTicket.value : selectedTicket;

  if (ticket && Array.isArray(ticket.seats)) {
    ticket.seats.forEach((seat) => {
      if (seat && typeof seat === "object" && seat.row !== undefined) {
        const seatLabel = `${seat.row}${seat.number}`;
        const type = String(seat.type).toLowerCase().trim();

        if (type === "vip") result.vip.push(seatLabel);
        else if (type === "couple") result.couple.push(seatLabel);
        else result.standard.push(seatLabel);
      }
    });
  }
  return result;
});

const loyaltyData = ref({
  current_tier: 'Bronze',
  next_tier: 'Silver',
  total_spent: 0,
  loyalty_points: 0,
  progress_percent: 0,
  remaining_amount: 0,
});

const cardRef = ref(null);

const handleMouseMove = (e) => {
  if (!cardRef.value) return;
  const card = cardRef.value;
  const rect = card.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const centerX = rect.width / 2;
  const centerY = rect.height / 2;
  
  const rotateX = ((y - centerY) / centerY) * -10;
  const rotateY = ((x - centerX) / centerX) * 10;
  
  card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
  
  const glow = card.querySelector('.gmc-glow');
  if (glow) {
    glow.style.top = `${y - rect.height}px`;
    glow.style.left = `${x - rect.width}px`;
  }
};

const handleMouseLeave = () => {
  if (!cardRef.value) return;
  cardRef.value.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
  const glow = cardRef.value.querySelector('.gmc-glow');
  if (glow) {
    glow.style.top = '-50%';
    glow.style.left = '-50%';
  }
};

const tierLabel = (tier) => {
  const labels = { Bronze: 'Đồng (Bronze)', Silver: 'Bạc (Silver)', Gold: 'Vàng (Gold)', Diamond: 'Kim Cương (Diamond)' };
  return labels[tier] || tier;
};

const formatCurrency = (val) => {
  if (!val) return '0đ';
  return parseInt(val).toLocaleString('vi-VN') + 'đ';
};

const fetchLoyaltyItems = async () => {
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
        fetchLoyaltyHistories();
        fetchLoyaltyItems();
        fetchMyVouchers();
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
        fetchLoyaltyHistories();
        fetchLoyaltyItems();
        fetchMyVouchers();
      } else {
        toast(res.data.message || 'Đổi combo thất bại', 'error');
      }
    } catch (error) {
      toast(error.response?.data?.message || 'Lỗi hệ thống khi đổi combo', 'error');
    }
  }
};

const fetchLoyaltyProgress = async () => {
  try {
    const res = await api.get('/loyalty/progress');
    if (res.data.success) {
      loyaltyData.value = res.data.data;
    }
  } catch (err) {
    console.error('Lỗi lấy tiến trình thẻ thành viên:', err);
  }
};

const fetchLoyaltyHistories = async () => {
  try {
    const res = await api.get('/loyalty/profile');
    if (res.data.success) {
      pointHistories.value = res.data.data.histories.data;
    }
  } catch (err) {
    console.error('Lỗi lấy lịch sử điểm:', err);
  }
};

const submitRefund = async () => {
  if (!refundReason.value.trim()) {
    toast('Vui lòng nhập lý do hoàn vé!', 'error');
    return;
  }
  
  try {
    const payload = {
      booking_id: selectedTicket.value.booking_id,
      reason: refundReason.value
    };
    const res = await api.post('/bookings/refund', payload);
    if (res.data.success) {
      toast('Gửi yêu cầu hoàn vé thành công! Vui lòng chờ phê duyệt.', 'success');
      isRefundModalOpen.value = false;
      isDetailModalOpen.value = false;
      refundReason.value = '';
    }
  } catch (err) {
    console.error(err);
    toast(err.response?.data?.message || 'Có lỗi xảy ra, không thể gửi yêu cầu.', 'error');
  }
};

const passwordForm = ref({
  old_password: '',
  new_password: '',
  confirm_password: ''
});
const passwordError = ref('');
const passwordLoading = ref(false);

const changePassword = async () => {
  if (!passwordForm.value.old_password || !passwordForm.value.new_password) {
    passwordError.value = 'Vui lòng nhập đầy đủ thông tin!';
    return;
  }
  if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
    passwordError.value = 'Mật khẩu xác nhận không khớp!';
    return;
  }
  if (passwordForm.value.new_password.length < 8) {
    passwordError.value = 'Mật khẩu mới phải có ít nhất 8 ký tự!';
    return;
  }
  
  passwordError.value = '';
  passwordLoading.value = true;
  
  try {
    const res = await api.post('/profile/password', {
      old_password: passwordForm.value.old_password,
      new_password: passwordForm.value.new_password
    });
    
    if (res.data.success) {
      toast('Đổi mật khẩu thành công!', 'success');
      passwordForm.value = { old_password: '', new_password: '', confirm_password: '' };
    }
  } catch (err) {
    passwordError.value = err.response?.data?.message || 'Có lỗi xảy ra khi đổi mật khẩu.';
  } finally {
    passwordLoading.value = false;
  }
};

onMounted(() => {
  fetchUserData();
  fetchBookingHistory();
  fetchAffectedTickets();
  fetchLoyaltyProgress();
  fetchLoyaltyHistories();
  fetchLoyaltyItems();
  fetchMyVouchers();
  fetchNotifications();
});

onUnmounted(() => {
  stopRetryTimer();
});
</script>

<style scoped>
/* ====
   CINEGO MODERN PROFILE REDESIGN (WHITE & RED TONE)
   ==== */

.cinego-profile-container {
  --accent-red: #e71a0f;
  --accent-red-hover: #c4150b;
  --accent-mint: #10b981;
  --bg-light: #f8f9fa;
  --text-dark: #111827;
  --text-muted: #6b7280;
  --card-bg: #ffffff;
  --radius-xl: 16px;
  --radius-lg: 12px;
  --radius-md: 8px;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
  --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 12px 32px rgba(231, 26, 15, 0.12);
  --border-light: #e5e7eb;
}

/* ==== LAYOUT CHUNG ==== */
.cinego-profile-container {
  max-width: 1200px;
  margin: 40px auto 80px;
  font-family: "Inter", "Roboto", sans-serif;
  color: var(--text-dark);
  padding: 0 20px;
  min-height: 70vh;
}

.cinego-main-header {
  margin-bottom: 30px;
  text-align: left;
  border-bottom: 2px solid var(--accent-red);
  padding-bottom: 10px;
  display: inline-block;
}

.cinego-main-header h2 {
  font-size: 26px;
  font-weight: 900;
  color: var(--text-dark);
  text-transform: uppercase;
  margin: 0;
  letter-spacing: 0.5px;
}

.cinego-profile-body {
  display: flex;
  gap: 30px;
  align-items: flex-start;
}

/* ==== SIDEBAR ==== */
.cinego-sidebar {
  width: 260px;
  flex-shrink: 0;
  background: var(--card-bg);
  border-radius: var(--radius-xl);
  padding: 24px 16px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-light);
  position: sticky;
  top: 100px;
  height: fit-content;
}

.info-data-row {
  display: flex;
  align-items: center;
  gap: 20px;
}

.info-data-row.column-layout {
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
}

.info-label {
  width: 140px;
  font-size: 14px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-data-row.column-layout .info-label {
  width: auto;
}

.info-text {
  font-size: 16px;
  color: var(--text-dark);
  font-weight: 600;
  background: #f9fafb;
  padding: 10px 16px;
  border-radius: var(--radius-md);
  border: 1px solid #e5e7eb;
  min-width: 250px;
}

.disabled-text {
  color: #9ca3af;
  background: #f3f4f6;
}

.cinego-input {
  flex: 1;
  max-width: 400px;
  padding: 14px 16px;
  border: 2px solid #e5e7eb;
  border-radius: var(--radius-md);
  font-size: 15px;
  color: var(--text-dark);
  font-weight: 500;
  transition: all 0.3s;
  background: #fff;
  outline: none;
}

.cinego-input:focus {
  border-color: var(--accent-red);
  box-shadow: 0 0 0 3px rgba(231, 26, 15, 0.15);
}

.cinego-input.wide {
  width: 100%;
  max-width: 100%;
}

.sidebar-title {
  color: var(--text-dark);
  font-size: 15px;
  font-weight: 800;
  margin: 0 0 20px 8px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.cinego-menu {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cinego-menu-btn {
  background: transparent;
  border: none;
  text-align: left;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  font-size: 14.5px;
  font-weight: 700;
  color: #4b5563; /* Darker grey for better visibility */
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.cinego-menu-btn:hover {
  background: #f3f4f6;
  color: var(--text-dark);
}

.cinego-menu-btn.active {
  background-color: #e71a0f !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(231, 26, 15, 0.3);
}

.wallet-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(231, 26, 15, 0.12);
  border-color: #e71a0f;
}

/* ==== NỘI DUNG CHÍNH ==== */
.cinego-content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 30px;
  min-width: 0;
}

/* BOX THÔNG TIN TỔNG QUAN (HERO CARD) */
.cinego-member-summary-box {
  background: var(--card-bg);
  border-radius: var(--radius-xl);
  padding: 16px;
  display: flex;
  gap: 16px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-light);
  position: relative;
  overflow: hidden;
}

.cinego-member-summary-box::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: var(--accent-red);
}

.avatar-block-professional {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
  padding: 20px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.avatar-upload-label {
  cursor: pointer;
  position: relative;
  display: block;
}

.avatar-frame {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  padding: 3px;
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  box-shadow: 0 8px 16px rgba(255, 0, 127, 0.2);
  position: relative;
  overflow: hidden;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  background: #fff;
  transition: filter 0.3s ease;
}

.avatar-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.avatar-upload-label:hover .avatar-overlay {
  opacity: 1;
}

.avatar-upload-label:hover .avatar-img {
  filter: brightness(0.7);
}

.camera-icon {
  font-size: 24px;
  color: white;
}

.avatar-info h4 {
  margin: 0 0 5px 0;
  font-size: 20px;
  color: #1e293b;
}

.avatar-info .text-muted {
  margin: 0;
  font-size: 14px;
  color: #64748b;
}

.professional-form .form-group-custom {
  margin-bottom: 20px;
}

.professional-form .form-label-custom {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #475569;
  font-size: 14px;
}

.professional-form .cinego-input {
  width: 100%;
  padding: 12px 16px;
  background: #fff;
  border: 2px solid #e2e8f0;
  border-radius: var(--radius-md);
  color: #1e293b;
  font-size: 15px;
  transition: all 0.3s ease;
}

.professional-form .cinego-input:focus {
  background: #fff;
  border-color: var(--accent-red);
  box-shadow: 0 0 0 3px rgba(231, 26, 15, 0.15);
  outline: none;
}

.form-actions-custom {
  margin-top: 30px;
  display: flex;
  justify-content: flex-end;
}

.summary-details {
  flex: 1;
  position: relative;
  z-index: 1;
  min-width: 0;
}

.welcome-text {
  font-size: 16px;
  margin: 0 0 2px 0;
  color: var(--text-dark);
}

.welcome-text strong {
  font-weight: 800;
  color: var(--accent-red);
}

.welcome-sub {
  font-size: 11px;
  color: var(--text-muted);
  margin: 0 0 10px 0;
}

/* THỐNG KÊ (GRID) */
.member-stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.stat-col {
  background: #f9fafb;
  padding: 10px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-light);
  display: flex;
  flex-direction: column;
  gap: 4px;
  transition: transform 0.2s;
}

.stat-col:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
  border-color: #d1d5db;
}

.stat-label {
  font-size: 9px;
  text-transform: uppercase;
  color: var(--text-muted);
  font-weight: 800;
  letter-spacing: 0.5px;
  margin: 0;
}

.stat-value {
  font-size: 14px;
  font-weight: 900;
  color: var(--text-dark);
  margin: 0;
}

.rank-badge-text {
  display: inline-block;
  background: #111827;
  color: #fff;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  margin: 4px 0;
  width: fit-content;
  letter-spacing: 1px;
}

.stat-sub {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}

.txt-red {
  color: var(--accent-red);
  font-weight: 800;
}

.txt-green {
  color: var(--accent-mint);
  font-weight: 700;
}

/* NÚT BẤM */
.btn-stat-view {
  background: #fff;
  border: 1px solid #d1d5db;
  color: var(--text-dark);
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 9px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 4px;
  width: fit-content;
}

.btn-stat-view:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.loyalty-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  margin-top: 16px;
}

.my-vouchers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 14px;
  margin-top: 16px;
}

.voucher-wallet-card {
  display: flex;
  align-items: stretch;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-left: 4px solid #dc2626;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.voucher-wallet-card:hover:not(.is-used) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
}

.voucher-wallet-card.is-used {
  opacity: 0.6;
  border-left-color: #cbd5e1;
}

.voucher-wallet-left {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 16px 14px;
  background: linear-gradient(135deg, #fef2f2, #fff5f5);
  min-width: 100px;
  text-align: center;
  gap: 6px;
}

.is-used .voucher-wallet-left {
  background: #f8fafc;
}

.voucher-wallet-code {
  font-size: 14px;
  font-weight: 900;
  color: #dc2626;
  letter-spacing: 0.5px;
  word-break: break-all;
}

.is-used .voucher-wallet-code {
  color: #94a3b8;
}

.voucher-wallet-status {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 10px;
  text-transform: uppercase;
}

.status-active {
  background: #1e293b;
  color: #fff;
}

.status-used {
  background: #e2e8f0;
  color: #475569;
}

.status-expired {
  background: #fef3c7;
  color: #b45309;
}

.voucher-wallet-center {
  flex: 1;
  padding: 14px 12px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.voucher-wallet-desc {
  margin: 0 0 4px 0;
  font-size: 13px;
  color: #334155;
  font-weight: 600;
}

.voucher-wallet-min {
  margin: 0 0 3px 0;
  font-size: 12px;
  color: #64748b;
}

.voucher-wallet-exp {
  margin: 0;
  font-size: 11px;
  color: #94a3b8;
}

.voucher-wallet-right {
  display: flex;
  align-items: center;
  padding: 0 14px;
}

.voucher-wallet-btn {
  padding: 8px 14px;
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.voucher-wallet-btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
}

.voucher-wallet-btn.expired {
  background: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
}

.loyalty-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}

.loyalty-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

.loyalty-card-badge {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  padding: 14px 16px;
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.loyalty-card-badge.combo-badge {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.loyalty-card-points {
  font-size: 28px;
  font-weight: 900;
  color: #fff;
  line-height: 1;
}

.loyalty-card-points-label {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.8);
}

.loyalty-card-body {
  padding: 14px 16px;
  flex: 1;
}

.loyalty-card-title {
  margin: 0 0 6px 0;
  font-size: 15px;
  font-weight: 800;
  color: #1e293b;
}

.loyalty-card-desc {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
}

.loyalty-card-btn {
  margin: 0 16px 16px;
  padding: 10px 0;
  border: none;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: #fff;
  letter-spacing: 0.5px;
}

.loyalty-card-btn.combo-btn {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.loyalty-card-btn:hover:not(.disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.loyalty-card-btn.combo-btn:hover:not(.disabled) {
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.loyalty-card-btn.disabled {
  background: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-cinego-small {
  background: #fff;
  color: var(--text-dark);
  border: 1px solid #d1d5db;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-block;
}

.btn-cinego-small:hover {
  background: #f9fafb;
  border-color: var(--text-dark);
}

.btn-cinego-submit {
  background: var(--accent-red);
  color: #fff;
  border: none;
  padding: 14px 32px;
  border-radius: 30px;
  font-size: 15px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(231, 26, 15, 0.3);
  margin-top: 20px;
}

.btn-cinego-submit:hover:not(:disabled) {
  background: var(--accent-red-hover);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(231, 26, 15, 0.4);
}

.btn-cinego-submit:disabled {
  background: #d1d5db;
  box-shadow: none;
  cursor: not-allowed;
}

/* KHỐI NỘI DUNG (TAB CONTENT) */
.cinego-tab-dynamic-content {
  background: var(--card-bg);
  border-radius: var(--radius-xl);
  padding: 30px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-light);
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.cinego-section-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f3f4f6;
}

.cinego-section-title h3 {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-dark);
  margin: 0;
  position: relative;
}

.cinego-section-title h3::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -18px;
  width: 40px;
  height: 2px;
  background: var(--accent-red);
}

/* FORM VÀ INPUT SIÊU ĐẸP */
.cinego-info-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-top: 10px;
}

/* ==== BẢNG LỊCH SỬ GIAO DỊCH DẠNG CARD ==== */
.history-filter-toggle {
  display: inline-flex;
  background: #f3f4f6;
  border-radius: 30px;
  padding: 4px;
  gap: 4px;
}

.history-filter-toggle button {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
  padding: 8px 24px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.history-filter-toggle button:hover {
  color: var(--text-dark);
}

.history-filter-toggle button.active {
  background: #fff;
  color: var(--accent-red);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.table-responsive {
  overflow-x: auto;
  padding-bottom: 10px;
}

.cinego-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 12px; /* Tạo khoảng cách giữa các hàng để giống dạng Card */
  min-width: 800px;
}

.cinego-table th {
  padding: 0 20px 8px;
  text-align: left;
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: none;
}

.cinego-table td {
  padding: 18px 20px;
  font-size: 14.5px;
  font-weight: 500;
  color: var(--text-dark);
  background: #fff;
  border-top: 1px solid var(--border-light);
  border-bottom: 1px solid var(--border-light);
  vertical-align: middle;
  transition: all 0.2s;
}

.table-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-start;
  flex-wrap: wrap;
}

/* Bo góc cho thẻ Card của mỗi dòng */
.cinego-table td:first-child {
  border-left: 1px solid var(--border-light);
  border-radius: 12px 0 0 12px;
  font-weight: 700;
  color: var(--text-dark);
}

.btn-pagination {
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #334155;
  padding: 6px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-pagination:hover:not(:disabled) {
  background: var(--text-dark);
  color: #fff;
  border-color: var(--text-dark);
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cinego-table td:last-child {
  border-right: 1px solid var(--border-light);
  border-radius: 0 12px 12px 0;
}

.cinego-table tr:hover td {
  background: #fdf2f2; /* Nền đỏ nhạt khi hover */
  border-color: #fca5a5;
  cursor: pointer;
}

/* Cột Phim & Bold */
.movie-title-cell,
.bold-text {
  font-weight: 800;
  color: var(--text-dark);
}

/* Các trạng thái (Badge) */
.badge {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  display: inline-block;
  text-align: center;
  letter-spacing: 0.3px;
}
.badge-success {
  background: #dcfce7;
  color: #166534;
}
.badge-warning {
  background: #fef9c3;
  color: #854d0e;
}
.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-pending {
  background: #f1f5f9;
  color: #64748b;
}

.btn-table-action {
  background: #fff;
  border: 1px solid var(--accent-red);
  color: var(--accent-red);
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  text-transform: uppercase;
}

.btn-table-action:hover {
  background: var(--accent-red);
  color: #fff;
}

/* ==== RESPONSIVE ==== */
@media (max-width: 900px) {
  .cinego-profile-body {
    flex-direction: column;
  }
  .cinego-sidebar {
    width: 100%;
  }
  .member-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .cinego-member-summary-box {
    flex-direction: column;
    text-align: center;
  }
  .member-stats-grid {
    grid-template-columns: 1fr;
  }
  .info-data-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .info-text,
  .cinego-input {
    width: 100%;
    max-width: 100%;
  }
}

/* MODAL CHI TIẾT */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--card-bg);
  width: 100%;
  max-width: 500px;
  border-radius: var(--radius-xl);
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalScale {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal-header {
  background: var(--text-dark);
  color: #fff;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 4px solid var(--accent-red);
}

.modal-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.btn-close {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: #fff;
  font-size: 20px;
  cursor: pointer;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  line-height: 1;
}

.btn-close:hover {
  background: var(--accent-red);
}

.modal-body {
  padding: 24px;
  max-height: 70vh;
  overflow-y: auto;
}

/* NÚT QR CODE */
.btn-qr {
  display: inline-block;
  background: #fff;
  color: var(--text-dark);
  border: 2px solid var(--text-dark);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  margin-right: 8px;
}
.btn-qr:hover {
  background: var(--text-dark);
  color: #fff;
}
/* ---- LOYALTY PROGRESS BAR ---- */
.loyalty-progress-bar-wrap {
  margin-top: 8px;
  padding: 10px 12px;
  background: linear-gradient(135deg, #fefce8, #fff7ed);
  border: 1px solid #fde68a;
  border-radius: 10px;
}

.loyalty-progress-info {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #78716c;
  margin-bottom: 6px;
}

.loyalty-progress-info strong {
  color: #0f172a;
}

.loyalty-progress-track {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 999px;
  overflow: hidden;
}

.loyalty-progress-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #f59e0b, #ef4444);
  transition: width 0.8s ease-in-out;
}

.loyalty-progress-remaining {
  margin-top: 6px;
  font-size: 10px;
  color: #78716c;
  text-align: center;
}

.loyalty-progress-remaining strong {
  color: #e71a0f;
}

.loyalty-max-rank {
  text-align: center;
  font-size: 14px;
  color: #854d0e;
  margin: 0;
}

/* Rank badge colors */
.rank-bronze { color: #a16207; }
.rank-silver { color: #6b7280; }
.rank-gold { color: #d97706; }
.rank-diamond { color: #7c3aed; }

/* ---- TIER MODAL ---- */
.tier-modal-wrapper {
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  max-height: 85vh;
  overflow: visible;
  overflow-y: auto;
}

.tier-modal-title {
  text-align: center;
  font-size: 22px;
  font-weight: 800;
  color: #111827;
  margin-bottom: 8px;
}

.tier-modal-subtitle {
  text-align: center;
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 24px;
}

.tier-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.tier-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.tier-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.tier-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
  color: white;
}

.tier-info h4 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 700;
}

.tier-info ul {
  margin: 0;
  padding-left: 20px;
  font-size: 13.5px;
  color: #475569;
  line-height: 1.5;
}

.tier-info ul li {
  margin-bottom: 4px;
}

/* PREMIUM 3D CARD CSS */
.premium-profile-grid {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

@media (max-width: 900px) {
  .premium-profile-grid {
    grid-template-columns: 1fr;
  }
}

.tier-card-wrap {
  width: 310px;
  flex-shrink: 0;
}

.tier-card-3d {
  cursor: pointer;
  perspective: 1000px;
}

.gilded-member-card {
  position: relative;
  height: 180px;
  border-radius: 16px;
  padding: 18px 20px;
  color: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
  transition: transform 0.1s ease, box-shadow 0.25s ease;
  transform-style: preserve-3d;
}

.gilded-member-card:hover {
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.3);
  z-index: 10;
}

.gmc-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.25) 0%, transparent 60%);
  transform: rotate(30deg);
  pointer-events: none;
  transition: top 0.1s ease, left 0.1s ease;
}

.gmc-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 1;
}

.gmc-brand {
  font-size: 15px;
  font-weight: 900;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
}

.gmc-brand small {
  font-size: 10px;
  font-weight: 700;
  opacity: 0.85;
  letter-spacing: 0.5px;
}

.gmc-chip-icon {
  font-size: 26px;
  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.35));
}

.tier-bg-bronze { background: linear-gradient(135deg, #c98a52 0%, #8c4f22 55%, #5b2f12 100%); }
.tier-bg-silver { background: linear-gradient(135deg, #cfd8e3 0%, #8795a8 55%, #4a5870 100%); }
.tier-bg-gold { background: linear-gradient(135deg, #f6d365 0%, #d9a221 55%, #8c6a0d 100%); }
.tier-bg-diamond { background: linear-gradient(135deg, #67e8f9 0%, #22a7c9 55%, #0e5f7a 100%); }

.gmc-body {
  z-index: 1;
  display: flex;
  flex-direction: column;
}

.gmc-title {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  margin-bottom: 3px;
  word-break: break-word;
}

.gmc-email {
  font-size: 11px;
  opacity: 0.85;
  letter-spacing: 0.3px;
  word-break: break-all;
}

.gmc-footer {
  z-index: 1;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.25);
  padding-top: 10px;
}

.gmc-points-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.gmc-points {
  font-size: 11px;
  font-weight: 600;
  opacity: 0.95;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.gmc-points b {
  font-size: 12px;
  font-weight: 900;
}

.gmc-tier {
  font-size: 12px;
  font-weight: 800;
  background: rgba(255, 255, 255, 0.22);
  padding: 6px 10px;
  border-radius: 999px;
  backdrop-filter: blur(4px);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
  white-space: nowrap;
}
</style>









<style scoped>
.notif-page-item {
  display: flex;
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s;
  gap: 16px;
  align-items: center;
}
.notif-page-item:hover {
  background: #f8fafc;
}
.notif-page-item.unread {
  background: #fff5f5;
}
.notif-page-icon {
  font-size: 24px;
}
.notif-page-message {
  margin: 0 0 4px 0;
  font-size: 15px;
  color: #334155;
  line-height: 1.5;
}
.notif-page-item.unread .notif-page-message {
  font-weight: 700;
  color: #0f172a;
}
.notif-page-time {
  font-size: 12px;
  color: #94a3b8;
}
</style>


