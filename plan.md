# Stages 3 + 4 + 5 — Navigation, Site Settings, and Locale Detection

## Context

Stages 1 and 2 landed the data model (Page + PageContent, locale-scoped navigations + site_settings columns) and the per-locale Page editing UX. The remaining stages wire the rest of the admin surface and frontend routing to be locale-aware so that, together, a consuming app can actually run in multiple locales end-to-end.

These three stages are relatively independent but share one underlying mechanic: `app()->getLocale()` is the single source of truth at runtime, so Stage 5 (which computes that from domain + URL) is the anchor — when it works correctly, Stage 3 (Navigation) and Stage 4 (SiteSettings) fall out naturally because their readers already call `app()->getLocale()`.

User decisions (confirmed):

- **Navigation fallback is config-only** (`cms.navigation_fallback`), no per-nav override.
- **`->translatable()` is a Filament field macro** (`TextInput::make('x')->translatable()`).
- **Site Settings UX = single page + locale switcher in header.** Switching reloads form with merged values; saving writes translatable fields to the locale row, non-translatable to the global row.
- **Domain mappings: many domains → one locale, exact host match**, unique on `domain`.

Implementation order: **Stage 5 first** (sets app locale from domain), then **Stage 3 + Stage 4 in parallel** (both consume the locale but don't depend on each other).

---

## Stage 5 — Domain-Based Locale Detection & Middleware

### 5.1 Schema & Model

- New migration `2026_04_..._create_locale_domain_mappings_table.php`:
  - `id`, `locale` (string, indexed), `domain` (string, **unique**), timestamps.
  - No FK to anything (locales live in config).
- New model [src/Models/LocaleDomainMapping.php](src/Models/LocaleDomainMapping.php): `$guarded = []`, no relationships needed.

### 5.2 `Locales` helper additions

[src/Support/Locales.php](src/Support/Locales.php):

- `fromDomain(string $host): ?string` — look up `LocaleDomainMapping` by exact `domain` match, return `locale` or `null`. Cache in-request (static array) so repeated calls in the same request don't re-hit the DB.

### 5.3 `LocaleDetectionMiddleware` — full logic

[src/Http/Middleware/LocaleDetectionMiddleware.php](src/Http/Middleware/LocaleDetectionMiddleware.php) replaces the Stage 1 stub with:

1. Resolve the **initial locale** from `Locales::fromDomain($request->getHost()) ?? Locales::default()`.
2. Read the URL `{locale}` route parameter (if present and `Locales::isValid()`), it **overrides** the domain-derived default. This is the classic "locale prefix wins over domain default" behavior.
3. **Redirect rule**: if the route has a `{locale}` segment that equals the current effective default (domain-derived), 301-redirect to the same URL without the prefix. E.g. `en.example.com/en/about` → `en.example.com/about`. Use `redirect()->to(..., 301)` with the request query string preserved.
4. `app()->setLocale($locale)` so all downstream code (validation strings, Blade, Inertia share) sees the right locale.

Ordering: Laravel middleware precedence — the consuming app registers this **before** `HandleInertiaRequests` in its HTTP kernel. The [routes/web.php](routes/web.php) group already wires it per-route, which is sufficient; document the kernel-ordering note in a Stage-5 readme snippet.

### 5.4 `PageController` — consume `missing_locale_behavior`

[src/Http/Controllers/PageController.php](src/Http/Controllers/PageController.php):

- `show()`: when no `PageContent` matches `(slug, locale)`:
  - If `config('cms.missing_locale_behavior') === 'redirect'`:
    - If the current locale has a frontpage, redirect to its URL.
    - Else, 404.
  - Else (`'404'`): 404.
- `frontpage()`: when no frontpage exists in the current locale:
  - If `navigation_fallback`-style semantics were desired we'd fall back to default-locale frontpage, but the Stage 1 spec explicitly says **404 if no frontpage in the active locale**. Keep it as a plain 404.

The current `show()` already redirects to the frontpage when the record is the frontpage — keep that branch.

### 5.5 `HandleInertiaRequests` — share locale context

[src/Http/Middleware/HandleInertiaRequests.php](src/Http/Middleware/HandleInertiaRequests.php) `share()`:

- Add a `locale` key → `app()->getLocale()`.
- Add a `locales` key → `Locales::all()` (for future Stage 6b switcher UI — harmless to ship early).

### 5.6 Filament admin page for domain mappings

New [src/Filament/Resources/LocaleDomainMappings/LocaleDomainMappingResource.php](src/Filament/Resources/LocaleDomainMappings/LocaleDomainMappingResource.php) (full Resource with CRUD is simpler than a custom Page for a plain table):

- Form: `Select::make('locale')` options from `Locales::all()`; `TextInput::make('domain')` required, unique, lowercase-normalized on save.
- Table: columns `domain`, `locale`; standard CRUD.
- Navigation group: "Settings" (sibling of Site Settings).
- Register in [src/CmsPlugin.php](src/CmsPlugin.php) alongside existing resources.

### 5.7 Tests

- Extend [tests/Feature/Routing/LocaleRouteSkeletonTest.php](tests/Feature/Routing/LocaleRouteSkeletonTest.php) / new `LocaleDetectionMiddlewareTest.php`:
  - Domain mapping resolves to the mapped locale (no URL prefix).
  - Domain without mapping falls back to `cms.default_locale`.
  - `/en/about` when `en` is default → 301 to `/about`.
  - `/da/om-os` when `da` is secondary → serves 200 from the Danish content.
  - Missing locale content: `redirect` config → redirects to locale frontpage; `404` config → 404s; redirect with no frontpage in that locale → 404.

---

## Stage 3 — Navigation Localization

### 3.1 Navigation form + resource

[src/Filament/Resources/Navigations/Schemas/NavigationForm.php](src/Filament/Resources/Navigations/Schemas/NavigationForm.php):

- Add `Select::make('locale')` with options `Locales::all()`, default `Locales::default()`, required, **visible only when `count(Locales::all()) > 1`**. Disabled on edit (a nav record belongs to one locale; to change locale, delete and recreate).
- Update `type` unique validation from `->unique()` to `->unique(modifyRuleUsing: fn ($rule, Get $get) => $rule->where('locale', $get('locale')), ignoreRecord: true)` — compound uniqueness with locale.

[src/Filament/Resources/Navigations/Tables/NavigationsTable.php](src/Filament/Resources/Navigations/Tables/NavigationsTable.php):

- Add `TextColumn::make('locale')` (badge-style via `->badge()`), visible only when >1 locales. Sortable.
- Default sort: `locale` then `type`.

[src/Filament/Resources/Navigations/Pages/ListNavigations.php](src/Filament/Resources/Navigations/Pages/ListNavigations.php):

- No locale *filter* needed — rows are globally listed, locale shown as a column. Keeps discoverability ("why is my Danish header missing?") higher than a hidden filter.

### 3.2 `HandleInertiaRequests::loadNavigation()` fallback

[src/Http/Middleware/HandleInertiaRequests.php](src/Http/Middleware/HandleInertiaRequests.php) — current impl returns `[]` when no nav exists for the active locale.

- Extract the nav-lookup into a small helper (or inline rewrite) that:
  1. Queries `Navigation::where('type', $type)->where('locale', $locale)->first()`.
  2. If null and `config('cms.navigation_fallback') === 'default_locale'` and `$locale !== Locales::default()`, re-query with the default locale.
  3. If still null, return `[]`.
- After resolving the nav, use **the original visitor locale** (not the fallback locale) for `ReplacePageID::make()->handle($nav->items, $locale)` — we want slugs the visitor can actually reach. If a link points to a page that has no `PageContent` in the visitor's locale, the existing published-filter step (lines 41–49 of that file) will drop it.
- Keep the published-filter step; extend it to also check `locale`: a published sibling in a different locale doesn't make the link valid.

### 3.3 `ReplacePageID`

[src/Actions/ReplacePageID.php](src/Actions/ReplacePageID.php) already accepts `?string $locale = null` — no signature change needed. Just verify callers pass the visitor locale explicitly (they do in `HandleInertiaRequests`).

### 3.4 `NavigationFormSchema` override — verify

The `CmsServiceProvider::$navigationFormSchema` discovery mechanism ([src/CmsServiceProvider.php](src/CmsServiceProvider.php) lines 163–170) doesn't touch locale and keeps working. Add a test that confirms a consumer's custom blocks still render per-locale navs end-to-end.

### 3.5 Tests

New `tests/Feature/Localization/NavigationLocaleTest.php`:

- Create a header nav in `en` and in `da`. Visit `/` vs `/da` and assert the shared Inertia `header` prop contains the right items.
- `navigation_fallback=default_locale`: locale with no nav gets the default-locale nav.
- `navigation_fallback=empty`: locale with no nav gets `[]`.
- Nav link points to a page with no `PageContent` in the visitor's locale → link is filtered out.
- Compound unique `(type, locale)` — creating a second `en` header fails validation.

---

## Stage 4 — Site Settings Localization + `->translatable()` Decorator

### 4.1 `->translatable()` macro

New [src/Filament/Support/TranslatableField.php](src/Filament/Support/TranslatableField.php) — a tiny boot helper:

- In [src/CmsServiceProvider.php](src/CmsServiceProvider.php) `boot()`, register the macro on Filament's `Field` base class:
  ```php
  Field::macro('translatable', function () {
      /** @var Field $this */
      $this->getViewData(); // no-op, just typing
      return $this->extraAttributes(['data-translatable' => 'true'], merge: true)
          ->registerTranslatable();
  });
  ```
  In practice the macro sets a `meta` flag the settings page reads when deciding where to persist the value. Concretely: we set `$field->extraAttributes(['data-translatable' => true])` *and* store the field name in a static registry keyed by the current schema so the save step knows which fields are translatable.

Simpler approach (preferred): the macro just sets a known meta via `$field->getContainer()` → instead, use a **name convention list** on the page itself:

- The macro records the field's statepath in a static `$translatableFields` bag on a new `Translatable` service class.
- `ManageSiteSettings::save()` reads the bag to partition form state into "translatable" vs "global".

Concrete shape:

```php
// src/Filament/Support/Translatable.php
class Translatable
{
    private static array $fields = [];
    public static function mark(string $statePath): void { self::$fields[$statePath] = true; }
    public static function isTranslatable(string $statePath): bool { return isset(self::$fields[$statePath]); }
    public static function all(): array { return array_keys(self::$fields); }
    public static function reset(): void { self::$fields = []; }
}
```

Macro body:

```php
Field::macro('translatable', function () {
    /** @var \Filament\Schemas\Components\Field $this */
    Translatable::mark($this->getStatePath());
    return $this->hint(__('cms::cms.site_settings.translatable_hint'))
                ->hintIcon('heroicon-o-language');
});
```

The visual hint (small "Translatable" icon) makes intent clear in the form.

### 4.2 `SiteSettings` model changes

[src/Models/SiteSettings.php](src/Models/SiteSettings.php):

- Replace `getSingleton()` with two methods:
  - `global(): self` — `firstOrCreate(['locale' => null])`.
  - `forLocale(string $locale): ?self` — `firstWhere('locale', $locale)`.
- New `getResolved(string $locale): array`:
  - Deep-merge `global()->data ?? []` with `forLocale($locale)?->data ?? []`; locale wins per key.
  - Return the merged array.

Keep backward compat: `getSingleton()` → alias to `global()` so Stage 2 code that still calls it doesn't break. Delete the alias in a follow-up pass.

### 4.3 `ManageSiteSettings` page

[src/Filament/Pages/ManageSiteSettings.php](src/Filament/Pages/ManageSiteSettings.php):

- Add a **header action** `Select`-style locale switcher (Filament `Actions\Action` is awkward for Select; use a simple `Livewire` public property `$editingLocale` with a top-of-form `Select` component *outside* the main form, rendered via a custom view hook OR a disabled `Hidden` field + header).

Pragmatic path: add a `Select::make('_editing_locale')` as the first field in the form, `->options([null => 'Global'] + Locales::all())`, `->live()`, `->afterStateUpdated(fn ($state) => $this->switchLocale($state))`, `->dehydrated(false)`.

- `mount()`: default `$editingLocale = null` (global).
- `mount()` / `switchLocale()`: fill form with:
  - If `$editingLocale === null`: global row's `data`.
  - Otherwise: `SiteSettings::getResolved($editingLocale)` (merged view) so translators see inherited values greyed-in-mind; they can override by editing.
- `save()` (lines 55–67):
  - If `$editingLocale === null`: write entire form state to the global row's `data`.
  - Otherwise:
    - Split form state using `Translatable::all()`: keys marked translatable go to `forLocale($editingLocale)->data`; other keys go to `global()->data` (so non-translatable edits always update global regardless of which locale is being edited). **Better UX**: when editing a locale, *visually disable* non-translatable fields so the admin doesn't accidentally touch them.
    - Use `SiteSettings::updateOrCreate(['locale' => $editingLocale], ['data' => $localeData])` to materialize the per-locale row only when it has translatable overrides.
- When editing a locale row and *no* fields are marked translatable yet, the locale switcher is disabled with a tooltip "Mark fields as translatable first." — avoids creating empty locale rows.

### 4.4 `DefaultSiteSettingsSchema` — annotate showcase fields

[src/Filament/Pages/Schemas/DefaultSiteSettingsSchema.php](src/Filament/Pages/Schemas/DefaultSiteSettingsSchema.php):

- Mark `site_name` (text) and `og_image` (media) as `->translatable()` — the two flavors the roadmap calls out. Everything else stays global by default.
- Consumers adding fields via the `extraTabs()` stub simply chain `->translatable()` on any field they want per-locale.

### 4.5 `HandleInertiaRequests` — consume resolved settings

[src/Http/Middleware/HandleInertiaRequests.php](src/Http/Middleware/HandleInertiaRequests.php) lines 98–102:

- Replace `SiteSettings::getSingleton()->data` with `SiteSettings::getResolved(app()->getLocale())`.
- `ResolveSettingsMedia::make()->handle(...)` still runs on the merged output — order is preserved.
- The `Arr::except([head_scripts, body_start_scripts, body_end_scripts])` filter stays.

### 4.6 `PageController` — same treatment

[src/Http/Controllers/PageController.php](src/Http/Controllers/PageController.php) line 117 — swap to `SiteSettings::getResolved(app()->getLocale())` for `ResolveSettingsMedia`. Line 133 stays (it fetches a URL, not data).

### 4.7 Lang keys

[lang/en/cms.php](lang/en/cms.php) + [lang/da/cms.php](lang/da/cms.php):

- `site_settings.editing_locale_label` ("Editing locale")
- `site_settings.global_label` ("Global (default)")
- `site_settings.translatable_hint` ("Can be overridden per locale")
- `site_settings.non_translatable_when_locale` ("Global field — switch to 'Global' to edit")

### 4.8 Tests

New `tests/Feature/Localization/SiteSettingsLocalizationTest.php`:

- `getResolved(locale)` returns global when no locale row exists.
- `getResolved(locale)` deep-merges locale over global.
- Translatable field set in a locale row is returned for that locale; global value for others.
- Non-translatable field in a locale row (via the save flow) is redirected to the global row.
- `HandleInertiaRequests` shares locale-resolved settings.
- `->translatable()` macro registers the field's statepath in the `Translatable` bag.
- `ResolveSettingsMedia` still resolves UUIDs correctly on the merged output.

---

## Critical files to modify

**Stage 5**:
- New: `database/migrations/2026_04_..._create_locale_domain_mappings_table.php`, [src/Models/LocaleDomainMapping.php](src/Models/LocaleDomainMapping.php), [src/Filament/Resources/LocaleDomainMappings/*](src/Filament/Resources/LocaleDomainMappings/), `tests/Feature/Localization/LocaleDetectionMiddlewareTest.php`.
- Modified: [src/Support/Locales.php](src/Support/Locales.php), [src/Http/Middleware/LocaleDetectionMiddleware.php](src/Http/Middleware/LocaleDetectionMiddleware.php), [src/Http/Middleware/HandleInertiaRequests.php](src/Http/Middleware/HandleInertiaRequests.php), [src/Http/Controllers/PageController.php](src/Http/Controllers/PageController.php), [src/CmsPlugin.php](src/CmsPlugin.php).

**Stage 3**:
- Modified: [src/Filament/Resources/Navigations/Schemas/NavigationForm.php](src/Filament/Resources/Navigations/Schemas/NavigationForm.php), [src/Filament/Resources/Navigations/Tables/NavigationsTable.php](src/Filament/Resources/Navigations/Tables/NavigationsTable.php), [src/Http/Middleware/HandleInertiaRequests.php](src/Http/Middleware/HandleInertiaRequests.php).
- New: `tests/Feature/Localization/NavigationLocaleTest.php`.

**Stage 4**:
- New: [src/Filament/Support/Translatable.php](src/Filament/Support/Translatable.php), `tests/Feature/Localization/SiteSettingsLocalizationTest.php`.
- Modified: [src/CmsServiceProvider.php](src/CmsServiceProvider.php) (macro registration), [src/Models/SiteSettings.php](src/Models/SiteSettings.php), [src/Filament/Pages/ManageSiteSettings.php](src/Filament/Pages/ManageSiteSettings.php), [src/Filament/Pages/Schemas/DefaultSiteSettingsSchema.php](src/Filament/Pages/Schemas/DefaultSiteSettingsSchema.php), [src/Http/Middleware/HandleInertiaRequests.php](src/Http/Middleware/HandleInertiaRequests.php), [src/Http/Controllers/PageController.php](src/Http/Controllers/PageController.php), [lang/en/cms.php](lang/en/cms.php), [lang/da/cms.php](lang/da/cms.php).

Doc updates at the end: [LOCALIZATION_ROADMAP.md](LOCALIZATION_ROADMAP.md), [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

---

## Reused utilities / functions

- [src/Support/Locales.php](src/Support/Locales.php) — `all()`, `default()`, `isValid()`, `keys()` exist; adding `fromDomain()`.
- [src/Actions/ReplacePageID.php](src/Actions/ReplacePageID.php) — already has `?string $locale` param; no signature change.
- [src/Actions/ResolveSettingsMedia.php](src/Actions/ResolveSettingsMedia.php) — stays unchanged; runs on merged output.
- `CmsServiceProvider::$navigationFormSchema` discovery — untouched; verified to still work.
- Filament's native `Field::macro()` — used for `->translatable()`.

---

## Verification

1. `composer test` — full Pest suite green. New tests cover domain detection, missing-locale redirects, nav fallback, settings merge.
2. `vendor/bin/pint --test` — clean on all touched files.
3. Manual end-to-end in a consuming app with 2 locales + 2 domains:
   - Set up: `example.com → en`, `example.dk → da` in the admin mapping page. `cms.default_locale = 'en'`.
   - Visit `example.com/` → English frontpage; `example.com/da/om-os` → Danish page; `example.com/en/about` → 301 → `example.com/about`; `example.dk/` → Danish frontpage.
   - Delete the Danish version of a page; visit its URL → redirects to Danish frontpage (per `missing_locale_behavior=redirect`).
   - Create `en` header nav only; visit Danish site → header items still appear (fallback=default_locale). Flip config to `empty` → header is blank on Danish side.
   - In Site Settings, switch locale dropdown to `da`; change `site_name`; save. Inertia `settings.site_name` now differs per locale. Change `og_image` on `da`; verify global `og_image` is untouched. Edit a non-translatable field while on `da` — field is disabled / value doesn't persist to the locale row.
   - Navigation list view: visible `locale` column; can create a second header for `da` without colliding with the `en` one; trying to create a second `en` header fails validation.
