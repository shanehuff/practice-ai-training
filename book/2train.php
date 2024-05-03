<?php

class TextModel {
    private $bookContent;
    private $sentenceTokens;
    private $sentenceScores;

    public function __construct($bookContent, $sentenceTokens = [], $sentenceScores = []) {
        $this->bookContent = $bookContent;
        $this->sentenceTokens = $sentenceTokens;
        $this->sentenceScores = $sentenceScores;
    }

    public function findRelevantPassages($question) {
        // If sentence tokens and scores are not already calculated, calculate them
        if (empty($this->sentenceTokens) || empty($this->sentenceScores)) {
            $this->calculateSentenceTokensAndScores();
        }

        // Imagine each sentence in the book as a story chapter.
        $sentences = preg_split('/[.?!]/', $this->bookContent);

        // Imagine each word in the question and in the sentences as different Lego blocks.
        $questionTokens = array_unique(preg_split('/\s+/', strtolower($question)));

        // We find the sentences with the highest scores.
        // These sentences are the most relevant to the question.
        $relevantIndices = array_keys($this->sentenceScores, max($this->sentenceScores));
        $relevantPassages = array_intersect_key($sentences, array_flip($relevantIndices));

        // We return these relevant sentences as the answer.
        return $relevantPassages;
    }

    private function calculateSentenceTokensAndScores() {
        // Split the book content into sentences
        $sentences = preg_split('/[.?!]/', $this->bookContent);

        // Imagine each word in the sentences as different Lego blocks.
        foreach ($sentences as $sentence) {
            $sentenceTokens[] = array_unique(preg_split('/\s+/', strtolower($sentence)));
        }

        // We count how many times each word appears in each sentence.
        // Then, we give each word a score based on how rare it is across all sentences.
        // We multiply these scores together to get a score for each sentence.
        foreach ($sentenceTokens as $sentenceToken) {
            $score = 0;
            foreach ($questionTokens as $token) {
                $tf = array_count_values($sentenceToken)[$token] ?? 0; // Count how many times the word appears in the sentence
                $idf = log(count($sentenceTokens) / (1 + array_reduce($sentenceTokens, function ($carry, $item) use ($token) {
                    return $carry + (in_array($token, $item) ? 1 : 0);
                }, 0))); // Calculate how rare the word is across all sentences
                $score += $tf * $idf; // Multiply these values together to get the score for the sentence
            }
            $this->sentenceScores[] = $score;
        }
    }

    public function generateAnswer($passages) {
        // For simplicity, just return the relevant passage.
        return implode(' ', $passages);
    }

    public function train($textData) {
        // Append the new text data to the existing book content.
        $this->bookContent .= ' ' . $textData;

        // Clear previously calculated sentence tokens and scores
        $this->sentenceTokens = [];
        $this->sentenceScores = [];
    }

    public function saveModel($filename) {
        // Save the model to a file including book content, sentence tokens, and scores.
        file_put_contents($filename, serialize([
            'bookContent' => $this->bookContent,
            'sentenceTokens' => $this->sentenceTokens,
            'sentenceScores' => $this->sentenceScores
        ]));
    }

    public function loadModel($filename) {
        // Load the model from a file.
        $data = unserialize(file_get_contents($filename));
        $this->bookContent = $data['bookContent'];
        $this->sentenceTokens = $data['sentenceTokens'] ?? [];
        $this->sentenceScores = $data['sentenceScores'] ?? [];
    }
}

// Check for command line arguments
if ($argc < 2) {
    echo "Usage: php script.php [train|ask] [args]\n";
    exit(1);
}

// Parse command line arguments
$command = $argv[1];

// Create a new instance of the TextModel class
$model = new TextModel('');

// Execute the appropriate command
switch ($command) {
    case 'train':
        if ($argc < 3) {
            echo "Error: Missing text data for training.\n";
            exit(1);
        }
        $textData = implode(' ', array_slice($argv, 2));
        $model->loadModel('trained_model.txt'); // Load existing model if available
        $model->train($textData);
        $model->saveModel('trained_model.txt');
        echo "Model trained and saved successfully.\n";
        break;
    case 'ask':
        if ($argc < 3) {
            echo "Error: Missing question.\n";
            exit(1);
        }
        $question = implode(' ', array_slice($argv, 2));
        $model->loadModel('trained_model.txt');
        $passages = $model->findRelevantPassages($question);
        $answer = $model->generateAnswer($passages);
        echo "Answer: \n$answer\n";
        break;
    default:
        echo "Error: Invalid command.\n";
        exit(1);
}
?>
