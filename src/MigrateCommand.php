<?php
/**
 * @link https://github.com/vuongxuongminh/migrate-phone-number
 * @copyright Copyright (c) 2018 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace VXM\MPN;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

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
        $connection = DriverManager::getConnection([
            'host' => $question->ask($input, $output, new Question('DB Host (127.0.0.1, localhost...): ')),
            'driver' => 'pdo_' . $question->ask($input, $output, new Question('DB PDO Driver (mysql, pgsql, sqlsrv, sqlite): ')),
            'user' => $question->ask($input, $output, new Question('DB Username (root): ')),
            'password' => $question->ask($input, $output, (new Question('DB Password: '))->setHidden(true)->setHiddenFallback(false)),
            'dbname' => $question->ask($input, $output, new Question('DB Name: '))
        ]);

        if ($connection->connect()) {
            $output->writeln('<info>Connected to database</info>');
            $this->migrate($connection, $input, $output);
            $output->writeln('<info>Done!</info>');
        }
    }

    protected function migrate(Connection $connection, InputInterface $input, OutputInterface $output): void
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
        $question = $this->getHelper('question');
        $tableColumns = $question->ask($input, $output, new Question('Migrate table columns (table1:column1, table2:column2 ...): '));
        $schema = $connection->getSchemaManager();

        foreach ($this->normalizeTableColumns($tableColumns) as $table => $columns) {
            $tableIndexes = $schema->listTableIndexes($table);
            $primary = $tableIndexes['primary'] ?? null;
            if (null === $primary) {
                $confirm = new ConfirmationQuestion("Table `$table` haven't primary key can't create backup file are you want to continue?", false);
                if (false === $question->ask($input, $output, $confirm)) {
                    return;
                }
            }
        }

        if ($question->ask($input, $output, new ConfirmationQuestion('Are you want to continue?', false))) {
            $this->migrate($connection, $input, $output);
        }
    }

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

}