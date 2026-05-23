<?php

namespace WpPublicRevisions;

use WpPublicRevisions\Admin\Metabox;
use WpPublicRevisions\Frontend\Shortcode;
use WpPublicRevisions\Frontend\Viewer;

class Initialize {
    public function __construct() {}

    public static function init() {
        $shortcode = new Shortcode();
        $shortcode->init();

        $viewer = new Viewer();
        $viewer->init();

        $metabox = new Metabox();
        $metabox->init();
    }
}