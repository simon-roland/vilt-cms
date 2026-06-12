## Stage 1 — Done ✅

23/23 tests pass; Pint clean on all touched files.

What shipped:

- **Config**: 4 new keys (`locales`, `default_locale`, `missing_locale_behavior`, `navigation_fallback`) + `src/Support/Locales.php` helper.
- **Schema**: 3 migrations splitting `pages` → `pages` + `page_contents`, adding `locale` to `navigations` and `site_settings`.
- **Models**: slim `src/Models/Page.php` grouping entity with timestamp-matched soft-delete cascade; new `src/Models/PageContent.php` carrying all per-locale state + media + draft/publish logic + per-locale frontpage hook.
- `src/Rules/ReservedLocaleSlug.php` wired into PageForm and change-slug action.
- Filament surface retargeted at `PageContent` (scoped to default locale) with `CreatePage` creating the parent `Page` + child content.
- `PageController`, `PublishPage`, `ReplacePageID`, `AddMediaToPage`, `HandleInertiaRequests`, `PreviewMode` all locale-aware. Session key nested as `cms_preview_mode.{locale}`.
- `LocaleDetectionMiddleware` stub + locale-prefixed routes (`pages.show`, `pages.show.localized`, `pages.frontpage`, `pages.frontpage.localized`).
- Pest + Testbench + Pint set up. Tests cover: round-trip migration, per-locale frontpage uniqueness, soft-delete cascade semantics, reserved-slug, route skeleton.

Known Stage-5 follow-ups (per plan): middleware-ordering relative to `HandleInertiaRequests` still depends on host-app registration; the locale redirect/404 rules in `cms.missing_locale_behavior` aren't consumed yet.

---

## Stage 2 — Done ✅ (revised after UX testing)

25/25 tests pass; Pint clean on all touched files.

### Stage 2 revision (post-testing pass)

Hands-on testing after the first Stage 2 drop surfaced conceptual and UX issues that reshaped the data model and several flows. All revision work is folded into this section; the "What shipped" notes below are the current state.

**Schema revisions (applied in-place to the Stage 1 migrations since the feature branch has no downstream consumers yet):**

- `name` moved back to `pages` as a global internal identifier. Per-locale titles belong in `meta.title` (SEO) — so `page_contents.name` is gone.
- `page_contents` is **not** soft-deletable any more. Deleting a locale is permanent. `Page` remains soft-deletable.
- `Page::booted()` no longer needs the timestamp-matched cascade-restore logic — it's all driven by Page's own `SoftDeletes` and the FK's `cascadeOnDelete` on force-delete.
- `PageContent::page()` relation is `withTrashed()` so a content row can still reach a soft-deleted parent.
- `published_content` snapshot is `{layout, blocks, meta}` only — `name` is no longer a per-locale publishable field.
- `ReservedLocaleSlug` rule renamed/expanded to `PageSlug`, which now covers both reserved-key and kebab-case format in one rule. The three form sites (PageForm, addLocaleAction, changeSlugAction) all route through it.

What shipped:

- **Foundation fixes** (pre-requisites):
    - Removed `getRouteKeyName()` override from `PageContent` — admin URLs use `id` to avoid ambiguity when two locales share the same slug.
    - Fixed admin toolbar edit URL in `PageController` to pass model instance instead of slug.
    - Fixed `Filament\Fields\Page` select — was querying `pages.name` which no longer exists after Stage 1; now queries `PageContent` in the default locale.
- **List view** (`PagesTable`):
    - `modifyQueryUsing()` returns one row per Page, preferring the default-locale content and falling back to the min-id row when a page has no default-locale content.
    - `page.name` column (searchable, sortable via relation).
    - Locale badges column: colour-coded green (published) / yellow (draft) / gray (missing) per locale, rendered with `->html()` so the inline spans actually render. Column is hidden when only one locale is configured.
    - Custom `TernaryFilter` scopes by `Page.deleted_at` (whereHas). Page-level bulk actions (delete / restore / force-delete) operate on the parent `Page`, not individual `PageContent` rows.
- **Edit view** — `EditPage` and `EditPublishedPage`:
    - `getTitle()` renders the page name; `getSubheading()` shows locale label + status (draft / published) when >1 locales configured.
    - Locale switcher in the header actions row shows all locales with status badges. Active locale is highlighted primary and disabled; other existing locales link to their edit page; missing locales trigger an "Add locale" modal inline.
    - "View page" URLs use locale-prefixed routes for non-default locales (`pages.show.localized`, `pages.frontpage.localized`).
    - Form no longer has a `name` field. Slug field is read-only (edited via `changeSlugAction`) and hidden entirely when `is_frontpage` is true.
- **Create view** (`CreatePage`): inline form with `name` (→ `Page`), optional `locale` Select (only when >1 locales, defaults to default), and `slug` for the first `PageContent`. Rich content editing moves to the subsequent edit screen.
- **Rename action** (`HasPageActions::renamePageAction()`): single-field modal that writes to `Page.name` and reloads the current URL so the title/subheading reflect the new name.
- **"Add locale" modal** (`HasPageActions::addLocaleAction()`):
    - Collects source choice (blank draft | copy from existing locale) + slug (defaults to slugified page name).
    - Slug validated with `PageSlug` (reserved-locale + format) and a locale-scoped unique rule.
    - Redirects to the new content's edit page on completion.
- **"Copy content from locale" action** (`HasPageActions::copyFromLocaleAction()`):
    - Overwrites the current draft's `layout`, `blocks`, `meta` from a selected sibling locale. Does not touch the published version.
    - Visible only when at least one sibling locale exists and the record's parent Page is not trashed.
- **Duplicate action**: creates a new `Page` (with user-supplied name) and copies every locale's content. Other locales get auto-suffixed slugs via `uniqueSlug()`.
- **Split delete actions** (`HasPageActions`):
    - `deleteLocaleAction()` — hard-deletes the current `PageContent`. Hidden when it would leave the Page empty. After delete, redirects to a remaining sibling (prefers default locale).
    - `deletePageAction()` — soft-deletes the parent `Page`. Redirects to the list.
    - `restorePageAction()` / `forceDeletePageAction()` — operate on the parent `Page`, visible only when it's trashed.
- **Slug validation dedupe**: single `PageSlug` rule in `src/Rules/` covers reserved-locale + format. Used everywhere a `PageContent` slug is validated.
- **Lang files** (`en` + `da`): rename / delete-locale / delete-page / restore / force-delete / create-locale keys added on top of the Stage 2 switcher + modal keys.
- **Tests**:
    - `tests/Feature/Localization/PageLocaleContentTest.php` — scenarios covering blank locale creation, copy-from-locale, duplicate-all-locales, copy-without-affecting-published, independent-publish per locale, per-locale slug uniqueness, list-view eager-load query, and the assertion that `published_content` no longer stores `name`.
    - `tests/Feature/Models/PageSoftDeleteCascadeTest.php` — rewritten for the simpler model: Page soft-delete hides it via default scope, restore brings it back, force-delete removes all contents via FK cascade.
    - `tests/Feature/Models/PageContentFrontpageTest.php` — updated to seed via `makePage(['name' => ...])` since `PageContent` no longer has `name`.
    - `tests/Feature/Migrations/PageLocalizationMigrationTest.php` — expects `pages.name` to survive the split and `page_contents` to have no `name` / no `deleted_at`.
    - `tests/Feature/Validation/ReservedSlugTest.php` — asserts `PageSlug` rejects reserved keys, malformed slugs, and trailing-whitespace slugs.

---

## Stages 3 + 4 + 5 — Done ✅ (per plan.md)

58/58 tests pass; Pint clean.

**Stage 5 — Domain-Based Locale Detection:**

- `locale_domain_mappings` migration + `LocaleDomainMapping` model (saves/deletes flush the in-process domain cache).
- `Locales::fromDomain()` with per-host static cache + `flushDomainCache()`.
- `LocaleDetectionMiddleware` full logic: domain default → URL prefix override → 301 de-prefixing redirect (query string preserved) → `app()->setLocale()`.
- `PageController` consumes `cms.missing_locale_behavior` (`redirect` to the locale frontpage when one exists, else 404; or hard `404`).
- `LocaleDomainMappingResource` (Filament CRUD, lowercase-normalized unique domains), registered only when >1 locales configured.
- Tests: `LocaleDetectionMiddlewareTest` (domain mapping, fallback, 301 rules, prefix-wins-over-domain, missing-locale behaviors, cache invalidation).

**Stage 3 — Navigation Localization:**

- `NavigationForm`: locale Select (visible >1 locales, disabled on edit), compound `(type, locale)` unique validation.
- `NavigationsTable`: locale badge column, locale+type default sort.
- `HandleInertiaRequests::loadNavigation()`: per-locale lookup with `cms.navigation_fallback` (`default_locale`|`empty`); published-filter checks the **visitor** locale; `ReplacePageID` resolves slugs in the visitor locale.
- Tests: `NavigationLocaleTest`.

**Stage 4 — Site Settings Localization:**

- `Field::macro('translatable')` + `Translatable` registry (reset per schema build to avoid cross-schema/long-running-runtime leakage).
- `SiteSettings::global()` / `forLocale()` / `getResolved()` (deep merge, locale wins); `getSingleton()` kept as deprecated alias.
- `ManageSiteSettings`: locale switcher select; non-translatable fields disabled (with hint) when editing a locale; **diff-on-save** — only values differing from global are stored per-locale (`Translatable::overrides()`), and a locale row with no remaining overrides is deleted so it keeps inheriting future global changes.
- `app.blade.php`, Inertia share, and frontend all read `getResolved(app()->getLocale())`.
- Tests: `SiteSettingsLocalizationTest`.

**Cross-cutting hardening:**

- Inertia shared props (`locale`, `defaultLocale`, `header`, `footer`, `settings`) are **lazy closures**, evaluated at response-render time — correct locale regardless of where `HandleInertiaRequests` sits relative to `LocaleDetectionMiddleware` in the consuming app's stack. The kernel-ordering caveat from Stage 1 is gone.
- `tests/TestCase.php` now pushes `HandleInertiaRequests` into the `web` group (mirrors consuming apps), so feature tests assert real shared props.
- Fixed `useHref()` (frontend): route param rename (`page` → `slug`) and locale-prefix awareness via the new shared `defaultLocale` prop.

## Stage 6 — Done ✅ (6a hreflang + 6b switcher)

- `PageController` shares a `locale_variants` prop (null when 1 locale): per configured locale `{slug, available, url}` — `available` = published content exists; `url` = the variant when reachable for the current visitor (drafts count for admins/preview), else that locale's frontpage, else `null`. URLs are domain-default-aware paths (2 extra queries, no N+1).
- `Head.vue`: `<link rel="alternate" hreflang>` for available variants (only when ≥2) + `x-default` pointing at the domain-default locale.
- `CmsToolbar.vue`: locale switcher (current highlighted, unreachable locales rendered disabled), visible only with >1 locales.
- TS types: `PageProps.locale/locales/defaultLocale/locale_variants`, `LocaleVariant`.
- Tests: `LocaleVariantsTest`.

**Known follow-ups before release:**

- Merge `main` into this branch — `main` has `613ce4a` (Ziggy route filtering) which this branch predates.
- Site-settings UX: switching the editing locale discards unsaved changes without warning.
- `SiteSettings::getResolved()` runs per call (blade + share each query); request-level memoization deliberately skipped for now.
- Visitor-facing locale switcher beyond the admin toolbar is left to consuming apps via `locale_variants`/`locales` props (per roadmap).
