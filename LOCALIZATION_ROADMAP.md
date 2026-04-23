# Localization Roadmap

## Key Decisions

- **Audience**: Internal package only. Breaking changes land directly; no external upgrade guide, but consuming projects need clean data migration + test coverage.
- **Locale content creation**: On-demand — `PageContent` rows are only created when an admin explicitly starts editing a locale.
- **URL strategy**: Default locale has no path prefix (`/about-us`); secondary locales are prefixed (`/da/om-os`). Accessing `/en/about-us` when `en` is default → 301 to `/about-us`. Accessing `/da` (prefix only) → Danish frontpage.
- **Domain mapping**: Domains map to an initial default locale; the user stays on the same domain. Locale switching changes the path prefix, not the domain.
- **Missing locale behavior** (configurable, default = redirect): when a URL has no `PageContent` in the active locale, redirect to that locale's frontpage. If the target locale has no frontpage either, 404. Consumers can opt in to 404-always via `cms.missing_locale_behavior`.
- **Per-locale frontpage**: optional. A locale can exist with zero or one frontpage. `is_frontpage` is unique *per locale* (compound constraint with `locale`).
- **Navigation**: Must only show links to pages that exist (published) in the current locale.
- **Translatable fields**: Decorator API — `->translatable()` on any Filament field (or `Translatable::wrap(...)`). One mechanism covers text, textarea, rich text, media, etc.
- **Reserved slugs**: `PageContent` slug cannot equal any configured locale key. Enforced at form-validation time; also guard when a new locale is added to config.
- **Laravel translator**: detected content locale also drives `app()->setLocale()` so UI/validation strings follow.
- **AI translation**: Stage 7 (future backlog).

---

## Conventions & Cross-Cutting

These apply to all stages; detailed per-stage plans should reference them.

- **Test coverage** is required per stage. At minimum:
  - Stage 1: data-migration round-trip (draft-only, published, frontpage, soft-deleted pages all survive the split intact).
  - Stages 2–6: feature tests for the new locale-aware flows end-to-end.
- **Preview mode × locale** (`?preview=draft|published`): preview state is per-locale. The existing session key becomes locale-scoped. Decide concrete shape in Stage 2's detailed plan.
- **Middleware ordering**: `LocaleDetectionMiddleware` must run before `HandleInertiaRequests` so shared props already reflect the resolved locale.
- **Route caching**: verify `/{locale?}/{slug}` pattern is cacheable; if not, document that `route:cache` is off in consuming apps.
- **NavigationFormSchema override** (commit `8f00064`) must keep working after Stage 3.

---

## Stage 1 — Foundation: Config, Database, Models & Routing

**Goal**: Establish the data model backbone. Breaking — everything else depends on it.

- Add `locales` (keyed array, e.g. `['en' => 'English', 'da' => 'Dansk']`), `default_locale`, `missing_locale_behavior` (`redirect`|`404`, default `redirect`), and `navigation_fallback` (`default_locale`|`empty`, default `default_locale`) to `config/cms.php`.
- **Breaking DB change**: `pages` becomes a thin group/parent entity (`id`, timestamps, soft deletes only).
- New `page_contents` table: `id`, `page_id`, `locale`, `name`, `slug`, `layout`, `blocks`, `meta`, `published_content`, `published_at`, `is_frontpage`, timestamps.
  - Compound unique `(locale, slug)`.
  - Compound unique `(locale, is_frontpage)` with `is_frontpage` nullable (allows zero frontpages per locale).
  - Soft deletes: TBD in detailed plan — decide cascade semantics (restoring a `Page` should restore its `PageContent` rows).
- `navigations`: add `locale` column, change unique constraint to `(type, locale)`.
- `site_settings`: add nullable `locale` column. `null` row = global defaults; locale-specific rows = overrides merged on top. Drop the `id = 1` singleton invariant; add `SiteSettings::getResolved(string $locale): array`.
- New `PageContent` model with all existing draft/publish logic moved to it. `Page` becomes the grouping model. The per-locale frontpage hook (currently in `Page::booted()`) moves to `PageContent` and scopes by `locale`.
- Reserved-slug validation: `PageContent` slug rule rejects any configured locale key.
- **Data migration**: existing pages are seeded into `page_contents` under `config('cms.default_locale')`. Migration test seeds pages in all states (draft-only, published, frontpage, soft-deleted) and asserts round-trip.
- Routing skeleton: `/{slug}` for default locale, `/{locale}/{slug}` for others. Stub `LocaleDetectionMiddleware` added (full logic in Stage 5).

---

## Stage 2 — Page Localization in Filament + Draft/Publish

**Goal**: Full per-locale page editing with the complete draft/publish lifecycle.

- `PageResource` list: per-locale status badges; locale filter. Aggregate "page status" for sorting/filtering: TBD in detailed plan (candidate: "any locale published").
- `PageResource` edit: locale switcher in the header via `?locale=da` query param (shareable URL). If no `PageContent` row exists for the selected locale, show an "Add this locale" prompt.
- "Add this locale" UX: modal with two choices — blank draft, or copy from another locale (defaults to default locale). Decided now to avoid blocking form/URL design.
- All existing page actions (`PublishPage`, `EditPublishedPage`, discard draft, unpublish) become locale-aware, targeting the correct `PageContent` row. `EditPublishedPage` simplifies — it now edits a `PageContent`'s `published_content` directly instead of the old shared-row snapshot.
- **Copy content from locale** action: copies `layout`, `blocks`, `meta` (optionally `name`/`slug`) from one `PageContent` to another as a new draft.
- Per-locale frontpage: `is_frontpage` is unique per locale; a locale may have none.
- Per-locale slug auto-generation from the locale's `name`, plus reserved-slug rejection from Stage 1.
- Preview mode session key made locale-scoped.

---

## Stage 3 — Navigation Localization

**Goal**: Locale-specific navigations with a safe fallback.

- `NavigationResource`: locale switcher, separate rows per `(type, locale)`.
- `ReplacePageID` action gains a `$locale` parameter (internal signature change); resolves `page_id` → the locale's `PageContent` slug.
- Navigation fallback driven by `cms.navigation_fallback`: `default_locale` (use default locale's nav) or `empty` (serve empty nav).
- Navigation rendering "only show links to pages published in the current locale" is implemented here (single home, referenced from Stage 6).
- Verify the `NavigationFormSchema` override mechanism still works with locale-aware rows.

---

## Stage 4 — Site Settings Localization + `->translatable()` Decorator

**Goal**: Locale-overridable site settings via a general translatable-field API.

- Resolution: `SiteSettings::getResolved($locale)` returns global row deep-merged with the locale-specific row. `ResolveSettingsMedia` runs *after* the merge.
- **`->translatable()` decorator / `Translatable::wrap(...)`**: one mechanism that wraps any Filament field (text, textarea, rich text, media picker, etc.). Fields without `->translatable()` remain global.
- `SiteSettingsSchema` stub gets at least one showcase `->translatable()` field of each flavor (text + media) to validate the API.
- No hardcoded "these fields are global" lists — consumers express intent via `->translatable()`.
- Can ship after Stage 1 alone (only depends on `locales` config + `site_settings.locale` column).

---

## Stage 5 — Domain-Based Locale Detection & Middleware

**Goal**: Map domains to locales and wire up locale-aware URL routing.

- New `locale_domain_mappings` table: `id`, `locale`, `domain` (unique).
- Filament admin page for managing domain → locale mappings.
- `LocaleDetectionMiddleware`: domain lookup → fall back to `config('cms.default_locale')`. Sets the app locale (drives `app()->setLocale()`) and determines URL path prefix. Registered before `HandleInertiaRequests`.
- Route behaviour:
  - Default locale = no prefix (`/about-us`).
  - Secondary locales = `/da/om-os`.
  - `/en/about-us` when `en` is default → 301 to `/about-us`.
  - `/da` (prefix only) → Danish frontpage if it exists, else 404.
  - URL with no matching `PageContent` in the active locale → per `cms.missing_locale_behavior` (default: redirect to locale frontpage; 404 if no frontpage).

---

## Stage 6 — Frontend Locale Switching & SEO

**Goal**: Give visitors locale control and give search engines the correct signals.

- Pages carry a `locale_variants` prop: `{ en: { slug: 'about-us', available: true }, da: { slug: 'om-os', available: false }, … }`. "Available" means a **published** `PageContent` exists. Assembled with a single eager-loaded query (no N+1).
- `Head` component: renders `<link rel="alternate" hreflang="…">` for each available locale + `x-default` pointing at the default locale. (Can ship earlier — only depends on Stage 2.)
- `CmsToolbar`: locale switcher showing the current locale and links to other locales. Unavailable locales link to *that locale's frontpage* (not dead/disabled) unless the locale has no frontpage either, in which case they render disabled.
- Switching navigates to the target locale's path-prefixed slug on the same domain.
- Vue/TypeScript type definitions updated for `locale_variants`.

---

## Stage 7 (Future) — AI Translation via DeepL

**Goal**: "Translate from locale" as a fast-path for translators. Builds on Stage 2's copy-from-locale foundation.

- Config: API key, enabled/disabled per locale pair in `cms.php`.
- Schema-aware block walker: each block type declares which of its fields are translatable; the walker extracts strings, translates, and writes back without flattening block structure or touching media UUIDs / page IDs.
- Backlog — not in scope for the current work.

---

## Stage Dependencies

```
Stage 1 (Foundation)
  ├── Stage 2 (Page Localization)
  │     ├── Stage 3 (Navigation)           — parallel with 4 and 5
  │     ├── Stage 6a (hreflang / SEO)      — can ship without Stage 5
  │     └── Stage 5 (Domain Middleware)
  │           └── Stage 6b (Switcher UI)
  │                 └── Stage 7 (AI) [future]
  └── Stage 4 (Site Settings)              — only needs Stage 1
```

Stage 6 is split into **6a** (hreflang rendering, `locale_variants` prop) which depends only on Stage 2, and **6b** (visitor-facing switcher UI) which depends on Stage 5 for correct link targets.

---

## Open Items for Detailed Plans

These are the known TBDs to decide inside each stage's detailed plan rather than at the roadmap level:

- **Stage 1**: soft-delete semantics for `PageContent` and cascade on `Page` restore.
- **Stage 2**: exact shape of the "Add this locale" modal; aggregate page-status rule for list filters; locale-scoped preview session key shape.
- **Stage 3**: whether `navigation_fallback` is also overridable per navigation (vs. config-only).
- **Stage 4**: concrete serialization format for the merged + media-resolved settings payload shared to Inertia.
