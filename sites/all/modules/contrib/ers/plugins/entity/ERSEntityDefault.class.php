<?php
/**
 * @file
 * Base class for the ERS Entity plugin.
 */

/**
 * Interface to describe how ERSEntity plugin objects are implemented.
 */
interface ERSEntityInterface {
  /**
   * Initialize the plugin object.
   */
  public function init($plugin);

  // Public Drupal hooks
  public function hook_menu(&$items);
  public function hook_menu_alter(&$items);
  public function hook_form_alter(&$form, &$form_state, $form_id);
  public function hook_permission(&$items);

  // Entity specific Drupal hooks
  public function hook_entity_load(&$entities);
  public function hook_entity_presave($entity);
  public function hook_entity_insert($entity);
  public function hook_entity_update($entity);
  public function hook_entity_delete($entity);
  public function hook_field_attach_delete_revision($entity);

  // Panels and CTools hooks
  public function hook_panels_pane_content_alter(&$content, $pane, $args, $context);

  // Deploy and UUID hooks
  public function hook_entity_uuid_load(&$entities, $entity_type);
  public function hook_entity_uuid_save(&$entity, $entity_type);
  public function hook_entity_uuid_presave(&$entity, $entity_type);
  public function hook_deploy_entity_dependencies($entity, $entity_type);


  /**
   * Add contextual links to a panel pane.
   */
  public function add_pane_contextual_links($content, $entity);

  // General API

  /**
   * Add or update an new entity schedule record.
   */
  public function update_entity_schedule($entity, $publish_date, $revision_id);

  /**
   * Update the published and draft revision IDs for the entity.
   */
  public function update_entity_state($entity);

  /**
   * Set the published revision current for the entity.
   */
  public function set_published_revision_current($entity_id, $entity);

  /**
   * Unpublish entities that support publishing flag.
   */
  public function unpublish($entity_id, $entity);

  /**
   * Get a full schedule for the given entity.
   */
  public function get_schedule($entity);

  /**
   * Execute the schedule.
   *
   * This will change the published revision and mark the schedule complete.
   */
  public function execute_schedule($schedule);

  /**
   * Add entity specific form to the ERS settings form.
   *
   * This is primarily to allow bundle selection per entity type.
   */
  public function settings_form(&$form, &$form_state);

  /**
   * Validate entity specific settings on the ERS settings form.
   */
  public function settings_form_validate(&$form, &$form_state);

  /**
   * Submit entity specific settings on the ERS settings form.
   */
  public function settings_form_submit(&$form, &$form_state);
}

/**
 * Base class for the ERS Entity plugin.
 */
class ERSEntityDefault implements ERSEntityInterface {
  /**
   * The plugin metadata.
   */
  public $plugin = NULL;

  /**
   * The entity type the plugin is for. This is from the $plugin array.
   */
  public $entity_type = '';

  /**
   * Whether the entity supports published/unpublished state.
   */
  public $supports_publishing_flag = FALSE;

  /**
   * Initialize the plugin and store the plugin info.
   */
  function init($plugin) {
    $this->plugin = $plugin;
    $this->entity_type = $plugin['name'];
  }

  /**
   * Implements a delegated hook_permission.
   */
  public function hook_permission(&$items) {
    $entity_info = entity_get_info($this->entity_type);
    // Make a permission for each bundle we control.
    foreach (array_filter($this->plugin['bundles']) as $bundle) {
      if (empty($entity_info['bundles'][$bundle])) {
        continue;
      }

      $items["publish $this->entity_type $bundle content"] = array(
        'title' => t('%entity_name %bundle_name: Publish/schedule revisions', array(
          '%entity_name' => $entity_info['label'],
          '%bundle_name' => $entity_info['bundles'][$bundle]['label'],
        )),
      );
    }
  }

  /**
   * Implements a delegated hook_menu.
   */
  public function hook_menu(&$items) {
    if (!empty($this->plugin['entity path'])) {
      // Figure out where in the path the entity will be.
      $bits = explode('/', $this->plugin['entity path']);
      foreach ($bits as $count => $bit) {
        if (strpos($bit, '%') === 0) {
          $position = $count;
          break;
        }
      }

      if (!isset($position)) {
        return;
      }

      $total = count($bits);

      // Add items for publish and set-draft
      $items[$this->plugin['revision path'] . '/%/set-draft'] = array(
        'title' => 'Set draft',
        'page callback' => 'ers_entity_plugin_switcher_page',
        'page arguments' => array($this->entity_type, 'set_draft', $position, $count + 2),
        'access callback' => 'ers_entity_plugin_access_switcher',
        'access arguments' => array($this->entity_type, 'publish', $position, $count + 2),
        'type' => MENU_CALLBACK,
        'load arguments' => array(),
      );

      // Add items for publish and set-draft
      $items[$this->plugin['revision path'] . '/%/publish'] = array(
        'title' => 'Publish revision',
        'page callback' => 'ers_entity_plugin_switcher_page',
        'page arguments' => array($this->entity_type, 'publish', $position, $count + 2),
        'access callback' => 'ers_entity_plugin_access_switcher',
        'access arguments' => array($this->entity_type, 'publish', $position, $count + 2),
        'type' => MENU_CALLBACK,
        'load arguments' => array(),
      );

      // Add items for unpublish.
      if ($this->supports_publishing_flag) {
        $items[$this->plugin['revision path'] . '/%/unpublish'] = array(
          'title' => 'Publish revision',
          'page callback' => 'ers_entity_plugin_switcher_page',
          'page arguments' => array($this->entity_type, 'unpublish', $position, $count + 2),
          'access callback' => 'ers_entity_plugin_access_switcher',
          'access arguments' => array($this->entity_type, 'unpublish', $position, $count + 2),
          'type' => MENU_CALLBACK,
          'load arguments' => array(),
        );
      }

    }
  }

  /**
   * Implements a delegated hook_menu_alter.
   */
  public function hook_menu_alter(&$items) {

  }

  /**
   * Implements a delegated hook_menu_alter.
   */
  public function hook_form_alter(&$form, &$form_state, $form_id) {

  }

  /**
   * Implements a delegated hook_panels_pane_content_alter()
   *
   * This exists primarily to add some extra contextual items to give more
   * Panels access to the scheduling information.
   */
  public function hook_panels_pane_content_alter(&$content, $pane, $args, $context) {
    if ($pane->type == 'entity_field') {
      list($entity_type, $field_name) = explode(':', $pane->subtype);
      if ($this->entity_type != $entity_type) {
        return;
      }

      // Extract the entity from the context.
      $plugin = ctools_get_content_type($pane->type);
      $pane_context = ctools_content_select_context($plugin, $pane->subtype, $pane->configuration, $context);

      $entity = $pane_context->data;
      list($entity_id, $revision_vid, $bundle) = entity_extract_ids($entity_type, $entity);

      if (!empty($this->plugin['bundles'][$bundle])) {
        // This simply makes it easier for the entity specific stuff to not have to
        // do all the above stuff.
        $this->add_pane_contextual_links($content, $entity);
      }
    }
  }

  /**
   * Implements a delegated hook_entity_uuid_load().
   */
  public function hook_entity_uuid_load(&$entities, $entity_type) { }

  /**
   * Implements a delegated hook_entity_uuid_save().
   */
  public function hook_entity_uuid_save(&$entity, $entity_type) { }

  /**
   * Implements a delegated hook_entity_uuid_presave().
   */
  public function hook_entity_uuid_presave(&$entity, $entity_type) { }

  /**
   * Implements a delegated hook_deploy_entity_dependencies().
   */
  public function hook_deploy_entity_dependencies($entity, $entity_type) { }

  public function add_pane_contextual_links($content, $entity) { }

  // Entity specific Drupal hooks
  public function hook_entity_load(&$entities) {
    if (!empty($this->ers_revision_reset)) {
      return;
    }

    // Fetch entity state information.
    $keys = array_keys($entities);
    $states = db_query("SELECT * FROM {ers_entity_state} WHERE entity_type = '" . $this->entity_type . "' AND entity_id IN (:ids)", array(':ids' => $keys))->fetchAllAssoc('entity_id');

    // Fetch entity schedule information.
    $result = db_query("SELECT * FROM {ers_schedule} WHERE entity_type = '" . $this->entity_type . "' AND entity_id IN (:ids) AND completed = 0", array(':ids' => $keys));

    // Sort them by entity id.
    while ($schedule = $result->fetchObject()) {
      $schedules[$schedule->entity_id][$schedule->revision_id] = $schedule;
    }

    $entity_info = entity_get_info($this->entity_type);
    $revision_key = $entity_info['entity keys']['revision'];

    foreach ($entities as $entity_id => $entity) {
      list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
      if (empty($this->plugin['bundles'][$bundle])) {
        continue;
      }

      if (!empty($states[$entity_id])) {
        $entity->published_revision_id = $states[$entity_id]->published_revision_id;
        $entity->draft_revision_id = $states[$entity_id]->draft_revision_id;
        $entity->ers_state_id = $states[$entity_id]->state_id;
      }
      else {
        $entity->published_revision_id = $revision_id;
        $entity->draft_revision_id = $revision_id;
      }

      // If this is an edit path for this entity then we need to switch it
      // for the draft revision if this is not the draft revision.
      if ($revision_id != $entity->draft_revision_id &&
          empty($this->ers_revision_swap)) {
        $pattern = str_replace('%', $entity_id, $this->plugin['edit paths match']);
        if (drupal_match_path($_GET['q'], $pattern)) {
          ers_set_on_edit_path();

          $this->ers_revision_reset = TRUE;
          $draft_revisions = entity_load($this->entity_type, array($entity_id), array($revision_key => $entity->draft_revision_id));
          $this->ers_revision_reset = FALSE;

          // Swap the entity!
          if (!empty($draft_revisions[$entity_id])) {
            $entity = $entities[$entity_id] = $draft_revisions[$entity_id];
            // And restore this data too.
            $entity->published_revision_id = $states[$entity_id]->published_revision_id;
            $entity->draft_revision_id = $states[$entity_id]->draft_revision_id;
            $entity->ers_state_id = $states[$entity_id]->state_id;
          }
          else {
            // If the draft revision cannot be located because it was deleted
            // then we force the current revision to also be the draft.
            $entity->draft_revision_id = $revision_id;
          }
        }
      }

      // Load the entity's future schedule.
      $entity->ers_schedule = !empty($schedules[$entity_id]) ? $schedules[$entity_id] : array();
      foreach ($entity->ers_schedule as $schedule) {
        if ($schedule->publish_date <= time()) {
          $entity = $entities[$entity_id] = $this->execute_schedule($schedule, $entity);
          // And restore this data too.
          $entity->published_revision_id = $states[$entity_id]->published_revision_id;
          $entity->draft_revision_id = $states[$entity_id]->draft_revision_id;
          $entity->ers_state_id = $states[$entity_id]->state_id;
          $entity->ers_schedule = !empty($schedules[$entity_id]) ? $schedules[$entity_id] : array();
        }
      }

      // Mark that we have schedules available.
      if (!empty($entity->ers_schedule)) {
        ers_set_entity_scheduled($this->entity_type, $entity_id, $entity);

        // If we are previewing a future revision, find the proper revision for the
        // date given.
        if (!empty($_GET['preview-schedule'])) {
          // Refresh these in case revision_id got swapped.
          list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
          $publish_date = $_GET['preview-schedule'];

          $preview_revision_id = $revision_id;
          foreach ($entity->ers_schedule as $id => $schedule) {
            if ($publish_date >= $schedule->publish_date) {
              $preview_revision_id = $id;
            }
          }

          // If we found a new one to load, load it.
          if ($preview_revision_id != $revision_id) {
            $this->ers_revision_reset = TRUE;
            $draft_revisions = entity_load($this->entity_type, array($entity_id), array($revision_key => $preview_revision_id));
            $this->ers_revision_reset = FALSE;

            // Swap the entity!
            if (!empty($draft_revisions[$entity_id])) {
              $entity = $entities[$entity_id] = $draft_revisions[$entity_id];
              // And restore this data too.
              $entity->published_revision_id = $states[$entity_id]->published_revision_id;
              $entity->draft_revision_id = $states[$entity_id]->draft_revision_id;
              $entity->ers_state_id = $states[$entity_id]->state_id;
              $entity->ers_schedule = !empty($schedules[$entity_id]) ? $schedules[$entity_id] : array();
            }
          }
        }
      }
    }
  }

  public function hook_entity_presave($entity) {
    // We don't actually need to do anything here; this is only used by
    // entities that manipulate a published flag, such as node.
  }

  public function hook_entity_insert($entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    // If we do not support a publishing flag then we must ensure that
    // the initial published revision is set.
    if (!isset($entity->ers_schedule_revision_id) && empty($handler->supports_publishing_flag)) {
      $entity->ers_schedule_revision_id = $revision_id;
    }

    if (!empty($this->plugin['bundles'][$bundle])) {
      $entity->ers_state_id = db_insert('ers_entity_state', array('return' => Database::RETURN_INSERT_ID))
        ->fields(array(
          'entity_type' => $this->entity_type,
          'entity_id' => $entity_id,
          'published_revision_id' => $entity->ers_schedule_revision_id,
          'draft_revision_id' => $revision_id,
        ))
        ->execute();
      $entity->published_revision_id = $entity->ers_schedule_revision_id;
    }

    // Do we have to schedule publishing this revision?
    if (!empty($entity->ers_new_schedule)) {
      // Set the schedule to current revision id, or 0 for unpublishing. We need
      // this again here since ers_schedule_revision_id doesn't survive entity
      // saving stuff.
      $handler = ers_entity_plugin_get_handler($this->entity_type);
      if ($handler->supports_publishing_flag) {
        $entity->ers_schedule_revision_id = ($entity->ers_schedule_selector == 'publish' ? $revision_id : 0);
      }
      else {
        $entity->ers_schedule_revision_id = $revision_id;
      }

      $this->update_entity_schedule($entity, $entity->ers_new_schedule, $entity->ers_schedule_revision_id);
    }

    // Store state information.
    $this->update_entity_state($entity);
  }

  public function hook_entity_update($entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    // Only operate on this bundle if we are configured to.
    if (empty($this->plugin['bundles'][$bundle])) {
      return;
    }

    // Don't muck with saving if it's a revision reset.
    if (!empty($this->ers_revision_reset)) {
      return;
    }

    // Is scheduling unpublishing. Change revision_id to 0 otherwhise keep this
    // as is so it will schedule the correct revision rather than the current
    // one.
    if ($entity->ers_schedule_revision_id == 0) {
      $revision_id = 0;
    }

    // If we were instructed to remove a schedule entry, do it.
    if (!empty($entity->ers_remove_schedule)) {
      db_delete('ers_schedule')
        ->condition('entity_type', $this->entity_type)
        ->condition('entity_id', $entity_id)
        ->condition('revision_id', $revision_id)
        ->execute();
    }

    // Do we have to schedule publishing this revision?
    if (!empty($entity->ers_new_schedule)) {
      $this->update_entity_schedule($entity, $entity->ers_new_schedule, $revision_id);
    }

    // Store state information.
    $this->update_entity_state($entity);
  }

  public function hook_entity_delete($entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    db_delete('ers_entity_state')
      ->condition('entity_type', $this->entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();
    db_delete('ers_schedule')
      ->condition('entity_type', $this->entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();
  }

  public function hook_field_attach_delete_revision($entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
    db_delete('ers_schedule')
      ->condition('entity_type', $this->entity_type)
      ->condition('revision_id', $revision_id)
      ->condition('entity_id', $entity_id)
      ->execute();

    // They should not be able to delete the published revision ID, but they can
    // delete the draft.
    if (!empty($entity->ers_state_id) && $entity->draft_revision_id == $revision_id) {
      db_update('ers_entity_state')
        ->fields(array(
          'draft_revision_id' => $entity->published_revision_id
        ))
        ->condition('state_id', $entity->ers_state_id)
        ->execute();
    }
  }

  /**
   * Add or update an new entity schedule record.
   */
  public function update_entity_schedule($entity, $publish_date, $revision_id) {
    // Execute immediately?
    if ($publish_date > time()) {
      // No, this is future schedule. Record it.
      // Is there already an schedule for the revision?
      if (!empty($entity->ers_schedule[$revision_id])) {
        // Update the revision with the new date.
        db_update('ers_schedule')
          ->fields(array('publish_date' => $publish_date))
          ->condition('schedule_id', $entity->ers_schedule[$revision_id]->schedule_id)
          ->execute();
      }
      else {
        list($entity_id, $old_revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
        db_insert('ers_schedule')
          ->fields(array(
            'entity_type' => $this->entity_type,
            'entity_id' => $entity_id,
            'revision_id' => $revision_id,
            'publish_date' => $publish_date,
            'completed' => 0,
          ))
          ->execute();
      }
    }
    else {
      // Yes, change the published revision right now.
      $entity->published_revision_id = $revision_id;

      // Was there already a schedule for this?
      if (!empty($entity->ers_schedule[$revision_id])) {
        // Mark that it's completed.
        db_update('ers_schedule')
          ->fields(array('completed' => TRUE, 'publish_date' => $publish_date))
          ->condition('schedule_id', $entity->ers_schedule[$revision_id]->schedule_id)
          ->execute();
      }
    }
  }

  /**
   * Update the published and draft revision IDs for the entity.
   */
  public function update_entity_state($entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
    // Store state information.
    if (empty($entity->ers_state_id)) {
      db_insert('ers_entity_state')
        ->fields(array(
          'entity_type' => $this->entity_type,
          'entity_id' => $entity_id,
          'published_revision_id' => $revision_id,
          'draft_revision_id' => $revision_id,
        ))
        ->execute();
    }
    else {
      // When updating, the newest save is the draft revision ID, unless
      // $entity->ers_retain_draft is set to TRUE.

      $draft_revision_id = empty($entity->ers_retain_draft) ? $revision_id : $entity->draft_revision_id;

      db_update('ers_entity_state')
        ->fields(array(
          'published_revision_id' => $entity->published_revision_id,
          'draft_revision_id' => $draft_revision_id,
        ))
        ->condition('state_id', $entity->ers_state_id)
        ->execute();

      // If the entity being saved has a revision id that is not the
      // published revision, then set that we need to reset the
      // current version back to the published version via hook_exit.
      if ($entity->published_revision_id != $revision_id) {
        ers_set_entity_saved($this->entity_type, $entity_id, $entity);
      }
    }
  }

  // General API
  public function set_published_revision_current($entity_id, $entity) {
    // THIS MUST BE IMPLEMENTED PER ENTITY TYPE
  }

  // General API
  public function unpublish($entity_id, $entity) {
    // THIS MUST BE IMPLEMENTED PER ENTITY TYPE
  }

  /**
   * Get a full schedule for the given entity.
   */
  public function get_schedule($entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    return db_query("SELECT * FROM {ers_schedule} WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY completed DESC, publish_date ASC", array(':entity_type' => $this->entity_type, ':entity_id' => $entity_id))->fetchAllAssoc('revision_id');
  }

  /**
   * Implements a delegated execute_schedule.
   */
  public function execute_schedule($schedule, $entity = NULL) {
    if (empty($entity)) {
      $entities = entity_load($this->entity_type, array($schedule->entity_id));
      if (empty($entities[$schedule->entity_id])) {
        watchdog('ers', 'Attempted to publish entity %entity_type:%entity_id on schedule but the entity could not be loaded.', WATCHDOG_ERROR);
      }
    }

    if (!empty($entity)) {
      $entity->published_revision_id = $schedule->revision_id;
      $this->set_published_revision_current($schedule->entity_id, $entity);
    }

    $schedule->completed = TRUE;
    drupal_write_record('ers_schedule', $schedule, array('schedule_id'));

    return $entity;
  }

  public function settings_form(&$form, &$form_state) {
    $entity_info = entity_get_info($this->entity_type);
    if (!$entity_info) {
      return;
    }

    // Assemble an options array out of the bundle labels.
    $options = array();
    foreach ($entity_info['bundles'] as $bundle => $info) {
      $options[$bundle] = $info['label'];
    }

    $form[$this->entity_type] = array(
      '#title' => t('Entity: @type', array('@type' => $entity_info['label'])),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#description' => t('Choose which bundles will be controlled by ERS for this entity type.'),
      '#default_value' => variable_get('ers_entity_bundle_' . $this->entity_type, array()),
    );
  }

  public function settings_form_validate(&$form, &$form_state) {
  }

  public function settings_form_submit(&$form, &$form_state) {
    $entity_info = entity_get_info($this->entity_type);
    if (!$entity_info) {
      return;
    }

    variable_set('ers_entity_bundle_' . $this->entity_type, $form_state['values'][$this->entity_type]);
  }

  /**
   * Determine if the current user has access to publish the entity.
   *
   * This is called indirectly via ers_entity_plugin_access_switcher which
   * is a menu access callback.
   *
   * It may need to be overridden by entity types to account for overrides
   * such as 'administer nodes' that we cannot know about generically.
   */
  public function access_publish($entity, $new_revision_id) {
    if (empty($entity) || !is_object($entity)) {
      return FALSE;
    }

    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    // Make sure the revision exists
    $entity_info = entity_get_info($this->entity_type);
    $revision_key = $entity_info['entity keys']['revision'];

    $draft_revisions = entity_load($this->entity_type, array($entity_id), array($revision_key => $new_revision_id));

    if (!empty($draft_revisions[$entity_id])) {
      return user_access("publish $this->entity_type $bundle content");
    }
  }

  /**
   * Determine if the current user has access to publish the entity.
   *
   */
  public function access_unpublish($entity, $new_revision_id) {
    return $this->access_publish($entity, $new_revision_id);
  }

  /**
   * Provide a call back page to set which revision is the draft revision.
   *
   * This produces no output; it changes the revision and then performs
   * a goto. It uses a token to protect from CSRF attacks.
   */
  public function page_set_draft($js, $input, $entity, $new_revision_id) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);
    if (!isset($_GET['token']) || !drupal_valid_token($_GET['token'], $entity_id)) {
      return MENU_ACCESS_DENIED;
    }

    // Access control on the menu item should have already verified the
    // revision exists.
    db_update('ers_entity_state')
      ->fields(array('draft_revision_id' => $new_revision_id))
      ->condition('entity_type', $this->entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();

    drupal_goto(entity_uri($this->entity_type, $entity));
  }

  /**
   * Provide a call back page to publish or schedule a revision.
   *
   * This produces no output; it changes the revision and then performs
   * a goto. It uses a token to protect from CSRF attacks.
   */
  public function page_publish($js, $input, $entity, $new_revision_id) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    return drupal_get_form('ers_entity_schedule_full_form', $this->entity_type, $entity, $new_revision_id);
  }

  /**
   * Provide a call back page to unpublish a revision for the entity types that
   * support the publishing flag.
   *
   * This produces no output; it changes the published flag and then performs
   * a goto. It uses a token to protect from CSRF attacks.
   */
  public function page_unpublish($js, $input, $entity) {
    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    return drupal_get_form('ers_entity_schedule_unpublish_form', $this->entity_type, $entity, NULL);
  }

  /**
   * Fix field cache for the proper revision.
   *
   * File field (possibly others) tries to load the "current" revision in order
   * to see if someone might've deleted a file and remove deleted files. However,
   * what happens is that it loads the previous revision and compares that to
   * the current revision. That can cause it to decide files were deleted if
   * they appear in one revision but not the other.
   */
  public function fix_revision_cache($entity_id, $entity) {
    // If there is no published revision, the draft revision will remain the
    // current revision so no changes need to be made.
    if (empty($entity->published_revision_id)) {
      return;
    }

    list($entity_id, $revision_id, $bundle) = entity_extract_ids($this->entity_type, $entity);

    // Update the field cache so that file field doesn't delete files
    // because they're different on the formely current revision
    // than the currently current revision.
    $fake = entity_create_stub_entity($this->entity_type, array($entity_id, $entity->published_revision_id, $bundle));
    field_attach_load_revision($this->entity_type, array($entity_id => $fake));

    $data = array();
    $instances = field_info_instances($this->entity_type, $bundle);
    foreach ($instances as $instance) {
      $data[$instance['field_name']] = $fake->{$instance['field_name']};
    }
    $cid = "field:$this->entity_type:$entity_id";
    cache_set($cid, $data, 'cache_field');
  }
}
