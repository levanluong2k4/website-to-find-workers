---
name: clean-code
description: Pragmatic coding standards - concise, direct, no over-engineering, no unnecessary comments
allowed-tools: Read, Write, Edit
version: 2.0
priority: CRITICAL
---

# Clean Code - Pragmatic AI Coding Standards

> **CRITICAL SKILL** - Be **concise, direct, and solution-focused**.

---

## Core Principles

| Principle | Rule |
|-----------|------|
| **SRP** | Single Responsibility - each function/class does ONE thing |
| **DRY** | Don't Repeat Yourself - extract duplicates, reuse |
| **KISS** | Keep It Simple - simplest solution that works |
| **YAGNI** | You Aren't Gonna Need It - don't build unused features |
| **Boy Scout** | Leave code cleaner than you found it |

---

## Naming Rules

| Element | Convention |
|---------|------------|
| **Variables** | Reveal intent: `userCount` not `n` |
| **Functions** | Verb + noun: `getUserById()` not `user()` |
| **Booleans** | Question form: `isActive`, `hasPermission`, `canEdit` |
| **Constants** | SCREAMING_SNAKE: `MAX_RETRY_COUNT` |

> **Rule:** If you need a comment to explain a name, rename it.

---

## Function Rules

| Rule | Description |
|------|-------------|
| **Small** | Max 20 lines, ideally 5-10 |
| **One Thing** | Does one thing, does it well |
| **One Level** | One level of abstraction per function |
| **Few Args** | Max 3 arguments, prefer 0-2 |
| **No Side Effects** | Don't mutate inputs unexpectedly |

---

## Code Structure

| Pattern | Apply |
|---------|-------|
| **Guard Clauses** | Early returns for edge cases |
| **Flat > Nested** | Avoid deep nesting (max 2 levels) |
| **Composition** | Small functions composed together |
| **Colocation** | Keep related code close |

---

## AI Coding Style

| Situation | Action |
|-----------|--------|
| User asks for feature | Write it directly |
| User reports bug | Fix it, don't explain |
| No clear requirement | Ask, don't assume |

---

## Anti-Patterns (DON'T)

| ❌ Pattern | ✅ Fix |
|-----------|-------|
| Comment every line | Delete obvious comments |
| Helper for one-liner | Inline the code |
| Factory for 2 objects | Direct instantiation |
| utils.ts with 1 function | Put code where used |
| "First we import..." | Just write code |
| Deep nesting | Guard clauses |
| Magic numbers | Named constants |
| God functions | Split by responsibility |

---

## 🔴 Before Editing ANY File (THINK FIRST!)

**Before changing a file, ask yourself:**

| Question | Why |
|----------|-----|
| **What imports this file?** | They might break |
| **What does this file import?** | Interface changes |
| **What tests cover this?** | Tests might fail |
| **Is this a shared component?** | Multiple places affected |

**Quick Check:**
```
File to edit: UserService.ts
└── Who imports this? → UserController.ts, AuthController.ts
└── Do they need changes too? → Check function signatures
```

> 🔴 **Rule:** Edit the file + all dependent files in the SAME task.
> 🔴 **Never leave broken imports or missing updates.**

---

## Summary

| Do | Don't |
|----|-------|
| Write code directly | Write tutorials |
| Let code self-document | Add obvious comments |
| Fix bugs immediately | Explain the fix first |
| Inline small things | Create unnecessary files |
| Name things clearly | Use abbreviations |
| Keep functions small | Write 100+ line functions |

> **Remember: The user wants working code, not a programming lesson.**

---

## 🔴 Self-Check Before Completing (MANDATORY)

**Before saying "task complete", verify:**

| Check | Question |
|-------|----------|
| ✅ **Goal met?** | Did I do exactly what user asked? |
| ✅ **Files edited?** | Did I modify all necessary files? |
| ✅ **Code works?** | Did I test/verify the change? |
| ✅ **No errors?** | Lint and TypeScript pass? |
| ✅ **Nothing forgotten?** | Any edge cases missed? |

> 🔴 **Rule:** If ANY check fails, fix it before completing.

---

## Verification Scripts (MANDATORY)

> 🔴 **CRITICAL:** Each agent runs ONLY their own skill's scripts after completing work.

### Agent → Script Mapping

| Agent | Script | Command |
|-------|--------|---------|
| **frontend-specialist** | UX Audit | `python .agent/skills/frontend-design/scripts/ux_audit.py .` |
| **frontend-specialist** | A11y Check | `python .agent/skills/frontend-design/scripts/accessibility_checker.py .` |
| **backend-specialist** | API Validator | `python .agent/skills/api-patterns/scripts/api_validator.py .` |
| **mobile-developer** | Mobile Audit | `python .agent/skills/mobile-design/scripts/mobile_audit.py .` |
| **database-architect** | Schema Validate | `python .agent/skills/database-design/scripts/schema_validator.py .` |
| **security-auditor** | Security Scan | `python .agent/skills/vulnerability-scanner/scripts/security_scan.py .` |
| **seo-specialist** | SEO Check | `python .agent/skills/seo-fundamentals/scripts/seo_checker.py .` |
| **seo-specialist** | GEO Check | `python .agent/skills/geo-fundamentals/scripts/geo_checker.py .` |
| **performance-optimizer** | Lighthouse | `python .agent/skills/performance-profiling/scripts/lighthouse_audit.py <url>` |
| **test-engineer** | Test Runner | `python .agent/skills/testing-patterns/scripts/test_runner.py .` |
| **test-engineer** | Playwright | `python .agent/skills/webapp-testing/scripts/playwright_runner.py <url>` |
| **Any agent** | Lint Check | `python .agent/skills/lint-and-validate/scripts/lint_runner.py .` |
| **Any agent** | Type Coverage | `python .agent/skills/lint-and-validate/scripts/type_coverage.py .` |
| **Any agent** | i18n Check | `python .agent/skills/i18n-localization/scripts/i18n_checker.py .` |

> ❌ **WRONG:** `test-engineer` running `ux_audit.py`
> ✅ **CORRECT:** `frontend-specialist` running `ux_audit.py`

---

### 🔴 Script Output Handling (READ → SUMMARIZE → ASK)

**When running a validation script, you MUST:**

1. **Run the script** and capture ALL output
2. **Parse the output** - identify errors, warnings, and passes
3. **Summarize to user** in this format:

```markdown
## Script Results: [script_name.py]

### ❌ Errors Found (X items)
- [File:Line] Error description 1
- [File:Line] Error description 2

### ⚠️ Warnings (Y items)
- [File:Line] Warning description

### ✅ Passed (Z items)
- Check 1 passed
- Check 2 passed

**Should I fix the X errors?**
```

4. **Wait for user confirmation** before fixing
5. **After fixing** → Re-run script to confirm

> 🔴 **VIOLATION:** Running script and ignoring output = FAILED task.
> 🔴 **VIOLATION:** Auto-fixing without asking = Not allowed.
> 🔴 **Rule:** Always READ output → SUMMARIZE → ASK → then fix.


---
name: laravel-clean-code
description: Pragmatic Laravel MVC standards - Skinny Controllers, Form Requests, Eloquent best practices
allowed-tools: Read, Write, Edit, Terminal
version: 2.0
priority: CRITICAL
---

# Clean Code - Laravel MVC Standards

> **CRITICAL SKILL** - Giữ **Controller mỏng (Skinny)**, Logic nằm ở **Model/Service**, và Validation nằm ở **FormRequest**.

---

## MVC Core Principles

| Thành phần | Trách nhiệm (Responsibility) | Quy tắc vàng |
|------------|------------------------------|--------------|
| **Model** | Xử lý dữ liệu & Logic nghiệp vụ | **Fat Model**: Logic nằm ở đây |
| **View** | Hiển thị dữ liệu (Blade/API Resource) | **Dumb View**: Không chứa logic phức tạp |
| **Controller** | Điều phối (Nhận request -> Gọi Model -> Trả View) | **Skinny Controller**: Chỉ điều phối, không xử lý |
| **Route** | Định tuyến URL | Chỉ định tuyến, không dùng Closure function |

---

## Naming Rules (Laravel Conventions)

| Thành phần | Quy tắc | Ví dụ |
|------------|---------|-------|
| **Controller** | PascalCase (Singular) | `UserController`, `AuthController` |
| **Model** | PascalCase (Singular) | `User`, `Product`, `Order` |
| **Table** | snake_case (Plural) | `users`, `products`, `orders` |
| **Variable** | camelCase | `$userList`, `$isActive` |
| **Method** | camelCase | `getActiveUsers()`, `store()` |
| **Route Name** | snake_case / dot.notation | `users.index`, `admin.dashboard` |
| **Config/DB** | snake_case | `api_key`, `created_at` |

---

## Controller Rules (Skinny Controller)

| Quy tắc | Mô tả |
|---------|-------|
| **No Validation** | Không viết `$request->validate()` trong controller. Dùng **FormRequest**. |
| **No Query Logic** | Không viết query phức tạp (`Where` chains) trong controller. Dùng **Model Scopes**. |
| **Return Types** | Luôn return `View`, `JsonResponse`, hoặc `RedirectResponse`. |
| **Dependency Injection** | Inject Service/Repository vào constructor hoặc method. |

> ❌ **Sai:**
> ```php
> public function store(Request $request) {
>     $request->validate(['name' => 'required']); // Sai chỗ
>     $user = new User; $user->name = $request->name; $user->save();
>     return redirect('/home');
> }
> ```

> ✅ **Đúng:**
> ```php
> public function store(StoreUserRequest $request, UserService $service) {
>     $service->createUser($request->validated());
>     return to_route('home');
> }
> ```

---

## Model Rules (Eloquent Power)

| Pattern | Áp dụng |
|---------|---------|
| **Fillable** | Luôn khai báo `$fillable` hoặc `$guarded` để tránh Mass Assignment. |
| **Relationships** | Định nghĩa rõ ràng (`hasMany`, `belongsTo`). Luôn return type. |
| **Scopes** | Đóng gói query tái sử dụng (`scopeActive`, `scopePopular`). |
| **Accessors/Mutators** | Format dữ liệu (`getFormattedPriceAttribute`). |

---

## Anti-Patterns (DON'T DO THIS)

| ❌ Pattern | ✅ Fix (Laravel Way) |
|-----------|----------------------|
| **N+1 Query** (Query trong loop) | Dùng Eager Loading (`User::with('posts')->get()`) |
| **Logic trong Blade** (`@php`) | Chuyển logic về Model hoặc View Composer |
| **Gọi `env()` bên ngoài config** | Chỉ gọi `config('app.key')`, không gọi `env()` trực tiếp |
| **Query Builder lộn xộn** | Dùng Eloquent Scopes hoặc Service Class |
| **Hardcode URLs** | Dùng `route('name')` hoặc `url()` helper |
| **Try-Catch trong Controller** | Dùng Global Exception Handler (`App/Exceptions/Handler.php`) |

---

## 🔴 Before Editing ANY File (THINK FIRST!)

**Trước khi sửa file, tự hỏi:**

| Câu hỏi | Lý do |
|---------|-------|
| **Route có đổi không?** | Cần cập nhật `web.php` hoặc `api.php` |
| **Model có field mới?** | Cần tạo Migration và update `$fillable` |
| **Validation thay đổi?** | Cập nhật file `FormRequest` tương ứng |
| **File này được import ở đâu?** | Kiểm tra `use App\Models\...` |

**Quick Check:**