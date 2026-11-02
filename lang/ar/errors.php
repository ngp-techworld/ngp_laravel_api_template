<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API error messages
|--------------------------------------------------------------------------
|
| The "message" of every error response. One key per error "code"
| (see app/Exceptions/ApiExceptionRenderer.php). Keep lang/en in sync.
|
*/

return [
    'bad_request' => 'الطلب غير صالح.',
    'unauthenticated' => 'يجب تسجيل الدخول أولاً.',
    'forbidden' => 'ليس لديك صلاحية للقيام بهذا الإجراء.',
    'not_found' => 'العنصر المطلوب غير موجود.',
    'method_not_allowed' => 'طريقة الطلب غير مسموحة لهذا الرابط.',
    'conflict' => 'لا يمكن تنفيذ الطلب في الحالة الحالية.',
    'csrf_token_mismatch' => 'انتهت صلاحية الجلسة. حدّث الصفحة وحاول مرة أخرى.',
    'validation_failed' => 'البيانات المدخلة غير صالحة.',
    'too_many_requests' => 'طلبات كثيرة جداً. حاول مرة أخرى بعد :seconds ثانية.',
    'server_error' => 'حدث خطأ غير متوقع. حاول مرة أخرى لاحقاً.',
    'service_unavailable' => 'الخدمة غير متاحة حالياً. حاول مرة أخرى لاحقاً.',
    'http_error' => 'تعذّر تنفيذ الطلب.',
    'session_required' => 'تسجيل الدخول بالجلسة متاح لتطبيقات الويب المسموحة فقط. استخدم /api/v1/auth/token للحصول على رمز دخول.',
];
