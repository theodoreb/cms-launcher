<?php

// Make it easier for Project Browser to install things into the local site.
$settings['skip_permissions_hardening'] = TRUE;

// Allow use of the direct-write mode added to Package Manager in Drupal 11.2.
$settings['package_manager_allow_direct_write'] = TRUE;

// Don't warn about using Package Manager, despite its being experimental.
$settings['testing_package_manager'] = TRUE;

// Allow `localhost` as a trusted host pattern to prevent an error on the status
// report.
$settings['trusted_host_patterns'][] = '^localhost$';

// Export configuration outside the web root.
$settings['config_sync_directory'] = '../config';

// Set up the SQLite database.
$databases['default']['default'] = [
  'prefix' => '',
  'database' => 'sites/default/files/.ht.sqlite',
  'driver' => 'sqlite',
  'namespace' => 'Drupal\\sqlite\\Driver\\Database\\sqlite',
  'autoload' => 'core/modules/sqlite/src/Driver/Database/sqlite/',
];

// Suppress the warning raised by `skip_permissions_hardening`.
// @see drupal_cms_installer_install_tasks()
putenv('IS_DDEV_PROJECT=1');

// Core's IconFinder reads the drive letter of a Windows path ("C:\...") as a URL
// scheme, and refuses to read the icon file, so icons don't render on Windows.
// This swaps in a subclass that reads a one-letter scheme as a drive letter.
// @see https://www.drupal.org/i/3579927
$GLOBALS['conf']['container_service_providers']['launcher'] = 'LauncherServiceProvider';

// Both classes extend Drupal classes, so they are declared when Drupal first
// asks for them, not when this file loads.
spl_autoload_register(function (string $class): void {
  if ($class === 'LauncherServiceProvider') {
    final class LauncherServiceProvider implements \Drupal\Core\DependencyInjection\ServiceModifierInterface {

      public function alter(\Drupal\Core\DependencyInjection\ContainerBuilder $container): void {
        $container->getDefinition(\Drupal\Core\Theme\Icon\IconFinder::class)->setClass(LauncherIconFinder::class);
      }

    }
  }
  elseif ($class === 'LauncherIconFinder') {
    final class LauncherIconFinder extends \Drupal\Core\Theme\Icon\IconFinder {

      public function getFileContents(string $uri): string|bool {
        $parts = parse_url($uri);
        if ($parts === FALSE || isset($parts['host'])) {
          return FALSE;
        }
        // No PHP stream wrapper has a one-letter name, so a one-letter scheme
        // is a drive letter. Every other scheme stays refused, as in core.
        if (isset($parts['scheme']) && strlen($parts['scheme']) !== 1) {
          return FALSE;
        }
        // parse_url() finds no host in a UNC path ("\\host\share").
        if (str_starts_with($parts['path'] ?? '', '\\\\')) {
          return FALSE;
        }
        return file_exists($uri) ? file_get_contents($uri) : FALSE;
      }

    }
  }
});
