<?php

namespace WpPublicRevisions\Lib;

use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;
use Jfcherng\Diff\Options\DifferOptions;
use Jfcherng\Diff\Options\RendererOptions;
use Jfcherng\Diff\Renderer\RendererConstant;

class Diff {
    /**
     * Generates the difference between the base and the new revision content
     * @param string $content
     * @return string JSON that shows the difference
     */
    public static function generate_diff($content) {
        return gzcompress($content);
    }

    /**
     * Applies the difference from the JSON from the DB to text
     * @param mixed $blob
     * @return string
     */
    public static function apply_diff($blob) : string {
        return gzuncompress($blob);
    }
}