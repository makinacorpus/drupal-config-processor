<?php

namespace Drupal\config_processor\helper;

use Drush\Drush;

/**
 * Miscellaneous Helper functions.
 */
class MiscHelper {
  public const DEFAULT_SETTINGS_FILENAME = 'config-processor.settings.yml';

  /**
   * Replace placeholders using variable values into string.
   *
   * @param string|array $input
   *   String containing placeholders.
   * @param array $vars
   *   Values to replace placeholders with.
   *
   * @return string
   *   String with replaced placeholders.
   */
  public static function strVar(string|array $input, array $vars): string {
    return str_replace(array_keys($vars), array_values($vars), is_array($input) ? implode("\n", $input) : $input);
  }

  /**
   * Log report using drush logger.
   *
   * @param string $report
   *   Report string.
   * @param array $values
   *   Value to replace placeholder with.
   *
   * @return void
   *   Return nothing.
   */
  public static function logReport(string $report, array $values = []) {
    Drush::logger()->info(static::strVar($report, $values));
  }

  /**
   * Settings file lookup.
   *
   * @return string|null
   *   Settings file path if file was found.
   */
  public static function lookupSettingsFiles() {
    $pathPart = explode(DIRECTORY_SEPARATOR, __DIR__);
    while (count($pathPart)) {
      array_pop($pathPart);
      $expectedConfigFilePath = implode(DIRECTORY_SEPARATOR, $pathPart) . DIRECTORY_SEPARATOR . MiscHelper::DEFAULT_SETTINGS_FILENAME;
      if (file_exists($expectedConfigFilePath)) {
        return $expectedConfigFilePath;
      }
    }
    return NULL;
  }

}
