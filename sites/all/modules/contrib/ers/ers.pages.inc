<?php
/**
 * @file
 * Contains administrative page callbacks for the ERS module.
 */

/**
 * Basic ERS settings page.
 */
function ers_settings_page() {
  return drupal_get_form('ers_settings_form');
}

function ers_settings_form($form, &$form_state) {
  $plugins = ers_get_entity_plugins();
  foreach ($plugins as $entity_type => $plugin) {
    if ($handler = ers_entity_plugin_get_handler($entity_type)) {
      $handler->settings_form($form, $form_state);
    }
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  return $form;
}

function ers_settings_form_validate($form, $form_state) {
  $plugins = ers_get_entity_plugins();
  foreach ($plugins as $entity_type => $plugin) {
    if ($handler = ers_entity_plugin_get_handler($entity_type)) {
      $handler->settings_form_validate($form, $form_state);
    }
  }
}

function ers_settings_form_submit($form, $form_state) {
  $plugins = ers_get_entity_plugins();
  foreach ($plugins as $entity_type => $plugin) {
    if ($handler = ers_entity_plugin_get_handler($entity_type)) {
      $handler->settings_form_submit($form, $form_state);
    }
  }

  drupal_set_message(t('The configuration options have been saved.'));
}
