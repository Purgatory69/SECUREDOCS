<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchemaController extends Controller
{
    /**
     * Return the current database schema (simplified: table names and column datatypes only) as JSON.
     */
    public function get(): JsonResponse
    {
        try {
            $sql = <<<'SQL'
SELECT json_build_object('tables', json_agg(json_build_object(
  'name', t.table_name,
  'columns', cols.columns
) ORDER BY t.table_name))::text AS schema_data
FROM information_schema.tables t
LEFT JOIN (
  SELECT c.table_name,
         json_agg(json_build_object(
           'name', c.column_name,
           'type', CASE 
             WHEN c.data_type = 'character varying' THEN 'VARCHAR'
             WHEN c.data_type = 'bigint' THEN 'BIGINT'
             WHEN c.data_type = 'integer' THEN 'INTEGER'
             WHEN c.data_type = 'boolean' THEN 'BOOLEAN'
             WHEN c.data_type = 'timestamp with time zone' THEN 'TIMESTAMP'
             WHEN c.data_type = 'timestamp without time zone' THEN 'TIMESTAMP'
             WHEN c.data_type = 'text' THEN 'TEXT'
             WHEN c.data_type = 'json' THEN 'JSON'
             WHEN c.data_type = 'jsonb' THEN 'JSONB'
             WHEN c.data_type = 'uuid' THEN 'UUID'
             WHEN c.data_type = 'numeric' THEN 'NUMERIC'
             WHEN c.data_type = 'real' THEN 'REAL'
             WHEN c.data_type = 'double precision' THEN 'DOUBLE'
             WHEN c.data_type = 'date' THEN 'DATE'
             WHEN c.data_type = 'time without time zone' THEN 'TIME'
             WHEN c.data_type = 'bytea' THEN 'BYTEA'
             ELSE UPPER(c.data_type)
           END
         ) ORDER BY c.ordinal_position) AS columns
  FROM information_schema.columns c
  WHERE c.table_schema = 'public'
  GROUP BY c.table_name
) cols ON cols.table_name = t.table_name
WHERE t.table_schema = 'public' AND t.table_type = 'BASE TABLE';
SQL;

            $rows = DB::select($sql);
            if (!$rows || !isset($rows[0]->schema_data)) {
                return response()->json(['tables' => []]);
            }
            
            $json = json_decode($rows[0]->schema_data, true);
            if ($json === null) {
                Log::warning('SchemaController: JSON decode returned null, returning empty tables.');
                return response()->json(['tables' => []]);
            }
            
            return response()->json($json);
        } catch (\Throwable $e) {
            Log::error('SchemaController error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'error' => 'Failed to load schema',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
