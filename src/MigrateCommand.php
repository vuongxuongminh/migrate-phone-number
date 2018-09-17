<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Index;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class MigrateCommand
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0
 */
class MigrateCommand extends Command
{

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('migrate')
            ->setDescription('Migrate phone number 11 to 10')
            ->setHelp('https://github.com/vuongxuongminh/migrate-phone-number')
            ->addArgument('config', InputArgument::OPTIONAL, 'Config file');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $output->writeln('<info>Please enter a database information</info>');
        $connection = $this->connection = DriverManager::getConnection([
            'host' => $question->ask($input, $output, new Question('DB Host (127.0.0.1, localhost...): ')),
            'driver' => 'pdo_' . $question->ask($input, $output, new Question('DB PDO Driver (mysql, pgsql, sqlsrv, sqlite): ')),
            'user' => $question->ask($input, $output, new Question('DB Username (root): ')),
            'password' => $question->ask($input, $output, (new Question('DB Password: '))->setHidden(true)->setHiddenFallback(false)),
            'dbname' => $question->ask($input, $output, new Question('DB Name: '))
        ]);

        if ($connection->connect()) {
            $output->writeln('<info>Connected to database</info>');
            $this->migrate($input, $output, $connection);
            $output->writeln('<info>Done!</info>');
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
        $tableColumns = $question->ask($input, $output, new Question('Migrate table columns (table1:column1, table2:column2 ...): '));
        $connection->beginTransaction();

        try {
            foreach ($this->normalizeTableColumns($tableColumns) as $table => $columns) {
                $primaryKey = $this->getPrimaryKey($table, $connection);
                $backupSQL = [];

                if ($primaryKey === null) {
                    $confirm = new ConfirmationQuestion("Table `$table` haven't primary key can't create backup file are you want to continue?", false);
                    if ($question->ask($input, $output, $confirm) === false) {
                        $connection->rollBack();
                        return;
                    }

                    $selectColumns = $columns;
                    $backupSQL = false;
                } else {
                    $selectColumns = array_merge($primaryKey, $columns);
                }

                $statement = $connection->createQueryBuilder()->select($selectColumns)->from($table)->execute();
                while ($row = $statement->fetch()) {
                    if (is_array($backupSQL)) {
                        $backupSQL[] = $this->getBackupSQL($table, $row, $connection);
                    }
                }
            }
            $connection->commit();
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }

        if ($question->ask($input, $output, new ConfirmationQuestion('Are you want to continue?', false))) {
            $this->migrate($connection, $input, $output);
        }
    }

    /**
     * Phương thức hổ trợ chuyển đổi cấu trúc bảng cột sang mảng PHP.
     *
     * @param string $tableColumns Chuỗi bảng và cột do end-user nhập
     * @return array Mảng gồm có các khóa là tên bảng và giá trị là mảng danh sách cột cần chuyển đổi số điện thoại
     */
    private function normalizeTableColumns(string $tableColumns): array
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
     * @param string $table
     * @param array $row
     * @param Connection $connection
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getBackupSQL(string $table, array $row, Connection $connection): string
    {
        $qb = $connection->createQueryBuilder()->update($table);

        foreach ($row as $column => $value) {
            $qb->set($column, ":$column");
        }

        foreach ($this->getPrimaryKey($table, $connection) as $column) {
            $qb->andWhere("$column = :$column")->setParameter(":$column", $row[$column]);
        }

        return $qb->getSQL();
    }

    /**
     * @var array Cache các primary keys đã lấy trước đó
     */
    private $_primaryKeys = [];

    /**
     * @param string $table
     * @param Connection $connection
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getPrimaryKey(string $table, Connection $connection): ?array
    {
        if (array_key_exists($table, $this->_primaryKeys)) {
            return $this->_primaryKeys[$table];
        } else {
            $table = $connection->getSchemaManager()->listTableDetails($table);

            if ($table->hasPrimaryKey()) {
                return $this->_primaryKeys = $table->getPrimaryKeyColumns();
            } else {
                return $this->_primaryKeys = null;
            }
        }
    }

}