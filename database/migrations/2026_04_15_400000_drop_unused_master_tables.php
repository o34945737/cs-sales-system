<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally left as a no-op.
        // These master tables are still used by routes, controllers, pages, and tests.
    }

    public function down(): void
    {
        // No-op.
    }
};
