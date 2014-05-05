The Entity Revision Scheduling module is a bit of a combination module that
takes over control of which revision is current and allows the scheduling
of future revisions.

In order to achieve this, there are two major components:

1) A record for supported entities is kept that records which is the 
   'published' revision and which is the 'draft' revision. During hte editing 
   process, the 'draft' revision is loaded to replace the 'published' 
   revision. Whenever a revision is saved, the 'published' revision is then 
   reloaded and resaved to ensure that it remains published even though
   changes were made to the current revision.

   In addition, the 'revision' flag is always set to false if the draft 
   revision is not the same as the public revision, and the checkbox is 
   revealed with a comment so that administrators realize that using the 
   checkbox would create a new draft revision.

   Any revision can be set as the draft revision, and then edited, but only the
   draft revision can be edited.

   If the published revision is the same as the draft revision, the revision 
   flag is forced to true and disabled so that the published revision cannot
   be edited directly.

2) On cron, a schedule allows the system to automatically switch to a given
   'published' revision at a certain time.

For supported entities, the 'revisions' page is completely taken over and
replaced with a page that provides the proper tools for seeing which
revision is published, which is the draft, seeing the schedule and setting
the future schedule.

To use this module, you need to visit Administer >> Configuration >> Entity 
Revision Scheduler and enable it for whichever entity bundles you want; by
default it is not enabled for any bundles.

Entities are managed via a plugin which utilizes OO to do its business,
allowing entity-specific code to work. This is necessary as entities are
not fully generic and we do not always know everything that is needed
to know.

The module includes a preview widget as a block which is added to the page
by default. Users with the proper permission can use this widget to preview
an entity by choosing one of the available future dates. This is particularly
useful when an entity is composed of several other entities with different
schedules.

There is a hook_ers_entity_plugin_process(&$plugin, $info) that modules can
use to modify information about an existing entity type plugin. This is most
useful for adding additional paths that need to be handled, for example.