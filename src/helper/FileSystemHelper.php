<?php

namespace Drupal\config_processor\helper;

/**
 * File system helper functions.
 */
class FileSystemHelper {

  /**
   * Analyze file path and create missing directories as needed.
   *
   * @param string $basePath
   *   Base path.
   * @param string $filePath
   *   File path from base path.
   *
   * @return void
   *   Return nothing.
   */
  public static function createMissingDir($basePath, $filePath) {

    // Split path as array.
    $pathParts = explode(DIRECTORY_SEPARATOR, $filePath);

    // Remove file from path parts.
    array_pop($pathParts);

    // Make base directory if missing.
    static::makeDir($basePath, TRUE);

    // Make missing sub directories in file path.
    $dirPath = $basePath;
    foreach ($pathParts as $pathPart) {
      $dirPath .= '/' . $pathPart;
      static::makeDir($dirPath, TRUE);
    }
  }

  /**
   * List all YAML files in the specified directory and subdirectories.
   *
   * @param string $dirPath
   *   Directory to list.
   *
   * @return array|null
   *   return a list of files in directory or null if there is no file.
   */
  public static function listDirFiles($dirPath):array|null {
    return self::listDirAndSubDirFiles($dirPath);
  }

  /**
   * List all YAML files in the specified directory and subdirectories.
   *
   * @param string $dirPath
   *   Main directory path to list.
   * @param string $subDirPath
   *   Sub-directory path inside the main directory to list during a recursive
   *   process.
   *
   * @return array|null
   *   return a list of files in directory or null if there is no file.
   */
  protected static function listDirAndSubDirFiles($dirPath, $subDirPath = ''):array|null {
    $currentDirFiles = array_diff(scandir($dirPath), ['..', '.']);
    $allDirFiles = [];

    if (count($currentDirFiles) < 1) {
      return NULL;
    }

    foreach ($currentDirFiles as $file) {
      $filename = $dirPath . DIRECTORY_SEPARATOR . $file;
      $fileParts = pathinfo($filename);
      if (is_dir($filename)) {
        $allDirFiles = array_merge($allDirFiles, static::listDirFiles($filename, $subDirPath . $file . '/'));
      }
      elseif ($fileParts['extension'] === 'yml') {
        $allDirFiles[$filename] = $subDirPath . $file;
      }
    }
    return $allDirFiles;
  }

  /**
   * Make a directory.
   *
   * @param string $dir
   *   Directory to create.
   * @param bool $ignoreExist
   *   Do not throw error if directory already exist.
   *
   * @return void
   *   Return nothing
   */
  private static function makeDir(string $dir, bool $ignoreExist = FALSE) {
    if (file_exists($dir)) {
      if (is_dir($dir)) {
        if ($ignoreExist) {
          return;
        }
        throw new LogicException(sprintf('"%s" directory already exists', $dir));
      }
      throw new LogicException(sprintf('"%s" file exists :can\'t create directory', $dir));
    };
    mkdir($dir);
  }

}
