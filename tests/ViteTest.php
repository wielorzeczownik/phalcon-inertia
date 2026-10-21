<?php

namespace PhalconInertia\Tests;

use PhalconInertia\Exception;
use PhalconInertia\Vite;

class ViteTest extends TestCase
{
    public function test_build_tags_preload_imports_and_collect_css_once()
    {
        $public = $this->publicDir(['manifest.json' => self::manifest()]);

        $tags = explode("\n", (new Vite($public))->tags(['resources/js/app.js', 'resources/css/extra.css'], 'n"1'));

        $this->assertSame([
            '<link rel="modulepreload" href="/build/assets/vendor-1.js" nonce="n&quot;1">',
            '<link rel="modulepreload" href="/build/assets/shared-2.js" nonce="n&quot;1">',
            '<link rel="stylesheet" href="/build/assets/app-3.css" nonce="n&quot;1">',
            '<link rel="stylesheet" href="/build/assets/vendor-4.css" nonce="n&quot;1">',
            '<link rel="stylesheet" href="/build/assets/extra-5.css" nonce="n&quot;1">',
            '<script type="module" src="/build/assets/app-6.js" nonce="n&quot;1"></script>',
        ], $tags);
    }

    public function test_vite_5_manifest_location_and_custom_urls_are_supported()
    {
        $public = $this->publicDir(['.vite/manifest.json' => self::manifest()]);
        $vite = new Vite($public, 'assets-build', 'https://cdn.example.com');

        $this->assertSame('https://cdn.example.com/assets-build/assets/logo-7.png', $vite->asset('resources/img/logo.png'));
        $this->assertSame(md5_file($public.'/assets-build/.vite/manifest.json'), $vite->version());
    }

    public function test_hot_file_switches_to_the_dev_server()
    {
        $public = $this->publicDir(['manifest.json' => self::manifest()], "http://localhost:5173/\n");
        $vite = new Vite($public);

        $this->assertTrue($vite->isHot());
        $this->assertSame(
            '<script type="module" src="http://localhost:5173/@vite/client"></script>'."\n"
            .'<script type="module" src="http://localhost:5173/resources/js/app.js"></script>'."\n"
            .'<link rel="stylesheet" href="http://localhost:5173/resources/css/extra.css">',
            $vite->tags(['resources/js/app.js', 'resources/css/extra.css'])
        );
        $this->assertSame('http://localhost:5173/resources/img/logo.png', $vite->asset('resources/img/logo.png'));
    }

    public function test_missing_manifest_or_entry_is_reported()
    {
        $empty = new Vite($this->publicDir([]));
        $built = new Vite($this->publicDir(['manifest.json' => self::manifest()]));

        $this->assertSame('', $empty->version());
        $this->assertThrowsMessage('Vite manifest not found', function () use ($empty) {
            $empty->tags('resources/js/app.js');
        });
        $this->assertThrowsMessage('Unable to locate file in Vite manifest: resources/js/nope.js.', function () use ($built) {
            $built->tags('resources/js/nope.js');
        });
    }

    private function assertThrowsMessage($message, $callback)
    {
        try {
            call_user_func($callback);
        } catch (Exception $e) {
            $this->assertHasText($message, $e->getMessage());

            return;
        }

        $this->fail('Expected a PhalconInertia\Exception.');
    }

    private function publicDir(array $files, $hot = null)
    {
        $dir = sys_get_temp_dir().'/vite-'.uniqid('', true);
        $build = $dir.'/'.(isset($files['.vite/manifest.json']) ? 'assets-build' : 'build');
        foreach ($files as $name => $content) {
            if (! is_dir(dirname($build.'/'.$name))) {
                mkdir(dirname($build.'/'.$name), 0777, true);
            }
            file_put_contents($build.'/'.$name, json_encode($content));
        }
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if ($hot !== null) {
            file_put_contents($dir.'/hot', $hot);
        }

        return $dir;
    }

    private static function manifest()
    {
        return [
            'resources/js/app.js' => [
                'file' => 'assets/app-6.js',
                'isEntry' => true,
                'imports' => ['_vendor.js', '_shared.js'],
                'css' => ['assets/app-3.css'],
            ],
            '_vendor.js' => ['file' => 'assets/vendor-1.js', 'css' => ['assets/vendor-4.css']],
            '_shared.js' => ['file' => 'assets/shared-2.js', 'imports' => ['_vendor.js']],
            'resources/css/extra.css' => ['file' => 'assets/extra-5.css', 'isEntry' => true],
            'resources/img/logo.png' => ['file' => 'assets/logo-7.png'],
        ];
    }
}
