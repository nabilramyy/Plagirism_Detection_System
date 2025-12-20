<?php
// app/Services/PlagiarismService.php

class PlagiarismService
{
    /**
     * Called from SubmissionController::checkPlagiarism()
     * Returns: ['plagiarised', 'exact', 'partial', 'matchingWords']
     */
       public function check(string $text, array $existingSubmissions): array
    {
        // 0) Fast path: exact match
        $normalizedNew = trim(mb_strtolower($text, 'UTF-8'));
        foreach ($existingSubmissions as $sub) {
            $existingText = trim(mb_strtolower($sub['text_content'] ?? '', 'UTF-8'));
            if ($existingText !== '' && $existingText === $normalizedNew) {
                return [
                    'plagiarised'   => 100,
                    'exact'         => 100,
                    'partial'       => 100,
                    'matchingWords' => $this->tokenize($text),
                ];
            }
        }

        // If no previous submissions -> nothing to compare
        if (empty($existingSubmissions)) {
            return [
                'plagiarised'   => 0,
                'exact'         => 0,
                'partial'       => 0,
                'matchingWords' => [],
            ];
        }

        // 1) Prepare payload for Python SVC service
        $payload = [
            'text'    => $text,
            'existing'=> array_map(function ($row) {
                return [
                    'id'   => $row['id'] ?? null,
                    'text' => $row['text_content'] ?? '',
                ];
            }, $existingSubmissions),
        ];

        // 2) Call the Python ML API
        $ch = curl_init('http://127.0.0.1:5001/predict_plagiarism');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 5,
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            curl_close($ch);
            // Fallback if ML service is down
            return [
                'plagiarised'   => 0,
                'exact'         => 0,
                'partial'       => 0,
                'matchingWords' => [],
            ];
        }
        curl_close($ch);

        $data = json_decode($resp, true);
        $maxProb = $data['max_prob'] ?? 0.0;
        $avgProb = $data['avg_prob'] ?? 0.0;

        // 3) Convert probabilities (0–1) to percentages
        $plagiarisedPct = (int)round($maxProb * 100);
        $exactPct       = $plagiarisedPct >= 90 ? $plagiarisedPct : max(0, $plagiarisedPct - 20);
        $partialPct     = (int)round($avgProb * 100);

        // 4) Use your existing matchingWords logic
        $matchingWords = $this->extractMatchingWords(
            $this->tokenize($text),
            array_map(function ($row) {
                return $this->tokenize($row['text_content'] ?? '');
            }, $existingSubmissions)
        );

        return [
            'plagiarised'   => $plagiarisedPct,
            'exact'         => $exactPct,
            'partial'       => $partialPct,
            'matchingWords' => $matchingWords,
        ];
    }
    /**
     * Tokenizer: lowercase, keep letters + spaces, split on whitespace.
     */
    protected function tokenize(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-zA-Z\s]+/u', ' ', $text);
        $parts = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return $parts ?: [];
    }

    /**
     * TF-IDF sparse vector: index => value.
     */
    protected function tfidfVector(array $tokens, array $vocab, array $df, int $N): array
    {
        $tf = [];
        foreach ($tokens as $w) {
            $tf[$w] = ($tf[$w] ?? 0) + 1;
        }

        $vec   = [];
        $total = count($tokens) ?: 1;
        foreach ($tf as $w => $count) {
            if (!isset($vocab[$w])) {
                continue;
            }
            $index = $vocab[$w];
            $tfVal = $count / $total;
            $idf   = log(($N + 1) / (($df[$w] ?? 0) + 1)); // smoothed IDF
            $score = $tfVal * $idf;
            if ($score != 0.0) {
                $vec[$index] = $score;
            }
        }

        return $vec;
    }

    /**
     * Cosine similarity between two sparse vectors.
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        foreach ($a as $i => $v) {
            if (isset($b[$i])) {
                $dot += $v * $b[$i];
            }
        }

        $normA = 0.0;
        foreach ($a as $v) {
            $normA += $v * $v;
        }

        $normB = 0.0;
        foreach ($b as $v) {
            $normB += $v * $v;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Find some overlapping words between new text and all other docs.
     */
    protected function extractMatchingWords(array $queryTokens, array $otherDocsTokens, int $limit = 20): array
    {
        $querySet = array_count_values($queryTokens);
        $matches  = [];

        foreach ($otherDocsTokens as $docTokens) {
            foreach ($docTokens as $w) {
                if (isset($querySet[$w])) {
                    $matches[$w] = true;
                }
            }
        }

        $words = array_keys($matches);

        // Longer words first
        usort($words, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        return array_slice($words, 0, $limit);
    }
}
