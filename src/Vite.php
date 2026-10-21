<?php

namespace PhalconInertia;

class Vite
{
    private $publicPath;

    private $buildDirectory;

    private $assetUrl;

    private $manifest;

    public function __construct($publicPath, $buildDirectory = 'build', $assetUrl = '/')
    {
        $this->publicPath = rtrim($publicPath, '/');
        $this->buildDirectory = trim($buildDirectory, '/');
        $this->assetUrl = rtrim($assetUrl, '/').'/';
    }

    public function isHot()
    {
        return is_file($this->publicPath.'/hot');
    }

    public function tags($entries, $nonce = null)
    {
        $entries = (array) $entries;

        if ($this->isHot()) {
            $tags = [self::script($this->hotUrl('@vite/client'), $nonce)];
            foreach ($entries as $entry) {
                $tags[] = self::isCss($entry) ? self::stylesheet($this->hotUrl($entry), $nonce) : self::script($this->hotUrl($entry), $nonce);
            }

            return implode("\n", $tags);
        }

        $manifest = $this->manifest();
        $preloads = [];
        $styles = [];
        $scripts = [];
        foreach ($entries as $entry) {
            $chunk = $this->chunk($manifest, $entry);
            $this->collect($manifest, $entry, $preloads, $styles);
            if (self::isCss($chunk['file'])) {
                $styles[$chunk['file']] = true;
            } else {
                $scripts[$chunk['file']] = true;
            }
        }

        $tags = [];
        foreach (array_keys($preloads) as $file) {
            $tags[] = '<link rel="modulepreload" href="'.self::escape($this->buildUrl($file)).'"'.self::nonce($nonce).'>';
        }
        foreach (array_keys($styles) as $file) {
            $tags[] = self::stylesheet($this->buildUrl($file), $nonce);
        }
        foreach (array_keys($scripts) as $file) {
            $tags[] = self::script($this->buildUrl($file), $nonce);
        }

        return implode("\n", $tags);
    }

    public function asset($path)
    {
        if ($this->isHot()) {
            return $this->hotUrl($path);
        }

        $manifest = $this->manifest();

        return $this->buildUrl($this->chunk($manifest, $path)['file']);
    }

    public function version()
    {
        $path = $this->manifestPath();

        return $path === null ? '' : md5_file($path);
    }

    private function collect(array $manifest, $key, array &$preloads, array &$styles)
    {
        $chunk = $this->chunk($manifest, $key);
        foreach (isset($chunk['css']) ? $chunk['css'] : [] as $css) {
            $styles[$css] = true;
        }
        foreach (isset($chunk['imports']) ? $chunk['imports'] : [] as $import) {
            $file = $this->chunk($manifest, $import)['file'];
            if (! isset($preloads[$file])) {
                $preloads[$file] = true;
                $this->collect($manifest, $import, $preloads, $styles);
            }
        }
    }

    private function chunk(array $manifest, $key)
    {
        if (! isset($manifest[$key]['file'])) {
            throw new Exception('Unable to locate file in Vite manifest: '.$key.'.');
        }

        return $manifest[$key];
    }

    private function manifest()
    {
        if ($this->manifest === null) {
            $path = $this->manifestPath();
            if ($path === null) {
                throw new Exception('Vite manifest not found in '.$this->publicPath.'/'.$this->buildDirectory.'. Run the Vite build or start the dev server.');
            }
            $this->manifest = json_decode(file_get_contents($path), true);
        }

        return $this->manifest;
    }

    private function manifestPath()
    {
        foreach (['manifest.json', '.vite/manifest.json'] as $name) {
            $path = $this->publicPath.'/'.$this->buildDirectory.'/'.$name;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function hotUrl($path)
    {
        return rtrim(trim(file_get_contents($this->publicPath.'/hot')), '/').'/'.ltrim($path, '/');
    }

    private function buildUrl($file)
    {
        return $this->assetUrl.$this->buildDirectory.'/'.$file;
    }

    private static function isCss($path)
    {
        return (bool) preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)(\?|$)/', $path);
    }

    private static function script($url, $nonce)
    {
        return '<script type="module" src="'.self::escape($url).'"'.self::nonce($nonce).'></script>';
    }

    private static function stylesheet($url, $nonce)
    {
        return '<link rel="stylesheet" href="'.self::escape($url).'"'.self::nonce($nonce).'>';
    }

    private static function nonce($nonce)
    {
        return $nonce === null ? '' : ' nonce="'.self::escape($nonce).'"';
    }

    private static function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
