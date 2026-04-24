<?php

namespace app;

class Importer
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function import($data)
    {
        $name = $data->name ?? 'Imported Project';
        $type = $data->type ?? 'json';
        $content = $data->content ?? '';

        if (empty($content)) {
            return ['success' => false, 'message' => 'Empty content'];
        }

        if (isset($data->project_id) && !empty($data->project_id)) {
            $projectId = $data->project_id;
        } else {
            $nameEscaped = $this->escape($name);
            $this->db->exec("INSERT INTO projects (name) VALUES ('$nameEscaped')");
            $projectId = $this->db->lastInsertRowID();
        }

        try {
            $result = $this->parseJson($content, $projectId);
            if ($result['success']) {
                $this->autoDetectRelations($projectId);
            }
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function parseJson($json, $projectId)
    {
        $data = json_decode($json, true);
        if (!$data) {
            return ['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()];
        }

        if (!isset($data['tables']) || empty($data['tables'])) {
            return ['success' => false, 'message' => 'No tables found in JSON'];
        }

        foreach ($data['tables'] as $table) {
            $tableName = $this->escape($table['name'] ?? 'unnamed');
            $tableComments = $this->escape($table['comments'] ?? '');

            // --- SMART UPSERT LOGIC ---
            // 1. Check if table with same name exists in THIS project
            $existing = $this->db->querySingle("SELECT id FROM tables WHERE name = '$tableName' AND project_id = $projectId");
            if ($existing) {
                // 2. Delete old columns
                $this->db->exec("DELETE FROM kolom WHERE table_id = $existing");
                // 3. Delete old table record
                $this->db->exec("DELETE FROM tables WHERE id = $existing");
            }

            $this->db->exec("INSERT INTO tables (name, project_id) VALUES ('$tableName', $projectId)");
            $tableId = $this->db->lastInsertRowID();

            if (!empty($table['columns']) && is_array($table['columns'])) {
                foreach ($table['columns'] as $col) {
                    $colName   = $this->escape($col['name'] ?? '');
                    $typeData  = $this->escape($col['typeData'] ?? 'string');
                    $size      = $this->escape($col['size'] ?? '');
                    $relasi    = $this->escape($col['relasi'] ?? '');

                    if (empty($colName)) continue;

                    $stmt = $this->db->prepare("INSERT INTO kolom (table_id, name, typeData, size, relasi) VALUES (:tid, :name, :type, :size, :relasi)");
                    $stmt->bindValue(':tid', $tableId, SQLITE3_INTEGER);
                    $stmt->bindValue(':name', $colName, SQLITE3_TEXT);
                    $stmt->bindValue(':type', $typeData, SQLITE3_TEXT);
                    $stmt->bindValue(':size', $size, SQLITE3_TEXT);
                    $stmt->bindValue(':relasi', $relasi, SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
        }

        return ['success' => true, 'projectId' => $projectId];
    }

    private function autoDetectRelations($projectId)
    {
        $tablesRes = $this->db->query("SELECT id, name FROM tables WHERE project_id = $projectId");
        $allTables = [];
        while ($row = $tablesRes->fetchArray(SQLITE3_ASSOC)) {
            $allTables[$row['name']] = $row['id'];
        }

        foreach ($allTables as $tableName => $tableId) {
            $colsRes = $this->db->query("SELECT id, name FROM kolom WHERE table_id = $tableId");
            while ($col = $colsRes->fetchArray(SQLITE3_ASSOC)) {
                $colName = strtolower($col['name']);
                if (preg_match('/^(\w+)_id$/', $colName, $matches)) {
                    $targetBase = $matches[1];
                    $possibleTargets = [$targetBase, $targetBase . 's', $targetBase . 'es'];
                    foreach ($possibleTargets as $targetName) {
                        if (isset($allTables[$targetName])) {
                            $relation = $targetName . ':id';
                            $stmt = $this->db->prepare("UPDATE kolom SET relasi = :rel WHERE id = :id");
                            $stmt->bindValue(':rel', $relation, SQLITE3_TEXT);
                            $stmt->bindValue(':id', $col['id'], SQLITE3_INTEGER);
                            $stmt->execute();
                            break;
                        }
                    }
                }
            }
        }
    }

    private function escape($string)
    {
        return \SQLite3::escapeString((string)$string);
    }
}
