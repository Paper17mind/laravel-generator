<?php

namespace app;
require_once __DIR__ . '/Query.php';
require_once __DIR__ . '/Importer.php';
use app\DB;
use app\Importer;

class Handler extends DB
{
    public function process(
        $data,
        $method,
        $table,
        $id = 'id',
        $child = null,
        $foreign = null,
        $where = null,
        $idParam = null
    ) {
        $input = $data;
        $fill = array_map(function ($x) {
            return is_integer($x) ? $x : "'$x'";
        }, array_values($input));

        $columns = "'" . implode("' ,'", array_keys($input)) . "'";
        $values = implode(' ,', array_values($fill));

        if ($method === 'post') {
            return $this->insert($table, $columns, $values);
        } elseif ($method === 'update') {
            return $this->update($table, $data['id'], $columns, $values);
        } elseif ($method === 'delete') {
            return $this->destroy($table, $data['id']);
        } elseif ($method === 'join') {
            return $this->join($table, $child, $foreign, $id, $where, $idParam);
        } else {
            return $this->get($table);
        }
    }
    // manual
    function query($q)
    {
        return $this->cmd($q);
    }
    function execute($q)
    {
        return $this->runQuery($q);
    }
    function create($id, $wizardData = null)
    {
        return $this->generate($id, $wizardData);
    }
    function createCols($id, $wizardData = null)
    {
        return $this->generateCols($id, $wizardData);
    }
    function preview($data, $projectId)
    {
        return $this->getPreview($data, $projectId);
    }
    function importData($data)
    {
        $importer = new Importer($this->db);
        return json_encode($importer->import($data));
    }
    function askAI($prompt, $framework = 'Laravel', $history = [])
    {
        $apiKey = getenv('GROQ_API_KEY') ?: 'YOUR_GROQ_API_KEY';
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        
        $messages = [
            [
                'role' => 'system',
                'content' => "Kamu adalah AI Database Architect. Tugasmu membantu user merancang skema database untuk project yang menggunakan framework $framework. 
                PENTING: Jika user meminta saran tabel, berikan penjelasan singkat di awal, lalu berikan skema dalam format JSON di dalam blok kode triple backtick (```json). 

                Gunakan Bahasa Inggris untuk penamaan tabel dan kolom (snake_case).

                Format JSON harus seperti ini:
                {
                \"tables\": [
                    {
                    \"name\": \"table_name\",
                    \"columns\": [
                        {\"name\": \"id\", \"typeData\": \"bigInteger\"},
                        {\"name\": \"column_name\", \"typeData\": \"string\", \"size\": \"255\"}
                    ]
                    }
                ]
                }

                Gunakan tipe data yang umum dan bisa digenerate: string, integer, bigInteger, text, date, timestamp, boolean, decimal."
            ]
        ];

        // Add history
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content
            ];
        }

        // Add current prompt
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $data = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => $messages,
            'temperature' => 0.7
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return json_encode(['error' => 'cURL Error: ' . $err]);
        }
        return $response;
    }
}
