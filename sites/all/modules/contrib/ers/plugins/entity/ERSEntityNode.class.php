<?php
/**
 * @file
 * Class for the ERS node entity plugin.
 */

/**
 * ERS Entity node plugin class.
 *
 * Handles node specific functionality for ERS.
 */
class ERSEntityNode extends ERSEntityDefault {

  public $supports_publishing_flag = TRUE;

  public function hook_menu_alter(&$items) {
    // Provide a nicer title to remind the user that this will edit drafts.
    $items['node/%node/edit']['title'] = 'Edit';
    $items['node/%node/edit']['title callback'] = 'ers_entity_edit_title_callback';
    $items['node/%node/edit']['title arguments'] = array('node', 1);

    // Completely take over the revisions page because we're adding a lot to it.
    $items['node/%node/revisions'] = array(
      'title' => 'Revisions',
      'page callback' => 'ers_entity_plugin_switcher_page',
      'page arguments' => array('node', 'revisions', 1),
      'access callback' => 'ers_entity_plugin_access_switcher',
      'access arguments' => array('node', 'revisions', 1),
      'weight' => 2,
      'type' => MENU_LOCAL_TASK,
    );
  }

  public function hook_entity_presave($entity) {
    if (empty($this->plugin['bundles'][$entity->type])) {
      return;
    }

    // Make sure the published flag matches our publish state.
    if (isset($entity->ers_schedule_revision_id)) {
      // This flag indicates we're about to change schedules.
      $entity->status = empty($entity->ers_schedule_revision_id) ? NODE_NOT_PUBLISHED : NODE_PUBLISHED;
    }
    else if (!empty($entity->ers_new_schedule) && $entity->ers_new_schedule <= time() && isset($entity->ers_schedule_selector)) {
      // In this case, the schedule selector is set but we do not yet know a
      // revision ID so we use THIS setting to determine publish state.
      $entity->status = $entity->ers_schedule_selector == 'unpublish' ? NODE_NOT_PUBLISHED : NODE_PUBLISHED;
    }
    else {
      // Otherwise, ensure it matches the existing flag.
      $entity->status = empty($entity->published_revision_id) ? NODE_NOT_PUBLISHED : NODE_PUBLISHED;
    }
  }

  public function set_published_revision_current($entity_id, $entity) {
    if (empty($this->plugin['bundles'][$entity->type]) || !isset($entity->published_revision_id)) {
      return;
    }

    $this->fix_revision_cache($entity_id, $entity);

    $this->ers_revision_reset = TRUE;

    $entity_info = entity_get_info($this->entity_type);
    $revision_key = $entity_info['entity keys']['revision'];
    $published_revisions = entity_load($this->entity_type, array($entity_id), array($revision_key => $entity->published_revision_id));
    $published_revision = $published_revisions[$entity_id];

    $published_revision->original = $entity;
    // Retain this to make sure we set the published flag on presave.
    $published_revision->published_revision_id = $entity->published_revision_id;

    // _node_save_revision() will completely overwrite the UID of the revision
    // which we absolutely do no want in this context. So we store that
    // UID and restore it after the node_save.
    $uid = $published_revision->revision_uid;

    node_save($published_revision);
    if ($uid != $GLOBALS['user']->uid) {
      db_update('node_revision')
        ->fields(array('uid' => $uid))
        ->condition('vid', $entity->published_revision_id)
        ->execute();
    }

    db_update('ers_entity_state')
      ->fields(array('published_revision_id' => $entity->published_revision_id))
      ->condition('entity_type', $this->entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();

    $entity = $published_revision;
    $this->ers_revision_reset = FALSE;
  }

  /**
   * Implements a delegated hook_form_alter.
   */
  public function hook_form_alter(&$form, &$form_state, $form_id) {
    if (!empty($form['#node_edit_form'])) {
      $node = $form_state['node'];
      if (empty($this->plugin['bundles'][$node->type])) {
        return;
      }

      // Make sure to include this file when form is cached.
      $form_state['build_info']['files'][] = $this->plugin['path'] . '/' . $this->plugin['handler'] . '.class.php';

      // Set "published" checkbox to value, so it get hidden. And set it's
      // value to FALSE as default.
      $form['options']['status']['#type'] = 'value';
      $form['options']['status']['#value'] = FALSE;

      if (!empty($node->nid)) {
        // Reset the revision field.
        if ($node->published_revision_id == $node->draft_revision_id) {
          $form['revision_information']['revision']['#default_value'] = TRUE;
          $form['revision_information']['revision']['#description'] = t('Creating a new revision will create a draft revision. This revision will not be published until it is published from the revisions tab. Creating a new revision will create a new draft revision only.');
          $form['revision_information']['revision']['#disabled'] = TRUE;
        }
        else {
          $form['revision_information']['revision']['#default_value'] = FALSE;
          $form['revision_information']['revision']['#description'] = t('You are currently editing the draft revision. This draft will not be published until it is published from the revisions tab.');
        }
        // @todo: maybe we should get default value from revision being edited?
        $publish_default = variable_get('ers_publish_draft_' . $node->type, TRUE);
      }
      else {
        $publish_default = variable_get('ers_publish_new_' . $node->type, TRUE);
      }

      $form['ers'] = array(
        '#type' => 'fieldset',
        '#title' => t('Scheduling'),
        '#access' => ((user_access("publish node $node->type content") || user_access('administer nodes'))),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#group' => 'additional_settings',
      );
      $form['ers'] += ers_entity_schedule_form('node', $node);
      // Set default values.
      $form['ers']['ers_schedule_add']['#default_value'] = $publish_default;
      $form['ers']['ers']['ers_schedule_selector']['#default_value'] = $publish_default ? 'publish' : NULL;

      $form['#submit'][] = 'ers_node_form_submit';
    }
    elseif ($form_id == 'node_type_form') {
      $type = $form['#node_type'];
      $form['ers'] = array(
        '#type' => 'fieldset',
        '#title' => t('Scheduling options'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#group' => 'additional_settings',
      );
      $form['ers']['ers_publish_new'] = array(
        '#type' => 'checkbox',
        '#title' => t('Publish'),
        '#description' => t('Set new nodes to be published by default.'),
        '#default_value' => variable_get('ers_publish_new_' . $type->type, TRUE),
      );
      $form['ers']['ers_publish_draft'] = array(
        '#type' => 'checkbox',
        '#title' => t('Publish draft'),
        '#description' => t('Set new drafts to be published by default.'),
        '#default_value' => variable_get('ers_publish_draft_' . $type->type, TRUE),
      );
      // Remove default 'Published' checkbox.
      unset($form['workflow']['node_options']['#options']['status']);
    }
  }

  /**
   * Provide a page for node revisions.
   *
   * This page takes over the normal node revision page, and provides a UI
   * in with which we can schedule revisions to be published or set an
   * alternate revision to be draft.
   */
  public function page_revisions($js, $input, $node) {
    // Ensure ERS is enabled for this node, otherwise, call the default node_revision_overview
    $ers_nodes = variable_get('ers_entity_bundle_node', array());
    if (!in_array($node->type, $ers_nodes)) {
      module_load_include('inc', 'node', 'node.pages');
      return node_revision_overview($node);
    }
    $schedules = $this->get_schedule($node);
    drupal_set_title(t('Revisions for %title', array('%title' => $node->title)), PASS_THROUGH);

    $header = array(t('Revision'), t('Schedule'), t('Operations'));

    $revisions = node_revision_list($node);

    $rows = array();

    $publish_permission = FALSE;
    if ((user_access("publish node $node->type content") || user_access('administer nodes')) && node_access('update', $node)) {
      $publish_permission = TRUE;
    }

    $delete_permission = FALSE;
    if ((user_access('delete revisions') || user_access('administer nodes')) && node_access('delete', $node)) {
      $delete_permission = TRUE;
    }

    foreach ($revisions as $revision) {
      $row = array();
      $operations = array(
        'data' => array(),
      );
      if ($revision->current_vid > 0 && !empty($node->status)) {
        $operations['class'] = array('revision-current');
        $row[] = array('data' => t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'short'), "node/$node->nid"), '!username' => theme('username', array('account' => $revision))))
                                 . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : ''),
                       'class' => array('revision-current'));
        $operations['data'][] = drupal_placeholder(t('published revision'));
        if ($publish_permission) {
          $operations['data'][] = l(t('unpublish'), "node/$node->nid/revisions/$revision->vid/unpublish", array('query' => drupal_get_destination()));
        }
        $schedule = array('data' => array(), 'class' => 'revision-current');
      }
      else {
        $row[] = t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'short'), "node/$node->nid/revisions/$revision->vid/view"), '!username' => theme('username', array('account' => $revision))))
                 . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : '');
        if ($publish_permission) {
          $operations['data'][] = l(t('publish'), "node/$node->nid/revisions/$revision->vid/publish", array('query' => drupal_get_destination()));
        }
        if ($publish_permission && $node->draft_revision_id != $revision->vid) {
          $operations['data'][] = l(t('set draft'), "node/$node->nid/revisions/$revision->vid/set-draft", array('query' => array('token' => drupal_get_token($node->nid)) + drupal_get_destination()));
        }
        else if ($node->draft_revision_id == $revision->vid) {
          $operations['data'][] = '<strong>' . t('draft') . '</strong>';
        }

        // Only show up delete operation if there are more then one revision.
        if (count($revisions) > 1 && $delete_permission) {
          $operations['data'][] = l(t('delete'), "node/$node->nid/revisions/$revision->vid/delete");
        }
        $schedule = array('data' => array());
      }

      if (!empty($schedules[$revision->vid])) {
        $date = format_date($schedules[$revision->vid]->publish_date, 'short');
        if ($schedules[$revision->vid]->completed) {
          $schedule['data'][] = t('Publish: %date (completed)', array('%date' => $date));
        }
        else {
          $schedule['data'][] = t('Publish: %date (waiting)', array('%date' => $date));
        }
      }
      else {
        $schedule['data'][] = t('Publish: no schedule');
      }

      // Add info about unpublishing schedule.
      if (!empty($schedules[0]) && $revision->current_vid > 0 && !empty($node->status)) {
        $date = format_date($schedules[0]->publish_date, 'short');
        if ($schedules[0]->completed) {
          $schedule['data'][] = t('Unpublish: %date (completed)', array('%date' => $date));
        }
        else {
          $schedule['data'][] = t('Unpublish: %date (waiting)', array('%date' => $date));
        }
      }

      $schedule['data'] = implode('<br />', $schedule['data']);
      $row[] = $schedule;

      $operations['data'] = implode(' &nbsp; ', $operations['data']);
      $row[] = $operations;
      $rows[] = $row;
    }

    $build['node_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $build;
  }

  /**
   * Determine if the current user has access to publish the entity.
   *
   * This is called indirectly via ers_entity_plugin_access_switcher which
   * is a menu access callback.
   *
   * Access callback for "Revisions tab". This is a stripped down version of
   * _node_revision_access() to handle only 'view' operation and to allow it to
   * show up when there's only one revision.
   */
  public function access_revisions($node) {
    $access = &drupal_static(__FUNCTION__, array());

    if (!isset($access[$node->vid])) {
      if ((!user_access('view revisions') && !user_access('administer nodes'))) {
        $access[$node->vid] = FALSE;
        return FALSE;
      }

      $node_current_revision = node_load($node->nid);
      $is_current_revision = $node_current_revision->vid == $node->vid;

      if (user_access('administer nodes')) {
        $access[$node->vid] = TRUE;
      }
      else {
        // First check the access to the current revision and finally, if the
        // node passed in is not the current revision then access to that, too.
        $access[$node->vid] = node_access('view', $node_current_revision) && ($is_current_revision || node_access('view', $node));
      }
    }

    return $access[$node->vid];
  }

  /**
   * Implements a delegated hook_panels_pane_content_alter()
   *
   * This exists primarily to add some extra contextual items to give more
   * Panels access to the scheduling information.
   */
  public function hook_panels_pane_content_alter(&$content, $pane, $args, $context) {
    parent::hook_panels_pane_content_alter($content, $pane, $args, $context);

    // In addition to generic stuff provided by the parent, we support the
    // following specific panes.
    if ($pane->type == 'node_title' || $pane->type == 'node_content') {
      // Extract the entity from the context.
      $plugin = ctools_get_content_type($pane->type);
      $pane_context = ctools_content_select_context($plugin, $pane->subtype, $pane->configuration, $context);

      $entity = $pane_context->data;
      list($entity_id, $revision_vid, $bundle) = entity_extract_ids($this->entity_type, $entity);

      // Don't add this stuff on the node page since it's there as tabs.
      if ($pane->type == 'node_content' && node_is_page($entity)) {
        return;
      }

      if (!empty($this->plugin['bundles'][$bundle])) {
        $this->add_pane_contextual_links($content, $entity);
      }
    }

    if ($pane->type == 'node') {
      $nid = $pane->configuration['nid'];
      if (!is_numeric($nid)) {
        return;
      }

      $entity = node_load($pane->configuration['nid']);
      if (!empty($this->plugin['bundles'][$entity->type])) {
        $this->add_pane_contextual_links($content, $entity);
      }
    }
  }

  /**
   * Implements a delegated hook_entity_uuid_load().
   */
  public function hook_entity_uuid_load(&$entities, $entity_type) {
    if ($entity_type == 'node') {
      foreach ($entities as $key => &$node) {
        $vids = array_keys(node_revision_list($node));
        $vuuids = entity_get_uuid_by_id('node', $vids, TRUE);

        // Store these in separate properties as we might not have a local vid
        // to rewrite them to before we save at the other end.
        if (isset($node->published_revision_id) && isset($vuuids[$node->published_revision_id])) {
          $node->published_revision_vuuid = $vuuids[$node->published_revision_id];
        }
        if (isset($node->draft_revision_id) && isset($vuuids[$node->draft_revision_id])) {
          $node->draft_revision_vuuid = $vuuids[$node->draft_revision_id];
        }

        if (!empty($node->ers_schedule)) {
          foreach ($node->ers_schedule as $vid => &$item) {
            if (is_numeric($item->revision_id)) {
              $item->revision_id = $vuuids[$item->revision_id];
            }
          }
          unset($item);
        }

        $revisions = array();
        foreach ($vuuids as $vid => $vuuid) {
          // entity_uuid_load() doesn't do revisions :(
          $revision = array($node->nid => clone node_load($node->nid, $vid));
          $hook = 'entity_uuid_load';
          foreach (module_implements($hook) as $module) {
            // Prevent infinite recursion.
            if ($module != 'ers') {
              $function = $module . '_' . $hook;
              $function($revision, $entity_type);
            }
          }
          $revisions[$vuuid] = $revision[$node->nid];
        }
        $node->ers_deploy_revisions = $revisions;
      }
      unset($node);
    }
  }

  /**
   * Implements a delegated hook_entity_uuid_save().
   */
  public function hook_entity_uuid_save(&$entity, $entity_type) {
    if ($entity_type == 'node' && !empty($entity->ers_deploy_revisions)) {
      $ids = entity_get_id_by_uuid($entity_type, array_keys($entity->ers_deploy_revisions), TRUE);
      foreach ($entity->ers_deploy_revisions as $vuuid => $revision) {
        $revision = (object)$revision;
        $timestamp = $revision->revision_timestamp;
        $uid = $revision->revision_uid;
        if ($uid != '1') {
          $uid = reset(entity_get_id_by_uuid('user', array($uid)));
        }
        if (isset($ids[$vuuid]) && ($vid = $ids[$vuuid]) && node_load($entity->nid, $vid)) {
          // The same revision already exists on the target site.
          // Make the target revision current.
          db_update('node')
            ->fields(array(
              'vid' => $vid,
            ))
            ->condition('nid', $entity->nid)
            ->execute();
          $revision->vid = $vid;
          $revision->revision = FALSE;
        }
        else {
          // This revision doesn't yet exist on the target site, so we need to
          // create a new one.
          $revision->revision = TRUE;
        }
        entity_uuid_save('node', $revision);
        // This may have generated a new uid, vuuid and timestamp as if we
        // had saved this revision from the UI.  So we need to update these
        // back to the correct original values.
        $fields = array(
          'vuuid' => $vuuid,
          'timestamp' => $timestamp,
        );
        if (is_numeric($uid)) {
          $fields['uid'] = $uid;
        }
        db_update('node_revision')
          ->fields($fields)
          ->condition('vid', $revision->vid)
          ->execute();
      }
      // Only now that all possibly needed revisions have been created can we
      // properly transform the published, draft and scheduled revision ids.
      $vuuids = array($entity->published_revision_vuuid => 1, $entity->draft_revision_vuuid => 1);
      foreach ($entity->ers_schedule as $item) {
        $vuuids[$item['revision_id']] = 1;
      }
      if (array_filter(array_keys(array_diff_key($vuuids, $ids)))) {
        // Some of the ids we need were not loaded yet.
        $ids = entity_get_id_by_uuid('node', array_filter(array_keys($vuuids)), TRUE);
      }
      $entity->published_revision_id = $ids[$entity->published_revision_vuuid];
      $entity->draft_revision_id = $ids[$entity->draft_revision_vuuid];
      $this->set_published_revision_current($entity->nid, $entity);
      db_update('ers_entity_state')
        ->fields(array('draft_revision_id' => $entity->draft_revision_id))
        ->condition('entity_type', $this->entity_type)
        ->condition('entity_id', $entity->nid)
        ->execute();

      // Save the scheduling information, if any.
      db_delete('ers_schedule')
        ->condition('entity_type', $this->entity_type)
        ->condition('entity_id', $entity->nid)
        ->condition('completed', 0)
        ->execute();
      foreach ($entity->ers_schedule as $item) {
        $item['revision_id'] = $ids[$item['revision_id']];
        $item['entity_id'] = $entity->nid;
        unset($item['schedule_id']);
        db_insert('ers_schedule')
          ->fields($item)
          ->execute();
      }
      drupal_static_reset('ers_set_entity_saved');
    }
  }

  /**
   * Implements a delegated hook_entity_uuid_presave().
   */
  function hook_entity_uuid_presave(&$entity, $entity_type) {
    if ($entity_type == 'node' && isset($entity->published_revision_id)) {
      // Temporarily reset these to known sensible values for this site.
      unset($entity->published_revision_id);
      unset($entity->draft_revision_id);
      if ($nid = reset(entity_get_id_by_uuid('node', array($entity->uuid)))) {
        $existing = db_query("SELECT published_revision_id, draft_revision_id FROM {ers_entity_state} WHERE entity_type = :type AND entity_id = :nid", array(
          ':type' => 'node',
          ':nid' => $nid,
        ))->fetch();
        $entity->published_revision_id = $existing->published_revision_id;
        $entity->draft_revision_id = $existing->published_revision_id;
      }
    }
  }

  /**
   * Implements a delegated hook_deploy_entity_dependencies().
   */
  public function hook_deploy_entity_dependencies($entity, $entity_type) {
    if ($entity_type == 'node') {
      $dependencies = array();
      foreach (node_revision_list($entity) as $revision) {
        deploy_add_dependencies($dependencies, $revision, 'user', 'uid');
      }
      return $dependencies;
    }
  }


  /**
   * Add contextual links to relevant panes to get to scheduling information.
   */
  public function add_pane_contextual_links($content, $entity) {
    list($entity_id, $revision_vid, $bundle) = entity_extract_ids($this->entity_type, $entity);
    if ((user_access("publish node $entity->type content") && (user_access('administer nodes')) || user_access('view revisions'))) {
      $content->admin_links[] = array(
        'title' => t('View revisions'),
        'alt' => t("View a list of revisions for this entity and associated schedule."),
        'href' => "node/$entity_id/revisions",
        'query' => drupal_get_destination(),
      );
    }
  }
}

function ers_node_form_submit($form, &$form_state) {
  ers_entity_schedule_form_submit($form['ers'], $form_state, 'node', $form_state['node']);
}
