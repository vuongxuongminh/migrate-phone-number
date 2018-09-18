<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN;

/**
 * Class SpreadSheetCommand
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
class SpreadSheetCommand extends MigrateCommand
{

    /**
     * @inheritdoc
     */
    protected static $defaultName = 'migrate:ss';

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setDescription('Migrate phone number of Spread Sheet column value 11 to 10');

    }

    protected function migrate(): void
    {
        // TODO: Implement migrate() method.
    }

}