<?php
/**
 * Created by ron for ReachLocal.
 * Date: 6/6/14
 */

function nasdaqomx_settings_form($form, &$form_state) {
  $form = array();

  $form['nasdaqomx_company_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Company ID'),
    '#description' => t('Company ID issued by NASDAQ OMX for web services access.'),
    '#required' => TRUE,
    '#default_value' => variable_get('nasdaqomx_company_id', '')
  );

  $form['nasdaqomx_pin'] = array(
    '#type' => 'textfield',
    '#size' => 8,
    '#maxlength' => 10,
    '#title' => t('PIN'),
    '#description' => t('PIN issued by NASDAQ OMX for web services access.'),
    '#required' => TRUE,
    '#default_value' => variable_get('nasdaqomx_pin', '')
  );

  $form['nasdaqomx_domain'] = array(
    '#type' => 'textfield',
    '#title' => t('Domain'),
    '#description' => t('The base domain where the releases are located. Include the complete protocol including &quot;http://&quot;.'),
    '#default_value' => variable_get('nasdaqomx_domain', ''),
    '#required' => TRUE,
  );

  return system_settings_form($form);
}