<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed default user
        User::factory()->create([
            'name' => 'Admin Pengguna',
            'email' => 'admin@enterprise.com',
            'password' => bcrypt('password123')
        ]);

        // 2. Seed Source Connections
        $source1 = DB::table('source_connections')->insertGetId([
            'name' => 'ERP Production DB',
            'type' => 'pgsql',
            'status' => 'active',
            'last_sync_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $source2 = DB::table('source_connections')->insertGetId([
            'name' => 'Excel Sales Uploads',
            'type' => 'excel',
            'status' => 'active',
            'last_sync_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $source3 = DB::table('source_connections')->insertGetId([
            'name' => 'ClickHouse DW',
            'type' => 'pgsql',
            'status' => 'active',
            'last_sync_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        // 3. Seed Import Logs
        $log1 = DB::table('import_logs')->insertGetId([
            'source_connection_id' => $source2,
            'status' => 'completed',
            'total_records' => 150,
            'success_records' => 148,
            'failed_records' => 2,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        $log2 = DB::table('import_logs')->insertGetId([
            'source_connection_id' => $source1,
            'status' => 'completed',
            'total_records' => 200,
            'success_records' => 200,
            'failed_records' => 0,
            'created_at' => Carbon::now()->subDays(1),
            'updated_at' => Carbon::now()->subDays(1)
        ]);

        $log3 = DB::table('import_logs')->insertGetId([
            'source_connection_id' => $source1,
            'status' => 'failed',
            'total_records' => 100,
            'success_records' => 0,
            'failed_records' => 100,
            'error_details' => 'Database connection timeout at staging area.',
            'created_at' => Carbon::now()->subHours(4),
            'updated_at' => Carbon::now()->subHours(4)
        ]);

        // 4. Seed Imported Projects
        $proj1 = DB::table('imported_projects')->insertGetId([
            'source_connection_id' => $source2,
            'import_log_id' => $log1,
            'external_id' => 'PRJ001',
            'original_name' => 'Pengembangan Sistem Inventory A1',
            'normalized_name' => 'pengembangan sistem inventory a1',
            'metadata' => json_encode(['value' => 150000000]),
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        $proj2 = DB::table('imported_projects')->insertGetId([
            'source_connection_id' => $source2,
            'import_log_id' => $log1,
            'external_id' => 'PRJ002',
            'original_name' => 'Sistem Inventory A-1 (Update)',
            'normalized_name' => 'sistem inventory a 1 update',
            'metadata' => json_encode(['value' => 150000000]),
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        $proj3 = DB::table('imported_projects')->insertGetId([
            'source_connection_id' => $source2,
            'import_log_id' => $log1,
            'external_id' => 'PRJ003',
            'original_name' => 'Implementasi ERP SAP Finance',
            'normalized_name' => 'implementasi erp sap finance',
            'metadata' => json_encode(['value' => 850000000]),
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        $proj4 = DB::table('imported_projects')->insertGetId([
            'source_connection_id' => $source2,
            'import_log_id' => $log1,
            'external_id' => 'PRJ004',
            'original_name' => 'SAP Finance ERP Implementation',
            'normalized_name' => 'sap finance erp implementation',
            'metadata' => json_encode(['value' => 845000000]),
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        $proj5 = DB::table('imported_projects')->insertGetId([
            'source_connection_id' => $source2,
            'import_log_id' => $log1,
            'external_id' => 'PRJ005',
            'original_name' => 'Dashboard Eksekutif UIN Jambi',
            'normalized_name' => 'dashboard eksekutif uin jambi',
            'metadata' => json_encode(['value' => 75000000]),
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        // Seed Duplicate Candidates
        DB::table('duplicate_candidates')->insert([
            [
                'import_log_id' => $log1,
                'project_a_id' => $proj1,
                'project_b_id' => $proj2,
                'similarity_score' => 0.8200,
                'confidence_level' => 'medium',
                'status' => 'pending',
                'ai_validation_status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'import_log_id' => $log1,
                'project_a_id' => $proj3,
                'project_b_id' => $proj4,
                'similarity_score' => 0.9100,
                'confidence_level' => 'high',
                'status' => 'pending',
                'ai_validation_status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);

        // 5. Seed physical warehouse tables
        DB::table('dim_customer')->delete();
        DB::table('dim_customer')->insert([
            ['customer_name' => 'Asrofi Binsar', 'email' => 'asrofibinsarwoto@gmail.com', 'country' => 'Indonesia', 'signup_date' => '2026-01-10', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_name' => 'Budi Santoso', 'email' => 'budi@corp.com', 'country' => 'Indonesia', 'signup_date' => '2026-02-15', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_name' => 'Alice Johnson', 'email' => 'alice.j@global.com', 'country' => 'United States', 'signup_date' => '2026-03-01', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_name' => 'Michael Smith', 'email' => 'm.smith@tech.com', 'country' => 'United Kingdom', 'signup_date' => '2026-03-12', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_name' => 'Yuki Tanaka', 'email' => 'yuki@tanaka.jp', 'country' => 'Japan', 'signup_date' => '2026-04-05', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_name' => 'Siti Aminah', 'email' => 'siti@domain.id', 'country' => 'Indonesia', 'signup_date' => '2026-04-18', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_name' => 'John Doe', 'email' => 'john.doe@email.com', 'country' => 'United States', 'signup_date' => '2026-04-20', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        DB::table('dim_product')->delete();
        DB::table('dim_product')->insert([
            ['product_name' => 'Cloud Data Architecture Setup', 'category' => 'Consulting', 'price' => 15000.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['product_name' => 'Enterprise AI Dashboard License', 'category' => 'Software', 'price' => 4500.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['product_name' => 'Automated ETL Pipeline Tool', 'category' => 'Software', 'price' => 2500.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['product_name' => 'Real-time Data Observability Plugin', 'category' => 'Software', 'price' => 1200.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['product_name' => 'Basic Data Warehouse Training', 'category' => 'Education', 'price' => 800.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        DB::table('fact_sales')->delete();
        DB::table('fact_sales')->insert([
            ['customer_id' => 1, 'product_id' => 1, 'quantity' => 1, 'amount' => 15000.00, 'sales_date' => '2026-05-01', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_id' => 1, 'product_id' => 3, 'quantity' => 2, 'amount' => 5000.00, 'sales_date' => '2026-05-15', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_id' => 2, 'product_id' => 2, 'quantity' => 3, 'amount' => 13500.00, 'sales_date' => '2026-05-03', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_id' => 3, 'product_id' => 1, 'quantity' => 1, 'amount' => 15000.00, 'sales_date' => '2026-05-10', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_id' => 4, 'product_id' => 3, 'quantity' => 1, 'amount' => 2500.00, 'sales_date' => '2026-05-12', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_id' => 5, 'product_id' => 5, 'quantity' => 10, 'amount' => 8000.00, 'sales_date' => '2026-05-20', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_id' => 6, 'product_id' => 4, 'quantity' => 5, 'amount' => 6000.00, 'sales_date' => '2026-05-22', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['customer_id' => 7, 'product_id' => 2, 'quantity' => 1, 'amount' => 4500.00, 'sales_date' => '2026-05-25', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        DB::table('fact_payment')->delete();
        DB::table('fact_payment')->insert([
            ['sales_id' => 1, 'payment_method' => 'Bank Transfer', 'payment_status' => 'Success', 'payment_date' => '2026-05-02', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sales_id' => 2, 'payment_method' => 'Credit Card', 'payment_status' => 'Success', 'payment_date' => '2026-05-15', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sales_id' => 3, 'payment_method' => 'Bank Transfer', 'payment_status' => 'Success', 'payment_date' => '2026-05-04', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sales_id' => 4, 'payment_method' => 'Credit Card', 'payment_status' => 'Success', 'payment_date' => '2026-05-11', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sales_id' => 5, 'payment_method' => 'Paypal', 'payment_status' => 'Success', 'payment_date' => '2026-05-12', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sales_id' => 6, 'payment_method' => 'Bank Transfer', 'payment_status' => 'Failed', 'payment_date' => '2026-05-20', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sales_id' => 7, 'payment_method' => 'Credit Card', 'payment_status' => 'Success', 'payment_date' => '2026-05-23', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sales_id' => 8, 'payment_method' => 'Paypal', 'payment_status' => 'Success', 'payment_date' => '2026-05-26', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        // 6. Seed warehouse tables metadata
        $tbl_sales = DB::table('warehouse_tables')->insertGetId([
            'name' => 'fact_sales',
            'row_count' => 8,
            'col_count' => 6,
            'source_system' => 'ERP System',
            'quality_score' => 94,
            'description' => 'Tabel fakta yang menyimpan data transaksi penjualan layanan software dan lisensi consulting perusahaan.',
            'dashboards_used' => json_encode(['Revenue Dashboard', 'Sales Analytics', 'Executive Summary Board']),
            'key_columns' => json_encode(['sales_id', 'customer_id', 'product_id', 'amount']),
            'business_owner' => 'Sales & Revenue Department',
            'last_refresh' => Carbon::now()->subHours(2),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $tbl_payment = DB::table('warehouse_tables')->insertGetId([
            'name' => 'fact_payment',
            'row_count' => 8,
            'col_count' => 5,
            'source_system' => 'Stripe/Gateway API',
            'quality_score' => 88,
            'description' => 'Tabel transaksi pembayaran dari gerbang pembayaran (Stripe, Paypal, Transfer Bank) yang memuat status sukses/gagal pembayaran.',
            'dashboards_used' => json_encode(['Financial Reporting Dashboard', 'AR Dashboard']),
            'key_columns' => json_encode(['payment_id', 'sales_id', 'payment_status']),
            'business_owner' => 'Finance & Accounting Department',
            'last_refresh' => Carbon::now()->subHours(2),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $tbl_customer = DB::table('warehouse_tables')->insertGetId([
            'name' => 'dim_customer',
            'row_count' => 7,
            'col_count' => 5,
            'source_system' => 'CRM System Salesforce',
            'quality_score' => 98,
            'description' => 'Tabel dimensi data pelanggan, memuat informasi negara asal, email, nama, serta tanggal pendaftaran awal.',
            'dashboards_used' => json_encode(['Customer Retention Analytics', 'CRM Reporting Hub']),
            'key_columns' => json_encode(['customer_id', 'customer_name', 'country']),
            'business_owner' => 'Marketing Team',
            'last_refresh' => Carbon::now()->subDays(1),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $tbl_product = DB::table('warehouse_tables')->insertGetId([
            'name' => 'dim_product',
            'row_count' => 5,
            'col_count' => 4,
            'source_system' => 'Product Catalog Service',
            'quality_score' => 100,
            'description' => 'Daftar produk, jenis kategori (Software, Consulting, Training), serta harga lisensi resmi.',
            'dashboards_used' => json_encode(['Product Profitability Dashboard', 'Sales Analytics']),
            'key_columns' => json_encode(['product_id', 'product_name', 'category']),
            'business_owner' => 'Product Management Team',
            'last_refresh' => Carbon::now()->subDays(3),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        // Seed Columns Schema metadata
        DB::table('warehouse_columns')->insert([
            // fact_sales
            ['table_id' => $tbl_sales, 'name' => 'sales_id', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 8, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_sales, 'name' => 'customer_id', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 7, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_sales, 'name' => 'product_id', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 5, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_sales, 'name' => 'quantity', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 4, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_sales, 'name' => 'amount', 'data_type' => 'DECIMAL(12,2)', 'is_nullable' => 'NO', 'distinct_count' => 7, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_sales, 'name' => 'sales_date', 'data_type' => 'DATE', 'is_nullable' => 'NO', 'distinct_count' => 6, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // fact_payment
            ['table_id' => $tbl_payment, 'name' => 'payment_id', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 8, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_payment, 'name' => 'sales_id', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 8, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_payment, 'name' => 'payment_method', 'data_type' => 'VARCHAR(100)', 'is_nullable' => 'NO', 'distinct_count' => 3, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_payment, 'name' => 'payment_status', 'data_type' => 'VARCHAR(50)', 'is_nullable' => 'NO', 'distinct_count' => 2, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_payment, 'name' => 'payment_date', 'data_type' => 'DATE', 'is_nullable' => 'NO', 'distinct_count' => 7, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // dim_customer
            ['table_id' => $tbl_customer, 'name' => 'customer_id', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 7, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_customer, 'name' => 'customer_name', 'data_type' => 'VARCHAR(255)', 'is_nullable' => 'NO', 'distinct_count' => 7, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_customer, 'name' => 'email', 'data_type' => 'VARCHAR(255)', 'is_nullable' => 'YES', 'distinct_count' => 7, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_customer, 'name' => 'country', 'data_type' => 'VARCHAR(100)', 'is_nullable' => 'NO', 'distinct_count' => 4, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_customer, 'name' => 'signup_date', 'data_type' => 'DATE', 'is_nullable' => 'NO', 'distinct_count' => 7, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // dim_product
            ['table_id' => $tbl_product, 'name' => 'product_id', 'data_type' => 'INTEGER', 'is_nullable' => 'NO', 'distinct_count' => 5, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_product, 'name' => 'product_name', 'data_type' => 'VARCHAR(255)', 'is_nullable' => 'NO', 'distinct_count' => 5, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_product, 'name' => 'category', 'data_type' => 'VARCHAR(100)', 'is_nullable' => 'NO', 'distinct_count' => 3, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['table_id' => $tbl_product, 'name' => 'price', 'data_type' => 'DECIMAL(12,2)', 'is_nullable' => 'NO', 'distinct_count' => 5, 'missing_percentage' => 0.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        // 7. Seed ETL Pipelines
        $pipe1 = DB::table('etl_pipelines')->insertGetId([
            'name' => 'Sales_Ingestion_Pipeline',
            'source_layer' => 'ERP Production DB',
            'target_layer' => 'Staging Layer',
            'frequency' => 'Hourly',
            'is_active' => 'active',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $pipe2 = DB::table('etl_pipelines')->insertGetId([
            'name' => 'Customer_Sync_Pipeline',
            'source_layer' => 'Salesforce CRM API',
            'target_layer' => 'Data Warehouse',
            'frequency' => 'Daily',
            'is_active' => 'active',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $pipe3 = DB::table('etl_pipelines')->insertGetId([
            'name' => 'Financials_Aggregation_Job',
            'source_layer' => 'Staging Layer',
            'target_layer' => 'Data Mart',
            'frequency' => 'Daily',
            'is_active' => 'active',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        // Seed ETL Job Runs
        DB::table('etl_job_runs')->insert([
            [
                'pipeline_id' => $pipe1,
                'status' => 'Success',
                'start_time' => Carbon::now()->subHours(2),
                'end_time' => Carbon::now()->subHours(1)->subMinutes(58),
                'duration_seconds' => 120,
                'rows_processed' => 250,
                'error_message' => null,
                'ai_failure_analysis' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'pipeline_id' => $pipe2,
                'status' => 'Success',
                'start_time' => Carbon::now()->subDays(1),
                'end_time' => Carbon::now()->subDays(1)->addMinutes(15),
                'duration_seconds' => 900,
                'rows_processed' => 1250,
                'error_message' => null,
                'ai_failure_analysis' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'pipeline_id' => $pipe3,
                'status' => 'Failed',
                'start_time' => Carbon::now()->subHours(4),
                'end_time' => Carbon::now()->subHours(3)->subMinutes(58),
                'duration_seconds' => 120,
                'rows_processed' => 0,
                'error_message' => "Database Connection Timeout on Target Host 'ClickHouse DW' at 10.22.41.98:8123. Connection reset by peer.",
                'ai_failure_analysis' => json_encode([
                    'root_cause' => 'Timeout koneksi ke ClickHouse DW. Host tujuan tidak merespons dalam 60 detik.',
                    'possibilities' => ['Database ClickHouse mati/restart', 'Kebijakan firewall memblokir port 8123', 'Overload jaringan pada cluster target'],
                    'impact' => 'Data penjualan finansial harian gagal dipindahkan ke Data Mart. Dashboard Financials tidak akan menunjukkan data terbaru hari ini.',
                    'recommendations' => ['Periksa apakah service ClickHouse berjalan di server tujuan', 'Validasi rute firewall dan port 8123 antar host', 'Jalankan ulang job secara manual setelah status host dipastikan UP.'],
                    'priority' => 'High'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);

        // 8. Seed DQ Recommendations
        DB::table('dq_recommendations')->insert([
            [
                'table_name' => 'fact_payment',
                'finding_type' => 'Duplicate Records',
                'finding_summary' => 'Terdeteksi 2 transaksi pembayaran dengan kode referensi Stripe yang identik namun diproses dua kali.',
                'business_impact' => 'Dapat menyebabkan pelaporan penjualan ganda (double-counting revenue) pada dashboard finansial sebesar Rp16.000.000.',
                'recommended_action' => 'Jalankan deduplikasi menggunakan referensi transaksi Stripe ID. Terapkan unique constraint pada kolom transaksi Stripe.',
                'priority_level' => 'High',
                'quality_score_impact' => 12,
                'is_resolved' => 'pending',
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1)
            ],
            [
                'table_name' => 'dim_customer',
                'finding_type' => 'Missing Values',
                'finding_summary' => 'Kolom email pada tabel dim_customer memiliki nilai kosong (null) sebanyak 4.2% dari total pelanggan.',
                'business_impact' => 'Tim Marketing tidak bisa mengirimkan buletin bulanan atau promosi tersegmentasi kepada pelanggan-pelanggan tersebut.',
                'recommended_action' => 'Tambahkan validasi input form pendaftaran email bersifat mandatory, lakukan backfill untuk email kosong menggunakan data CRM sekunder.',
                'priority_level' => 'Medium',
                'quality_score_impact' => 2,
                'is_resolved' => 'pending',
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1)
            ]
        ]);
    }
}
