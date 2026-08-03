<?php
$file = 'src/views/admin/ShowtimesView.vue';
$content = file_get_contents($file);

$regex = '/<button type="button" @click="addTimeSlot" class="btn-add-time" style="padding: 4px 10px; font-size: 0.85rem;">\+ Thêm khung giờ<\/button>/is';
$new = '<div style="display: flex; gap: 8px;">
                        <button type="button" @click="form.times = [\'\']" class="btn-add-time" style="padding: 4px 10px; font-size: 0.85rem; color: #64748b; border-color: #cbd5e1;">Xóa tất cả</button>
                        <button type="button" @click="addTimeSlot" class="btn-add-time" style="padding: 4px 10px; font-size: 0.85rem;">+ Thêm khung giờ</button>
                    </div>';

$content = preg_replace($regex, $new, $content);
file_put_contents($file, $content);
echo "Replaced button!";
