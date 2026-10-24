<?php

namespace PhalconInertia;

class Page
{
    const MAX_SAFE_INTEGER = 9007199254740991;

    private static $mergeLists = [
        Prop::APPEND => 'mergeProps',
        Prop::PREPEND => 'prependProps',
        Prop::DEEP => 'deepMergeProps',
    ];

    private $partial;

    private $only;

    private $except;

    private $reset;

    private $loaded;

    private $scrollList;

    private $options;

    private $context;

    private $meta = [];

    private function __construct($component, $partial, array $headers, array $options)
    {
        $this->context = new RenderContext($component, isset($options['request']) ? $options['request'] : null);
        $this->partial = $partial;
        $this->only = self::csv(self::header($headers, 'X-Inertia-Partial-Data'));
        $this->except = self::csv(self::header($headers, 'X-Inertia-Partial-Except'));
        $this->reset = self::csv(self::header($headers, 'X-Inertia-Reset'));
        $this->loaded = $partial ? [] : self::csv(self::header($headers, 'X-Inertia-Except-Once-Props'));
        $this->scrollList = self::header($headers, 'X-Inertia-Infinite-Scroll-Merge-Intent') === 'prepend' ? 'prependProps' : 'mergeProps';
        $this->options = $options;
    }

    public static function build($component, array $props, $url, $version, array $headers = [], array $options = [])
    {
        $partial = $component !== '' && self::header($headers, 'X-Inertia-Partial-Component') === $component;
        $builder = new self($component, $partial, $headers, $options);
        $resolved = $builder->props(self::unpack($props), '', false);
        $meta = $builder->meta;
        $flash = isset($options['flash']) ? $options['flash'] : [];
        if (! empty($options['preserveBigIntegers'])) {
            $resolved = self::bigIntegers($resolved);
            $flash = self::bigIntegers($flash);
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

        if (! empty($options['preserveFragment'])) {
            $page['preserveFragment'] = true;
        }

        foreach (['mergeProps', 'prependProps', 'deepMergeProps', 'matchPropsOn', 'deferredProps', 'rescuedProps'] as $list) {
            if (isset($meta[$list])) {
                $page[$list] = $meta[$list];
            }
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

        if (! empty($flash)) {
            $page['flash'] = (object) $flash;
        }

        if (! empty($options['preserveBigIntegers'])) {
            $page['preserveBigIntegers'] = true;
        }

        return $page;
    }

    /**
     * Replace ProvidesInertiaProperties objects under numeric keys with the props they provide.
     */
    public static function provide(array $props, RenderContext $context)
    {
        $provided = [];
        foreach ($props as $key => $value) {
            if (! is_int($key) || ! $value instanceof ProvidesInertiaProperties) {
                $provided[$key] = $value;

                continue;
            }

            foreach ($value->toInertiaProperties($context) as $name => $prop) {
                $provided[$name] = $prop;
            }
        }

        return $provided;
    }

    public static function resolve($value)
    {
        $value = self::unwrap($value);

        return is_array($value) ? array_map([__CLASS__, 'resolve'], $value) : $value;
    }

    /**
     * Resolve a level of the props tree. Children of a prop that wasn't a plain array (a closure or a Prop)
     * skip partial filtering, like the parent they came from.
     */
    private function props(array $props, $prefix, $parentResolved)
    {
        $props = self::provide($props, $this->context);
        $resolved = [];
        foreach ($props as $key => $prop) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (! $this->isSelected($path, $prop, $parentResolved)) {
                // An optional or deferred prop left out of the first visit still tells the client how to merge and keep it
                if (! $this->partial && $prop instanceof Prop) {
                    if ($prop->group() !== null && ! $this->wasLoaded($path, $prop)) {
                        $this->meta['deferredProps'][$prop->group()][] = $path;
                    }
                    $this->collectMerge($path, $prop);
                    $this->collectOnce($path, $prop);
                }

                continue;
            }

            if ($this->wasLoaded($path, $prop)) {
                $this->collectOnce($path, $prop);

                continue;
            }

            try {
                $value = self::unwrap($prop);
                if ($value instanceof ProvidesInertiaProperty) {
                    $value = $value->toInertiaProperty(new PropertyContext($path, $props, $this->context->request));
                }
            } catch (\Exception $e) {
                self::rescue($prop, $e, $this->options);
                $this->meta['rescuedProps'][] = $path;

                continue;
            } catch (\Throwable $e) {
                self::rescue($prop, $e, $this->options);
                $this->meta['rescuedProps'][] = $path;

                continue;
            }

            if ($prop instanceof Prop) {
                $value = $this->collect($path, $prop, $value);
            }

            $resolved[$key] = is_array($value) ? $this->props($value, $path, $parentResolved || ! is_array($prop)) : $value;
        }

        return $resolved;
    }

    private function collect($path, Prop $prop, $value)
    {
        if ($prop->scrollOptions() !== null) {
            $scroll = self::scroll($value, $prop->scrollOptions());
            $value = $scroll[0];
            $this->meta['scrollProps'][$path] = $scroll[1] + ['reset' => in_array($path, $this->reset, true)];
        }

        $this->collectMerge($path, $prop);
        $this->collectOnce($path, $prop);

        return $value;
    }

    private function collectMerge($path, Prop $prop)
    {
        if (in_array($path, $this->reset, true) || ($this->partial && ! $this->isRequested($path))) {
            return;
        }

        if ($prop->scrollOptions() !== null) {
            $this->meta[$this->scrollList][] = $path.'.'.$prop->scrollOptions()['wrapper'];
        }

        if ($prop->mergeMode() !== null) {
            $this->meta[self::$mergeLists[$prop->mergeMode()]][] = $path;
            foreach ($prop->matchFields() as $field) {
                $this->meta['matchPropsOn'][] = $path.'.'.$field;
            }
        }
    }

    private function collectOnce($path, Prop $prop)
    {
        if ($prop->isOnce() && ! ($this->partial && ! $this->isRequested($path))) {
            $this->meta['onceProps'][$prop->onceKey($path)] = ['prop' => $path, 'expiresAt' => $prop->expiresAt()];
        }
    }

    private function isSelected($path, $prop, $parentResolved)
    {
        if ($prop instanceof Prop && $prop->isAlways()) {
            return true;
        }

        if (! $this->partial) {
            return ! ($prop instanceof Prop && ($prop->isOptional() || $prop->group() !== null));
        }

        if ($parentResolved) {
            return true;
        }

        if ($this->only && ! self::matches($path, $this->only) && ! self::leadsTo($path, $this->only)) {
            return false;
        }

        return ! self::matches($path, $this->except);
    }

    /**
     * Whether a partial reload asked for this path itself, not only for something inside it.
     */
    private function isRequested($path)
    {
        return (! $this->only || self::matches($path, $this->only)) && ! self::matches($path, $this->except);
    }

    private function wasLoaded($path, $prop)
    {
        return $prop instanceof Prop && $prop->isOnce() && ! $prop->isFresh() && in_array($prop->onceKey($path), $this->loaded, true);
    }

    /**
     * Whether the path is one of the given paths or inside one.
     */
    private static function matches($path, array $paths)
    {
        foreach ($paths as $candidate) {
            if ($path === $candidate || strpos($path, $candidate.'.') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether one of the given paths is inside the path.
     */
    private static function leadsTo($path, array $paths)
    {
        foreach ($paths as $candidate) {
            if (strpos($candidate, $path.'.') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Move top-level keys like "user.permissions" into the nested array they point into.
     */
    private static function unpack(array $props)
    {
        foreach ($props as $key => $value) {
            if (strpos((string) $key, '.') === false) {
                continue;
            }

            unset($props[$key]);
            $segments = explode('.', (string) $key);
            $last = array_pop($segments);
            $node = &$props;
            foreach ($segments as $segment) {
                if (isset($node[$segment]) && $node[$segment] instanceof \Closure) {
                    $node[$segment] = call_user_func($node[$segment]);
                }
                if (! isset($node[$segment]) || ! is_array($node[$segment])) {
                    $node[$segment] = [];
                }
                $node = &$node[$segment];
            }
            $node[$last] = $value;
            unset($node);
        }

        return $props;
    }

    /**
     * Wrap integers beyond JavaScript's safe range. Objects with their own JSON form (DateTime and other
     * internal classes) are left alone, and $seen holds the ancestors so a cycle is left to json_encode.
     */
    private static function bigIntegers($value, array $seen = [])
    {
        if (is_int($value)) {
            return $value > self::MAX_SAFE_INTEGER || $value < -self::MAX_SAFE_INTEGER ? ['$bigint' => (string) $value] : $value;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::bigIntegers($item, $seen);
            }

            return $value;
        }

        if (! is_object($value) || isset($seen[spl_object_hash($value)])) {
            return $value;
        }

        $seen[spl_object_hash($value)] = true;
        if ($value instanceof \BackedEnum) {
            return self::bigIntegers($value->value, $seen);
        }

        if ($value instanceof \UnitEnum) {
            return $value;
        }

        if ($value instanceof \JsonSerializable) {
            return self::bigIntegers($value->jsonSerialize(), $seen);
        }

        if ($value instanceof \stdClass || ! self::isInternal($value)) {
            return (object) self::bigIntegers(get_object_vars($value), $seen);
        }

        return $value;
    }

    private static function isInternal($object)
    {
        for ($class = new \ReflectionClass($object); $class; $class = $class->getParentClass()) {
            if ($class->isInternal()) {
                return true;
            }
        }

        return false;
    }

    private static function unwrap($value)
    {
        while ($value instanceof Prop || $value instanceof \Closure) {
            $value = $value instanceof Prop ? $value->get() : call_user_func($value);
        }

        return $value;
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
        $custom = $options['metadata'];
        if (! is_array($custom) && is_callable($custom)) {
            $custom = call_user_func($custom, $value);
        }

        if ($custom instanceof ProvidesScrollMetadata) {
            $custom = [
                'pageName' => $custom->getPageName(),
                'previousPage' => $custom->getPreviousPage(),
                'nextPage' => $custom->getNextPage(),
                'currentPage' => $custom->getCurrentPage(),
            ];
        }

        $page = self::paginator($value);
        if ($page === null && $custom === null) {
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

        return [$value, array_merge($metadata, (array) $custom)];
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
