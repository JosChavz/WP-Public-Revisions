<?php

namespace WpPublicRevisions\Lib;
class Diff {
    /**
     * Generates the difference between the base and the new revision content
     * @param string $content
     * @return string JSON that shows the difference
     */
    public static function generate_diff(string $content): string
    {
        return gzcompress($content);
    }

    /**
     * Applies the difference from the JSON from the DB to text
     * @param mixed $blob
     * @return string
     */
    public static function apply_diff(mixed $blob) : string {
        return gzuncompress($blob);
    }
}