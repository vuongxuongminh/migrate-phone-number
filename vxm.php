#!/usr/bin/env php
<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 *
 * An entry script
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */

$autoloadFile = dirname(__DIR__, 2) . '/autoload.php';
if (!file_exists($autoloadFile)) {
    $autoloadFile = __DIR__ . '/vendor/autoload.php';
}

require($autoloadFile);

use Symfony\Component\Console\Application;

use VXM\MPN\MigrateCommand;

$app = new Application('Migrate Phone Number', '1.0.0');
$app->add(new MigrateCommand);
$app->run();