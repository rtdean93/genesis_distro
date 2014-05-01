<?php

/**
 * @file
 * Theme related processing functions.
 */

/**
 * Implements hook_css_alter().
 */
function demonstratie_core_css_alter(&$css) {
  // Remove styles that we do not need.
  $remove = array(
    'system' => 'system.theme.css',
    'search' => 'search.css',
  );

  foreach ($remove as $module => $file) {
    $path = drupal_get_path('module', $module) . '/' . $file;
    if (isset($css[$path])) {
      unset($css[$path]);
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function demonstratie_core_form_search_block_form_alter(&$form, &$form_state, $form_id) {
  // Give the search submit some different styles.
  $form['actions']['submit']['#attributes']['class'][] = 'action-item-primary';
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function demonstratie_core_form_user_login_block_alter(&$form, &$form_state, $form_id) {
  // Make the submit button more noticeable.
  $form['actions']['submit']['#attributes']['class'][] = 'action-item-primary';
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function demonstratie_core_form_user_login_alter(&$form, &$form_state, $form_id) {
  // Make the submit button more noticeable.
  $form['actions']['submit']['#attributes']['class'][] = 'action-item-primary';
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function demonstratie_core_form_user_pass_alter(&$form, &$form_state, $form_id) {
  // Make the submit button more noticeable.
  $form['actions']['submit']['#attributes']['class'][] = 'action-item-primary';
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function demonstratie_core_form_user_register_form_alter(&$form, &$form_state, $form_id) {
  // Make the submit button more noticeable.
  $form['actions']['submit']['#attributes']['class'][] = 'action-item-primary';
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function demonstratie_core_form_commerce_cart_add_to_cart_form_alter(&$form, &$form_state, $form_id) {
  // Make the "Add to cart" button more noticeable.
  $form['submit']['#attributes']['class'][] = 'action-item-large-primary';
}

/**
 * Implements hook_preprocess_html().
 */
function demonstratie_core_preprocess_html(&$variables, $hook) {
  // Add more meta tags to proper mobile device compatibility.
  $head = array(
    'viewport' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'viewport',
        'content' => 'width=device-width, target-densityDpi=160dpi, initial-scale=1',
      ),
    ),
    'mobileoptimized' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'MobileOptimized',
        'content' => 'width',
      ),
    ),
    'handheldfriendly' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'HandheldFriendly',
        'content' => 'true',
      ),
    ),
    'apple_mobile_web_app_capable' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'apple-mobile-web-app-capable',
        'content' => 'yes',
      ),
    ),
    'cleartype' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'http-equiv' => 'cleartype',
        'content' => 'on',
      ),
    ),
    'x_ua_compatible' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'http-equiv' => 'X-UA-Compatible',
        'content' => 'IE-edge,chrome=1',
      ),
    ),
  );
  foreach ($head as $key => $data) {
    drupal_add_html_head($data, $key);
  }

  // Add classes for the status of header regions.
  switch (FALSE) {
    case isset($variables['page']['search']):
      $variables['classes_array'][] = 'no-search';
    case isset($variables['page']['header_first']):
      $variables['classes_array'][] = 'no-header-first';
    case isset($variables['page']['header_second']):
      $variables['classes_array'][] = 'no-header-second';
      break;
  }

}

/**
 * Implements hook_preprocess_page().
 */
function demonstratie_core_preprocess_page(&$variables, $hook) {
  $variables['page_title_attributes_array'] = array(
    'class' => array(
      'site-name',
    ),
  );

  if ($variables['logo']) {
    // If the logo is available, hide the title.
    $variables['page_title_attributes_array']['class'][] = 'element-invisible';

    // Make the image output via a renderable array.
    $variables['page']['logo_image'] = array(
      '#theme' => 'image_formatter',
      '#item' => array(
        'uri' => $variables['logo'],
        'alt' => $variables['site_name'] . ' logo',
        'attributes' => array(
          'class' => array('site-logo'),
        ),
      ),
    );

    // If the front page is not being viewed, link the image to the front page.
    if (!$variables['is_front']) {
      $variables['page']['logo_image']['#path'] = array(
        'path' =>  '<front>',
        'options' => array(
          'attributes' => array(
            'class' => array('site-logo-link'),
          ),
        ),
      );
    }
  }

  // Make the menus renderable arrays.
  if ($variables['main_menu']) {
    $variables['page']['main_menu'] = array(
      '#theme' => 'links__system_main_menu',
      '#links' => $variables['main_menu'],
      '#attributes' => array(
        'class' => array('site-menu', 'main-menu'),
      ),
      '#heading' => array(
        'text' => t('Main menu'),
        'level' => 'h3',
        'class' => array('menu-header element-invisible'),
      ),
    );
  }
  if ($variables['secondary_menu']) {
    $variables['page']['secondary_menu'] = array(
      '#theme' => 'links__system_secondary_menu',
      '#links' => $variables['secondary_menu'],
      '#attributes' => array(
        'class' => array('site-menu', 'secondary-menu'),
      ),
      '#heading' => array(
        'text' => t('Secondary menu'),
        'level' => 'h3',
        'class' => array('menu-header element-invisible'),
      ),
    );
  }
}

/**
 * Implements hook_process_page().
 */
function demonstratie_core_process_page(&$variables, $hook) {
  // Crunch down the page title attributes array into a string.
  $variables['page_title_attributes'] = drupal_attributes($variables['page_title_attributes_array']);
}

/**
 * Implements hook_preprocess_block().
 */
function demonstratie_core_preprocess_block(&$variables, $block) {
  // Add a class to the block title.
  $variables['title_attributes_array']['class'][] = 'block-title';
  $variables['content_attributes_array']['class'][] = 'block-content';
}

/**
 * Implements hook_preprocess_node().
 */
function demonstratie_core_preprocess_node(&$variables, $hook) {
  // Add a general class to the node title.
  $variables['title_attributes_array']['class'][] = 'node-title';
  $variables['title_content_array']['class'][] = 'node-content';
}

/**
 * Implements hook_preprocess_panels_pane().
 */
function demonstratie_core_preprocess_panels_pane(&$variables, $hook) {
  // Mimic blocks.
  $variables['classes_array'][] = 'block';
  $variables['title_attributes_array']['class'][] = 'block-title';
  $variables['content_attributes_array']['class'][] = 'block-content';
}

/**
 * Overrides theme_menu_local_tasks().
 *
 * Change the classes used on the local tasks so the entire system.menu.css
 * stylesheet does not have to be disabled.
 */
function demonstratie_core_menu_local_tasks(&$variables) {
  $output = '';

  if (!empty($variables['primary'])) {
    $variables['primary']['#prefix'] = '<h2 class="element-invisible">' . t('Primary tabs') . '</h2>';
    $variables['primary']['#prefix'] .= '<ul class="local-tasks local-tasks-primary">';
    $variables['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['primary']);
  }
  if (!empty($variables['secondary'])) {
    $variables['secondary']['#prefix'] = '<h2 class="element-invisible">' . t('Secondary tabs') . '</h2>';
    $variables['secondary']['#prefix'] .= '<ul class="local-tasks local-tasks-secondary">';
    $variables['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['secondary']);
  }

  return $output;
}

/**
 * Overrides theme_menu_local_task()
 *
 * Add a general class to the list items and links so the css does not have to
 * be overly specific.
 */
function demonstratie_core_menu_local_task($variables) {
  $link = $variables['element']['#link'];
  $link_text = $link['title'];

  // Add a general class to the link.
  $link['localized_options']['attributes']['class'][] = 'local-task-link';

  if (!empty($variables['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = '<span class="element-invisible">' . t('(active tab)') . '</span>';

    // If the link does not contain HTML already, check_plain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = check_plain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array('!local-task-title' => $link['title'], '!active' => $active));
  }

  return '<li class="local-task' . (!empty($variables['element']['#active']) ? ' active"' : '"') . '>' . l($link_text, $link['href'], $link['localized_options']) . "</li>\n";
}
