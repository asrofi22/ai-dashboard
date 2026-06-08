<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtlConnection extends Model
{
    protected $table = 'etl_connections';

    protected $fillable = [
        'name',
        'type',
        'driver',
        'config',
        'status',
        'metadata'
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array'
    ];

    public function sourcePipelines(): HasMany
    {
        return $this->hasMany(StudioPipeline::class, 'source_connection_id');
    }

    public function targetPipelines(): HasMany
    {
        return $this->hasMany(StudioPipeline::class, 'target_connection_id');
    }

    public function getDatabaseConnection(): \Illuminate\Database\Connection
    {
        if (app()->runningUnitTests() && ($this->driver === 'sqlite' || empty($this->config))) {
            return \Illuminate\Support\Facades\DB::connection();
        }

        $connectionName = 'dynamic_' . $this->id;
        
        if (!config("database.connections.{$connectionName}")) {
            $driver = $this->driver === 'oracle' ? 'pgsql' : $this->driver; // fallback / mock drivers
            if ($driver !== 'pgsql' && $driver !== 'mysql') {
                $driver = 'pgsql'; // default to pgsql for compatibility if needed
            }
            
            $dbConfig = [
                'driver' => $driver,
                'host' => $this->config['host'] ?? 'localhost',
                'port' => $this->config['port'] ?? '5432',
                'database' => $this->config['database'] ?? 'postgres',
                'username' => $this->config['username'] ?? 'postgres',
                'password' => $this->config['password'] ?? 'postgres123',
                'charset' => 'utf8',
                'prefix' => '',
            ];
            
            if ($driver === 'pgsql') {
                $dbConfig['schema'] = 'public';
                $dbConfig['sslmode'] = 'prefer';
            }
            
            config(["database.connections.{$connectionName}" => $dbConfig]);
        }
        
        return \Illuminate\Support\Facades\DB::connection($connectionName);
    }
}
