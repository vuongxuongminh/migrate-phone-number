<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\Question;


/**
 * Lớp SpreadsheetCommand hổ trợ chuyển đổi dữ liệu các cột trên Spreadsheet chứa số điện thoại 11 số sang 10 số.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
class SpreadsheetCommand extends MigrateCommand
{

    /**
     * @var Spreadsheet Đối tượng spreadsheet chứa các cột trong các sheet cần chuyển đổi số điện thoại 11 số sang 10 số.
     */
    protected $spreadsheet;

    /**
     * @inheritdoc
     */
    protected static $defaultName = 'migrate:ss';

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Lệnh hổ trợ chuyển đổi dữ liệu các cột trên bảng Spreadsheet chứa số điện thoại 11 số sang 10 số.');
    }

    /**
     * @inheritdoc
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function migrate(): void
    {
        $this->outputted->writeln('<info>Thông tin file spreadsheet</info>');

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $file = $question->ask($this->inputted, $this->outputted, new Question('<info>Hãy nhập đường dẫn file csv, xls, xlsx (ví dụ: C:\folder\a.csv): </info>'));

        try {
            $reader = IOFactory::createReaderForFile($file);
            $spreadsheet = $this->spreadsheet = $reader->load($file);
        } catch (\Throwable $e) {
            $this->outputted->writeln('<error>Đường dẫn thư mục không hợp lệ!</error>');
            return;
        }

        $this->outputted->writeln('<info>Handled spreadsheet</info>');
        $this->migrateSpreadsheet();
        $writerClass = str_replace('PhpOffice\PhpSpreadsheet\Reader', 'PhpOffice\PhpSpreadsheet\Writer', get_class($reader));
        (new $writerClass($spreadsheet))->save($file);
        $this->outputted->writeln('<info>Hoàn tất</info>');
    }

    /**
     * Phương thức hổ trợ migrate spreadsheet theo dữ liệu yêu cầu của end-user.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function migrateSpreadsheet(): void
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $sheetColumns = $question->ask($this->inputted, $this->outputted, new Question('<info>Danh sách sheet và cột (ví dụ: 0:A, 0:B, 1:C ...): </info>'));

        foreach ($this->normalizeSheetColumns($sheetColumns) as $sheet => $columns) {
            $worksheet = $this->spreadsheet->getSheet($sheet);
            $this->migrateWorksheet($worksheet, $columns);
        }
    }

    /**
     * Phương thức hổ trợ migrate theo các cột trong worksheet chỉ định.
     *
     * @param Worksheet $worksheet Đối tượng worksheet chứa các cột.
     * @param array $columns Mảng các cốt chứa số điện thoại 11 số.
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function migrateWorksheet(Worksheet $worksheet, array $columns): void
    {
        $this->outputted->writeln("<info>Thực thi chuyển đổi dữ liệu trên sheet: `{$worksheet->getTitle()}`...</info>");

        $migrated = false;
        $progressBar = new ProgressBar($this->outputted);
        $progressBar->start();

        foreach ($columns as $column) {
            $highestRow = $worksheet->getHighestRow($column);
            $progressBar->setMaxSteps($progressBar->getMaxSteps() + $highestRow);

            for ($i = 1; $i <= $highestRow; $i++) {
                $migrated = true;
                $cell = $worksheet->getCell("$column:$i");
                $phoneNumber = $this->convert($cell->getValue());
                $cell->setValue($phoneNumber);
                $progressBar->advance();
            }
        }

        if ($migrated) {
            $progressBar->finish();
            $this->outputted->writeln("\n<info>Hoàn tất chuyển đổi dữ liệu trên sheet: `{$worksheet->getTitle()}`</info>");
        } else {
            $this->outputted->writeln("<comment>Bỏ qua sheet: `{$worksheet->getTitle()}` vì sheet không có dữ liệu.</comment>");
        }
    }

    /**
     * Phương thức hổ trợ chuyển đổi cấu trúc bảng cột sang mảng PHP.
     *
     * @param string $sheetColumns Chuỗi sheet và cột do end-user nhập.
     * @return array Mảng gồm có các khóa là id các sheet và giá trị là mảng danh sách cột cần chuyển đổi số điện thoại.
     */
    protected function normalizeSheetColumns(string $sheetColumns): array
    {
        $result = [];
        $sheetColumns = array_map('trim', explode(',', $sheetColumns));

        foreach ($sheetColumns as $sheetColumn) {
            list($sheet, $column) = explode(':', $sheetColumn);
            $result[$sheet][] = $column;
        }

        return $result;
    }

}