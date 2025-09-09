<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchemaController extends Controller
{
    /**
     * Return the current database schema (tables, columns, PKs, FKs) as JSON.
     */
    public function get(): JsonResponse
    {
        try {
            $sql = <<<'SQL'
WITH cols AS (
  SELECT c.table_name,
         json_agg(json_build_object(
           'n', c.column_name,
           't', c.data_type
         ) ORDER BY c.ordinal_position) AS cols
  FROM information_schema.columns c
  WHERE c.table_schema = 'public'
  GROUP BY c.table_name
),
primary_keys AS (
  SELECT kcu.table_name,
         array_agg(kcu.column_name ORDER BY kcu.ordinal_position) AS pk
  FROM information_schema.table_constraints tc
  JOIN information_schema.key_column_usage kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
    AND tc.table_name = kcu.table_name
  WHERE tc.table_schema = 'public' AND tc.constraint_type = 'PRIMARY KEY'
  GROUP BY kcu.table_name
),
foreign_keys AS (
  SELECT tc.table_name AS st,
         json_agg(json_build_object(
           'sc', kcu.column_name,
           'tt', ccu.table_name,
           'tc', ccu.column_name
         ) ORDER BY kcu.ordinal_position) AS fks
  FROM information_schema.table_constraints tc
  JOIN information_schema.key_column_usage kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
  JOIN information_schema.constraint_column_usage ccu
    ON ccu.constraint_name = tc.constraint_name
    AND ccu.table_schema = tc.table_schema
  WHERE tc.table_schema = 'public' AND tc.constraint_type = 'FOREIGN KEY'
  GROUP BY tc.table_name
)
SELECT json_build_object('tables', json_agg(json_build_object(
  't', t.table_name,
  'c', COALESCE(cols.cols, '[]'::json),
  'pk', COALESCE(primary_keys.pk, ARRAY[]::text[]),
  'fk', COALESCE(foreign_keys.fks, '[]'::json)
) ORDER BY t.table_name))::text AS s
FROM information_schema.tables t
LEFT JOIN cols ON cols.table_name = t.table_name
LEFT JOIN primary_keys ON primary_keys.table_name = t.table_name
LEFT JOIN foreign_keys ON foreign_keys.st = t.table_name
WHERE t.table_schema = 'public' AND t.table_type = 'BASE TABLE';
SQL;

            $rows = DB::select($sql);
            if (!$rows || !isset($rows[0]->s)) {
                return response()->json(['tables' => []]);
            }
            $json = json_decode($rows[0]->s, true);
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
