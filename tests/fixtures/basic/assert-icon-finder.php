<?php

declare(strict_types=1);

// Confirms that the launcher's IconFinder reads a local icon file, including a
// Windows path that starts with a drive letter, and refuses every other URI.
// The fixture has no Drupal, so the parent class is a stub.

namespace Drupal\Core\Theme\Icon {
  class IconFinder {}
}

namespace {
  $app_root = __DIR__ . '/web';
  $site_path = 'sites/default';
  require $app_root . '/' . $site_path . '/settings.php';

  if ($GLOBALS['conf']['container_service_providers']['launcher'] !== 'LauncherServiceProvider') {
    throw new AssertionError('The service provider is not registered.');
  }

  $icon = tempnam(sys_get_temp_dir(), 'icon');
  file_put_contents($icon, '<svg/>');

  $cases = [
    // On Windows, this path starts with a drive letter.
    [$icon, '<svg/>'],
    ['file://' . $icon, FALSE],
    ['public://icon.svg', FALSE],
    ['http://example.com/icon.svg', FALSE],
    ['//example.com/icon.svg', FALSE],
    ['\\\\example\\share\\icon.svg', FALSE],
  ];
  $finder = new LauncherIconFinder();
  foreach ($cases as [$uri, $expected]) {
    if ($finder->getFileContents($uri) !== $expected) {
      throw new AssertionError("getFileContents($uri) does not return " . var_export($expected, TRUE) . '.');
    }
  }
  unlink($icon);
}
