<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Order Timeline - tracks all order activities
        Schema::create('order_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('event_type', 50); // status_change, note_added, shipment_created, etc.
            $table->string('event_title');
            $table->text('event_description')->nullable();
            $table->json('event_data')->nullable(); // Additional structured data
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('performer_type', 20)->default('admin'); // admin, customer, system
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index('event_type');
        });

        // Order Shipments - for split shipments (created first due to foreign key dependency)
        Schema::create('order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('shipment_code', 50)->unique();
            $table->string('status', 30)->default('created'); // created, picked_up, in_transit, delivered, returned
            $table->string('courier_name')->nullable();
            $table->string('courier_id')->nullable();
            $table->string('awb_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('weight', 8, 2)->nullable(); // in kg
            $table->json('dimensions')->nullable(); // {length, width, height}
            $table->foreignId('shipping_address_id')->nullable()->constrained('customer_addresses')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('pickup_scheduled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('awb_number');
        });

        // Order Shipment Items
        Schema::create('order_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('order_shipments')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->timestamps();

            $table->unique(['shipment_id', 'order_item_id']);
        });

        // Order Fulfillments - tracks partial fulfillments
        Schema::create('order_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('fulfillment_code', 50)->unique();
            $table->string('status', 30)->default('pending'); // pending, processing, shipped, delivered, cancelled
            $table->foreignId('shipment_id')->nullable()->constrained('order_shipments')->onDelete('set null');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('fulfilled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // Order Fulfillment Items
        Schema::create('order_fulfillment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained('order_fulfillments')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('item_total', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['fulfillment_id', 'order_item_id']);
        });

        // Order Holds - tracks hold/release actions
        Schema::create('order_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('hold_reason_code', 50); // fraud_check, payment_verification, stock_issue, customer_request, etc.
            $table->text('hold_reason')->nullable();
            $table->string('status', 20)->default('active'); // active, released, expired
            $table->timestamp('hold_until')->nullable(); // Optional auto-release
            $table->timestamp('released_at')->nullable();
            $table->text('release_notes')->nullable();
            $table->foreignId('held_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // Order Overrides - tracks manual overrides
        Schema::create('order_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('override_type', 50); // status_override, price_override, shipping_override, etc.
            $table->string('field_name')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason');
            $table->boolean('requires_approval')->default(false);
            $table->string('approval_status', 20)->nullable(); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['order_id', 'override_type']);
        });

        // Order SLA Configuration
        Schema::create('order_sla_config', function (Blueprint $table) {
            $table->id();
            $table->string('sla_type', 50)->unique(); // confirmation_time, processing_time, shipping_time, delivery_time
            $table->string('description');
            $table->integer('hours_limit'); // SLA hours
            $table->boolean('is_active')->default(true);
            $table->boolean('send_alerts')->default(true);
            $table->integer('alert_before_hours')->default(2); // Alert X hours before breach
            $table->json('applicable_statuses')->nullable(); // Which order statuses this applies to
            $table->timestamps();
        });

        // Add new columns to orders table for enhanced management
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_on_hold')->default(false)->after('order_status');
            $table->string('hold_reason_code')->nullable()->after('is_on_hold');
            $table->timestamp('hold_since')->nullable()->after('hold_reason_code');
            $table->string('fulfillment_status', 30)->default('unfulfilled')->after('hold_since'); // unfulfilled, partial, fulfilled
            $table->integer('total_shipments')->default(0)->after('fulfillment_status');
            $table->boolean('is_split_shipment')->default(false)->after('total_shipments');
            $table->timestamp('confirmed_at')->nullable()->after('is_split_shipment');
            $table->timestamp('processing_started_at')->nullable()->after('confirmed_at');
            $table->timestamp('shipped_at')->nullable()->after('processing_started_at');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->integer('priority')->default(0)->after('delivered_at'); // 0=normal, 1=high, 2=urgent
            $table->json('tags')->nullable()->after('priority'); // Custom tags for filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_on_hold', 'hold_reason_code', 'hold_since',
                'fulfillment_status', 'total_shipments', 'is_split_shipment',
                'confirmed_at', 'processing_started_at', 'shipped_at', 'delivered_at',
                'priority', 'tags'
            ]);
        });

        Schema::dropIfExists('order_sla_config');
        Schema::dropIfExists('order_overrides');
        Schema::dropIfExists('order_holds');
        Schema::dropIfExists('order_shipment_items');
        Schema::dropIfExists('order_fulfillment_items');
        Schema::dropIfExists('order_fulfillments');
        Schema::dropIfExists('order_shipments');
        Schema::dropIfExists('order_timeline');
    }
};
