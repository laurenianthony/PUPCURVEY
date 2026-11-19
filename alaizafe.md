# PUPCURVEY Backend-to-Frontend Integration Contract

Build the frontend Blade/Tailwind/Alpine.js views in PUP Maroon (`#800000`) and Gold (`#D4AF37`) to connect directly to our existing Laravel backend routes, form inputs, and PostgreSQL JSONB structures. Do NOT modify backend controllers, routes, migrations, or Filament resources.

## 1. Required Blade Views & Passed Variables
| View File Path | Route Name | Variables Passed from Backend |
|---|---|---|
| `resources/views/layouts/app.blade.php` | Authenticated Layout | Use `auth()->user()` (`name`, `student_id`, `status`, `wallet_balance`) |
| `resources/views/welcome.blade.php` | `home` (`GET /`) | None |
| `resources/views/auth/register.blade.php` | `register` (`GET /register`) | None |
| `resources/views/auth/login.blade.php` | `login` (`GET /login`) | None |
| `resources/views/auth/forgot-password.blade.php` | `password.request` (`GET /forgot-password`) | None |
| `resources/views/auth/reset-password.blade.php` | `password.reset` (`GET /reset-password/{token}`) | `$request` (`$request->route('token')`, `$request->email`) |
| `resources/views/auth/pending.blade.php` | `verification.pending` (`GET /verification-pending`) | Use `auth()->user()` (`name`, `student_id`, `program`, `year_level`) |
| `resources/views/surveys/index.blade.php` | `surveys.index` (`GET /surveys`) | `$surveys` (Collection of eligible `Survey` models with `responses_count`) |
| `resources/views/surveys/create.blade.php` | `surveys.create` (`GET /surveys/create`) | None |
| `resources/views/surveys/edit.blade.php` | `surveys.edit` (`GET /surveys/{survey}/edit`) | `$survey` (`Survey` model with `$survey->schema` pre-loaded) |
| `resources/views/surveys/show.blade.php` | `surveys.show` (`GET /s/{survey}`) | `$survey` (`Survey` model with `$survey->schema`) |
| `resources/views/inbox/my-surveys.blade.php` | `inbox.my-surveys` (`GET /inbox/my-surveys`) | `$surveys` (Creator's own `Survey` models with `responses_count`) |
| `resources/views/inbox/survey-results.blade.php` | `surveys.results` (`GET /surveys/{survey}/results`) | `$survey`, `$summary` (array keyed by question `id`), `$individualResponses` |
| `resources/views/inbox/wallet.blade.php` | `inbox.wallet` (`GET /inbox/wallet`) | `$user`, `$professors` (active `Professor` models), `$transactions` (paginated) |
| `resources/views/settings/edit.blade.php` | `settings.edit` (`GET /settings`) | `$user` (`auth()->user()`) |

---

## 2. Exact Form Input `name` Attributes & Endpoints
- **Register (`POST /register`):** `name`, `student_id` (regex `^\d{4}-\d{5}-CL-\d$`), `email`, `program` (`BSIT`, `BTLED`, `BSENT`), `year_level` (`1`–`4`), `password`, `password_confirmation`
- **Login (`POST /login`):** `email`, `password`, `remember`
- **Forgot Password (`POST /forgot-password`):** `email`
- **Reset Password (`POST /reset-password`):** `token`, `email`, `password`, `password_confirmation`
- **Create Survey (`POST /surveys`) & Edit Survey (`PATCH /surveys/{survey}`):**
  - `title` (string, required)
  - `description` (string, nullable)
  - `target_programs[]` (array, min 1: `BSIT`, `BTLED`, `BSENT`, or `ALL`)
  - `target_year_levels[]` (array, min 1: `1`, `2`, `3`, `4`, or `ALL`)
  - `schema_json` (hidden input containing JSON string — see Section 3)
  - `target_respondents` (integer, nullable)
  - `deadline_at` (datetime string, nullable)
  - `shuffle_questions` (`1` or `0`)
  - `collect_respondent_info` (`1` or `0`)
- **Toggle Survey Open/Close (`PATCH /surveys/{survey}/toggle-close`):** No fields needed (CSRF + `@method('PATCH')`).
- **Submit Survey Response (`POST /surveys/{survey}/responses`):**
  - `answers_json` (hidden input containing JSON string — see Section 4)
- **Redeem Wallet Points (`POST /inbox/wallet/redeem`):**
  - `professor_id` (integer ID from `$professors`)
  - `points` (integer, min `1`, max `$user->wallet_balance`)
- **Settings — Update Password (`PUT /password`):**
  - `current_password`, `password`, `password_confirmation`
- **Settings — Email Reset Link (`POST /settings/send-reset-link`):**
  - No inputs needed (just CSRF token; automatically emails reset link to `$user->email`).

---

## 3. `schema_json` Structure (Survey Builder -> `surveys.schema`)
Serialize the form builder state into `schema_json` using this exact structure:
```json
{
  "sections": [
    {
      "id": "sec_1",
      "title": "Section Title",
      "description": "Optional section instructions",
      "questions": [
        {
          "id": "q_1",
          "type": "multiple_choice",
          "label": "Question text here",
          "required": true,
          "options": ["Option A", "Option B"]
        }
      ]
    }
  ]
}
```

Supported `type` values parsed by `ResponseController`:
- **Choice types** (`"options": [...]`): `multiple_choice`, `checkboxes`, `dropdown`
- **Scale type** (`"min": 1, "max": 5, "min_label": "...", "max_label": "..."`): `linear_scale`
- **Grid types** (`"rows": [...], "columns": [...]`): `multiple_choice_grid`, `checkbox_grid`
- **Text/Input types** (no options array needed): `short_answer`, `paragraph`, `date`, `time`

## 4. `answers_json` Structure (Survey Answerer -> `responses.answers`)
Key the JSON object by each question's `id` and serialize into `answers_json`:
```json
{
  "q_1": "Option A",
  "q_2": ["Checkbox 1", "Checkbox 2"],
  "q_3": 5,
  "q_4": { "Row 1": "Column A", "Row 2": "Column B" },
  "q_5": "Short text answer"
}
```

## 5. Global Flash Messages & Validation Errors
Display these in `resources/views/layouts/app.blade.php`:
- `session('success')` (green toast/banner)
- `session('error')` (red toast/banner)
- `session('status')` (used by Laravel's password reset link confirmation)
- `$errors->all()` (validation error list, including required question alerts)