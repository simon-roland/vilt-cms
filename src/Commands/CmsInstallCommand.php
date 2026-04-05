<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;
use RolandSolutions\ViltCms\Traits\PublishesStubs;

class CmsInstallCommand extends Command
{
    use PublishesStubs;

    protected $signature = 'cms:install';

    protected $description = 'Install the CMS package into a Laravel project';

    private array $manualSteps = [];

    public function handle(): int
    {
        $this->info('Installing CMS...');
        $this->newLine();

        $fresh = $this->isFreshInstall();

        if ($fresh) {
            $this->line('  <fg=green>✓</> Fresh Laravel install detected — aggressive mode');
        } else {
            $this->line('  <fg=yellow>!</> Existing project detected — will print manual steps where needed');
        }

        $this->newLine();

        $this->publishAndMigrate();
        $this->publishConfig();
        $this->publishBladeView();
        $this->publishAppCss();
        $this->publishMiddleware();
        $this->registerMiddleware($fresh);
        $this->registerFilamentResources($fresh);
        $this->createFilamentTheme($fresh);
        $this->addFilamentUserToModel($fresh);
        $this->publishStarterPhpClasses();
        $this->publishAppTs($fresh);
        $this->publishVueComponents();
        $this->publishTsConfig();
        $this->publishViteConfig($fresh);
        $this->addTailwindSources();
        $this->clearStockRoutes($fresh);
        $this->seedShowcaseContent();
        $this->publishFilamentAssets();
        $this->createStorageLink();
        $this->installNpmDependencies();

        $this->newLine();
        $this->info('CMS installed successfully!');

        if (!empty($this->manualSteps)) {
            $this->newLine();
            $this->warn('Manual steps required:');
            foreach ($this->manualSteps as $i => $step) {
                $this->line('  ' . ($i + 1) . '. ' . $step);
            }
        }

        $this->newLine();
        if ($this->confirm('Would you like to create a Filament admin user?', true)) {
            passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(base_path('artisan')) . ' filament:user');
        }

        return self::SUCCESS;
    }

    private function isFreshInstall(): bool
    {
        $welcomeExists = file_exists(base_path('resources/views/welcome.blade.php'));
        $appJsExists = file_exists(base_path('resources/js/app.js'));
        $appTsExists = file_exists(base_path('resources/js/app.ts'));
        $viteConfigJs = file_exists(base_path('vite.config.js'));

        $appJsContent = $appJsExists ? file_get_contents(base_path('resources/js/app.js')) : '';
        $viteContent = $viteConfigJs ? file_get_contents(base_path('vite.config.js')) : '';
        $viteConfigTs = file_exists(base_path('vite.config.ts'))
            ? file_get_contents(base_path('vite.config.ts'))
            : '';

        $stockAppJs = $appJsExists && str_contains($appJsContent, "import './bootstrap'") && !$appTsExists;
        $noVueInVite = !str_contains($viteContent, '@vitejs/plugin-vue')
            && !str_contains($viteConfigTs, '@vitejs/plugin-vue');

        return $welcomeExists && $stockAppJs && $noVueInVite;
    }

    private function publishAndMigrate(): void
    {
        $this->step('Publishing and running migrations');
        $this->callSilent('vendor:publish', ['--tag' => 'cms-migrations', '--force' => true]);
        $this->callSilent('migrate');
        $this->done('Migrations published and run');
    }

    private function publishConfig(): void
    {
        $this->step('Publishing config');
        $this->callSilent('vendor:publish', ['--tag' => 'cms-config', '--force' => true]);
        $this->done('config/cms.php published');
    }

    private function publishBladeView(): void
    {
        $this->step('Publishing app.blade.php');
        $this->callSilent('vendor:publish', ['--tag' => 'cms-views', '--force' => true]);
        $this->done('resources/views/app.blade.php published');
    }

    private function publishMiddleware(): void
    {
        $this->step('Publishing HandleInertiaRequests middleware');
        $this->publishFile(
            __DIR__ . '/../../stubs/HandleInertiaRequests.stub',
            app_path('Http/Middleware/HandleInertiaRequests.php'),
            force: false,
            confirmOverwrite: false,
        );
    }

    private function registerMiddleware(bool $fresh): void
    {
        $this->step('Registering middleware in bootstrap/app.php');
        $path = base_path('bootstrap/app.php');
        $content = file_get_contents($path);

        if (str_contains($content, 'HandleInertiaRequests::class')) {
            $this->skip('HandleInertiaRequests already registered in bootstrap/app.php');

            return;
        }

        // Look for the stock ->withMiddleware block
        $pattern = '/->withMiddleware\(function\s*\(Middleware\s+\$middleware\)\s*:\s*void\s*\{\s*(\/\/\s*)?\}\)/s';

        if ($fresh && preg_match($pattern, $content)) {
            $replacement = <<<'PHP'
->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
PHP;
            $content = preg_replace($pattern, $replacement, $content);
            file_put_contents($path, $content);
            $this->done('HandleInertiaRequests registered in bootstrap/app.php');
        } else {
            $this->manual(
                'Register middleware in bootstrap/app.php:' . PHP_EOL .
                '    ->withMiddleware(function (Middleware $middleware): void {' . PHP_EOL .
                '        $middleware->web(append: [\\App\\Http\\Middleware\\HandleInertiaRequests::class]);' . PHP_EOL .
                '    })'
            );
        }
    }

    private function registerFilamentResources(bool $fresh): void
    {
        $this->step('Registering CmsPlugin in AdminPanelProvider');
        $path = app_path('Providers/Filament/AdminPanelProvider.php');

        if (!file_exists($path)) {
            $this->callSilent('make:filament-panel', ['id' => 'admin']);
            if (!file_exists($path)) {
                $this->skip('AdminPanelProvider not found — skipping');

                return;
            }
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'CmsPlugin')) {
            $this->skip('CmsPlugin already registered in AdminPanelProvider');

            return;
        }

        $uses = 'use RolandSolutions\ViltCms\CmsPlugin;';
        $plugin = '->plugin(CmsPlugin::make())';

        $canInject = $fresh && (
            str_contains($content, '->resources([])') ||
            str_contains($content, '->discoverResources(')
        );

        if ($canInject) {
            // Inject use statement after namespace line
            $content = preg_replace(
                '/(^namespace\s+[^;]+;\s*)/m',
                "$1\n" . $uses . "\n",
                $content,
                1
            );

            if (str_contains($content, '->resources([])')) {
                $content = str_replace('->resources([])', $plugin, $content);
            } else {
                $content = preg_replace(
                    '/(->discoverResources\((?:[^()]*|\([^()]*\))*\))/',
                    "$1\n            {$plugin}",
                    $content,
                    1
                );
            }

            file_put_contents($path, $content);
            $this->done('CmsPlugin registered in AdminPanelProvider');
        } else {
            $this->manual(
                'Add to AdminPanelProvider.php use statements:' . PHP_EOL .
                '    ' . $uses . PHP_EOL .
                'And add to your panel() method:' . PHP_EOL .
                '    ' . $plugin
            );
        }
    }

    private function createFilamentTheme(bool $fresh): void
    {
        $this->step('Creating Filament panel theme');
        $themePath = base_path('resources/css/filament/admin/theme.css');

        if (!is_dir(dirname($themePath))) {
            mkdir(dirname($themePath), 0755, true);
        }

        if (!file_exists($themePath)) {
            file_put_contents($themePath, <<<'CSS'
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
@source '../../../../vendor/roland-solutions/vilt-cms/resources/views/**/*';
CSS);
            $this->done('resources/css/filament/admin/theme.css created');
        } else {
            // Ensure CMS @source is present even if theme already exists
            $content = file_get_contents($themePath);
            if (!str_contains($content, 'roland-solutions/vilt-cms')) {
                file_put_contents($themePath, rtrim($content) . PHP_EOL . "@source '../../../../vendor/roland-solutions/vilt-cms/resources/views/**/*';" . PHP_EOL);
                $this->done('CMS @source added to existing Filament theme');
            } else {
                $this->skip('Filament theme already includes CMS @source');
            }
        }

        // Register viteTheme in AdminPanelProvider
        $providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
        if (file_exists($providerPath)) {
            $content = file_get_contents($providerPath);
            if (!str_contains($content, 'viteTheme')) {
                if ($fresh) {
                    $content = preg_replace(
                        '/(->plugin\(CmsPlugin::make\(\)\))/',
                        "$1\n            ->viteTheme('resources/css/filament/admin/theme.css')",
                        $content,
                        1
                    );
                    file_put_contents($providerPath, $content);
                    $this->done('viteTheme registered in AdminPanelProvider');
                } else {
                    $this->manual("Add to your panel() method in AdminPanelProvider.php:\n    ->viteTheme('resources/css/filament/admin/theme.css')");
                }
            } else {
                $this->skip('viteTheme already registered in AdminPanelProvider');
            }
        }

        // Add theme CSS to vite input if using the stub vite config
        $viteConfigPath = base_path('vite.config.js');
        if (file_exists($viteConfigPath)) {
            $content = file_get_contents($viteConfigPath);
            if (!str_contains($content, 'filament/admin/theme.css')) {
                $content = str_replace(
                    "'resources/css/app.css'",
                    "'resources/css/app.css', 'resources/css/filament/admin/theme.css'",
                    $content
                );
                file_put_contents($viteConfigPath, $content);
                $this->done('Filament theme CSS added to vite.config.js inputs');
            } else {
                $this->skip('Filament theme CSS already in vite.config.js');
            }
        }
    }

    private function addFilamentUserToModel(bool $fresh): void
    {
        $this->step('Adding FilamentUser to User model');
        $path = app_path('Models/User.php');

        if (!file_exists($path)) {
            $this->manual('User model not found at app/Models/User.php — add FilamentUser manually');

            return;
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'FilamentUser')) {
            $this->skip('User model already implements FilamentUser');

            return;
        }

        if ($fresh) {
            // Add use statements before class declaration
            $uses = "use Filament\\Models\\Contracts\\FilamentUser;\nuse Filament\\Panel;\n";
            $content = preg_replace(
                '/^(use\s+(?:Illuminate|Database).*\n)(?!use\s)/m',
                '$1' . $uses,
                $content,
                1
            );

            // Add implements FilamentUser to class declaration
            $content = preg_replace(
                '/^(class\s+User\s+extends\s+Authenticatable)(\s*)(\{|implements)/m',
                '$1 implements FilamentUser$2$3',
                $content,
                1
            );

            // Add canAccessPanel method before the last closing brace
            $method = <<<'PHP'

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
PHP;
            $content = preg_replace('/(\})\s*$/', $method . "\n}", $content);

            file_put_contents($path, $content);
            $this->done('User model updated with FilamentUser');
        } else {
            $this->manual(
                'Add to app/Models/User.php:' . PHP_EOL .
                '    use Filament\\Models\\Contracts\\FilamentUser;' . PHP_EOL .
                '    use Filament\\Panel;' . PHP_EOL .
                '    class User extends Authenticatable implements FilamentUser' . PHP_EOL .
                '    public function canAccessPanel(Panel $panel): bool { return true; }'
            );
        }
    }

    private function publishAppTs(bool $fresh): void
    {
        $this->step('Publishing app.ts');
        $this->publishFile(
            __DIR__ . '/../../stubs/app.ts.stub',
            base_path('resources/js/app.ts'),
            force: false,
            confirmOverwrite: false,
        );

        // Remove the old app.js if it's the stock file
        $appJs = base_path('resources/js/app.js');
        if ($fresh && file_exists($appJs)) {
            unlink($appJs);
            $this->done('resources/js/app.js removed');
        }
    }

    private function publishVueComponents(): void
    {
        $this->step('Publishing Vue components');

        foreach ($this->groupFiles('vue') as [$stub, $dest]) {
            $this->publishFile($stub, $dest, force: false, confirmOverwrite: false);
        }
    }

    private function publishTsConfig(): void
    {
        $this->step('Publishing tsconfig.json');
        $this->publishFile(
            __DIR__ . '/../../stubs/tsconfig.json.stub',
            base_path('tsconfig.json'),
            force: false,
            confirmOverwrite: false,
        );
    }

    private function publishViteConfig(bool $fresh): void
    {
        $this->step('Configuring Vite');
        $stub = __DIR__ . '/../../stubs/vite.config.js.stub';

        if ($fresh) {
            // Replace stock vite.config.js
            $dest = base_path('vite.config.js');
            copy($stub, $dest);
            $this->done('vite.config.js replaced with CMS config');
        } else {
            $viteTs = base_path('vite.config.ts');
            $viteJs = base_path('vite.config.js');
            $viteContent = file_exists($viteTs)
                ? file_get_contents($viteTs)
                : (file_exists($viteJs) ? file_get_contents($viteJs) : '');

            if (str_contains($viteContent, '@cms')) {
                $this->skip('Vite @cms alias already configured');

                return;
            }

            $this->manual(
                'Add to your vite.config resolve.alias:' . PHP_EOL .
                "    '@cms': path.resolve(__dirname, 'vendor/roland-solutions/vilt-cms/resources/js')," . PHP_EOL .
                'And set: preserveSymlinks: true'
            );
        }
    }

    private function addTailwindSources(): void
    {
        $this->step('Adding Tailwind @source directives to app.css');
        $path = base_path('resources/css/app.css');

        if (!file_exists($path)) {
            $this->manual(
                'Add to resources/css/app.css:' . PHP_EOL .
                "    @source '../**/*.vue';" . PHP_EOL .
                "    @source '../../vendor/roland-solutions/vilt-cms/resources/js/**/*.vue';"
            );

            return;
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'vendor/roland-solutions/vilt-cms')) {
            $this->skip('Tailwind CMS @source directive already present');

            return;
        }

        $directives = PHP_EOL . "@source '../**/*.vue';" . PHP_EOL
            . "@source '../../vendor/roland-solutions/vilt-cms/resources/js/**/*.vue';" . PHP_EOL;

        file_put_contents($path, $content . $directives);
        $this->done('Tailwind @source directives added to app.css');
    }

    private function clearStockRoutes(bool $fresh): void
    {
        $this->step('Clearing stock welcome route');
        $path = base_path('routes/web.php');

        if (!file_exists($path)) {
            $this->skip('routes/web.php not found — skipping');

            return;
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'view(\'welcome\')') || str_contains($content, 'view("welcome")')) {
            if ($fresh) {
                file_put_contents($path, "<?php\n");
                $this->done('Stock welcome route removed from routes/web.php');

                $welcome = base_path('resources/views/welcome.blade.php');
                if (file_exists($welcome)) {
                    unlink($welcome);
                    $this->done('resources/views/welcome.blade.php deleted');
                }
            } else {
                $this->manual('Remove the stock welcome route from routes/web.php — it conflicts with the CMS page routes');
            }

            return;
        }

        $this->skip('No stock welcome route found in routes/web.php');
    }

    private function publishAppCss(): void
    {
        $this->step('Publishing app.css');
        $dest = base_path('resources/css/app.css');
        $stub = __DIR__ . '/../../stubs/app.css.stub';

        if (file_exists($dest)) {
            $content = file_get_contents($dest);
            // Only overwrite if it's the stock Laravel app.css
            if (!str_contains($content, '@import \'tailwindcss\'') && !str_contains($content, '@tailwind')) {
                $this->skip('resources/css/app.css already exists with custom content — skipping');

                return;
            }
        }

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        copy($stub, $dest);
        $this->done('resources/css/app.css published');
    }

    private function publishStarterPhpClasses(): void
    {
        $this->step('Publishing starter block and layout classes');

        foreach ($this->groupFiles('php') as [$stub, $dest]) {
            $this->publishFile($stub, $dest, force: false, confirmOverwrite: false);
        }
    }



    private function seedShowcaseContent(): void
    {
        $this->step('Seeding showcase content');

        $this->callSilent('db:seed', [
            '--class' => \RolandSolutions\ViltCms\Database\Seeders\CmsShowcaseSeeder::class,
        ]);

        $this->done('Showcase content seeded');
    }

    private function publishFilamentAssets(): void
    {
        $this->step('Publishing Filament assets');
        $this->callSilent('filament:assets');
        $this->done('Filament assets published');
    }

    private function createStorageLink(): void
    {
        $this->step('Creating storage symlink');

        if (file_exists(public_path('storage'))) {
            $this->skip('Storage symlink already exists');

            return;
        }

        $this->callSilent('storage:link');
        $this->done('Storage symlink created');
    }

    private function installNpmDependencies(): void
    {
        $this->step('Installing npm dependencies');

        $manager = $this->detectPackageManager();
        $packages = '@inertiajs/vue3 vue @vitejs/plugin-vue ziggy-js';

        $addCommands = [
            'yarn'  => "yarn add --dev {$packages}",
            'bun'   => "bun add --dev {$packages}",
            'pnpm'  => "pnpm add -D {$packages}",
            'npm'   => "npm install --save-dev {$packages}",
        ];

        $buildCommands = [
            'yarn'  => 'yarn build',
            'bun'   => 'bun run build',
            'pnpm'  => 'pnpm run build',
            'npm'   => 'npm run build',
        ];

        $addCommand = $addCommands[$manager];
        $this->line("  Running: <fg=cyan>{$addCommand}</>");

        $result = 0;
        passthru($addCommand . ' 2>&1', $result);

        if ($result !== 0) {
            $this->warn("Package install failed — run manually: {$addCommand}");

            return;
        }

        $this->done("Dependencies installed via {$manager}");

        $buildCommand = $buildCommands[$manager];
        $this->step('Building assets');
        $this->line("  Running: <fg=cyan>{$buildCommand}</>");

        $result = 0;
        passthru($buildCommand . ' 2>&1', $result);

        if ($result === 0) {
            $this->done('Assets built successfully');
        } else {
            $this->warn("Build failed — run manually: {$buildCommand}");
        }
    }

    private function detectPackageManager(): string
    {
        if (file_exists(base_path('bun.lockb')) || file_exists(base_path('bun.lock'))) {
            return 'bun';
        }
        if (file_exists(base_path('pnpm-lock.yaml'))) {
            return 'pnpm';
        }
        if (file_exists(base_path('yarn.lock'))) {
            return 'yarn';
        }
        if (file_exists(base_path('package-lock.json'))) {
            return 'npm';
        }

        return 'yarn';
    }

    private function manual(string $step): void
    {
        $this->manualSteps[] = $step;
        $this->line('  <fg=yellow>!</> Added to manual steps');
    }
}
