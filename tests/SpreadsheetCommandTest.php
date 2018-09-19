<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN\Tester;

use VXM\MPN\SpreadsheetCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Lớp SpreadsheetCommandTest thực hiện test database command.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
class SpreadsheetCommandTest extends BaseTestCase
{

    public function testEmptySheet()
    {
        $commandTester = new CommandTester($this->app->get('migrate:ss'));
        $commandTester->setInputs([
            'y',
            __DIR__ . '/resources/phone-numbers.xlsx',
            '2:A, 2:C'
        ]);
        $commandTester->execute(['command' => 'migrate:ss']);
        $output = $commandTester->getDisplay(true);
        $this->assertContains('Bỏ qua sheet', $output);
    }

    public function testErrorSpreadsheetFile()
    {
        $commandTester = new CommandTester($this->app->get('migrate:ss'));
        $commandTester->setInputs([
            'y',
            __DIR__ . '/resources/phone-numbers.xlsxx',
            '0:A, 0:C'
        ]);
        $commandTester->execute(['command' => 'migrate:ss']);
        $output = $commandTester->getDisplay(true);
        $this->assertContains('Đường dẫn thư mục không hợp lệ hoặc file không đúng định dạng!', $output);
    }

    /**
     * @depends testEmptySheet
     * @depends testErrorSpreadsheetFile
     */
    public function testSuccessful()
    {
        $commandTester = new CommandTester($this->app->get('migrate:ss'));
        $commandTester->setInputs([
            'y',
            __DIR__ . '/resources/phone-numbers.xlsx',
            '0:A, 0:C'
        ]);
        $commandTester->execute(['command' => 'migrate:ss']);
        $output = $commandTester->getDisplay(true);
        $this->assertContains('Hoàn tất chuyển đổi dữ liệu trên sheet', $output);
    }


}
