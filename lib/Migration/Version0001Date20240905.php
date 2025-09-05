<?php
declare(strict_types=1);

namespace OCA\Wol\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0001Date20240905 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('wol_devices')) {
            $table = $schema->createTable('wol_devices');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('user_id', 'string',  ['length' => 64,  'notnull' => true]);
            $table->addColumn('name',    'string',  ['length' => 190, 'notnull' => true]);
            $table->addColumn('mac',     'string',  ['length' => 32,  'notnull' => true]);
            $table->addColumn('broadcast','string', ['length' => 45,  'notnull' => true]);
            $table->addColumn('port',    'smallint',['unsigned' => true, 'notnull' => true, 'default' => 9]);
            $table->addColumn('created_at','bigint',['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'wol_user_idx');
        }

        return $schema;
    }
}
