<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warehouses / Store Locations
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['warehouse', 'store', 'fulfillment_center'])->default('warehouse');
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('IN');
            $table->string('pincode')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_returns')->default(true);
            $table->boolean('allows_pickup')->default(false);
            $table->integer('priority')->default(0); // For fulfillment order
            $table->json('operating_hours')->nullable();
            $table->timestamps();
        });

        // Warehouse Stock - current inventory levels per warehouse
        Schema::create('warehouse_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0); // Reserved for orders
            $table->integer('available_quantity')->virtualAs('quantity - reserved_quantity');
            $table->integer('reorder_level')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->string('bin_location')->nullable(); // Physical location within warehouse
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id', 'variant_id']);
            $table->index(['product_id', 'warehouse_id']);
        });

        // Stock Transfers between warehouses
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->enum('status', ['draft', 'pending', 'in_transit', 'received', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Stock Transfer Items
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity_requested');
            $table->integer('quantity_sent')->nullable();
            $table->integer('quantity_received')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stock_transfer_id', 'product_id']);
        });

        // Inventory Counts / Stock Audits
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_number')->unique();
            $table->foreignId('warehouse_id')->constrained();
            $table->enum('type', ['full', 'cycle', 'spot'])->default('full');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_items')->default(0);
            $table->integer('items_counted')->default(0);
            $table->integer('discrepancies')->default(0);
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
        });

        // Inventory Count Items
        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('bin_location')->nullable();
            $table->integer('expected_quantity');
            $table->integer('counted_quantity')->nullable();
            $table->integer('variance')->virtualAs('COALESCE(counted_quantity, 0) - expected_quantity');
            $table->enum('status', ['pending', 'counted', 'verified'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->index(['inventory_count_id', 'status']);
        });

        // Stock Movement Log - comprehensive history
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->enum('movement_type', [
                'purchase',
                'sale',
                'return',
                'transfer_in',
                'transfer_out',
                'adjustment',
                'count_correction',
                'damage',
                'expired',
                'reservation',
                'release_reservation'
            ]);
            $table->integer('quantity'); // Positive for in, negative for out
            $table->integer('balance_after'); // Running balance
            $table->string('reference_type')->nullable(); // Order, StockTransfer, InventoryCount, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['movement_type', 'created_at']);
        });

        // Stock Reservations - for pending orders
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('order_id')->constrained();
            $table->integer('quantity');
            $table->enum('status', ['reserved', 'allocated', 'released', 'fulfilled'])->default('reserved');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['warehouse_id', 'product_id', 'status']);
        });

        // Stock Alerts
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->enum('alert_type', ['low_stock', 'out_of_stock', 'overstock', 'expiring'])->default('low_stock');
            $table->integer('current_quantity');
            $table->integer('threshold_quantity')->nullable();
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'alert_type']);
            $table->index(['product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_count_items');
        Schema::dropIfExists('inventory_counts');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('warehouse_stock');
        Schema::dropIfExists('warehouses');
    }
};
