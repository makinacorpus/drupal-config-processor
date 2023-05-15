<?php

namespace Drupal\config_processor\Commands;

use Drupal\config_processor\ConfigProcessor;
use Drush\Commands\DrushCommands;

/**
 * Drush commands class.
 */
class ChaletsDevCommands extends DrushCommands {

  /**
   * Process config files and apply rules and actions to them.
   *
   * @param string $settingsFilePath
   *   Source config directory path.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @usage config_processor-apply cp:a
   *   Process config files and apply rules and actions to them.
   * @command config_processor:apply
   * @aliases cp:a
   *
   * @throws \Drush\Exceptions\CommandFailedException
   */
  public function configProcessorApply($settingsFilePath, array $options) {
    $configProcessor = new ConfigProcessor($settingsFilePath, $options);
    $configProcessor->apply();
  }

}
