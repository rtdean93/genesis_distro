<?php

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'icon' => 'icon_schedule_form.png',
  'title' => t('Scheduling settings'),
  'description' => t('Scheduling form.'),
  'required context' => new ctools_context_required(t('Form'), 'node_form'),
  'category' => t('Form'),
);


function ers_schedule_form_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = t('node_form');

  $block->title = t('Scheduling form');
  $block->delta = 'scheduling';

  if (isset($context->form)) {
    if (isset($context->form['ers'])) {
      $block->content['ers'] = $context->form['ers'];
      unset($block->content['ers']['#pre_render']);
      unset($block->content['ers']['#theme_wrappers']);
      $block->content['ers']['#type'] = '';
      $block->content['ers']['path']['#size'] /= 2;

      // Set access to false on the original rather than removing so that
      // vertical tabs doesn't clone it. I think this is due to references.
      $context->form['ers']['#access'] = FALSE;
    }
  }
  else {
    $block->content = t('Scheduling form.');
  }
  return $block;
}

function ers_schedule_form_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" Scheduling form', array('@s' => $context->identifier));
}

function ers_schedule_form_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
