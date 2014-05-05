<?php

class ERSEntityFieldablePanelsPane extends ERSEntityDefault {
  public function hook_menu_alter(&$items) {
    // Provide a nicer title to remind the user that this will edit drafts.
    $items['admin/structure/panels/entity/view/%fieldable_panels_panes/edit']['title'] = 'Edit';
    $items['admin/structure/panels/entity/view/%fieldable_panels_panes/edit']['title callback'] = 'ers_entity_edit_title_callback';
    $items['admin/structure/panels/entity/view/%fieldable_panels_panes/edit']['title arguments'] = array('fieldable_panels_pane', 5);


    // Completely take over the revisions page because we're adding a lot to it.
    $items['admin/structure/panels/entity/view/%fieldable_panels_panes/revision'] = array(
      'title' => 'Revisions',
      'page callback' => 'ers_entity_plugin_switcher_page',
      'page arguments' => array('fieldable_panels_pane', 'revisions', 5),
      'access callback' => 'fieldable_panels_panes_access',
      'access arguments' => array('delete', 5),
      'weight' => -7,
      'type' => MENU_LOCAL_TASK,
    );
  }

  public function set_published_revision_current($entity_id, $entity) {
    $this->fix_revision_cache($entity_id, $entity);
    $this->ers_revision_reset = TRUE;

    $entity_info = entity_get_info($this->entity_type);
    $revision_key = $entity_info['entity keys']['revision'];
    $published_revisions = entity_load($this->entity_type, array($entity_id), array($revision_key => $entity->published_revision_id));
    $published_revision = $published_revisions[$entity_id];

    $published_revision->original = $entity;

    // The revision save will completely overwrite the UID of the revision
    // which we absolutely do no want in this context. So we store that
    // UID and restore it after the node_save.
    $uid = $published_revision->uid;
    fieldable_panels_panes_save($published_revision);
    if ($uid != $GLOBALS['user']->uid) {
      db_update('fieldable_panels_panes_revision')
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
    if ($form_id == 'fieldable_panels_panes_entity_edit_form' || $form_id == 'fieldable_panels_panes_fieldable_panels_pane_content_type_edit_form') {
      $entity = $form_state['entity'];
      list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

      if (empty($this->plugin['bundles'][$bundle])) {
        return;
      }

      if (empty($entity->fpid)) {
        return;
      }

      // Reset the revision field.
      else if ($entity->published_revision_id == $entity->draft_revision_id) {
        $form['revision']['revision']['#default_value'] = TRUE;
        $form['revision']['revision']['#description'] = t('Creating a new revision will create a draft revision. This revision will not be published until it is published from the revisions tab. Creating a new revision will create a new draft revision only.');
        $form['revision_information']['revision']['#disabled'] = TRUE;
      }
      else {
        // Overried the default behavior on this because unlike nodes, fieldable panel
        // panes let you "edit" prior revisions but requires creating a new revision.
        // Our games let you actually edit the prior revision.
        $form['revision']['revision']['#disabled'] = FALSE;
        unset($form['revision']['revision']['#value']);
        $form['revision']['revision']['#default_value'] = FALSE;
        $form['revision']['revision']['#description'] = t('You are currently editing the draft revision. This draft will not be published until it is published from the revisions tab.');

        // Let them edit any revision without changing the draft revision.
        if ($entity->draft_revision_id != $revision_id) {
          $entity->ers_retain_draft = TRUE;
        }
      }

      // Provide a scheduling widget to schedule this draft.
      $form['revision'] += ers_entity_schedule_form($this->entity_type, $entity);

      array_unshift($form['#submit'], 'ers_fieldable_panels_pane_form_submit');
    }
  }

  /**
   * Provide a page for entity revisions.
   *
   * This page takes over the normal entity revision page, and provides a UI
   * in with which we can schedule revisions to be published or set an
   * alternate revision to be draft.
   */
  public function page_revisions($js, $input, $entity) {
    $schedules = $this->get_schedule($entity);

    $header = array(t('Revision'), t('Schedule'), t('Operations'));

    $revisions = array();
    $revisions = db_query('SELECT r.vid, r.title, r.log, r.uid, p.vid AS current_vid, r.timestamp, u.name FROM {fieldable_panels_panes_revision} r LEFT JOIN {fieldable_panels_panes} p ON p.vid = r.vid INNER JOIN {users} u ON u.uid = r.uid WHERE r.fpid = :fpid ORDER BY r.vid DESC', array(':fpid' => $entity->fpid))->fetchAllAssoc('vid');

    $rows = array();

    $publish_permission = FALSE;
    if ((user_access("publish fieldable_panels_pane $entity->bundle content") || user_access('administer fieldable panels panes')) && fieldable_panels_panes_access('update', $entity)) {
      $publish_permission = TRUE;
    }

    $update_permission = FALSE;
    if (fieldable_panels_panes_access('update', $entity)) {
      $update_permission = TRUE;
    }

    $delete_permission = FALSE;
    if ((user_access('delete revisions') || user_access('administer fieldable panels panes')) && fieldable_panels_panes_access('delete', $entity)) {
      $delete_permission = TRUE;
    }

    foreach ($revisions as $revision) {
      $row = array();
      $operations = array();

      if ($revision->current_vid > 0) {
        $row[] = array('data' => t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'short'), "admin/structure/panels/entity/view/$entity->fpid"), '!username' => theme('username', array('account' => $revision))))
                                 . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : ''),
                       'class' => array('revision-current'));
        $operations = array('data' => drupal_placeholder(t('published revision')), 'class' => array('revision-current'));
        $schedule = array('data' => '', 'class' => 'revision-current');
      }
      else {
        $operations = array(
          'data' => ''
        );
        $row[] = t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'short'), "admin/structure/panels/entity/view/$entity->fpid/revision/$revision->vid/view"), '!username' => theme('username', array('account' => $revision))))
                 . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : '');
        if ($publish_permission) {
          $operations['data'][] = l(t('publish'), "admin/structure/panels/entity/view/$entity->fpid/revision/$revision->vid/publish", array('query' => drupal_get_destination()));
        }
        if ($publish_permission && $entity->draft_revision_id != $revision->vid) {
          $operations['data'][] = l(t('set draft'), "admin/structure/panels/entity/view/$entity->fpid/revision/$revision->vid/set-draft", array('query' => array('token' => drupal_get_token($entity->fpid)) + drupal_get_destination()));
        }
        else if ($entity->draft_revision_id == $revision->vid && $publish_permission) {
          $operations['data'][] = '<strong>' . t('draft') . '</strong>';
        }

        if ($update_permission) {
          $operations['data'][] = l(t('edit'), "admin/structure/panels/entity/view/$entity->fpid/revision/$revision->vid/edit");
        }

        if ($delete_permission) {
          $operations['data'][] = l(t('delete'), "admin/structure/panels/entity/view/$entity->fpid/revision/$revision->vid/delete");
        }

        $schedule = array('data' => '');
      }

      if (!empty($schedules[$revision->vid])) {
        $date = format_date($schedules[$revision->vid]->publish_date, 'short');
        if ($schedules[$revision->vid]->completed) {
          $schedule['data'] = t('%date (completed)', array('%date' => $date));
        }
        else {
          $schedule['data'] = t('%date (waiting)', array('%date' => $date));
        }
      }
      else {
        $schedule['data'] = t('no schedule');
      }
      $row[] = $schedule;

      if (is_array($operations['data'])) {
        $operations['data'] = implode(' &nbsp; ', $operations['data']);
      }

      $row[] = $operations;
      $rows[] = $row;
    }

    $build['entity_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $build;
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
    if ($pane->type == 'fieldable_panels_pane') {
      $entity = fieldable_panels_panes_load_entity($pane->subtype);
      list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
      if (!empty($this->plugin['bundles'][$bundle])) {
        $this->add_pane_contextual_links($content, $entity);
      }
    }

  }

  /**
   * Implements a delegated hook_entity_uuid_load().
   */
  public function hook_entity_uuid_load(&$entities, $entity_type) {
    if ($entity_type == 'fieldable_panels_pane') {
      foreach ($entities as $key => &$entity) {
        // get the vuuids of this pane
        $entity_revisions = views_get_view_result('fieldable_pane_entity_revisions', 'default', $entity->fpid);
        foreach ($entity_revisions as $revision) {
          $vids[] = $revision->vid;
        }
        $vuuids = entity_get_uuid_by_id('fieldable_panels_pane', $vids, TRUE);

        // Store these in separate properties as we might not have a local vid
        // to rewrite them to before we save at the other end.
        if (isset($entity->published_revision_id) && isset($vuuids[$entity->published_revision_id])) {
          $entity->published_revision_vuuid = $vuuids[$entity->published_revision_id];
        }
        if (isset($entity->draft_revision_id) && isset($vuuids[$entity->draft_revision_id])) {
          $entity->draft_revision_vuuid = $vuuids[$entity->draft_revision_id];
        }

        foreach ($entity->ers_schedule as $vid => &$item) {
          if (is_numeric($item->revision_id)) {
            $item->revision_id = $vuuids[$item->revision_id];
          }
        }
        unset($item);

        $revisions = array();
        foreach ($vuuids as $vid => $vuuid) {
          // entity_uuid_load() doesn't do revisions :(
          $revision = array($entity->fpid => clone fieldable_panels_panes_load($entity->fpid, $vid));
          $hook = 'entity_uuid_load';
          foreach (module_implements($hook) as $module) {
            // Prevent infinite recursion.
            if ($module != 'ers') {
              $function = $module . '_' . $hook;
              $function($revision, $entity_type);
            }
          }
          $revisions[$vuuid] = $revision[$entity->fpid];
        }
        $entity->ers_deploy_revisions = $revisions;
      }
      unset($entity);
    }
  }

  /**
   * Implements a delegated hook_entity_uuid_save().
   */
  public function hook_entity_uuid_save(&$entity, $entity_type) {
    if ($entity_type == 'fieldable_panels_pane' && !empty($entity->ers_deploy_revisions)) {
      $ids = entity_get_id_by_uuid($entity_type, array_keys($entity->ers_deploy_revisions), TRUE);
      foreach ($entity->ers_deploy_revisions as $vuuid => $revision) {
        $revision = (object)$revision;
        $timestamp = $revision->timestamp;
        $uid = $revision->uid;
        if ($uid != '1') {
          $uid = reset(entity_get_id_by_uuid('user', array($uid)));
        }
        if (isset($ids[$vuuid]) && ($vid = $ids[$vuuid]) && fieldable_panels_panes_load($entity->fpid, $vid)) {
          // The same revision already exists on the target site.
          // Make the target revision current.
          db_update('fieldable_panels_panes')
            ->fields(array(
              'vid' => $vid,
            ))
            ->condition('fpid', $entity->fpid)
            ->execute();
          $revision->vid = $vid;
          $revision->revision = FALSE;
        }
        else {
          // This revision doesn't yet exist on the target site, so we need to
          // create a new one.
          $revision->revision = TRUE;
        }
        entity_uuid_save('fieldable_panels_pane', $revision);
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
        db_update('fieldable_panels_panes_revision')
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
        $ids = entity_get_id_by_uuid($entity_type, array_filter(array_keys($vuuids)), TRUE);
      }
      $entity->published_revision_id = $ids[$entity->published_revision_vuuid];
      $entity->draft_revision_id = $ids[$entity->draft_revision_vuuid];
      $this->set_published_revision_current($entity->fpid, $entity);

      db_merge('ers_entity_state')
        ->key(array(
          'entity_type' => $this->entity_type,
          'entity_id' => $entity->fpid,
        ))
        ->insertFields(array(
          'entity_type' => $this->entity_type,
          'entity_id' => $entity->fpid,
          'draft_revision_id' => $entity->draft_revision_id,
          'published_revision_id' => $entity->published_revision_id,
        ))
        ->updateFields(array('draft_revision_id' => $entity->draft_revision_id))
        ->execute();

      // Save the scheduling information, if any.
      db_delete('ers_schedule')
        ->condition('entity_type', $this->entity_type)
        ->condition('entity_id', $entity->fpid)
        ->condition('completed', 0)
        ->execute();
      foreach ($entity->ers_schedule as $item) {
        $item['revision_id'] = $ids[$item['revision_id']];
        $item['entity_id'] = $entity->fpid;
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
    if ($entity_type == 'fieldable_panels_pane' && isset($entity->published_revision_id)) {
      // Temporarily reset these to known sensible values for this site.
      unset($entity->published_revision_id);
      unset($entity->draft_revision_id);
      if ($id = reset(entity_get_id_by_uuid($entity_type, array($entity->uuid)))) {
        if ($existing = db_query("SELECT published_revision_id, draft_revision_id FROM {ers_entity_state} WHERE entity_type = :type AND entity_id = :id", array(
          ':type' => $entity_type,
          ':id' => $id,
        ))->fetch()) {
          $entity->published_revision_id = $existing->published_revision_id;
          $entity->draft_revision_id = $existing->published_revision_id;
        }
      }
    }
  }

  /**
   * Implements a delegated hook_deploy_entity_dependencies().
   */
  public function hook_deploy_entity_dependencies($entity, $entity_type) {
    if ($entity_type == 'fieldable_panels_pane') {
      $dependencies = array();

      foreach (views_get_view_result('fieldable_pane_entity_revisions', 'default', $entity->fpid) as $result) {
        $revision = fieldable_panels_panes_load($entity->fpid, $result->vid);
        deploy_add_dependencies($dependencies, $revision, 'user', 'uid');
      }
      return $dependencies;
    }
  }

  /**
   * Add contextual links to relevant panes to get to scheduling information.
   */
  public function add_pane_contextual_links($content, $entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
    if ((user_access("publish fieldable_panels_pane $entity->bundle content") && (user_access('administer fieldable panels panes')) || fieldable_panels_panes_access('delete', $entity))) {
      $content->admin_links[] = array(
        'title' => t('View revisions'),
        'alt' => t("View a list of revisions for this entity and associated schedule."),
        'href' => "admin/structure/panels/entity/view/$entity_id/revision",
        'query' => drupal_get_destination(),
      );
    }
  }
}

function ers_fieldable_panels_pane_form_submit($form, &$form_state) {
  ers_entity_schedule_form_submit($form['revision'], $form_state, 'fieldable_panels_pane', $form_state['entity']);
}
