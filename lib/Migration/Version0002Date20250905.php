<?php
declare(strict_types=1);

namespace OCA\Wol\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Adds a nullable "host" column to wol_devices */
class Version0002Date20250905 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('wol_devices')) {
            return null;
        }

        $table = $schema->getTable('wol_devices');

        // Add as NULLable (no default). App code will require it on insert.
        if (!$table->hasColumn('host')) {
            $table->addColumn('host', Types::STRING, [
                'notnull' => false,
                'length'  => 255,
            ]);
            return $schema; // a change was made
        }

        return null; // nothing to do
    }
}
