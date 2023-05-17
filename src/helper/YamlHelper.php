<?php

namespace Drupal\config_processor\helper;

use Drupal\Core\Serialization\Yaml;

/**
 * Yaml helper functions.
 */
class YamlHelper {

  /**
   * Remove properties from yaml data.
   *
   * @param array $yaml
   *   Yaml data.
   * @param array $props
   *   Properties to remove.
   *
   * @return array
   *   Structure:
   *    [
   *     'yaml' => array,
   *     'report' => array
   *    ]
   */
  public static function removeProps(array $yaml, array $props) {
    $report = [];
    foreach ($props as $prop) {
      $propFound = FALSE;
      if (array_key_exists($prop, $yaml)) {
        unset($yaml[$prop]);
        $propFound = TRUE;
      }
      $report[] = MiscHelper::strVar('Remove property @prop : @found', [
        '@prop' => $prop,
        '@found' => $propFound ? 'found' : 'not fount',
      ]);
    }
    return [
      'yaml' => $yaml,
      'report' => $report,
    ];
  }

  /**
   * Get Yaml data from file path.
   *
   * If $yaml parameter is not null, function just return it instead of reading
   * data from file.
   *
   * @param string $path
   *   Yaml file path.
   *
   * @return array
   *   Yaml data.
   */
  public static function getYaml(string $path): array {
    return Yaml::decode(file_get_contents($path));
  }

  /**
   * Save YAML data to file.
   *
   * @param string $basePath
   *   Base path.
   * @param string $filePath
   *   File path from base path.
   * @param array $yaml
   *   YAML data to save into file.
   *
   * @return string
   *   save Yaml action report line.
   */
  public static function saveYaml(string $basePath, string $filePath, array $yaml): string {
    $targetPath = $basePath . DIRECTORY_SEPARATOR . $filePath;
    FileSystemHelper::createMissingDir($basePath, $filePath);
    file_put_contents($targetPath, Yaml::encode($yaml));
    return MiscHelper::strVar('save "@target_path"', ['@target_path' => $targetPath]);
  }

}
