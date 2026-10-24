<?php

namespace PhalconInertia;

/**
 * Page numbers for an infinite scroll prop whose data doesn't come from a Phalcon paginator.
 */
interface ProvidesScrollMetadata
{
    /**
     * @return string
     */
    public function getPageName();

    /**
     * @return int|string|null
     */
    public function getPreviousPage();

    /**
     * @return int|string|null
     */
    public function getNextPage();

    /**
     * @return int|string|null
     */
    public function getCurrentPage();
}
