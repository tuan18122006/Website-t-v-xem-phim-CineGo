export const validatePricingConfig = (form = {}) => {
  const errors = {};
  let isValid = true;

  const fields = [
    ['standard_price', 'Giá vé Thường'],
    ['vip_price', 'Giá vé VIP'],
    ['couple_price', 'Giá vé Đôi']
  ];

  const MAX_BASE_PRICE = 999999000;

  for (const [field, label] of fields) {
    const value = form[field];

    if (value === '' || value === null || value === undefined) {
      errors[field] = ['Vui lòng nhập giá.'];
      isValid = false;
      continue;
    }

    if (!Number.isFinite(Number(value)) && !/^\d+$/.test(String(value).trim())) {
      errors[field] = [`${label} chỉ cho phép số nguyên dương. Ví dụ: 50, 70, 120.`];
      isValid = false;
      continue;
    }

    const rawValue = String(value).trim();
    const numericOnly = rawValue.replace(/\s+/g, '');

    if (/^-?\d+$/.test(numericOnly)) {
      const parsed = Number(numericOnly);
      if (parsed <= 0) {
        errors[field] = [`${label} phải lớn hơn 0.`];
        isValid = false;
        continue;
      }
    } else {
      errors[field] = [`${label} chỉ cho phép số nguyên dương. Ví dụ: 50, 70, 120.`];
      isValid = false;
      continue;
    }

    const parsed = Number(numericOnly);

    if (parsed <= 0) {
      errors[field] = [`${label} phải lớn hơn 0.`];
      isValid = false;
      continue;
    }

    if (parsed > MAX_BASE_PRICE) {
      errors[field] = [`${label} không được vượt quá ${MAX_BASE_PRICE.toLocaleString('vi-VN')} VNĐ.`];
      isValid = false;
    }
  }

  const standardPrice = Number(form.standard_price);
  const vipPrice = Number(form.vip_price);
  const couplePrice = Number(form.couple_price);
  const validBasePrices = [form.standard_price, form.vip_price, form.couple_price].every((value) => {
    return /^\d+$/.test(String(value).trim()) && Number(value) > 0 && Number.isFinite(Number(value));
  }) && fields.every(([field]) => !errors[field]);

  if (validBasePrices && standardPrice > vipPrice) {
    errors.standard_price = ['Giá vé Thường phải nhỏ hơn hoặc bằng Giá vé VIP.'];
    errors.vip_price = ['Giá vé VIP phải lớn hơn hoặc bằng Giá vé Thường.'];
    isValid = false;
  }

  if (validBasePrices && vipPrice > couplePrice) {
    errors.vip_price = ['Giá vé VIP phải nhỏ hơn hoặc bằng Giá vé Đôi.'];
    errors.couple_price = ['Giá vé Đôi phải lớn hơn hoặc bằng Giá vé VIP.'];
    isValid = false;
  }

  return { isValid, errors };
};

export const validatePricingRule = (rule = {}, index = 0, isEditing = false) => {
  const errors = {};
  let isValid = true;

  if (!rule.name || !String(rule.name).trim()) {
    errors[`rule_${index}_name`] = ['Vui lòng nhập tên quy tắc.'];
    isValid = false;
  }

  if (!rule.scope || !['system', 'movie'].includes(rule.scope)) {
    errors[`rule_${index}_scope`] = ['Vui lòng chọn phạm vi áp dụng.'];
    isValid = false;
  }

  if (rule.scope === 'movie' && (!rule.movie_id || !String(rule.movie_id).trim())) {
    errors[`rule_${index}_movie_id`] = ['Vui lòng chọn phim cho quy tắc theo phim.'];
    isValid = false;
  }

  if (rule.theater_id !== undefined && rule.theater_id !== null && rule.theater_id !== '' && !String(rule.theater_id).trim()) {
    errors[`rule_${index}_theater_id`] = ['Vui lòng chọn rạp hợp lệ.'];
    isValid = false;
  }

  if (rule.room_id !== undefined && rule.room_id !== null && rule.room_id !== '' && !String(rule.room_id).trim()) {
    errors[`rule_${index}_room_id`] = ['Vui lòng chọn phòng hợp lệ.'];
    isValid = false;
  }

  if (rule.theater_id && rule.room_id && String(rule.theater_id) !== String(rule.room_id).slice(0, 1)) {
    errors[`rule_${index}_room_id`] = ['Phòng phải thuộc rạp đã chọn.'];
    isValid = false;
  }

  if (!rule.seat_type || !String(rule.seat_type).trim()) {
    errors[`rule_${index}_seat_type`] = ['Vui lòng chọn loại ghế.'];
    isValid = false;
  }

  if (!rule.start_date || !String(rule.start_date).trim()) {
    errors[`rule_${index}_date`] = ['Vui lòng nhập ngày bắt đầu.'];
    isValid = false;
  }

  if (!rule.end_date || !String(rule.end_date).trim()) {
    errors[`rule_${index}_date`] = errors[`rule_${index}_date`] || ['Vui lòng nhập ngày kết thúc.'];
    isValid = false;
  }

  const today = new Date();
  const todayString = [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
  if (!isEditing && rule.start_date && String(rule.start_date) < todayString) {
    errors[`rule_${index}_date`] = ['Ngày bắt đầu không được nhỏ hơn ngày hiện tại.'];
    isValid = false;
  }

  if (rule.start_date && rule.end_date && new Date(rule.start_date) > new Date(rule.end_date)) {
    errors[`rule_${index}_date`] = ['Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.'];
    isValid = false;
  }

  const excludedDates = Array.isArray(rule.excluded_dates) ? rule.excluded_dates : [];
  if (new Set(excludedDates).size !== excludedDates.length) {
    errors[`rule_${index}_excluded_dates`] = ['Ngày ngoại trừ không được trùng nhau.'];
    isValid = false;
  }
  if (rule.start_date && rule.end_date && excludedDates.some(date => date < rule.start_date || date > rule.end_date)) {
    errors[`rule_${index}_excluded_dates`] = ['Ngày ngoại trừ phải nằm trong khoảng thời gian áp dụng.'];
    isValid = false;
  }

  const selectedDays = Array.isArray(rule.days) ? rule.days : [];
  const hasSelectedDays = selectedDays.length > 0 || rule.all_weekdays === true;
  if (!hasSelectedDays) {
    errors[`rule_${index}_days`] = ['Vui lòng chọn ít nhất một ngày áp dụng.'];
    isValid = false;
  }

  if (rule.use_time_filter) {
    if (!rule.time_from || !String(rule.time_from).trim()) {
      errors[`rule_${index}_time_from`] = ['Vui lòng nhập giờ bắt đầu.'];
      isValid = false;
    }
    if (!rule.time_to || !String(rule.time_to).trim()) {
      errors[`rule_${index}_time_to`] = ['Vui lòng nhập giờ kết thúc.'];
      isValid = false;
    }
    if (rule.time_from && rule.time_to && rule.time_from >= rule.time_to) {
      errors[`rule_${index}_time_to`] = ['Giờ kết thúc phải lớn hơn giờ bắt đầu.'];
      isValid = false;
    }
  }

  if (!rule.adjustment_type || !['surcharge', 'percentage', 'free'].includes(rule.adjustment_type)) {
    errors[`rule_${index}_adjustment_type`] = ['Vui lòng chọn kiểu điều chỉnh giá.'];
    isValid = false;
  }

  if (rule.adjustment_type !== 'free') {
    const rawValue = Number(rule.value);
    if (rule.value === '' || rule.value === null || rule.value === undefined || Number.isNaN(rawValue)) {
      errors[`rule_${index}_value`] = ['Vui lòng nhập giá trị điều chỉnh.'];
      isValid = false;
    } else if (rawValue <= 0) {
      errors[`rule_${index}_value`] = ['Giá trị điều chỉnh phải lớn hơn 0.'];
      isValid = false;
    } else if (rule.adjustment_type === 'percentage' && rawValue > 100) {
      errors[`rule_${index}_value`] = ['Tỷ lệ phần trăm không được vượt quá 100%.'];
      isValid = false;
    }
  }

  return { isValid, errors };
};
