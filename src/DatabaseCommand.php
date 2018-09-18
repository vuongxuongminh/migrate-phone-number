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
use Symfony\Component\Console\Question\Question;


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
    protected static $defaultName = 'migrate:db';

    /**
     * @var null|Connection Đối tượng kết nối CSDL để thực thi chuyển đổi.
     */
    protected $db;

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Lệnh hổ trợ chuyển đổi dữ liệu các cột trên bảng CSDL chứa số điện thoại 11 số sang 10 số.');
    }

    /**
     * @inheritdoc
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Throwable
     */
    protected function migrate(): void
    {
        $this->outputted->writeln('<info>Thông tin CSDL</info>');

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_' . $question->ask($this->inputted, $this->outputted, new Question('PDO Driver (mysql, pgsql, sqlsrv, sqlite): ')),
            'host' => $question->ask($this->inputted, $this->outputted, new Question('Host (ví dụ: 127.0.0.1, localhost...): ')),
            'dbname' => $question->ask($this->inputted, $this->outputted, new Question('DBName: ')),
            'user' => $question->ask($this->inputted, $this->outputted, new Question('User: ')),
            'password' => $question->ask($this->inputted, $this->outputted, (new Question('Pass: '))->setHidden(true)->setHiddenFallback(false)),
        ]);

        if ($connection->connect()) {
            $this->outputted->writeln('<info>Kết nối đến CSDL thành công!</info>');
            $this->db = $connection;
            $this->migrateDatabase();
            $this->outputted->writeln('<info>Hoàn tất!</info>');
        }
    }

    /**
     * Phương thức thực hiện chuyển đổi số điện thoại 11 sang 10 số.
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Throwable
     */
    protected function migrateDatabase(): void
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $tableColumns = $question->ask($this->inputted, $this->outputted, new Question('Danh sách bảng và cột (ví dụ: table1:column1, table2:column2, ...): '));

        $this->db->beginTransaction();
        try {
            foreach ($this->normalizeTableColumns($tableColumns) as $table => $columns) {
                $this->migrateTable($table, $columns);
            }

            $this->db->commit();
        } catch (\Throwable $throwable) {
            $this->outputted->writeln("<error>Có lỗi xảy ra! Thực hiện rollback dữ liệu đã thay đổi...</error>");
            $this->db->rollBack();

            throw $throwable;
        }
    }

    /**
     * Phương thức hổ trợ chuyển đổi cấu trúc bảng cột sang mảng PHP.
     *
     * @param string $tableColumns Chuỗi bảng và cột do end-user nhập.
     * @return array Mảng gồm có các khóa là tên bảng và giá trị là mảng danh sách cột cần chuyển đổi số điện thoại.
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
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function migrateTable(string $table, array $columns): void
    {
        $primaryKey = $this->getPrimaryKey($table);
        $selectColumns = array_unique(array_merge($columns, $primaryKey));
        $queryStatement = $this->db->createQueryBuilder()->select($selectColumns)->from($table)->execute();

        if (($rowCount = $queryStatement->rowCount()) > 0) {
            $this->outputted->writeln("<info>Thực thi chuyển đổi dữ liệu trên bảng: `$table`...</info>");
            $progressBar = new ProgressBar($this->outputted, $rowCount);
            $progressBar->start();

            while ($row = $queryStatement->fetch()) {
                $updateStatement = $this->db->createQueryBuilder()->update($table);
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
            $this->outputted->writeln("\n<info>Hoàn tất chuyển đổi dữ liệu trên bảng: `$table`</info>");
        } else {
            $this->outputted->writeln("<comment>Bỏ qua bảng: `$table` vì bảng rỗng.</comment>");
        }
    }

    /**
     * Phương thức hổ trợ lấy danh sách khóa chính trên bảng.
     *
     * @param string $table Bảng cần lấy danh sách khóa chính.
     *
     * @return array Mảng chứa danh sách khóa chính.
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getPrimaryKey(string $table): array
    {
        $tableDetail = $this->db->getSchemaManager()->listTableDetails($table);

        if ($tableDetail->hasPrimaryKey()) {
            return $tableDetail->getPrimaryKeyColumns();
        } else {
            return [];
        }
    }


}