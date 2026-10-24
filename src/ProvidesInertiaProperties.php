<?php

namespace PhalconInertia;

/**
 * An object passed to render() or share() without a key that provides several props at once.
 */
interface ProvidesInertiaProperties
{
    /**
     * @return array|\Traversable Prop name => value
     */
    public function toInertiaProperties(RenderContext $context);
}
