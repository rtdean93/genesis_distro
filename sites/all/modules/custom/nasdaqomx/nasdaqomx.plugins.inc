<?php
/**
 * @file Contains CTools plugin code.
 */

/**
 * Implements hook_ctools_plugin_api()
 */
function nasdaqomx_ctools_plugin_api() {
  $args = func_get_args();
  $module = array_shift($args);
  $api = array_shift($args);
  if ($module == "feeds" && $api == "feeds_importer_default") {
    return array("version" => 1);
  }
}

function nasdaqomx_feeds_plugins() {
  $plugins = array();

  // Custom fetcher.
  $plugins['NewsMLFetcher'] = array(
    'name' => 'NewsML Fetcher',
    'description' => t('Fetches news releases from the NASDAQ OMX web service'),
    'handler' => array(
      'parent' => 'FeedsHTTPFetcher',
      'class' => 'FeedsNewsMLFetcher',
      'file' => 'FeedsNewsMLFetcher.inc',
      'path' => drupal_get_path('module', 'nasdaqomx') . '/plugins',
    ),
  );

  $plugins['NewsMLParser'] = array(
    'name' => 'NewsML Parser',
    'description' => t('Parses news releases in the NewsML format.'),
    'handler' => array(
      'parent' => 'FeedsParser',
      'class' => 'FeedsNewsMLParser',
      'file' => 'FeedsNewsMLParser.inc',
      'path' => drupal_get_path('module', 'nasdaqomx') . '/plugins',
    ),
  );

  return $plugins;
}

/**
 * Implementation of hook_feeds_importer_default().
 */

function nasdaqomx_feeds_importer_default() {
  $export = array();

  $feeds_importer = new stdClass();
  $feeds_importer->disabled = FALSE; /* Edit this to true to make a default feeds_importer disabled initially */
  $feeds_importer->api_version = 1;
  $feeds_importer->id = 'nasdaq_news_release';
  $feeds_importer->config = array(
    'name' => 'NASDAQ News Release',
    'description' => 'News releases originally published on the investor\'s site by BlueShirt Group.',
    'fetcher' => array(
      'plugin_key' => 'NewsMLFetcher',
      'config' => array(
        'auto_detect_feeds' => FALSE,
        'use_pubsubhubbub' => FALSE,
        'designated_hub' => '',
        'request_timeout' => NULL,
      ),
    ),
    'parser' => array(
      'plugin_key' => 'NewsMLParser',
      'config' => array(),
    ),
    'processor' => array(
      'plugin_key' => 'FeedsNodeProcessor',
      'config' => array(
        'expire' => '-1',
        'author' => '1',
        'authorize' => 1,
        'mappings' => array(
          0 => array(
            'source' => 'ReleaseID',
            'target' => 'guid',
            'unique' => 1,
          ),
          1 => array(
            'source' => 'HeadLine',
            'target' => 'title',
            'unique' => FALSE,
          ),
          2 => array(
            'source' => 'SubHeadLine',
            'target' => 'news_subhead',
            'unique' => FALSE,
          ),
          3 => array(
            'source' => 'Body',
            'target' => 'body',
            'unique' => FALSE,
          ),
          4 => array(
            'source' => 'DateTimeLine',
            'target' => 'created',
            'unique' => FALSE,
          ),
          5 => array(
            'source' => 'DateLine',
            'target' => 'news_dateline:start',
            'unique' => FALSE,
          ),
          6 => array(
            'source' => 'Genre',
            'target' => 'news_type',
            'unique' => FALSE,
          ),
          7 => array(
            'source' => 'PDF',
            'target' => 'news_pdf_link',
            'unique' => FALSE,
          ),
          8 => array(
            'source' => 'PDFSize',
            'target' => 'news_pdf_size',
            'unique' => FALSE,
          ),
          9 => array(
            'source' => 'ReleaseID',
            'target' => 'news_release_id',
            'unique' => FALSE,
          ),
          10 => array(
            'source' => 'Source',
            'target' => 'news_source_link:url',
            'unique' => FALSE,
          ),
        ),
        'update_existing' => '0',
        'input_format' => 'full_html',
        'skip_hash_check' => 0,
        'bundle' => 'omx_news',
      ),
    ),
    'content_type' => 'newsml_importer',
    'update' => 0,
    'import_period' => '3600',
    'expire_period' => 3600,
    'import_on_create' => 1,
    'process_in_background' => 0,
  );


  $export['nasdaq_news_release'] = $feeds_importer;

  return $export;
}
