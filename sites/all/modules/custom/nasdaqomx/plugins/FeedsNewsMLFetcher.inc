<?php
/**
 * Created by ron for ReachLocal.
 * Date: 6/6/14
 */

class FeedsNewsMLFetcherResult extends FeedsHTTPFetcherResult {

  protected $type;

  protected $max;

  protected $start;

  protected $end;

  function __construct($url = NULL) {
    parent::__construct($url);
    $this->type = NULL;
    $this->max = 10;
    $this->start = NULL;
    $this->end = NULL;
  }


  public function getRaw() {
    $this->buildFeed();

    return $this->sanitizeRaw($this->raw);
  }

  public function setType($type) {
    $this->type = (empty($type) || $type == 'All' ? '' : $type);
  }

  public function setMax($max) {
    $this->max = intval($max);
    if ($this->max < 10) {
      $this->max = 10;
    }
  }

  public function setStart($date) {
    if (is_numeric($date)) {
      $date = intval($date);
    } else {
      $date = strtotime($date);
    }
    $this->start = $date;
  }

  public function setEnd($date) {
    if (is_numeric($date)) {
      $date = intval($date);
    } else {
      $date = strtotime($date);
    }
    $this->end = $date;
  }

  private function buildFeed() {
    feeds_include_library('http_request.inc', 'http_request');


    // First we have to get the listing of releases available.
    $params = array(
      'FUNCTION' => 'NewsReleases',
      'NUMBERPERPAGE' => $this->max,
      'OUTPUT' => 'xml',
    );
    if (!empty($this->type)) {
      $params['RELEASETYPE'] = $this->type;
    }

    if (!is_null($this->start)) {
      $params['STARTDATE'] = date('n/j/Y', $this->start);
    }

    if (!is_null($this->end)) {
      $params['ENDDATE'] = date('n/j/Y', $this->end);
    }

    $result = http_request_get($this->buildURL($params), NULL, NULL, NULL, $this->timeout);
    if (!in_array($result->code, array(200, 201, 202, 203, 204, 205, 206))) {
      throw new Exception(t('Download of @url failed with code !code.', array('@url' => $this->url, '!code' => $result->code)));
    }
    $list = $this->sanitizeRaw($result->data);
    $xml = @simplexml_load_string($list);
    // Get the ID's for the releases.
    $IdEls = $xml->xpath('/NewsML/NewsItem/Identification/NewsIdentifier/NewsItemID');
    $ids = array();

    // Set up the params for the release detail calls.
    $params = array(
      'FUNCTION' => 'NewsReleaseDetail',
      'RELEASEID' => NULL,
      'FORMAT' => 'HTML',
      'OUTPUT' => 'xml',
    );

    $doc = $this->buildBaseFeed();

    foreach ($IdEls as $el) {
      $id = (string) $el;
      preg_match('/^(([A-Za-z]{1,}\s?){1,}_)([0-9]*)$/', trim($id), $matches);
      $params['RELEASEID'] = intval(trim($matches[3]));
      $result = http_request_get($this->buildURL($params), NULL, NULL, NULL, $this->timeout);
      if (!in_array($result->code, array(200, 201, 202, 203, 204, 205, 206))) {
        throw new Exception(t('Download of @url failed with code !code.', array('@url' => $this->url, '!code' => $result->code)));
      }
      $data = $this->sanitizeRaw($result->data);
      $dom = new DOMDocument();
      libxml_use_internal_errors(TRUE);
      $dom->loadXML($data);
      $r = $dom->documentElement;
      $el = $r->getElementsByTagName('NewsItem')->item(0);
      $node = $doc->importNode($el, TRUE);
      $doc->documentElement->appendChild($node);
    }

    $this->raw = $doc->saveXML();
  }

  private function buildURL($params) {
    $params += $this->getBaseParams();

    $qs = '';
    foreach ($params as $key => $value) {
      $qs .= $key . '=' . rawurlencode($value) . '&';
    }
    $qs = substr($qs, 0, strlen($qs)-1);

    return ($this->url . '?' . $qs);
  }

  private function getBaseParams() {
    return array(
      'COMPANYID' => variable_get('nasdaqomx_company_id', ''),
      'PIN' => variable_get('nasdaqomx_pin', ''),
    );
  }

  private function buildBaseFeed() {
    $x = new DOMImplementation();
    $dtd = $x->createDocumentType('NewsML',
      '+//W3C//DTD NewsML 1.0//EN http://www.irxml.org/dtd/NewsML_1.0.dtd');
    $doc = $x->createDocument(NULL,"NewsML", $dtd);
    $doc->version = "1.0";
    $doc->encoding = "UTF-8";
    $doc->formatOutput = TRUE;


    $root = $doc->documentElement;
    $env = $doc->createElement('NewsEnvelope');
    $root->appendChild($env);
    $dateEl = $doc->createElement('DateAndTime', date('Ymd'));
    $env->appendChild($dateEl);

    return $doc;
  }

}

class FeedsNewsMLFetcher extends FeedsHTTPFetcher {

  public function fetch(FeedsSource $source) {
    $source_config = $source->getConfigFor($this);
    $fetcher_result = new FeedsNewsMLFetcherResult($source_config['source']);
    // $fetcher_result->setDomain($source_config['omx_domain']);
    if (in_array('All', $source_config['omx_type'])) {
      $fetcher_result->setType('All');
    } else {
      $fetcher_result->setType(implode(',', $source_config['omx_type']));
    }

    if (isset($source_config['omx_start']) && !empty($source_config['omx_start'])) {
      $start = strtotime($source_config['omx_start']);
      $fetcher_result->setStart($start);
    }
    else {
      $last_item_result = db_query("SELECT MAX(d.news_dateline_value) AS last_feed_date FROM {field_data_news_dateline} d;");
      if ($last_item_result->rowCount() > 0) {
        $last_date = $last_item_result->fetchAssoc();
        $last_date = strtotime($last_date['last_feed_date']);
        $fetcher_result->setStart($last_date);
      }
    }

    if (isset($source_config['omx_end']) && !empty($source_config['omx_end'])) {
      $fetcher_result->setEnd(strtotime($source_config['omx_end']));
    }


    $fetcher_result->setMax(2000);
    // When request_timeout is empty, the global value is used.
    $fetcher_result->setTimeout($this->config['request_timeout']);
    return $fetcher_result;
  }

  public function configForm(&$form_state) {
    $form = parent::configForm($form_state);

    unset($form['auto_detect_feeds'], $form['use_pubsubhubbub'], $form['designated_hub']);


    return $form;
  }

  function sourceFormValidate(&$values) {
    parent::sourceFormValidate($values);

    // If the start and end dates are present, make sure start is before end.
    if (!empty($values['omx_start']) && !empty($values['omx_end'])) {
      $start = strtotime($values['omx_start']);
      $end = strtotime($values['omx_end']);
      if ($end < $start) {
        form_set_error('omx_start', t('Start date must be before end date.'));
        form_set_error('omx_end', '');
      }
    }
  }

  function sourceForm($source_config) {
    $form = parent::sourceForm($source_config);

    // Reset our source URL to the base URL for the OMX feed if it doesn't exist.
    $form['source']['#title'] = t('Source URL');
    $form['source']['#default_value'] = 'https://apps.shareholder.com/irxml/irxml.aspx';
    $form['source']['#description'] = '<span  style="color:#FF0000;">' . t('The source URL has been pre-configured. <strong>DO NOT CHANGE THE ABOVE URL.</strong>') . '</span>';

    if (!isset($source_config['omx_type'])) {
      $source_config['omx_type'] = array('All');
    }

    if (!isset($source_config['omx_start'])) {
      $source_config['omx_start'] = NULL;
    }

    if (!isset($source_config['omx_end'])) {
      $source_config['omx_end'] = NULL;
    }

    $form['omx_type'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Release type(s)'),
      '#options' => drupal_map_assoc(array('All', 'General Releases', 'Earnings News')),
      '#default_value' => $source_config['omx_type'],
      '#size' => 5,
      '#required' => TRUE,
    );

    $format = 'm/j/Y';

    $form['omx_start'] = array(
      '#type' => 'date_text',
      '#title' => t('Start date'),
      '#default_value' => $source_config['omx_start'],
      '#date_format' =>  $format,
      '#description' => t('The minimum date for which to fetch press releases.<br>NOTE: If a date is entered, the auto increment used by cron will not work properly.'),
    );

    $form['omx_end'] = array(
      '#type' => 'date_text',
      '#title' => t('End date'),
      '#default_value' => $source_config['omx_end'],
      '#date_format' =>  $format,
      '#description' => t('The maximum date for which to fetch press releases.<br>NOTE: If a date is entered, your site may not be able to retrieve all of the available press releases.'),
    );

    //var_dump($form);exit;

    return $form;
  }
}
