<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Lớp DatabaseCommand hổ trợ chuyển đổi dữ liệu các cột trên bảng CSDL chứa số điện thoại 11 số sang 10 số.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
class DatabaseCommand extends MigrateCommand
{

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName('migrate:db')
            ->setDescription('Lệnh hổ trợ chuyển đổi dữ liệu các cột trên bảng CSDL chứa số điện thoại 11 số sang 10 số.');

        parent::configure();
    }

    /**
     * @inheritdoc
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $confirm = new ConfirmationQuestion('<comment>Lệnh này sẽ thực hiện thay đổi dữ liệu của bạn, hãy cân nhắc sao lưu dữ liệu trước khi thực thi. Bạn có muốn tiếp tục? (y/n): </comment>', false);

        if ($question->ask($input, $output, $confirm)) {
            $output->writeln('<info>Thông tin CSDL</info>');

            $connection = $this->connection = DriverManager::getConnection([
                'driver' => 'pdo_' . $question->ask($input, $output, new Question('PDO Driver (mysql, pgsql, sqlsrv, sqlite): ')),
                'host' => $question->ask($input, $output, new Question('Host (127.0.0.1, localhost...): ')),
                'dbname' => $question->ask($input, $output, new Question('DBName: ')),
                'user' => $question->ask($input, $output, new Question('User: ')),
                'password' => $question->ask($input, $output, (new Question('Pass: '))->setHidden(true)->setHiddenFallback(false)),
            ]);

            if ($connection->connect()) {
                $output->writeln('<info>Kết nối đến CSDL thành công!</info>');
                $this->migrate($input, $output, $connection);
                $output->writeln('<info>Hoàn tất!</info>');
            }
        }
    }

    /**
     * Phương thức thực hiện chuyển đổi số điện thoại 11 sang 10 số
     *
     * @param InputInterface $input Đối tượng input dùng để thu thập dữ liệu từ end-user
     * @param OutputInterface $output Đối tượng output dùng để show tiến trình
     * @param Connection $connection Đối tượng DBAL connection
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Throwable
     */
    protected function migrate(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $tableColumns = $question->ask($input, $output, new Question('Danh sách bảng và cột (table1:column1, table2:column2 ...): '));
        $connection->beginTransaction();

        try {
            foreach ($this->normalizeTableColumns($tableColumns) as $table => $columns) {
                $this->migrateTable($table, $columns, $output, $connection);
            }

            $connection->commit();
        } catch (\Throwable $throwable) {
            $output->writeln("<error>Có lỗi xảy ra! Thực hiện rollback dữ liệu đã thay đổi...</error>");
            $connection->rollBack();

            throw $throwable;
        }

        if ($question->ask($input, $output, new ConfirmationQuestion('Bạn có muốn tiếp tục với danh sách bảng và cột mới? (y/n): ', false))) {
            $this->migrate($input, $output, $connection);
        }
    }

    /**
     * Phương thức hổ trợ chuyển đổi cấu trúc bảng cột sang mảng PHP.
     *
     * @param string $tableColumns Chuỗi bảng và cột do end-user nhập
     * @return array Mảng gồm có các khóa là tên bảng và giá trị là mảng danh sách cột cần chuyển đổi số điện thoại
     */
    protected function normalizeTableColumns(string $tableColumns): array
    {
        $result = [];
        $tableColumns = array_map('trim', explode(',', $tableColumns));

        foreach ($tableColumns as $tableColumn) {
            list($table, $column) = explode(':', $tableColumn);
            $result[$table][] = $column;
        }

        return $result;
    }

    /**
     * Phương thức thực hiện chuyển đổi giá trị các cột chứa số điện thoại 11 số sang 10 số theo bảng được chỉ định.
     *
     * @param string $table Bảng chỉ định.
     * @param array $columns Các cột chứa số điện thoại 11 số.
     * @param OutputInterface $output Đối tượng output dùng để thông báo kết quả và tiến trình thực thi.
     * @param Connection $connection Đối tượng kết nối đến CSDL chứa bảng.
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function migrateTable(string $table, array $columns, OutputInterface $output, Connection $connection): void
    {
        $primaryKey = $this->getPrimaryKey($table, $connection);
        $selectColumns = array_unique(array_merge($columns, $primaryKey));
        $queryStatement = $connection->createQueryBuilder()->select($selectColumns)->from($table)->execute();

        if (($rowCount = $queryStatement->rowCount()) > 0) {
            $progressBar = new ProgressBar($output, $rowCount);
            $progressBar->start();
            $output->writeln("<info>Thực thi chuyển đổi dữ liệu trên bảng: `$table`...</info>");

            while ($row = $queryStatement->fetch()) {
                $updateStatement = $connection->createQueryBuilder()->update($table);
                foreach ($row as $column => $value) {
                    if (in_array($column, $columns, true)) {
                        $updateStatement->set($column, ":$column")->setParameter(":$column", $this->convert($value));
                    }

                    $updateStatement->andWhere("$column=:{$column}_condition")->setParameter(":{$column}_condition", $value);
                }

                $updateStatement->execute();
                $progressBar->advance();
            }

            $progressBar->finish();
            $output->writeln("<info>Hoàn tất chuyển đổi dữ liệu trên bảng: `$table`</info>");
        } else {
            $output->writeln("<comment>Bỏ qua bảng: `$table` vì bảng rỗng.</comment>");
        }
    }

    /**
     * Phương thức hổ trợ lấy danh sách khóa chính trên bảng.
     *
     * @param string $table Bảng cần lấy danh sách khóa chính.
     * @param Connection $connection Đối tượng kết nối đến CSDL chứa bảng.
     * @return array Mảng chứa danh sách khóa chính.
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getPrimaryKey(string $table, Connection $connection): array
    {
        $tableDetail = $connection->getSchemaManager()->listTableDetails($table);

        if ($tableDetail->hasPrimaryKey()) {
            return $tableDetail->getPrimaryKeyColumns();
        } else {
            return [];
        }
    }


}