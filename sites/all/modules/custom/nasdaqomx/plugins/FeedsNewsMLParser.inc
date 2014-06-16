<?php
/**
 * Created by ron for ReachLocal.
 * Date: 6/10/14
 */

class FeedsNewsMLParser extends FeedsParser {

  public function parse(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    /*
     * Set up the various xpaths that will be used to get the data elements.
     */
    $itemXpath = '/NewsML/NewsItem'; // Gets each NewsItem element.
    $idXpath = 'Identification/NewsIdentifier/NewsItemID';
    $dateXpath = 'NewsComponent/NewsLines/DateLine';
    $dateTimeXpath = 'NewsComponent/NewsLines/DateTimeLine';
    $headlineXpath = 'NewsComponent/NewsLines/HeadLine';
    $subheadXpath = 'NewsComponent/NewsLines/SubHeadLine';
    $metaLang = 'NewsComponent/DescriptiveMetaData/Language';
    $metaTypeXpath = 'NewsComponent/DescriptiveMetaData/Genre';
    $metaPropXpath = 'NewsComponent/DescriptiveMetaData/Property';

    $bodyXpath = 'NewsComponent/NewsComponent/ContentItem/DataContent/body/body.content';

    // Yes, using a DOM parser is a bit inefficient, but will do for now
    $xml = simplexml_load_string($fetcher_result->getRaw());
    $result = new FeedsParserResult();

    $releases = $xml->xpath($itemXpath);

    foreach ($releases as $release) {
      $item = array();
      $id = (string)$release->xpath($idXpath)[0];
      preg_match('/^(([A-Za-z]{1,}\s?){1,}_)([0-9]*)$/', trim($id), $matches);
      $item['ReleaseID'] = intval(trim($matches[3]));
      $item['HeadLine'] = (string) $release->xpath($headlineXpath)[0];
      $item['SubHeadLine'] = (string) $release->xpath($subheadXpath)[0];
      $item['DateLine'] = @strtotime(trim((string) $release->xpath($dateXpath)[0]));
      // We'll have to clean up the dates so we can parse them with strtotime().
      $d = trim((string) $release->xpath($dateTimeXpath)[0]);
      $d = str_replace(' ET', '', $d);
      $item['DateTimeLine'] = strtotime($d);

      // Get the meta data (type, language, attachments, etc.)
      $langEl = $release->xpath($metaLang)[0];
      $typeEl = $release->xpath($metaTypeXpath)[0];

      $item['Language'] = trim($langEl->attributes()['FormalName']);
      $item['Genre'] = trim($typeEl->attributes()['FormalName']);
      $item['PDF'] = '';
      $item['PDFSize'] = '';

      foreach ($release->xpath($metaPropXpath) as $el) {
        $attr = $el->attributes();
        if ($attr['FormalName'] != 'ReferencePDF' && $attr['FormalName'] != 'PDFSize') continue;
        switch ($attr['FormalName']) {
          case 'PDFSize':
            $item['PDFSize'] = intval($attr['Value']);
            break;
          default: // ReferencePDF
            $item['PDF'] = trim($attr['Value']);
            break;
        }
      }

      $body = trim((string) $release->xpath($bodyXpath)[0]);
      // watchdog('nasdaqomx', '!id data (pre-clean):<br><pre>!data</pre>', array('!id' => $item['ReleaseID'], '!data' => print_r(htmlentities($body), true)), WATCHDOG_NOTICE);

      // Some of the releases contain HTML document structures that need removed
      // so remove those known tags.
      $body = str_replace('<HTML>', '', $body);
      $body = str_replace('</HTML>', '', $body);
      $body = str_replace('<BODY>', '', $body);
      $body = str_replace('</BODY>', '', $body);

      $tags = array('title', 'head', 'style');

      foreach ($tags as $tag) {
        $exp = '/<' . $tag . '.*>(.*?)<\/' . $tag . '>/si';
        if (preg_match($exp, $body) !== 1) continue;
        $body = preg_replace($exp, '', $body);
      }


      $body = str_replace('<p></p>', '', $body);
      $body = str_replace('<p>&nbsp;</p>', '', $body);

      if (substr($body, 0, 11) == "<p>&nbsp;\n") {
        $body = substr($body, 12);
      }
      if (substr($body, 0, 4) == "<p>\n") {
        $body = substr($body, 5);
      }

      //watchdog('nasdaqomx', '!id data:<br><pre>!data</pre>', array('!id' => $item['ReleaseID'], '!data' => print_r(htmlentities($body), true)), WATCHDOG_NOTICE);

      //preg_match_all('/<MY_TAG>(.*?)<\/MY_TAG>/s', $str, $matches)

      $item['Body'] = trim($body);

      $domain = trim(variable_get('nasdaqomx_domain', ''));
      if (!empty($domain)) {
        if (substr($domain, -1, 1) != '/') {
          $domain .= '/';
        }
        $item['Source'] = $domain . 'releasedetail.cfm?ReleaseID=' . $item['ReleaseID'];
      } else {
        $item['Source'] = NULL;
      }

      $result->items[] = $item;
      // break;
    }

    //

    return $result;
  }

  public function getMappingSources() {
    return array(
      'ReleaseID' => array(
        'name' => t('Release ID'),
        'description' => t('The OMX unique identifier for the release.'),
      ),
      'HeadLine' => array(
        'name' => t('Headline'),
        'description' => t(''),
      ),
      'SubHeadLine' => array(
        'name' => t('Subheading'),
        'description' => t(''),
      ),
      'DateLine' => array(
        'name' => t('Date line for the release'),
        'description' => t('The date for the release.'),
      ),
      'DateTimeLine' => array(
        'name' => t('Date/Time'),
        'description' => t('The date/time for the release'),
      ),
      'Language' => array(
        'name' => t('Language'),
        'description' => t('The language that the release is written.'),
      ),
      'Genre' => array(
        'name' => t('Genre'),
        'description' => t('The type of news item (i.e. News Release or Earnings).'),
      ),
      'PDF' => array(
        'name' => t('PDF URL'),
        'description' => t('URL for the PDF version of this release.'),
      ),
      'PDFSize' => array(
        'name' => t('PDF Size'),
        'description' => t('Size of the PDF (if available) in bytes.'),
      ),
      'Body' => array(
        'name' => t('Body'),
        'description' => t('Content of the release.'),
      ),
      'Source' => array(
        'name' => t('Source'),
        'description' => t('The original URL for this release.'),
      )
    ) + parent::getMappingSources();
  }
} 