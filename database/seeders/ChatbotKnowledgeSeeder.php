<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/chatbot/dataset_chatbot_gymZ.csv');

        if (!file_exists($path)) {
            $this->command->error("Dataset not found: {$path}");
            return;
        }

        $file = fopen($path, 'r');

        // ============================
        // READ CSV HEADER
        // ============================
        $header = fgetcsv($file);
        if (!$header) {
            $this->command->error("CSV header not found");
            fclose($file);
            return;
        }

        $this->command->info("CSV Header: " . implode(', ', $header));

        $count = 0;

        // ============================
        // LOOP CSV ROWS
        // ============================
        while (($row = fgetcsv($file)) !== false) {

            if (count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);

            $question = trim($data['question'] ?? '');
            $answer   = trim($data['answer'] ?? '');

            if (strlen($question) < 5 || strlen($answer) < 5) {
                continue;
            }

            DB::table('chatbot_knowledge')->insert([
                'question'   => $question,
                'answer'     => $answer,
                'source'     => 'csv',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $count++;
            $this->command->info("Seeded: {$question}");
        }

        fclose($file);

        $this->command->info("✅ Chatbot Knowledge Seeding Completed ({$count} rows)");
    }
}
