<?php
/**
 * Bootstrap for unit tests.
 *
 * No full Nextcloud environment is needed — we only require:
 * 1. The Composer autoloader (provides OCP stubs via nextcloud/ocp and PHPUnit).
 * 2. The app's own autoloaded classes (lib/).
 *
 * The OCP Controller base-class is a real class from the nextcloud/ocp stub
 * package, so we can instantiate AjaxController with mocked dependencies
 * without touching the running Nextcloud instance at all.
 *
 * CompatibleMapper alias
 * ----------------------
 * In production, Application.php dynamically aliases CompatibleMapper to
 * either OCP\AppFramework\Db\Mapper (old NC) or OCA\TimeTracker\AppFramework\Db\OldNextcloudMapper.
 * For unit tests we replicate this alias so that the mapper classes can be
 * autoloaded and mocked without the full DI container.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('OCA\TimeTracker\AppFramework\Db\CompatibleMapper')) {
    if (class_exists(\OCP\AppFramework\Db\Mapper::class)) {
        class_alias(
            \OCP\AppFramework\Db\Mapper::class,
            'OCA\TimeTracker\AppFramework\Db\CompatibleMapper'
        );
    } else {
        class_alias(
            \OCA\TimeTracker\AppFramework\Db\OldNextcloudMapper::class,
            'OCA\TimeTracker\AppFramework\Db\CompatibleMapper'
        );
    }
}
