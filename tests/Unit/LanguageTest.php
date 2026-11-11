<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Saola\Core\Language\LanguageSources;
use Saola\Core\Language\LanguageStore;
use Saola\Core\Language\LanguageTree;
use Saola\Core\Language\TextResolver;
use Saola\Core\Providers\LanguageServiceProvider;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/saola-lang-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->root);
        parent::tearDown();
    }

    private function source(string $root, string $locale, string $file, array $values): void
    {
        @mkdir($root.'/'.$locale, 0777, true);
        file_put_contents($root.'/'.$locale.'/'.$file.'.php', '<?php return '.var_export($values, true).';');
    }

    public function test_project_and_package_use_locale_first_files_without_implicit_prefixes(): void
    {
        $this->source($this->root.'/app', 'vi', 'common', ['save' => 'Lưu']);
        $this->source($this->root.'/package', 'vi', 'admin', ['auth' => ['keep_login' => 'Duy trì đăng nhập']]);
        $this->source($this->root.'/override', 'vi', 'admin', ['auth' => ['keep_login' => 'Nhớ tôi']]);
        $this->source($this->root.'/app', 'vi', 'validation', ['save' => 'Không được nhập']);
        $sources = new LanguageSources();
        $sources->register('app', $this->root.'/app', ['common']);
        $sources->register('saola/admin', $this->root.'/package', ['admin'], $this->root.'/override');
        $catalog = $sources->catalog();
        $this->assertSame('Lưu', $catalog['vi']['save']['value']);
        $this->assertSame('Nhớ tôi', $catalog['vi']['auth.keep_login']['value']);
        $this->assertSame('saola/admin', $catalog['vi']['auth.keep_login']['owner']);
        $this->assertArrayNotHasKey('admin.auth.keep_login', $catalog['vi']);
    }

    public function test_duplicate_keys_across_locales_or_sources_are_rejected(): void
    {
        $this->source($this->root.'/app', 'en', 'common', ['save' => 'Save']);
        $this->source($this->root.'/package', 'vi', 'admin', ['save' => 'Lưu']);
        $sources = new LanguageSources();
        $sources->register('app', $this->root.'/app', ['common']);
        $sources->register('pkg', $this->root.'/package', ['admin']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate language key save');
        $sources->catalog();
    }

    public function test_tree_round_trip_root_keys_hyphens_empty_values_and_path_conflicts(): void
    {
        $tree = ['save' => '', 'module-name' => ['deep' => ['label' => 'Value']]];
        $this->assertSame($tree, LanguageTree::nest(LanguageTree::flatten($tree)));
        $this->expectException(\InvalidArgumentException::class);
        LanguageTree::nest(['save' => 'Save', 'save.label' => 'Conflict']);
    }

    public function test_text_is_scoped_uses_db_fallback_and_never_changes_laravel_translator(): void
    {
        $this->app->register(LanguageServiceProvider::class);
        $store = new class implements LanguageStore {
            public int $reads = 0;
            public function snapshot(string $locale): array
            {
                $this->reads++;
                return ['revision' => 3, 'messages' => $locale === 'vi'
                    ? ['save' => '', 'welcome' => 'Chào :name, :NAME, :Name']
                    : ['save' => 'Save', 'fallback' => 'English']];
            }
        };
        $this->app->instance(LanguageStore::class, $store);
        $this->app->setLocale('vi');
        $this->assertSame('', text('save'));
        $this->assertSame('Chào lan, LAN, Lan', text('welcome', ['name' => 'lan']));
        $this->assertSame('English', text('fallback'));
        $this->assertSame('', text('missing', [], ''));
        $this->assertSame('default', text('missing', 'default'));
        $this->assertSame('missing', text('missing'));
        $this->assertSame('Save', text('save', [], null, 'en'));
        $this->assertSame(2, $store->reads);
        $this->assertSame('save', __('save'));
        $first = $this->app->make(TextResolver::class);
        $this->app->forgetScopedInstances();
        $this->assertNotSame($first, $this->app->make(TextResolver::class));
        $this->app->setLocale('en');
        $this->assertSame('Save', text('save'));
        $this->assertSame(3, $store->reads);
    }
}
