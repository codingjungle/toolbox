<?php

/**
 * @brief      LangPrefix Interface
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox
 * @since      -storm_since_version-
 * @version    -storm_version-
 */

namespace IPS\toolbox\Code;

/**
 * LangPrefix Class
 * @mixin LangPrefix
 */
interface LangPrefix
{
    /**
     * add an array of lang prefixes to exclude in the lang check of analyzer
     * @param array $prefixes
     */
    public function prefixes(array &$prefixes): void;

    /**
     * add an array of lang suffixes to exclude in the lang check of analyzer
     * @param array $suffixes
     */
    public function suffixes(array &$suffixes): void;
}
