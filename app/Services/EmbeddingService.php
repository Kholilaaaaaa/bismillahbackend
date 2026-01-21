<?php

namespace App\Services;

class EmbeddingService
{
    /**
     * Hitung cosine similarity antara 2 vektor
     *
     * @param float[] $vectorA
     * @param float[] $vectorB
     * @return float
     */
    public static function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $length = min(count($vectorA), count($vectorB));

        if ($length === 0) {
            return 0.0;
        }

        for ($i = 0; $i < $length; $i++) {
            $a = (float) $vectorA[$i];
            $b = (float) $vectorB[$i];

            $dotProduct += $a * $b;
            $normA += $a ** 2;
            $normB += $b ** 2;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Ambil TOP-K embedding paling relevan
     *
     * @param float[] $queryVector
     * @param iterable $embeddings Array atau Collection dari model/array dengan 'embedding'
     * @param int $k
     * @return array
     */
    public static function topK(array $queryVector, iterable $embeddings, int $k = 3): array
    {
        $scores = [];

        foreach ($embeddings as $item) {
            // Dapatkan vektor
            if (is_object($item) && method_exists($item, 'getVector')) {
                $vector = $item->getVector();
            } elseif (is_array($item) && isset($item['embedding'])) {
                $vector = $item['embedding'];
            } else {
                continue; // skip jika tidak ada vektor
            }

            if (empty($vector)) {
                continue; // skip vektor kosong
            }

            $score = self::cosineSimilarity($queryVector, $vector);

            $scores[] = [
                'item' => $item,
                'score' => (float) $score,
            ];
        }

        // Urut dari paling relevan
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scores, 0, $k);
    }
}
