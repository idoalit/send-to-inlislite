<?php
/**
 * Plugin Name: S2In
 * Plugin URI: https://github.com/slims/slims9_bulian
 * Description: SLiMS to Inlislite database connector. Use the simplicity of SLiMS to input data to Inlislite.
 * Version: 0.0.1
 * Author: Ido Alit
 * Author URI: https://github.com/idoalit
 */

use Idoalit\S2i\Libs\CatalogHelper;
use Idoalit\S2i\Libs\IndexHelper;
use Idoalit\SlimsEloquentModels\Biblio;
use SLiMS\Plugins;

// load required library with composer autoload
require __DIR__ . '/vendor/autoload.php';

// load database connection
require __DIR__ . '/src/connection.php';

// Start plugins
Plugins::menu('system', 'Send to Inlislite DB', __DIR__ . '/src/send.php');

Plugins::hook(Plugins::BIBLIOGRAPHY_AFTER_SAVE, function($data) {
    IndexHelper::run(Biblio::find($data['biblio_id']));
});

Plugins::hook(Plugins::BIBLIOGRAPHY_AFTER_UPDATE, function($data) {
    IndexHelper::run(Biblio::find($data['biblio_id']));
});

Plugins::hook(Plugins::BIBLIOGRAPHY_AFTER_DELETE, function($biblio_id) {
    try {
        CatalogHelper::delete($biblio_id);
        \utility::jsToastr('Send to Inlislite', 'Bibliography DELETED!', 'success');
    } catch (\Throwable $th) {
        \utility::jsToastr('Send to Inlislite', $th->getMessage(), 'warning');
    }
});