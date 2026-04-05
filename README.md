# VILT-CMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/roland-solutions/vilt-cms.svg)](https://packagist.org/packages/roland-solutions/vilt-cms)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)

A CMS package for the **VILT stack** — Vue · Inertia · Laravel · Tailwind. It provides the CMS infrastructure while each project supplies its own design, blocks, and layouts.

## What's included

- **Page builder** — pages with a layout slot + N content blocks, draft/published status, frontpage flag, and SEO meta
- **Media library** — uploads, folder organisation, grid/list browser, responsive WebP conversions, bulk operations
- **Navigation** — header and footer nav builder with links and dropdowns
- **User management** — admin user CRUD
- **Filament 5 admin** — all resources wired up and ready via `CmsPlugin`
- **Inertia middleware base** — shared props (title, ziggy, navigation) with overridable hooks
- **Vue plugin + components** — `createCms()`, `Wrapper`, `Head`, `Navigation`, `LinkItem`, `Blocks`, `Accordion`
- **Block / layout generators** — `cms:make-block` and `cms:make-layout`

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
- Creates `app/Http/Middleware/HandleInertiaRequests.php` and registers it
- Creates a Filament panel and registers `CmsPlugin`
- Adds `FilamentUser` to your `User` model
- Publishes `resources/js/app.ts` and `resources/css/app.css`
- Replaces `vite.config.js` with a Vue + Tailwind + `@cms` alias config
- Publishes starter blocks, layouts, and Vue components to your app
- Installs npm packages and builds assets
- Seeds an example page and navigation via `CmsShowcaseSeeder`
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

| Group    | What it publishes                    |
| -------- | ------------------------------------ |
| `ts`     | `resources/js/app.ts`                |
| `vue`    | Vue components and pages             |
| `css`    | `resources/css/app.css`              |
| `config` | `config/cms.php`                     |
| `php`    | Starter PHP block and layout classes |

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
];
```

`buttons` and `padding` default to empty arrays — the corresponding selectors are hidden if the array is empty.

---

## Translations

The package ships English labels by default. To use Danish or override any string:

```bash
php artisan vendor:publish --tag=cms-lang
```

Published to `lang/vendor/cms/`. Activate Danish with `APP_LOCALE=da` in your `.env`.

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

## License

MIT — see [LICENSE](LICENSE).  
© 2025–2026 [Roland Solutions](https://simonroland.dk)
