<?php

namespace Drupal\config_processor;

use Drupal\config_processor\helper\FileSystemHelper;
use Drupal\config_processor\helper\MiscHelper;
use Drupal\config_processor\helper\YamlHelper;
use Drupal\Core\Serialization\Yaml;
use Drush\Exceptions\CommandFailedException;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Drupal Yaml config processor class.
 */
class ConfigProcessor {
  const VERBOSE_INDENT = '  ';

  /**
   * Drush options.
   */
  protected array $options;

  /**
   * Source directory where Yaml input files are stored.
   */
  protected string $configSourceDir;

  /**
   * Rules to apply.
   */
  protected array $rules;

  /**
   * Implements Drupal config processor.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   */
  public function __construct(string $settingsFilePath, array $options = []) {

    // Check settings files exists.
    if (!file_exists($settingsFilePath)) {
      throw new CommandFailedException(dt('Settings file "@filename" does not exist', ['@filename' => $settingsFilePath]));
    }

    // Validate settings files schema.
    // @todo Implement schema validation.
    // Init class properties.
    $this->options = $options;
    [
      'source-dir' => $this->configSourceDir,
      'rules' => $this->rules,
    ] = Yaml::decode(file_get_contents($settingsFilePath));
  }

  /**
   * Apply config processing.
   *
   * @return void
   *   return nothing
   */
  public function apply() {
    // Process all files.
    $fileList = FileSystemHelper::listDirFiles($this->configSourceDir);
    $globalReport = [
      'Analysing @numFiles files from "@source" directory.',
      '@fileEntriesReports',
    ];
    $fileEntriesReports = [];
    foreach ($fileList as $filePathFromSourceDir) {
      $fileEntryReport = ['file : @file', '@rules'];
      $fileEntryReportValues = [
        '@file' => $filePathFromSourceDir,
      ];

      $rulesReport = $this->processRules($filePathFromSourceDir);

      $fileEntryReportValues['@rules'] = implode("\n", $rulesReport);
      $fileEntriesReports[] = MiscHelper::strVar($fileEntryReport, $fileEntryReportValues);
    }

    MiscHelper::logReport(implode("\n", $globalReport), [
      '@numFiles' => count($fileList),
      '@source' => $this->configSourceDir,
      '@fileEntriesReports' => implode("\n", $fileEntriesReports),
    ]);
  }

  /**
   * Process rules for a given YAML source file.
   *
   * @param string $filePathFromSourceDir
   *   Yaml file path from source directory.
   *
   * @return array
   *   Array of report string (one report string by rule).
   */
  protected function processRules(string $filePathFromSourceDir): array {
    $rulesReport = [];
    $yaml = YamlHelper::getYaml($this->configSourceDir . DIRECTORY_SEPARATOR . $filePathFromSourceDir);
    foreach ($this->rules as $rule) {

      $breakRules = FALSE;
      $description = $rule['description'];
      $ruleReport = [];
      $ruleReportValues = [];
      $ruleReportValues['@description'] = $description;
      $matches = $rule['match'] ?? NULL;

      if (array_key_exists('action', $rule) && array_key_exists('actions', $rule)) {
        throw new \LogicException('"action" and "actions" property can\'t be used together.');
      }

      // Test if the rule match.
      if ($matches === NULL) {

        // When matches attribute is missing, rule match by default.
        $matched = TRUE;
      }
      else {

        // Test if one or more regex match, file path, rule applies.
        $matched = FALSE;
        foreach ($matches as $match) {
          if (preg_match($match, $filePathFromSourceDir)) {
            $matched = TRUE;
          }
        }
      }

      if ($matched) {
        // Process matching rule's actions.
        $actions = $rule['actions'];
        [
          'breakRules' => $breakRules,
          'actionsReport' => $actionsReport,
          'yaml' => $yaml,
        ] = $this->processActions($actions, $filePathFromSourceDir, $yaml);

        // Define rule message.
        $ruleReport[] = str_repeat(self::VERBOSE_INDENT, 1) . '! Match rule "@description"';
        $ruleReport[] = implode("\n", $actionsReport);

      }
      else {
        // No-matching rule.
        $ruleReport[] = str_repeat(self::VERBOSE_INDENT, 1) . 'X No match rule "@description"';
      }

      // Define rules message concatenating each rule message.
      $rulesReport[] = MiscHelper::strVar($ruleReport, $ruleReportValues);

      // Break rules when breaking action was met.
      if ($breakRules) {
        break;
      }
    }
    return $rulesReport;
  }

  /**
   * Process actions for a given YAML source file rule.
   *
   * @param array $actions
 *   Action to perform.
   * @param string $filePathFromSourceDir
 *   Path to Yaml file from the source directory.
   * @param array|null $yaml
 *   Yaml data.
   *
   * @return array
   *   Structure :
   *   [
   *     ['url' => string, 'queryParameters' => string[]],
   *     ...
   *   ].
   */
  #[ArrayShape([
    'breakRules' => "bool",
    'actionsReport' => "array",
    'yaml' => "array",
  ])]
  protected function processActions(array $actions, string $filePathFromSourceDir, $yaml = NULL): array {
    $actionsReport = [];
    $breakRules = FALSE;
    foreach ($actions as $action => $value) {
      $actionReport[] = str_repeat(self::VERBOSE_INDENT, 2) . '- Action = @action';
      $actionReportValues = ['@action' => $action];
      switch ($action) {
        case 'skip':
          $actionReport[] = str_repeat(self::VERBOSE_INDENT, 3) . '[Break next actions and skip to next file]';
          $breakRules = TRUE;
          break;

        case 'remove-props':
          [
            'yaml' => $yaml,
            'report' => $removePropsReport,
          ] = YamlHelper::removeProps($yaml, $value['props']);
          $actionReport[] = str_repeat(self::VERBOSE_INDENT, 3) . "- " . implode("\n" . str_repeat(self::VERBOSE_INDENT, 3) . "- ", $removePropsReport);
          break;

        case 'save':
          $saveYamlReport = YamlHelper::saveYaml($value['dest'], $filePathFromSourceDir, $yaml);
          $actionReport[] = str_repeat(self::VERBOSE_INDENT, 3) . "- " . str_repeat(self::VERBOSE_INDENT, 3) . "- " . $saveYamlReport;
          break;

        default:
          $actionReport[] = str_repeat(self::VERBOSE_INDENT, 3) . '[Unkown action "@action"]';
      }
      $actionsReport[] = MiscHelper::strVar($actionReport, $actionReportValues);
      if ($breakRules) {
        break;
      }
    }
    return [
      'breakRules' => $breakRules,
      'actionsReport' => $actionsReport,
      'yaml' => $yaml,
    ];
  }

}
