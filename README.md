# VILT-CMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/roland-solutions/vilt-cms.svg)](https://packagist.org/packages/roland-solutions/vilt-cms)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)

A CMS package for the **VILT stack** — Vue · Inertia · Laravel · Tailwind. It provides the CMS infrastructure while each project supplies its own design, blocks, and layouts.

## What's included

- **Page builder** — pages with a layout slot + N content blocks, draft/publish workflow, frontpage designation, SEO meta, soft-delete, and page duplication
- **Localization** — multi-locale content with per-locale drafts, publishing, slugs, and frontpages; domain → locale mapping; localized navigations and site settings; automatic `hreflang` tags
- **Media library** — uploads, folder organisation, grid/list browser, responsive WebP conversions, bulk operations
- **Navigation** — header and footer nav builder with links and dropdowns (block types are customisable), one navigation set per locale
- **Site settings** — global key/value store with optional per-locale overrides and a Filament admin page; fields auto-discovered from your app; shared on every Inertia request
- **User management** — admin user CRUD
- **Filament 5 admin** — all resources wired up and ready via `CmsPlugin`
- **Inertia middleware base** — shared props (title, ziggy, navigation, locale) with overridable hooks
- **Vue plugin + components** — `createCms()`, `Wrapper`, `Head`, `Navigation`, `LinkItem`, `Blocks`, `Accordion`
- **Block / layout / field generators** — `cms:make-block`, `cms:make-layout`, and `cms:make-field`

## Requirements

| Dependency      | Version |
| --------------- | ------- |
| PHP             | ^8.3    |
| Laravel         | ^13.0   |
| Filament        | ^5.0    |
| Inertia Laravel | ^3.0    |
| Vue             | 3       |
| Tailwind CSS    | 4       |
| Ziggy           | ^2.3    |

---

## Installation

### 1. Require the package

```bash
composer require roland-solutions/vilt-cms
```

### 2. Run the installer

```bash
php artisan cms:install
```

On a **fresh** Laravel project, the installer handles everything automatically:

- Publishes and runs migrations
- Publishes `config/cms.php` and `resources/views/app.blade.php`
- Asks whether the site is multi-locale and writes your locales into `config/cms.php`
- Creates `app/Http/Middleware/HandleInertiaRequests.php` and registers it
- Creates a Filament panel and registers `CmsPlugin`
- Adds `FilamentUser` to your `User` model
- Publishes `resources/js/app.ts` and `resources/css/app.css`
- Replaces `vite.config.js` with a Vue + Tailwind + `@cms` alias config
- Publishes starter blocks, layouts, and Vue components to your app
- Installs npm packages and builds assets
- Seeds showcase content via `CmsShowcaseSeeder` — a published frontpage and about page with SEO meta, header/footer navigations, and starter site settings, in **every configured locale** (Danish copy ships translated)
- Prompts you to create a Filament admin user

On an **existing** project, it prints manual steps for anything it cannot safely automate.

> **Security note:** The installer adds `canAccessPanel(): bool { return true; }` to your `User` model so you can reach the admin panel immediately. Before deploying to production, replace this with a real check (e.g. an `is_admin` column or a role).

---

## Adding blocks

Scaffold a new block:

```bash
php artisan cms:make-block Hero
```

This creates:

- `app/Cms/Blocks/HeroBlock.php` — Filament form schema
- `resources/js/cms/blocks/HeroBlock.vue` — Vue front-end component

Both files are **discovered automatically** — no registration needed. The PHP class is picked up by the CMS service provider at boot, and the Vue component is loaded via a glob import in `app.ts`.

Edit the generated files to define your block's fields and template.

---

## Adding layouts

```bash
php artisan cms:make-layout TwoColumn
```

Creates:

- `app/Cms/Layouts/TwoColumnLayout.php`
- `resources/js/cms/layouts/TwoColumnLayout.vue`

Same as blocks — both are discovered automatically.

---

## Adding fields

Fields are **reusable PHP form components** — they wrap one or more Filament inputs into a single class so you can share the same field configuration across multiple blocks, layouts, or the site settings schema.

Scaffold a new field:

```bash
php artisan cms:make-field Actions
```

This creates a single PHP file:

- `app/Cms/Fields/ActionsField.php` — extends `BaseField`, returns any Filament `Component`

No Vue component is needed — fields are PHP-only.

### When to use a field

Use a field when the same configuration appears in more than one block. For example, an `ActionsField` that wraps a `Repeater` of labeled buttons with link targets can be reused in a Hero block, a Banner block, and a Card block without duplicating the schema.

### The starter `ActionsField`

`cms:install` publishes `app/Cms/Fields/ActionsField.php` as a working example. It returns a `Repeater` where each item has a label and a link (internal page or external URL). It is immediately used in the `HeroBlock` starter.

### Using a field in a block

```php
use App\Cms\Fields\ActionsField;

Block::make('hero')
    ->schema([
        ID::make(),
        TextInput::make('headline')->label('Headline'),
        ActionsField::make(),
    ]);
```

Pass an options array to override the field name or label:

```php
ActionsField::make(['name' => 'cta', 'label' => 'Call to actions'])
```

### Returning a non-Field component

Because `BaseField::setup()` returns `Filament\Forms\Components\Component`, your field can return anything — a `Repeater`, `Group`, `Fieldset`, or a plain `Field`. The generated stub starts with a `TextInput` as a simple baseline.

---

## Pages

### Draft and publish

Every page maintains two independent copies of its content: a **draft** (the working copy) and a **published snapshot** (what visitors see).

A status banner at the top of the form shows the current state at a glance:

| State                               | What it means                                                 |
| ----------------------------------- | ------------------------------------------------------------- |
| **Never published**                 | The page exists only as a draft and is invisible to visitors. |
| **Published · in sync**             | The draft and live version are identical.                     |
| **Published · unpublished changes** | You have staged edits that haven't gone live yet.             |

The header actions update to match the current state:

| Button | When visible | What it does |
| ------ | ------------ | ------------ |
| **Publish** | Draft has unpublished changes | Snapshots the draft as the new live version |
| **Save as draft** | Published · in sync | Saves changes to draft without publishing |
| **Edit published version** | Published · unpublished changes | Opens a dedicated editor for the live snapshot, leaving the draft untouched |
| **Discard draft** | Published · unpublished changes | Reverts the draft back to the published snapshot |
| **View page** | Always (not trashed) | Opens the frontend; appends `?preview=draft` automatically when both versions exist |

**Save changes** at the bottom of the form is always present and is state-aware: when the draft and live version are identical it publishes on save; when unsaved draft changes exist it saves to draft only.

### Editing the live version directly

If you need to push a quick fix while a longer draft is in progress, click **Edit published version**. This opens a dedicated editor that writes directly to the published snapshot without touching your draft. Your staged changes are preserved and the live update is immediate.

### Frontpage

Only one page per locale is served at that locale's root (`/` for the default locale, `/da` for a prefixed one). You designate it from the **More actions** menu on any published page — the previous frontpage in that locale loses its designation automatically. The current frontpage is indicated by a notice in the form header rather than an editable toggle, so the status can never drift out of sync with the publish state. Unpublishing a page that is the frontpage automatically clears the designation.

### Slugs

A page's slug can be changed at any time via **Change slug** in the More actions menu. The one exception is the designated frontpage — its slug is locked while it holds that role. Changing a slug updates both the draft and the published snapshot simultaneously.

Use **Duplicate** when you want to reuse content at a new URL while keeping the original live.

### More actions

Secondary actions live in a **More actions** dropdown in the page header:

| Action                              | When it appears                                         |
| ----------------------------------- | ------------------------------------------------------- |
| **Change slug**                     | Always (except the frontpage and trashed pages)         |
| **Unpublish**                       | The page is published                                   |
| **Set as frontpage**                | The page is published and not already the frontpage     |
| **Duplicate**                       | Always                                                  |
| **Delete / Restore / Force delete** | Based on soft-delete state                              |

---

## Preview mode

Authenticated users see a fixed **CMS toolbar** at the top of every frontend page. Its three zones are:

- **Left** — links to Site settings, the Pages list, New page, and an **Edit** button that jumps directly to the current page's draft editor in the admin
- **Centre** — the page name and a relative "Edited X ago" timestamp
- **Right** — a **locale switcher** (when more than one locale is configured), and a **Draft / Published toggle** when both versions of the page exist, or a status pill ("Draft" or "Published") when only one version exists

Clicking **Draft** or **Published** in the toggle stores the choice in the session and reloads the page to render the appropriate version. When draft preview is active, navigation includes links to unpublished pages; in published mode those links are filtered out, matching what guests see.

Guests never see the toolbar and always receive published content only.

### Checking preview mode in application code

```php
use RolandSolutions\ViltCms\Support\PreviewMode;

if (PreviewMode::active()) {
    // current request is rendering draft content
}
```

### Customising preview mode access

The default behaviour ties preview mode to the CMS toolbar toggle. You can replace the check entirely — for example to make all authenticated editors see drafts without the toggle:

```php
// AppServiceProvider::boot()
use RolandSolutions\ViltCms\Support\PreviewMode;

PreviewMode::resolveUsing(fn () => auth()->check() && auth()->user()->hasRole('editor'));
```

The callback runs on every content-rendering request, so keep it lightweight.

---

## Localization

The CMS is single-locale out of the box. Enable multi-locale content by listing locales in `config/cms.php`:

```php
'locales' => [
    'en' => 'English',
    'da' => 'Dansk',
],
'default_locale' => 'en',
```

With one locale configured, all locale UI (switchers, badges, columns) stays hidden and nothing changes. With two or more, every part of the CMS becomes locale-aware.

### URL strategy

| URL | Serves |
| --- | ------ |
| `/about-us` | The page in the **default locale** (never prefixed) |
| `/da/om-os` | The page in the `da` locale (always prefixed) |
| `/da` | The Danish frontpage |
| `/en/about-us` | 301 → `/about-us` (prefix matching the default locale is stripped) |

Each locale's content has its own slug — the same page can be `/about-us` in English and `/da/om-os` in Danish. A slug can never collide with a configured locale key; this is enforced by validation everywhere slugs are edited.

When a URL has no content in the active locale, `cms.missing_locale_behavior` decides what happens: `redirect` (default) sends the visitor to that locale's frontpage (404 if the locale has no frontpage), `404` always 404s.

### Domain → locale mapping

A **Domains** admin resource (visible only with >1 locales) maps hostnames to locales — e.g. `example.com → en`, `example.dk → da`. Matching is exact (no protocol, no port), and the matched locale becomes the *default* for that domain: unprefixed URLs serve it, and its prefix is stripped via 301. A URL prefix always wins over the domain default, so `example.dk/en/about-us` serves English. Unmapped domains fall back to `cms.default_locale`.

Locale switching stays on the current domain — it changes the path prefix, not the host.

### Editing pages per locale

Content is created **on demand** — a page starts with one locale and grows:

- The page editor header shows a **locale switcher** with a draft/published badge per locale. Missing locales open an "Add locale" modal offering a blank draft or a copy of an existing locale.
- Draft/publish state, slugs, SEO meta, and frontpage designation are all **per locale** — publishing the Danish version doesn't touch the English one, and each locale may have (at most) one frontpage.
- The page **name** is a global internal identifier (renamed via an action); visitor-facing titles live in each locale's SEO meta.
- **Copy content from locale** overwrites the current draft from a sibling locale without touching the published version.
- **Delete locale** permanently removes one locale's content (hidden when it would leave the page empty); **Delete page** soft-deletes the whole page with all its locales.

### Navigation per locale

Each navigation (header/footer) belongs to one locale — the list view shows a locale column and `(type, locale)` is unique. The frontend automatically loads the navigation matching the visitor's locale, and links to pages without published content in that locale are filtered out.

When a locale has no navigation, `cms.navigation_fallback` applies: `default_locale` (default) serves the default locale's navigation with slugs resolved for the visitor's locale; `empty` serves nothing.

### Translatable site settings

Settings are global by default. Mark any field in your settings schema as overridable per locale with the `->translatable()` macro:

```php
TextInput::make('site_name')->translatable(),
MediaPicker::make('og_image')->translatable(),
```

The Settings page gains an **Editing locale** selector. When a locale is selected, non-translatable fields are disabled, and only values that actually **differ from the global value** are stored as overrides — untouched fields keep inheriting future global changes. Setting a field back to the global value removes the override. The frontend always receives the merged result for the active locale.

`site_name` and `og_image` are translatable in the default schema.

### Frontend props & SEO

Every Inertia response shares:

| Prop | Contents |
| ---- | -------- |
| `locale` | The active locale code |
| `locales` | All configured locales (`{ code: label }`) |
| `defaultLocale` | The unprefixed locale for the current domain |
| `locale_variants` | Per locale: `{ slug, available, url }` for the current page (`null` with 1 locale) |

`available` means a published version exists; `url` is the ready-to-use switch target — the variant itself when reachable, otherwise that locale's frontpage, otherwise `null`. The `Head` component renders `<link rel="alternate" hreflang>` tags (plus `x-default`) automatically from these variants, and the CMS toolbar shows a locale switcher for editors. Build a visitor-facing language switcher from the same props.

The package's `LocaleDetectionMiddleware` is applied to all CMS routes automatically, and locale-dependent shared props are resolved lazily at render time — no middleware-ordering configuration is needed in your app.

---

## Updating frontend files after a package upgrade

Use `cms:publish` to selectively re-publish stub files:

```bash
# Publish everything
php artisan cms:publish

# Publish only specific groups
php artisan cms:publish --only=vue --only=ts

# Overwrite without confirmation
php artisan cms:publish --force
```

Available groups:

| Group             | What it publishes                            |
| ----------------- | -------------------------------------------- |
| `ts`              | `resources/js/app.ts`                        |
| `vue`             | Vue components and pages                     |
| `css`             | `resources/css/app.css`                      |
| `config`          | `config/cms.php`                             |
| `php`             | Starter PHP block, layout, and field classes |
| `settings-schema` | `app/Cms/SiteSettingsSchema.php` (see below) |

---

## Site settings

The CMS ships with a **Settings** admin page for storing global values that should be available on every frontend page — things like a site logo, favicon, social media links, and a default Open Graph image.

Settings are shared automatically on every Inertia request as `$page.props.settings`. The admin page is tab-based, with tabs persisted in the query string so the browser remembers which tab you were on. With multiple locales configured, individual fields can be overridden per locale — see [Translatable site settings](#translatable-site-settings).

### Default tabs and fields

| Tab          | Field                 | Type        |
| ------------ | --------------------- | ----------- |
| General      | `site_name`           | Text input  |
| General      | `logo`                | MediaPicker |
| General      | `favicon`             | MediaPicker |
| Social Media | `facebook_url`        | URL input   |
| Social Media | `instagram_url`       | URL input   |
| Social Media | `linkedin_url`        | URL input   |
| Social Media | `x_url`               | URL input   |
| Social Media | `youtube_url`         | URL input   |
| SEO          | `og_image`            | MediaPicker |
| SEO          | `twitter_handle`      | Text input  |
| SEO          | `title_format`        | Text input  |
| Scripts      | `head_scripts`        | Code editor |
| Scripts      | `body_start_scripts`  | Code editor |
| Scripts      | `body_end_scripts`    | Code editor |

> **Script injection** — the three script fields are output directly into `app.blade.php` on every page request: `head_scripts` inside `<head>` (before your assets — good for analytics `<script>` tags or font preloads), `body_start_scripts` at the opening of `<body>`, and `body_end_scripts` at the closing of `<body>`. They are rendered as raw HTML and are **not** included in the Inertia `settings` prop sent to Vue.

### Adding custom settings tabs

Publish the schema stub to add your own tabs alongside the defaults:

```bash
php artisan cms:publish --only=settings-schema
```

This creates `app/Cms/SiteSettingsSchema.php`:

```php
class SiteSettingsSchema
{
    public static function extraTabs(): array
    {
        return [
            \Filament\Forms\Components\Tabs\Tab::make('Store')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('store_email')->label('Store email'),
                    // ... any Filament field
                ]),
        ];
    }
}
```

The file is auto-discovered at boot — no registration needed. The default tabs are always present; `extraTabs()` appends additional ones.

### Using settings in Vue

```ts
import { usePage } from "@inertiajs/vue3";

const { settings } = usePage().props;

// settings.logo_media?.[0]?.src  — resolved media URL
// settings.facebook_url          — plain string
```

Media fields (any field whose value is a UUID from the media library) are resolved server-side. A field `logo` stored as a UUID gets an additional `logo_media` key containing the full media object array — the same shape as block media fields. Custom fields you add follow the same convention automatically.

The `SiteSettings` TypeScript interface is exported from the CMS types and already applied to `PageProps.settings`. It includes a `[key: string]: unknown` catch-all so custom fields are accessible without type errors.

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=cms-config
```

**`config/cms.php`**

```php
return [
    // Eloquent model used for authentication and the admin user resource.
    // Must implement \Filament\Models\Contracts\FilamentUser.
    'user_model' => env('CMS_USER_MODEL', \App\Models\User::class),

    // Filesystem disk for uploaded media. Defaults to "public".
    'media_disk' => env('CMS_MEDIA_DISK', 'public'),

    // Options shown in the Button block's style selector.
    // [ value => label ]
    'buttons' => [
        'primary'   => 'Primary',
        'secondary' => 'Secondary',
    ],

    // Vertical padding options shown in the Video block's spacing selector.
    // [ value => label ]
    'padding' => [
        'sm' => 'Small',
        'md' => 'Medium',
        'lg' => 'Large',
    ],

    // URL path segment of your Filament panel, so the CMS page router
    // doesn't intercept admin URLs. Match your panel's ->path() setting.
    'panel_path' => env('CMS_PANEL_PATH', 'admin'),

    // Supported content locales keyed by locale code. Keys must be valid
    // URL segments. A single entry disables all locale UI.
    'locales' => [
        'en' => 'English',
    ],

    // Served when no locale prefix is in the URL and the domain is unmapped.
    // The default locale never carries a path prefix.
    'default_locale' => 'en',

    // When a URL has no content in the active locale:
    // 'redirect' → that locale's frontpage (404 if none), '404' → always 404.
    'missing_locale_behavior' => 'redirect',

    // When no navigation exists for the current locale:
    // 'default_locale' → fall back to the default locale's nav, 'empty' → none.
    'navigation_fallback' => 'default_locale',
];
```

`buttons` and `padding` default to empty arrays — the corresponding selectors are hidden if the array is empty. The locale keys are covered in detail in [Localization](#localization).

---

## Translations

The package ships English labels by default. To use Danish or override any string:

```bash
php artisan vendor:publish --tag=cms-lang
```

Published to `lang/vendor/cms/`. Activate Danish with `APP_LOCALE=da` in your `.env`.

On the frontend, the detected content locale also drives `app()->setLocale()`, so Laravel's translator, validation messages, and Blade strings follow the visitor's locale automatically.

---

## Customising shared Inertia props

Override `extraProps()` in your `HandleInertiaRequests` middleware to add or override shared props:

```php
protected function extraProps(Request $request): array
{
    return [
        'user' => $request->user()?->only('id', 'name'),
    ];
}
```

---

## Customising navigation form blocks

By default the navigation builder offers two block types: **Link** and **Dropdown**. You can replace this set by creating `app/Cms/NavigationFormSchema.php`:

```php
namespace App\Cms;

use RolandSolutions\ViltCms\Filament\Blocks\Link;

class NavigationFormSchema
{
    public static function blocks(): array
    {
        return [
            Link::make(),
            // add or remove block types here
        ];
    }
}
```

The file is auto-discovered at boot — no registration needed. Return any array of `Block` instances built from the classes in `RolandSolutions\ViltCms\Filament\Blocks\`.

---

## Customising navigation loading

Override `loadNavigation()` to change how (or whether) header/footer nav is loaded:

```php
protected function loadNavigation(string $type): array
{
    if ($type === 'footer') {
        return []; // disable footer nav
    }

    return parent::loadNavigation($type);
}
```

---

## Block resource resolution

If a block field stores a PHP class name in a `_resource` key, the package will instantiate it on every page render and inject the result back into the block data. This is useful for blocks that need to load dynamic data (e.g. a list of products).

Any class used as a block resource **must implement `RolandSolutions\ViltCms\Contracts\BlockResource`**:

```php
use RolandSolutions\ViltCms\Contracts\BlockResource;

class LatestPosts implements BlockResource
{
    public function __construct(array $data)
    {
        $this->posts = Post::latest()->take($data['count'] ?? 3)->get();
    }
}
```

---

## Fixed headers & the CMS toolbar

When the CMS toolbar is active, `Wrapper.vue` sets `--cms-toolbar-height` (default `44px`) on `:root`. Any layout that uses a `position: fixed` header should offset it by this variable so the header isn't obscured behind the toolbar.

Using an inline style in Vue:

```html
<header :style="{ top: 'var(--cms-toolbar-height, 0px)' }"></header>
```

Or in plain CSS:

```css
header {
    top: var(--cms-toolbar-height, 0px);
}
```

When the toolbar is not present the variable is set to `0px`, so the header behaves normally for regular visitors.

---

## License

MIT — see [LICENSE](LICENSE).  
© 2025–2026 [Roland Solutions](https://simonroland.dk)
