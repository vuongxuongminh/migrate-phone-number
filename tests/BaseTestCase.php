<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN\Tester;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Application;

use VXM\MPN\DatabaseCommand;
use VXM\MPN\SpreadsheetCommand;

/**
 * Lớp trừu tượng BaseTestCase hổ trợ việc cấu hình cơ bản cho các lớp test case.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
abstract class BaseTestCase extends TestCase
{

    /**
     * @var null|Application
     */
    protected $app;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $app = $this->mockApp();

        $app->add(new DatabaseCommand);
        $app->add(new SpreadsheetCommand);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->app = null;
    }

    /**
     * Khởi tạo application phục vụ cho việc gắn command test.
     *
     * @return Application Đối tượng app phục vụ cho việc test.
     */
    protected function mockApp()
    {
        return $this->app = new Application('Migrate Phone Number Test', '1.0.0');
    }

}
