import test from 'node:test';
import assert from 'node:assert/strict';
import { validatePricingConfig, validatePricingRule } from './pricingValidation.js';

test('validatePricingConfig detects blank required base prices', () => {
  const result = validatePricingConfig({ standard_price: '', vip_price: '', couple_price: '' });
  assert.equal(result.isValid, false);
  assert.match(result.errors.standard_price[0], /Vui lòng nhập/);
  assert.match(result.errors.vip_price[0], /Vui lòng nhập/);
  assert.match(result.errors.couple_price[0], /Vui lòng nhập/);
});

test('validatePricingConfig accepts stored thousand-unit values and rejects invalid decimal or negative values', () => {
  const validResult = validatePricingConfig({ standard_price: 50000, vip_price: 70000, couple_price: 120000 });
  assert.equal(validResult.isValid, true);

  const invalidResult = validatePricingConfig({ standard_price: '50abc', vip_price: '12.5', couple_price: -50 });
  assert.equal(invalidResult.isValid, false);
  assert.match(invalidResult.errors.standard_price[0], /chỉ cho phép số nguyên dương/i);
  assert.match(invalidResult.errors.vip_price[0], /chỉ cho phép số nguyên dương/i);
  assert.match(invalidResult.errors.couple_price[0], /lớn hơn 0/i);

  const maxResult = validatePricingConfig({ standard_price: 999999000, vip_price: 1000000000, couple_price: 100000 });
  assert.equal(maxResult.isValid, false);
  assert.match(maxResult.errors.vip_price[0], /không được vượt quá/i);
});

test('validatePricingConfig enforces standard <= VIP <= couple price order', () => {
  const result = validatePricingConfig({ standard_price: 80000, vip_price: 70000, couple_price: 120000 });
  assert.equal(result.isValid, false);
  assert.match(result.errors.standard_price[0], /nhỏ hơn hoặc bằng.*VIP/i);

  const coupleResult = validatePricingConfig({ standard_price: 50000, vip_price: 130000, couple_price: 120000 });
  assert.equal(coupleResult.isValid, false);
  assert.match(coupleResult.errors.vip_price[0], /nhỏ hơn hoặc bằng.*Đôi/i);
});

test('validatePricingRule requires rule name and movie when scope is movie', () => {
  const result = validatePricingRule({
    name: '',
    scope: 'movie',
    seat_type: 'vip',
    adjustment_type: 'surcharge',
    value: '',
    start_date: '',
    end_date: '',
    days: ['T2', 'T3'],
    all_weekdays: false
  }, 0);

  assert.equal(result.isValid, false);
  assert.match(result.errors.rule_0_name[0], /Vui lòng nhập tên quy tắc/i);
  assert.match(result.errors.rule_0_value[0], /Vui lòng nhập giá trị/i);
  assert.match(result.errors.rule_0_date[0], /Vui lòng nhập ngày/i);
});

test('validatePricingRule checks time range when use_time_filter is enabled', () => {
  const today = new Date();
  const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  const futureDate = new Date(today);
  futureDate.setDate(today.getDate() + 3);
  const futureDateString = `${futureDate.getFullYear()}-${String(futureDate.getMonth() + 1).padStart(2, '0')}-${String(futureDate.getDate()).padStart(2, '0')}`;
  const result = validatePricingRule({
    name: 'Khung giờ vàng',
    scope: 'system',
    seat_type: 'vip',
    adjustment_type: 'percentage',
    value: 20,
    start_date: todayDate,
    end_date: futureDateString,
    days: ['T2', 'T3'],
    all_weekdays: false,
    use_time_filter: true,
    time_from: '20:00',
    time_to: '19:00'
  }, 1);

  assert.equal(result.isValid, false);
  assert.match(result.errors.rule_1_time_to[0], /lớn hơn giờ bắt đầu/i);
});

test('validatePricingRule rejects percentage above 100 and missing day selection', () => {
  const today = new Date();
  const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  const futureDate = new Date(today);
  futureDate.setDate(today.getDate() + 3);
  const futureDateString = `${futureDate.getFullYear()}-${String(futureDate.getMonth() + 1).padStart(2, '0')}-${String(futureDate.getDate()).padStart(2, '0')}`;
  const result = validatePricingRule({
    name: 'Quy tắc %',
    scope: 'system',
    seat_type: 'standard',
    adjustment_type: 'percentage',
    value: 120,
    start_date: todayDate,
    end_date: futureDateString,
    days: [],
    all_weekdays: false
  }, 2);

  assert.equal(result.isValid, false);
  assert.match(result.errors.rule_2_value[0], /không được vượt quá 100/i);
  assert.match(result.errors.rule_2_days[0], /ít nhất một ngày/i);
});

test('validatePricingRule allows a valid system rule with full day selection', () => {
  const today = new Date();
  const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  const futureDate = new Date(today);
  futureDate.setDate(today.getDate() + 3);
  const futureDateString = `${futureDate.getFullYear()}-${String(futureDate.getMonth() + 1).padStart(2, '0')}-${String(futureDate.getDate()).padStart(2, '0')}`;
  const result = validatePricingRule({
    name: 'Quy tắc hợp lệ',
    scope: 'system',
    seat_type: 'vip',
    adjustment_type: 'surcharge',
    value: 30000,
    start_date: todayDate,
    end_date: futureDateString,
    days: ['T2', 'T3', 'T4'],
    all_weekdays: false,
    use_time_filter: false
  }, 3);

  assert.equal(result.isValid, true);
  assert.deepEqual(result.errors, {});
});

test('validatePricingRule rejects a start date before today', () => {
  const yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  const yesterdayString = `${yesterday.getFullYear()}-${String(yesterday.getMonth() + 1).padStart(2, '0')}-${String(yesterday.getDate()).padStart(2, '0')}`;

  const result = validatePricingRule({
    name: 'Quy tắc ngày hôm qua',
    scope: 'system',
    seat_type: 'standard',
    adjustment_type: 'surcharge',
    value: 10000,
    start_date: yesterdayString,
    end_date: yesterdayString,
    days: ['T2'],
    all_weekdays: false,
    use_time_filter: false
  }, 4);

  assert.equal(result.isValid, false);
  assert.match(result.errors.rule_4_date[0], /không được nhỏ hơn ngày hiện tại/i);
});

test('validatePricingRule validates exception dates inside the active range', () => {
  const validResult = validatePricingRule({
    name: 'Quy tắc có ngày ngoại trừ',
    scope: 'system',
    seat_type: 'all',
    adjustment_type: 'surcharge',
    value: 10000,
    start_date: '2099-08-26',
    end_date: '2099-09-02',
    excluded_dates: ['2099-08-27'],
    days: ['T2', 'T3'],
    all_weekdays: false,
    use_time_filter: false
  }, 5);
  assert.equal(validResult.isValid, true);

  const invalidResult = validatePricingRule({
    name: 'Quy tắc ngày ngoại trừ sai',
    scope: 'system',
    seat_type: 'all',
    adjustment_type: 'surcharge',
    value: 10000,
    start_date: '2099-08-26',
    end_date: '2099-09-02',
    excluded_dates: ['2099-09-03', '2099-09-03'],
    days: ['T2'],
    all_weekdays: false,
    use_time_filter: false
  }, 6);
  assert.equal(invalidResult.isValid, false);
  assert.match(invalidResult.errors.rule_6_excluded_dates[0], /không được trùng nhau|nằm trong khoảng/i);
});
