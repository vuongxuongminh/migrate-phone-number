<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN;

use Symfony\Component\Console\Command\Command;

/**
 * Lớp trừu tượng MigrateCommand hổ trợ các phương thức cơ bản cho việc chuyển đổi số điện thoại 11 số sang 10 số, giúp cho các lớp
 * kế thừa thực thi đơn giản hơn.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
class MigrateCommand extends Command
{

    /**
     * Chuổi hằng regex pattern dùng để định vị số điện thoại 11 số trên db.
     */
    const MIGRATE_PATTERN = '~(^|\'|")(\+?84|0)?(16[2-9]|12[0-9]|18[68]|199)(\d{7})($|\'|")~';

    /**
     * Mảng hằng dùng để chuyển đổi đầu số.
     */
    const MIGRATE_MAP = [
        '162' => '32', '163' => '33', '164' => '34', '165' => '35', '166' => '36', '167' => '37',
        '168' => '38', '169' => '39', '120' => '70', '121' => '79', '122' => '77', '123' => '83',
        '124' => '84', '125' => '85', '126' => '76', '127' => '81', '128' => '78', '129' => '82',
        '186' => '56', '188' => '58', '199' => '59'
    ];

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setHelp('https://github.com/vuongxuongminh/migrate-phone-number');

        parent::configure();
    }

    /**
     * Phương thức hổ trợ chuyển đổi số điện thoại 11 số sang 10 số.
     *
     * @param string $phoneNumber Số điện thoại 11 số cần chuyển đổi
     * @return string Số điện thoại sau khi chuyển đổi
     */
    protected function convert(string $phoneNumber): string
    {
        return preg_replace_callback(self::MIGRATE_PATTERN, function ($matches) {
            $matches[3] = self::MIGRATE_MAP[$matches[3]];
            array_shift($matches);

            return implode('', $matches);
        }, $phoneNumber);
    }

}