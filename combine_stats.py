import re

with open('cinego-frontend/src/views/client/ProfileView.vue', 'r', encoding='utf-8') as f:
    text = f.read()

start_marker = '<div class="member-stats-grid">'
end_marker = '<div class="cinego-tab-dynamic-content">'

start_idx = text.find(start_marker)
end_idx = text.find(end_marker)

if start_idx == -1 or end_idx == -1:
    print("Could not find boundaries")
    exit(1)

new_layout = """<div class="member-stats-layout" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 25px;">
              <!-- THẺ THÀNH VIÊN GRADIENT 3D TILT -->
              <div style="flex-shrink: 0; display: flex; align-items: stretch;">
                <div @pointerup="isTierModalOpen = true" @mousemove="handleMouseMove" @mouseleave="handleMouseLeave" style="cursor: pointer; perspective: 1000px;" title="Nhấn để xem chi tiết hạng thẻ">
                  <div
                    ref="cardRef"
                    class="gilded-member-card"
                    :class="'tier-bg-' + (loyaltyData.current_tier || 'Bronze').toLowerCase()"
                    style="height: 100%; display: flex; flex-direction: column;"
                  >
                    <div class="gmc-glow"></div>
                    <div class="gmc-chip-wrap">
                      <span class="gmc-chip-icon">💳</span>
                    </div>
                    <div class="gmc-header">
                      <span style="font-size: 14px; margin-right: 4px; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.2));">🛡️</span>
                      <span class="gmc-brand">CineGo Card</span>
                    </div>
                    <div class="gmc-body" style="flex: 1;">
                      <span class="gmc-title">{{ profileForm.name }}</span>
                      <span class="gmc-email" style="display: block; margin-bottom: 12px;">{{ profileForm.email }}</span>
                      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <span class="gmc-points" style="padding: 4px 6px; font-size: 11px;">💰 Chi tiêu: {{ formatPrice(loyaltyData.total_spent) }} đ</span>
                        <span class="gmc-points" style="padding: 4px 6px; font-size: 11px;">⭐ Điểm: {{ loyaltyData.loyalty_points || 0 }} P</span>
                      </div>
                    </div>
                    <div class="gmc-footer">
                      <span class="gmc-tier">🏆 Thành viên {{ tierLabel(loyaltyData.current_tier || 'Bronze') }}</span>
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
                  <p class="stat-value">{{ 0 }}</p>
                  <button class="btn-stat-view" @click="activeTab = 'loyalty'" style="align-self: flex-start; margin-top: auto;">Đổi ngay</button>
                </div>
                <div class="stat-col" style="height: 100%; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; background: #ffffff;">
                  <p class="stat-label">Combo Đổi Được</p>
                  <p class="stat-value">{{ 0 }}</p>
                  <button class="btn-stat-view" @click="activeTab = 'loyalty'" style="align-self: flex-start; margin-top: auto;">Đổi ngay</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="cinego-tab-dynamic-content">"""

text = text[:start_idx] + new_layout + text[end_idx + len(end_marker):]

with open('cinego-frontend/src/views/client/ProfileView.vue', 'w', encoding='utf-8') as f:
    f.write(text)

print("Replaced successfully")
