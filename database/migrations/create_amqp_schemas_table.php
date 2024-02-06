<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tableName;

    public function __construct()
    {
        $this->tableName = config('amqp-toolkit.table_name');
    }

    public function up()
    {
        if (! Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->id();
                $table->string('schema');
                $table->unsignedInteger('batch');
                $table->timestamp('migrated_at');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
};
