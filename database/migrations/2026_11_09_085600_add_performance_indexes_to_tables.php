<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Indexes pada tabel requests
        Schema::table('requests', function (Blueprint $table) {
            if (!$this->hasIndex('requests', 'idx_requests_day_time')) {
                $table->index(['Day_Request', 'Time_Request'], 'idx_requests_day_time');
            }
            if (!$this->hasIndex('requests', 'idx_requests_rack_status')) {
                $table->index(['Code_Rack', 'Status_Request'], 'idx_requests_rack_status');
            }
            if (!$this->hasIndex('requests', 'idx_requests_user')) {
                $table->index('Id_User', 'idx_requests_user');
            }
            if (!$this->hasIndex('requests', 'idx_requests_status')) {
                $table->index('Status_Request', 'idx_requests_status');
            }
        });

        // 2. Indexes pada tabel records
        Schema::table('records', function (Blueprint $table) {
            if (!$this->hasIndex('records', 'idx_records_day_time')) {
                $table->index(['Day_Record', 'Time_Record'], 'idx_records_day_time');
            }
            if (!$this->hasIndex('records', 'idx_records_code_rack')) {
                $table->index('Code_Rack', 'idx_records_code_rack');
            }
            if (!$this->hasIndex('records', 'idx_records_id_request')) {
                $table->index('Id_Request', 'idx_records_id_request');
            }
            if (!$this->hasIndex('records', 'idx_records_user')) {
                $table->index('Id_User', 'idx_records_user');
            }
        });

        // 3. Indexes pada tabel checks
        Schema::table('checks', function (Blueprint $table) {
            if (!$this->hasIndex('checks', 'idx_checks_time_check')) {
                $table->index('Time_Check', 'idx_checks_time_check');
            }
            if (!$this->hasIndex('checks', 'idx_checks_code_rack')) {
                $table->index('Code_Rack', 'idx_checks_code_rack');
            }
            if (!$this->hasIndex('checks', 'idx_checks_user')) {
                $table->index('Id_User', 'idx_checks_user');
            }
            if (!$this->hasIndex('checks', 'idx_checks_status_check')) {
                $table->index('Status_Check', 'idx_checks_status_check');
            }
        });

        // 4. Indexes pada tabel urgents
        if (Schema::hasTable('urgents')) {
            Schema::table('urgents', function (Blueprint $table) {
                if (!$this->hasIndex('urgents', 'idx_urgents_time_urgent')) {
                    $table->index('Time_Urgent', 'idx_urgents_time_urgent');
                }
                if (!$this->hasIndex('urgents', 'idx_urgents_code_rack')) {
                    $table->index('Code_Rack', 'idx_urgents_code_rack');
                }
                if (!$this->hasIndex('urgents', 'idx_urgents_request')) {
                    $table->index('Id_Request', 'idx_urgents_request');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if ($this->hasIndex('requests', 'idx_requests_day_time')) $table->dropIndex('idx_requests_day_time');
            if ($this->hasIndex('requests', 'idx_requests_rack_status')) $table->dropIndex('idx_requests_rack_status');
            if ($this->hasIndex('requests', 'idx_requests_user')) $table->dropIndex('idx_requests_user');
            if ($this->hasIndex('requests', 'idx_requests_status')) $table->dropIndex('idx_requests_status');
        });

        Schema::table('records', function (Blueprint $table) {
            if ($this->hasIndex('records', 'idx_records_day_time')) $table->dropIndex('idx_records_day_time');
            if ($this->hasIndex('records', 'idx_records_code_rack')) $table->dropIndex('idx_records_code_rack');
            if ($this->hasIndex('records', 'idx_records_id_request')) $table->dropIndex('idx_records_id_request');
            if ($this->hasIndex('records', 'idx_records_user')) $table->dropIndex('idx_records_user');
        });

        Schema::table('checks', function (Blueprint $table) {
            if ($this->hasIndex('checks', 'idx_checks_time_check')) $table->dropIndex('idx_checks_time_check');
            if ($this->hasIndex('checks', 'idx_checks_code_rack')) $table->dropIndex('idx_checks_code_rack');
            if ($this->hasIndex('checks', 'idx_checks_user')) $table->dropIndex('idx_checks_user');
            if ($this->hasIndex('checks', 'idx_checks_status_check')) $table->dropIndex('idx_checks_status_check');
        });

        if (Schema::hasTable('urgents')) {
            Schema::table('urgents', function (Blueprint $table) {
                if ($this->hasIndex('urgents', 'idx_urgents_time_urgent')) $table->dropIndex('idx_urgents_time_urgent');
                if ($this->hasIndex('urgents', 'idx_urgents_code_rack')) $table->dropIndex('idx_urgents_code_rack');
                if ($this->hasIndex('urgents', 'idx_urgents_request')) $table->dropIndex('idx_urgents_request');
            });
        }
    }
};
