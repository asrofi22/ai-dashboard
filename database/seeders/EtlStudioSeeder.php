<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EtlConnection;
use App\Models\StudioPipeline;
use App\Models\StudioPipelineRun;
use Carbon\Carbon;

class EtlStudioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Connections
        $conn_pg_erp = EtlConnection::create([
            'name' => 'PostgreSQL ERP',
            'type' => 'Database',
            'driver' => 'pgsql',
            'config' => [
                'host' => 'localhost',
                'port' => '5432',
                'database' => 'erp_prod',
                'username' => 'postgres'
            ],
            'status' => 'active',
            'metadata' => [
                'tables' => [
                    ['name' => 'users', 'type' => 'Table', 'row_count' => 1250, 'columns' => ['id', 'name', 'email', 'status', 'created_at']],
                    ['name' => 'orders', 'type' => 'Table', 'row_count' => 8420, 'columns' => ['order_id', 'user_id', 'amount', 'status', 'order_date']],
                    ['name' => 'transactions', 'type' => 'Table', 'row_count' => 8420, 'columns' => ['txn_id', 'order_id', 'payment_method', 'status', 'txn_date']]
                ],
                'views' => [
                    ['name' => 'active_users_v', 'columns' => ['id', 'name', 'email']]
                ]
            ]
        ]);

        $conn_oracle = EtlConnection::create([
            'name' => 'Oracle Finance ERP',
            'type' => 'Database',
            'driver' => 'oracle',
            'config' => [
                'host' => '10.15.2.41',
                'port' => '1521',
                'database' => 'FINPROD',
                'username' => 'apps'
            ],
            'status' => 'active',
            'metadata' => [
                'tables' => [
                    ['name' => 'customers_raw', 'type' => 'Table', 'row_count' => 1500, 'columns' => ['customer_id', 'cust_name', 'email_address', 'phone_no', 'country_code', 'signup_dt']],
                    ['name' => 'ap_invoices', 'type' => 'Table', 'row_count' => 5400, 'columns' => ['invoice_id', 'vendor_id', 'amount', 'invoice_date', 'status']],
                    ['name' => 'gl_balances', 'type' => 'Table', 'row_count' => 12000, 'columns' => ['ccid', 'period_name', 'entered_dr', 'entered_cr', 'net_amount']]
                ],
                'views' => []
            ]
        ]);

        $conn_sharepoint = EtlConnection::create([
            'name' => 'SharePoint Sales Repo',
            'type' => 'Collaboration Platform',
            'driver' => 'sharepoint',
            'config' => [
                'folder_url' => 'https://enterprise.sharepoint.com/teams/sales/Documents'
            ],
            'status' => 'active',
            'metadata' => [
                'folders' => [
                    ['name' => '/Shared Documents/Sales/Exports/', 'files_count' => 2],
                    ['name' => '/Shared Documents/Marketing/', 'files_count' => 1]
                ],
                'files' => [
                    ['name' => 'regional_sales_2026.xlsx', 'folder' => '/Shared Documents/Sales/Exports/', 'size' => '4.2 MB', 'last_modified' => '2026-05-28 14:22:00'],
                    ['name' => 'leads_export.csv', 'folder' => '/Shared Documents/Sales/Exports/', 'size' => '850 KB', 'last_modified' => '2026-06-02 09:10:00']
                ]
            ]
        ]);

        $conn_mysql = EtlConnection::create([
            'name' => 'MySQL Marketing DB',
            'type' => 'Database',
            'driver' => 'mysql',
            'config' => [
                'host' => '10.15.5.99',
                'port' => '3306',
                'database' => 'marketing',
                'username' => 'mkt_user'
            ],
            'status' => 'active',
            'metadata' => [
                'tables' => [
                    ['name' => 'leads_table', 'type' => 'Table', 'row_count' => 2500, 'columns' => ['lead_id', 'full_name', 'email', 'source', 'status', 'capture_date']],
                    ['name' => 'campaigns', 'type' => 'Table', 'row_count' => 84, 'columns' => ['campaign_id', 'campaign_name', 'budget', 'clicks', 'conversions']]
                ],
                'views' => []
            ]
        ]);

        $conn_dwh = EtlConnection::create([
            'name' => 'PostgreSQL Data Warehouse',
            'type' => 'Database',
            'driver' => 'pgsql',
            'config' => [
                'host' => 'localhost',
                'port' => '5432',
                'database' => 'postgres',
                'username' => 'postgres'
            ],
            'status' => 'active',
            'metadata' => [
                'tables' => [
                    ['name' => 'dim_customer', 'type' => 'Table', 'row_count' => 7, 'columns' => ['customer_id', 'customer_name', 'email', 'country', 'signup_date']],
                    ['name' => 'dim_product', 'type' => 'Table', 'row_count' => 5, 'columns' => ['product_id', 'product_name', 'category', 'price']],
                    ['name' => 'fact_sales', 'type' => 'Table', 'row_count' => 8, 'columns' => ['sales_id', 'customer_id', 'product_id', 'quantity', 'amount', 'sales_date']],
                    ['name' => 'fact_payment', 'type' => 'Table', 'row_count' => 8, 'columns' => ['payment_id', 'sales_id', 'payment_method', 'payment_status', 'payment_date']]
                ],
                'views' => []
            ]
        ]);

        // 2. Seed Pipelines
        $pipe1 = StudioPipeline::create([
            'name' => 'Sync_ERP_Customers_Job',
            'source_connection_id' => $conn_oracle->id,
            'source_table' => 'customers_raw',
            'transformations' => ['Remove Duplicate', 'Remove Null', 'Trim Text', 'Uppercase'],
            'target_connection_id' => $conn_dwh->id,
            'target_table' => 'dim_customer',
            'column_mapping' => [
                ['source' => 'customer_id', 'target' => 'customer_id'],
                ['source' => 'cust_name', 'target' => 'customer_name'],
                ['source' => 'email_address', 'target' => 'email'],
                ['source' => 'country_code', 'target' => 'country'],
                ['source' => 'signup_dt', 'target' => 'signup_date']
            ],
            'is_active' => 'active'
        ]);

        $pipe2 = StudioPipeline::create([
            'name' => 'Sync_Sharepoint_Sales_Leads',
            'source_connection_id' => $conn_sharepoint->id,
            'source_table' => 'leads_export.csv',
            'transformations' => ['Remove Null', 'Lowercase'],
            'target_connection_id' => $conn_dwh->id,
            'target_table' => 'dim_customer',
            'column_mapping' => [
                ['source' => 'lead_id', 'target' => 'customer_id'],
                ['source' => 'full_name', 'target' => 'customer_name'],
                ['source' => 'email', 'target' => 'email'],
                ['source' => 'country', 'target' => 'country']
            ],
            'is_active' => 'active'
        ]);

        // 3. Seed Runs
        // Success run for pipe1
        StudioPipelineRun::create([
            'pipeline_id' => $pipe1->id,
            'status' => 'Success',
            'start_time' => Carbon::now()->subHours(10),
            'end_time' => Carbon::now()->subHours(10)->addSeconds(42),
            'duration_seconds' => 42,
            'rows_read' => 1500,
            'rows_written' => 1485,
            'rows_rejected' => 15,
            'execution_logs' => "INFO - [10:00:00] Memulai pipeline Sync_ERP_Customers_Job...\n" .
                                "INFO - [10:00:02] Menghubungkan ke Oracle Finance ERP di 10.15.2.41:1521...\n" .
                                "INFO - [10:00:05] Berhasil mengekstrak 1500 baris dari customers_raw.\n" .
                                "INFO - [10:00:10] Menjalankan transformasi: 'Remove Duplicate' berdasarkan field email_address.\n" .
                                "INFO - [10:00:18] Menjalankan transformasi: 'Remove Null' pada field cust_name.\n" .
                                "INFO - [10:00:25] Menjalankan transformasi: 'Trim Text' & 'Uppercase'.\n" .
                                "INFO - [10:00:30] Menghubungkan ke target PostgreSQL Data Warehouse...\n" .
                                "INFO - [10:00:32] Mulai menulis data ke dim_customer...\n" .
                                "INFO - [10:00:40] Berhasil menulis 1485 baris. 15 baris terabaikan/ditolak.\n" .
                                "INFO - [10:00:42] Pipeline Sync_ERP_Customers_Job sukses diselesaikan.",
            'error_log' => null,
            'ai_failure_analysis' => null
        ]);

        // Failed run for pipe1
        StudioPipelineRun::create([
            'pipeline_id' => $pipe1->id,
            'status' => 'Failed',
            'start_time' => Carbon::now()->subHours(4),
            'end_time' => Carbon::now()->subHours(4)->addSeconds(12),
            'duration_seconds' => 12,
            'rows_read' => 0,
            'rows_written' => 0,
            'rows_rejected' => 0,
            'execution_logs' => "INFO - [16:00:00] Memulai pipeline Sync_ERP_Customers_Job...\n" .
                                "INFO - [16:00:02] Menghubungkan ke Oracle Finance ERP di 10.15.2.41:1521...\n" .
                                "ERROR - [16:00:12] Failed to establish connection to Oracle Host 10.15.2.41:1521. Connection refused by listener: ORA-12541: TNS:no listener.\n" .
                                "ERROR - [16:00:12] Eksekusi pipeline Sync_ERP_Customers_Job gagal ditangguhkan.",
            'error_log' => "ORA-12541: TNS:no listener. Failed to establish connection to Oracle Host 10.15.2.41:1521.",
            'ai_failure_analysis' => [
                'root_cause' => 'TNS Listener database Oracle di host target mati atau tidak mendengarkan port 1521.',
                'possibilities' => [
                    'Database server Oracle dimatikan untuk pemeliharaan rutin.',
                    'Service TNSLSNR di server Oracle dihentikan.',
                    'IP host Oracle 10.15.2.41 diubah atau mengalami kendala rute jaringan.'
                ],
                'impact' => 'Data profil pelanggan ERP tidak terbarui di gudang data DWH, menunda sinkronisasi metrik pemasaran harian.',
                'recommendations' => [
                    'Hubungi DB Admin Oracle untuk memeriksa status listener database.',
                    'Lakukan ping dan telnet ke IP 10.15.2.41 port 1521 dari server Laravel.',
                    'Jalankan ulang ETL Studio Run secara manual setelah listener dipastikan aktif kembali.'
                ],
                'priority' => 'High'
            ]
        ]);

        // Success run for pipe2
        StudioPipelineRun::create([
            'pipeline_id' => $pipe2->id,
            'status' => 'Success',
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now()->subHours(8)->addSeconds(28),
            'duration_seconds' => 28,
            'rows_read' => 200,
            'rows_written' => 198,
            'rows_rejected' => 2,
            'execution_logs' => "INFO - [12:00:00] Memulai pipeline Sync_Sharepoint_Sales_Leads...\n" .
                                "INFO - [12:00:03] Mengunduh file leads_export.csv dari SharePoint Sales Repo...\n" .
                                "INFO - [12:00:10] Sukses mengunduh file leads_export.csv (850 KB).\n" .
                                "INFO - [12:00:12] Memulai pembacaan CSV. Mengekstrak 200 baris.\n" .
                                "INFO - [12:00:15] Menjalankan transformasi: 'Remove Null' pada email.\n" .
                                "INFO - [12:00:18] Menjalankan transformasi: 'Lowercase' pada email.\n" .
                                "INFO - [12:00:22] Menghubungkan ke PostgreSQL DWH target...\n" .
                                "INFO - [12:00:24] Menulis data ke dim_customer...\n" .
                                "INFO - [12:00:27] Berhasil menulis 198 baris. 2 baris terabaikan karena null.\n" .
                                "INFO - [12:00:28] Pipeline Sync_Sharepoint_Sales_Leads selesai.",
            'error_log' => null,
            'ai_failure_analysis' => null
        ]);
    }
}
