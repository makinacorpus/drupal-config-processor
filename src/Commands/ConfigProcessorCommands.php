<?php

namespace Drupal\config_processor\Commands;

use Drupal\config_processor\ConfigProcessor;
use Drupal\config_processor\helper\MiscHelper;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Exceptions\CommandFailedException;

/**
 * Drush commands class.
 */
class ConfigProcessorCommands extends DrushCommands {

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
  public function configProcessorApply(string $settingsFilePath = NULL, array $options = NULL) {
    $commandFailed = FALSE;
    if ($settingsFilePath === NULL) {
      $settingsFilePath = MiscHelper::lookupSettingsFiles();
    }
    if ($settingsFilePath === NULL) {
      Drush::logger()->error(dt('Unable to detect configuration file "@filename".', ['@filename' => MiscHelper::DEFAULT_SETTINGS_FILENAME]));
    }
    elseif (!file_exists($settingsFilePath)) {
      Drush::logger()->error(dt('Unable to find configuration file at "@filepath".', ['@filepath' => $settingsFilePath]));
    }
    else {
      try {
        $configProcessor = new ConfigProcessor($settingsFilePath, $options);
        $configProcessor->apply();
      }
      catch (CommandFailedException $exception) {
        $commandFailed = TRUE;
      }
    }
    if ($commandFailed === TRUE) {
      Drush::logger()->info("Process done with errors");
    }
    else {
      Drush::logger()->info("Process done");
    }
  }

}
