<?php
/**
 * @file
 * rl_smart_site.features.taxonomy.inc
 */

/**
 * Implements hook_taxonomy_default_vocabularies().
 */
function rl_smart_site_taxonomy_default_vocabularies() {
  return array(
    'smart_site_category' => array(
      'name' => 'Smart Site Category',
      'machine_name' => 'smart_site_category',
      'description' => 'Used to categorize the Smart Site nodes',
      'hierarchy' => 0,
      'module' => 'taxonomy',
      'weight' => 0,
      'rdf_mapping' => array(
        'rdftype' => array(
          0 => 'skos:ConceptScheme',
        ),
        'name' => array(
          'predicates' => array(
            0 => 'dc:title',
          ),
        ),
        'description' => array(
          'predicates' => array(
            0 => 'rdfs:comment',
          ),
        ),
      ),
    ),
  );
}
