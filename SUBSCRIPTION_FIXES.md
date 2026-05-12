# إصلاحات آلية الاشتراك في الباقات

## المشاكل التي تم اكتشافها وإصلاحها

### 1. خطأ في تجديد الاشتراك (Renewal Bug) ✅
**المشكلة**: في السطر 265 من `PlanService.php`، كان الكود يستخدم `ends_at` بدلاً من `end_date`

**قبل الإصلاح**:
```php
$current->ends_at = $current->ends_at->addDays($plan->duration_days);
```

**بعد الإصلاح**:
```php
$current->end_date = \Carbon\Carbon::parse($current->end_date)->addDays($plan->duration_days);
$current->status = 'active';
```

### 2. حقل غير موجود في قاعدة البيانات ✅
**المشكلة**: في السطر 281، كان الكود يحاول تحديث حقل `subscription_price` الذي لا يوجد في جدول `vendors`

**قبل الإصلاح**:
```php
$vendor->update([
    'plan_id' => $plan->id,
    'subscription_start' => now(),
    'subscription_end' => now()->addDays($plan->duration_days),
    'subscription_price' => $plan->getRawOriginal('price'), // ❌ هذا الحقل غير موجود
]);
```

**بعد الإصلاح**:
```php
$vendor->update([
    'plan_id' => $plan->id,
    'subscription_start' => $startDate,
    'subscription_end' => $endDate,
]);
```

### 3. تحسين طريقة تحديد الاشتراك النشط ✅
**المشكلة**: دالة `activeSubscription()` كانت تتحقق فقط من الحالة (`status`) دون التحقق من التاريخ

**قبل الإصلاح**:
```php
public function activeSubscription()
{
    return $this->subscriptions()->where('status', 'active')->first();
}
```

**بعد الإصلاح**:
```php
public function activeSubscription()
{
    $today = now()->startOfDay();
    return $this->subscriptions()
        ->where('status', 'active')
        ->whereDate('end_date', '>=', $today)
        ->whereDate('start_date', '<=', $today)
        ->first();
}
```

### 4. إزالة حقل غير موجود من الـ Model ✅
**المشكلة**: حقل `commission_rate` كان موجود في `fillable` لكنه غير موجود في جدول `vendor_subscriptions`

**قبل الإصلاح**:
```php
protected $fillable = [
    'vendor_id',
    'plan_id',
    'start_date',
    'end_date',
    'price',
    'commission_rate', // ❌ غير موجود في قاعدة البيانات
    'status',
];
```

**بعد الإصلاح**:
```php
protected $fillable = [
    'vendor_id',
    'plan_id',
    'start_date',
    'end_date',
    'price',
    'status',
];
```

## التحسينات الإضافية

### 1. تحديث تواريخ الاشتراك في جدول Vendors عند التجديد
عند تجديد الاشتراك، يتم الآن تحديث `subscription_start` و `subscription_end` في جدول `vendors` أيضاً.

### 2. استخدام متغيرات للتواريخ
تم استخدام متغيرات `$startDate` و `$endDate` لضمان الاتساق في جميع العمليات.

## كيفية عمل آلية الاشتراك الآن

### عند الاشتراك في باقة جديدة:
1. ✅ التحقق من وجود المستخدم ودوره كـ vendor
2. ✅ التحقق من المنتجات المميزة (إذا كانت الباقة لا تدعمها)
3. ✅ التحقق من عدد المنتجات (إذا كانت الباقة لها حد أقصى)
4. ✅ إذا كان هناك اشتراك نشط في نفس الباقة → تجديده
5. ✅ إذا كان هناك اشتراك نشط في باقة أخرى → إلغاؤه (inactive)
6. ✅ إنشاء اشتراك جديد مع التواريخ الصحيحة
7. ✅ تحديث معلومات الباقة في جدول `vendors`

### عند تحديد الاشتراك النشط:
- ✅ التحقق من الحالة (`status = 'active'`)
- ✅ التحقق من أن تاريخ البداية (`start_date`) <= اليوم
- ✅ التحقق من أن تاريخ النهاية (`end_date`) >= اليوم

## ملاحظات مهمة

1. **حقل commission_rate**: تم إزالته من `VendorSubscription` لأنه غير موجود في قاعدة البيانات. إذا كنت تريد تتبع معدل العمولة لكل اشتراك، يجب إنشاء migration لإضافة هذا الحقل.

2. **حقل subscription_price**: تم إزالته من تحديث `vendors` لأنه غير موجود في قاعدة البيانات. السعر موجود في جدول `vendor_subscriptions`.

3. **التواريخ**: يتم الآن استخدام `Carbon` للتعامل مع التواريخ بشكل صحيح.

## الاختبار

للتحقق من أن كل شيء يعمل بشكل صحيح:

1. ✅ جرب الاشتراك في باقة جديدة
2. ✅ جرب تجديد الاشتراك في نفس الباقة
3. ✅ جرب تغيير الباقة (من باقة إلى أخرى)
4. ✅ تحقق من أن `activeSubscription()` ترجع فقط الاشتراكات النشطة فعلياً

## الملفات المعدلة

- `app/Services/PlanService.php` - إصلاح دالة `subscribeToPlan()`
- `app/Models/Vendor.php` - تحسين دالة `activeSubscription()`
- `app/Models/VendorSubscription.php` - إزالة `commission_rate` من fillable
