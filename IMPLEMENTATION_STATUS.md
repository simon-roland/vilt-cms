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

## Stage 2 — Done ✅

23/23 tests pass (7 new); Pint clean on all touched files.

What shipped:

- **Foundation fixes** (pre-requisites):
    - Removed `getRouteKeyName()` override from `PageContent` — admin URLs now use `id` to avoid ambiguity when two locales share the same slug.
    - Removed the default-locale filter from `PageResource::getEloquentQuery()` (filter moved to the list table).
    - Fixed admin toolbar edit URL in `PageController` to pass model instance instead of slug.
    - Fixed `Filament\Fields\Page` select — was querying `pages.name` which no longer exists after Stage 1; now queries `PageContent` in the default locale.
    - Added `->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')` to the create-form slug field to match the change-slug action's validation.
- **List view** (`PagesTable`):
    - `modifyQueryUsing()` scopes to default locale and eager-loads `page.contents`.
    - Locale badge column: one badge per configured locale, colour-coded green (published) / yellow (draft) / gray (missing). Column is hidden when only one locale is configured.
- **Edit view** — `EditPage` and `EditPublishedPage`:
    - Locale switcher dropdown in the header actions row showing all locales with status badges. Active locale is highlighted and disabled; other existing locales link to their edit page; missing locales trigger an "Add locale" modal inline.
    - "View page" URLs updated to use locale-prefixed routes for non-default locales (`pages.show.localized`, `pages.frontpage.localized`).
- **"Add locale" modal** (`HasPageActions::addLocaleAction()`):
    - Asks for name + slug (slug auto-generated from name). Source choice: blank draft or copy content from any existing locale.
    - Slug validated with `ReservedLocaleSlug`, regex, and a locale-scoped unique rule.
    - Redirects to the new content's edit page on completion.
- **"Copy content from locale" action** (`HasPageActions::copyFromLocaleAction()`):
    - Overwrites the current draft's `layout`, `blocks`, `meta` from a selected sibling locale. Does not touch the published version.
    - Visible only when at least one sibling locale exists and the record is not trashed.
- **Duplicate action** updated to copy all locale contents (not just the current one). Other locales get auto-suffixed slugs via a `uniqueSlug()` helper.
- **Lang files** (`en` + `da`): 13 new keys covering locale switcher, add-locale modal, and copy-from-locale action.
- **Filament deprecations resolved**:
    - `BadgeColumn` → `TextColumn` + `->badge()`.
    - Action `->form([…])` → `->schema([…])` (4 occurrences).
    - `Placeholder` → `Filament\Infolists\Components\TextEntry` with `->state()` (2 occurrences).
- **Tests** (`tests/Feature/Localization/PageLocaleContentTest.php`): 7 new scenarios covering blank locale creation, copy-from-locale, duplicate-all-locales, copy-without-affecting-published, independent-publish per locale, per-locale slug uniqueness, and the list-view eager-load query.
