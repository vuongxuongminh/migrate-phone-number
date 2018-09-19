<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Lớp trừu tượng MigrateCommand hổ trợ các phương thức cơ bản cho việc chuyển đổi số điện thoại 11 số sang 10 số, giúp cho các lớp
 * kế thừa thực thi đơn giản hơn.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
abstract class MigrateCommand extends Command
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
     * @var null|InputInterface Đối tượng Input khi thực thi lệnh. Nó chỉ có giá trị khi phương thực [[execute()]] được gọi.
     */
    protected $inputted;

    /**
     * @var null|OutputInterface Đối tượng Output khi thực thi lệnh. Nó chỉ có giá trị khi phương thực [[execute()]] được gọi.
     */
    protected $outputted;

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        ProgressBar::setFormatDefinition('normal', '[%bar%] %percent:3s%% (%elapsed:6s%)');
        $this->setHelp('https://github.com/vuongxuongminh/migrate-phone-number');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->inputted = $input;
        $this->outputted = $output;

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $confirm = new ConfirmationQuestion('<comment>Lệnh sẽ thực hiện thay đổi dữ liệu của bạn, hãy cân nhắc sao lưu dữ liệu trước khi thực hiện lệnh. Bạn có muốn tiếp tục? (y/n): </comment>', false);

        if ($question->ask($input, $output, $confirm)) {
            $this->migrate();
        }
    }

    /**
     * Phương thức thức trừu tượng đảm nhiệm việc thực thi chuyển đổi số điện thoại 11 số sang 10.
     */
    abstract protected function migrate(): void;

    /**
     * Phương thức hổ trợ chuyển đổi số điện thoại 11 số sang 10 số.
     *
     * @param string $phoneNumber Số điện thoại 11 số cần chuyển đổi
     * @return string Số điện thoại sau khi chuyển đổi
     */
    final protected function convert(string $phoneNumber): string
    {
        return preg_replace_callback(self::MIGRATE_PATTERN, function ($matches) {
            $matches[3] = self::MIGRATE_MAP[$matches[3]];
            array_shift($matches);

            return implode('', $matches);
        }, $phoneNumber);
    }

}
