<?php
/**
 * @file
 * Definition of the default plugin.
 *
 * The default plugin contains the base class in which the majority of the
 * functionality is stored. It isn't used like a normal plugin, so we
 * have a couple of special directives.
 *
 * @todo -- does this even need to be a plugin? The only thing we gain is
 * automatic registration of the class. We could just register it ourselves
 * and not have a plugin at all.
 */

$plugin = array(
  'handler' => 'ERSEntityDefault',
  'no ui' => TRUE,
  'hooks' => array(),
);
