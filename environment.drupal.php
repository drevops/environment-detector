<?php

/**
 * @file
 * Drupal integration entry point.
 *
 * Require this from a Drupal settings.php:
 * @code
 * require DRUPAL_ROOT . '/../vendor/drevops/environment-detector/environment.drupal.php';
 * @endcode
 *
 * A require runs in the caller's scope, so $settings and $config below are the
 * site's own variables. Passing them into the Drupal context by reference lets
 * the detector write the settings Drupal reads back: a context writing the
 * global $settings would update a different variable than the local one core
 * consumes, and the settings would silently never land.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Environment;

// Guarantee both are arrays even when required from an unusual scope; a real
// settings.php has them defined by core already.
$settings ??= [];
$config ??= [];

Environment::init(contexts: [new Drupal($settings, $config)]);
