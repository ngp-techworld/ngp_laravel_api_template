# بنية المشروع

قالب Laravel 13 لـ **JSON API** فقط (بدون واجهات Blade ولا Vite)، مبني على هيكل Laravel القياسي
ومقسوم **حسب المجال (domain)** جوّا كل مجلد، حسب `ngp-laravel` §2.
للتشغيل والتخصيص راجع [README.md](README.md)، ولعقد الـ API راجع [docs/API.md](docs/API.md).

## الطبقات

| الطبقة | المسؤولية | ممنوع عليها |
|---|---|---|
| `routes/api/v1/*.php` | الروابط، مقسومة حسب الجمهور: `public` (بدون دخول)، `customer` (مستخدم مسجّل) | أي منطق |
| `Http/Requests/<Domain>` | التحقق من المدخلات + `authorize()` عبر الـ Policy + تنظيف المدخلات (`prepareForValidation`) | الكتابة بالداتابيز |
| `Http/Controllers/Api/V1/<Audience>` | يستلم الطلب المتحقق منه، ينادي **Action واحد**، ويرجّع Resource | الاستعلامات وقواعد العمل و `try/catch` |
| `Actions/<Domain>` | حالة استخدام وحدة (`ListItems`، `IssueAccessToken`) بدالة `handle()` مكتوبة الأنواع | قراءة `request()` أو إرجاع HTTP response |
| `Services/<Domain>` | منطق مشترك بين أكتر من Action (لسا ما في، أول ما يلزم) | أمور الـ HTTP |
| `Models` | العلاقات، الـ casts، الـ scopes | سير العمل ونداء APIs خارجية |
| `Policies` | مين مسموحله شو (`ItemPolicy`) | — |
| `Http/Resources/<Domain>` | الشكل النهائي للـ JSON، وبيخبّي الأعمدة الداخلية | الاستعلامات |
| `Exceptions` | أخطاء المجال (`InvalidCredentials`) + `ApiExceptionRenderer` يلي بيوحّد شكل الأخطاء | — |
| `Support` | أدوات صغيرة بدون Laravel (`LikePattern`) | — |

> الاتجاه واحد: `Route → FormRequest → Controller → Action → Model / Service`، والرد دايماً عبر `JsonResource`.

## مسار الطلب

```text
HTTP
 ├─ SetLocale (global)          # Accept-Language → ar | en، والافتراضي ar
 ├─ statefulApi (Sanctum)       # إذا الطلب جاي من الـ SPA: كوكي جلسة + CSRF
 ├─ auth:sanctum / throttle     # 401 / 429
 ├─ FormRequest                 # validate → 422، authorize (Policy) → 403
 ├─ Controller ──▶ Action ──▶ Model (PostgreSQL)
 └─ JsonResource ──▶ { "data": ... }

أي Exception بأي مرحلة ──▶ ApiExceptionRenderer ──▶ { "message", "code", "errors"? }
```

مثال من القالب:

```text
GET /api/v1/items?per_page=5&search=milk
 → ListItemsRequest   (page ≥ 1، per_page 1..100، search ≤ 100، ItemPolicy::viewAny)
 → ItemController@index
 → ListItems::handle($user, 5, 1, 'milk')   # عناصر المستخدم فقط، الأحدث أولاً، ILIKE على الاسم
 → ItemResource::collection($paginator)     # data / links / meta
```

## تسجيل الدخول (Sanctum بطريقتين)

- **كوكي الـ SPA** (لوحة React على نفس الموقع): `GET /sanctum/csrf-cookie` ثم
  `POST /api/v1/auth/login`. الطلب بيعتبر "stateful" بس إذا الـ Origin/Referer موجود بـ
  `SANCTUM_STATEFUL_DOMAINS`، غير هيك بيرجع `400 session_required`.
- **Bearer token** (موبايل وسيرفر Next.js): `POST /api/v1/auth/token` بيرجّع توكن صالح 30 يوم
  (`SANCTUM_TOKEN_EXPIRATION`). ما في refresh token: عند 401 العميل بيسجّل دخول من جديد.
- الطريقتين بيوصلوا لنفس الـ guard `auth:sanctum`، فكل الروابط المحمية بتقبل الاتنين.
- الـ Policies بتفحص صلاحيات التوكن من `App\Enums\TokenAbility`؛ توكنات تطبيقاتنا فيها `*`،
  ودخول الكوكي بيعدّي كل الفحوصات.
- محاولات الدخول محدودة بـ limiter اسمه `login`: ‏5 بالدقيقة لكل إيميل + IP.

## الأخطاء

كل رد غير 2xx إله شكل واحد، مضبوط بـ `bootstrap/app.php` عن طريق `ApiExceptionRenderer`:

```json
{ "message": "رسالة مترجمة", "code": "validation_failed", "errors": { "email": ["..."] } }
```

- `code` ثابت والفرونت بيعتمد عليه (`unauthenticated`، `forbidden`، `not_found`،
  `validation_failed`، `invalid_credentials`، `too_many_requests`، `server_error`...).
- `errors` بيطلع بس مع 422.
- رسالة الـ 500 الحقيقية بتظهر بس بـ `local` و `testing`.
- خطأ مجال جديد؟ اعمل class بترث `ApiException` وحدد `status()` و `errorCode()`، وحط الرسالة
  بملفات `lang`.

## اللغة

- `SetLocale` بيختار اللغة من `Accept-Language` ضمن `app.supported_locales` (‏`ar`، `en`)،
  والافتراضي `ar`، وبيرجّع `Content-Language`.
- كل نص بيشوفه المستخدم بـ `lang/ar` و `lang/en`: رسائل الـ validation، أسماء الحقول
  (`validation.attributes`)، ورسائل الأخطاء (`errors.php`).

## قاعدة البيانات

- PostgreSQL بكل مكان (محلي، تستات، CI، إنتاج). التستات على قاعدة `app_test`.
- المفاتيح ULIDs بحروف كبيرة (`HasUppercaseUlids`)، فما في أرقام متسلسلة بالروابط أو الردود.
- كل التواريخ `timestamptz` بـ UTC، والـ API بيرجعها `...Z`.
- `Model::preventLazyLoading()` شغّال برّا الإنتاج: أي N+1 بيفشل فوراً، فاعمل `with()`.

## تقسيم المجلدات

```text
app/
├── Actions/
│   ├── Auth/                 # AuthenticateUser، IssueAccessToken، RevokeCurrentToken
│   ├── Item/                 # ListItems
│   └── User/                 # CreateUser
├── Console/Commands/         # CreateUserCommand (app:create-user)
├── Enums/                    # UserRole، TokenAbility
├── Exceptions/
│   ├── ApiException.php      # أساس أخطاء المجال
│   ├── ApiExceptionRenderer.php
│   └── Auth/                 # InvalidCredentials، SessionRequired
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── Public/Auth/      # LoginController، IssueTokenController
│   │   └── Customer/         # ItemController + Auth/ (me، logout، revoke token)
│   ├── Middleware/           # SetLocale
│   ├── Requests/             # Auth/، Item/
│   └── Resources/            # Auth/، Item/، User/
├── Models/                   # User، Item + Concerns/HasUppercaseUlids
├── Policies/                 # ItemPolicy
├── Providers/                # AppServiceProvider (lazy loading، rate limiter)
└── Support/                  # LikePattern
bootstrap/app.php             # الـ middleware وتسجيل شكل الأخطاء
config/                       # sanctum (stateful، expiration)، scramble، app (supported_locales)
database/
├── factories/                # UserFactory (admin)، ItemFactory (withoutDescription)
├── migrations/
└── seeders/                  # DatabaseSeeder (local فقط) → DemoSeeder
lang/{ar,en}/                 # validation، auth، errors...
routes/
├── api.php                   # /api/v1 → api/v1/*.php
├── api/v1/public.php         # بدون دخول
├── api/v1/customer.php       # auth:sanctum
└── console.php               # الجدولة (حذف التوكنات المنتهية)
tests/
├── Feature/<Domain>/         # Auth، Item، User + الأخطاء واللغة
└── Unit/<Domain>/            # Exceptions، Support
docs/                         # API.md، decisions/
```

## قواعد الكتابة

- `declare(strict_types=1)` و `final` و أنواع لكل شي: Pint بيفرضهم (`pint.json`)، و Larastan
  level 6 بيفحص الباقي.
- **Controller قصير:** طلب متحقق منه ← Action واحد ← Resource.
- **Action = حالة استخدام وحدة** باسم فعل + اسم (`ListItems`)، والكتابات المتعددة جوّا
  `DB::transaction`.
- **ما في `env()` برّا `config/`**، وإعدادات المنتج بـ `config/<product>.php`.
- **كل endpoint إله FormRequest و Policy و Resource و feature tests** (نجاح، 422، 401/403).
- **التستات بـ Pest** وبأسماء جمل (`it('never shows another user\'s items')`)، والـ factories مع
  states بدل arrays يدوية.

## ملخص الـ API

| Method | Path | Auth | ملاحظات |
|---|---|---|---|
| GET | `/sanctum/csrf-cookie` | — | ‏204 + كوكي `XSRF-TOKEN` |
| POST | `/api/v1/auth/login` | — (SPA) | ‏200 `{data: User}` + جلسة، throttle `login` |
| POST | `/api/v1/auth/token` | — | ‏201 `{data: {token, token_type, expires_at, user}}`، throttle `login` |
| GET | `/api/v1/auth/me` | sanctum | ‏200 `{data: User}` |
| POST | `/api/v1/auth/logout` | sanctum | ‏204 |
| DELETE | `/api/v1/auth/token` | sanctum | ‏204، بيلغي التوكن الحالي |
| GET | `/api/v1/items` | sanctum | `page`، `per_page` (‏1..100)، `search` |

التفاصيل الكاملة (الأكواد، الأشكال، البيانات التجريبية) بـ [docs/API.md](docs/API.md).

## إضافة ميزة جديدة

مثال: حجوزات (`Booking`).

1. **الداتابيز:** `php artisan make:migration create_bookings_table` بمفتاح
   `$table->ulid('id')->primary()`، و `foreignUlid(...)->constrained()` مع `cascadeOnDelete()` أو
   `restrictOnDelete()` عن قصد، و `timestampsTz()`، و index لكل عمود بـ `where`/`orderBy`.
2. **الموديل:** `app/Models/Booking.php` بـ `HasUppercaseUlids` و `#[Fillable]` والـ casts
   (والـ enums بـ `app/Enums`)، و factory مع states.
3. **الـ Action:** `app/Actions/Booking/CreateBooking.php` بدالة `handle()` مكتوبة الأنواع.
   منطق مشترك بين أكتر من Action ← `app/Services/Booking/`.
4. **الـ Policy:** `app/Policies/BookingPolicy.php` (بتنكشف لحالها)، ولو للتوكنات أضف قدرة
   بـ `TokenAbility`.
5. **الطلب:** `app/Http/Requests/Booking/StoreBookingRequest.php`: القواعد + `authorize()` عبر
   الـ Policy.
6. **الشكل:** `app/Http/Resources/Booking/BookingResource.php` (التواريخ بـ
   `toIso8601ZuluString()`).
7. **الـ Controller والرابط:** `Api/V1/Customer/BookingController.php`، والرابط بـ
   `routes/api/v1/customer.php` (أو `admin.php`).
8. **الترجمة:** أسماء الحقول بـ `validation.attributes`، وأي رسالة خطأ جديدة بـ `lang/ar` و
   `lang/en`.
9. **التستات:** `tests/Feature/Booking/` (نجاح، 422، 401/403، ومستخدم ما بيشوف بيانات غيره)،
   و `tests/Unit/` للمنطق.
10. شغّل `vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test` قبل الـ PR.
