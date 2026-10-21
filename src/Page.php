<?php

namespace PhalconInertia;

class Page
{
    private static $mergeLists = [
        Prop::APPEND => 'mergeProps',
        Prop::PREPEND => 'prependProps',
        Prop::DEEP => 'deepMergeProps',
    ];

    public static function build($component, array $props, $url, $version, array $headers = [], array $options = [])
    {
        $partial = $component !== '' && self::header($headers, 'X-Inertia-Partial-Component') === $component;
        $only = self::csv(self::header($headers, 'X-Inertia-Partial-Data'));
        $except = self::csv(self::header($headers, 'X-Inertia-Partial-Except'));
        $reset = self::csv(self::header($headers, 'X-Inertia-Reset'));
        $loaded = $partial ? [] : self::csv(self::header($headers, 'X-Inertia-Except-Once-Props'));
        $scrollList = self::header($headers, 'X-Inertia-Infinite-Scroll-Merge-Intent') === 'prepend' ? 'prependProps' : 'mergeProps';

        $resolved = [];
        $meta = [];
        foreach ($props as $key => $prop) {
            $key = (string) $key;
            if (! self::isSelected($key, $prop, $partial, $only, $except)) {
                continue;
            }

            if (self::wasLoaded($key, $prop, $loaded)) {
                $meta['onceProps'][$prop->onceKey($key)] = ['prop' => $key, 'expiresAt' => $prop->expiresAt()];

                continue;
            }

            try {
                $value = self::resolve($prop);
            } catch (\Exception $e) {
                self::rescue($prop, $e, $options);
                $meta['rescuedProps'][] = $key;

                continue;
            } catch (\Throwable $e) {
                self::rescue($prop, $e, $options);
                $meta['rescuedProps'][] = $key;

                continue;
            }

            if (! $prop instanceof Prop) {
                $resolved[$key] = $value;

                continue;
            }

            if ($prop->scrollOptions() !== null) {
                $scroll = self::scroll($value, $prop->scrollOptions());
                $value = $scroll[0];
                $isReset = in_array($key, $reset, true);
                if (! $isReset) {
                    $meta[$scrollList][] = $key.'.'.$prop->scrollOptions()['wrapper'];
                }
                $meta['scrollProps'][$key] = $scroll[1] + ['reset' => $isReset];
            }

            if ($prop->mergeMode() !== null && ! in_array($key, $reset, true)) {
                $meta[self::$mergeLists[$prop->mergeMode()]][] = $key;
                foreach ($prop->matchFields() as $field) {
                    $meta['matchPropsOn'][] = $key.'.'.$field;
                }
            }

            if ($prop->isOnce()) {
                $meta['onceProps'][$prop->onceKey($key)] = ['prop' => $key, 'expiresAt' => $prop->expiresAt()];
            }

            $resolved[$key] = $value;
        }

        $page = [
            'component' => $component,
            'props' => (object) $resolved,
            'url' => $url,
            'version' => $version,
        ];

        if (! empty($options['encryptHistory'])) {
            $page['encryptHistory'] = true;
        }

        if (! empty($options['clearHistory'])) {
            $page['clearHistory'] = true;
        }

        foreach (['mergeProps', 'prependProps', 'deepMergeProps', 'matchPropsOn'] as $list) {
            if (isset($meta[$list])) {
                $page[$list] = $meta[$list];
            }
        }

        if (! $partial) {
            foreach ($props as $key => $prop) {
                if ($prop instanceof Prop && $prop->group() !== null && ! self::wasLoaded((string) $key, $prop, $loaded)) {
                    $page['deferredProps'][$prop->group()][] = (string) $key;
                }
            }
        }

        if (isset($meta['rescuedProps'])) {
            $page['rescuedProps'] = $meta['rescuedProps'];
        }

        if (isset($meta['scrollProps'])) {
            $page['scrollProps'] = (object) $meta['scrollProps'];
        }

        if (isset($meta['onceProps'])) {
            $page['onceProps'] = (object) $meta['onceProps'];
        }

        if (! empty($options['sharedProps'])) {
            $page['sharedProps'] = array_values($options['sharedProps']);
        }

        if (! empty($options['flash'])) {
            $page['flash'] = (object) $options['flash'];
        }

        return $page;
    }

    public static function resolve($value)
    {
        if ($value instanceof Prop) {
            return self::resolve($value->get());
        }

        if ($value instanceof \Closure) {
            return self::resolve(call_user_func($value));
        }

        if (is_array($value)) {
            return array_map([__CLASS__, 'resolve'], $value);
        }

        return $value;
    }

    private static function isSelected($key, $prop, $partial, array $only, array $except)
    {
        if ($prop instanceof Prop && $prop->isAlways()) {
            return true;
        }

        if (! $partial) {
            return ! ($prop instanceof Prop && ($prop->isOptional() || $prop->group() !== null));
        }

        if ($only && ! in_array($key, $only, true)) {
            return false;
        }

        return ! in_array($key, $except, true);
    }

    private static function wasLoaded($key, $prop, array $loaded)
    {
        return $prop instanceof Prop && $prop->isOnce() && ! $prop->isFresh() && in_array($prop->onceKey($key), $loaded, true);
    }

    private static function rescue($prop, $exception, array $options)
    {
        if (! $prop instanceof Prop || ! $prop->shouldRescue()) {
            throw $exception;
        }

        if (isset($options['report'])) {
            call_user_func($options['report'], $exception);
        }
    }

    private static function scroll($value, array $options)
    {
        $page = self::paginator($value);
        if ($page === null && $options['metadata'] === null) {
            throw new Exception('Inertia::scroll() needs a Phalcon paginator page or the scroll metadata.');
        }

        $metadata = ['pageName' => 'page', 'previousPage' => null, 'nextPage' => null, 'currentPage' => null];
        if ($page !== null) {
            $items = $page[0];
            $current = $page[1];
            $last = $page[2];
            $value = [$options['wrapper'] => self::resolve(is_object($items) && method_exists($items, 'toArray') ? $items->toArray() : $items)];
            $metadata['previousPage'] = $current > 1 ? $current - 1 : null;
            $metadata['nextPage'] = $current < $last ? $current + 1 : null;
            $metadata['currentPage'] = $current;
        }

        return [$value, array_merge($metadata, (array) $options['metadata'])];
    }

    private static function paginator($page)
    {
        if (is_object($page) && method_exists($page, 'getItems') && method_exists($page, 'getCurrent') && method_exists($page, 'getLast')) {
            return [$page->getItems(), (int) $page->getCurrent(), (int) $page->getLast()];
        }

        if (is_object($page) && property_exists($page, 'items') && property_exists($page, 'current')) {
            return [$page->items, (int) $page->current, (int) (isset($page->last) ? $page->last : $page->total_pages)];
        }

        return null;
    }

    private static function header(array $headers, $name)
    {
        return isset($headers[$name]) ? (string) $headers[$name] : '';
    }

    private static function csv($value)
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
    }
}
