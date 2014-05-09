<?php

/**
 * Implementation of template preprocess for the view.
 */
function template_preprocess_views_bootstrap_thumbnail_plugin_style(&$vars) {
  $view     = $vars['view'];
  $options  = $view->style_plugin->options;
  $horizontal = ($options['alignment'] === 'horizontal');

  // If columns are set to 0 (Auto), apply the maximum from the number of columns
  // defined per device size.
  if (!$options['columns']) {
    $options['columns'] = 1;
    foreach (array('xs', 'sm', 'md', 'lg') as $size) {
      if (isset($vars['view']->style_options["columns_$size"]) && $vars['view']->style_options["columns_$size"] > 0) {
        $tmpColumns = 12 / $vars['view']->style_options["columns_$size"];
        if ($tmpColumns > $options['columns']) {
          $options['columns'] = $tmpColumns;
        }
      }
    }
  }

  // Split items by rows and columns.
  $vars['items'] = _views_bootstrap_split_rows($vars, $options['columns'], $horizontal);

  // Prepare column css classes. Add the class for each device size if set and if
  // different than 0 ("Do not define" option).
  $vars['col_classes'] = 'col';
  foreach (array('xs', 'sm', 'md', 'lg') as $size) {
    $vars['col_classes'] .= isset($vars['view']->style_options["columns_$size"]) && $vars['view']->style_options["columns_$size"]
      ? ' col-'.$size.'-'.$vars['view']->style_options["columns_$size"]
      : '';
  }
}

/**
 * Implementation of template preprocess for the view fields.
 */
function template_preprocess_views_bootstrap_thumbnail_plugin_rows(&$vars) {
  $view = &$vars['view'];

  foreach ($vars['options'] as $id => $field) {
    switch ($id) {
      case 'image':
      case 'title':
        if (isset($view->field[$field])) {
          $vars[$id] = $view->style_plugin->get_field($view->row_index, $field);
        }
        break;

      case 'content':
        $vars[$id] = array();
        foreach (array_filter($field) as $content_field) {
          if (isset($view->field[$content_field])) {
            $vars[$id][$content_field] = $view->style_plugin->get_field($view->row_index, $content_field);
          }
        }
        break;
    }
  }
}
