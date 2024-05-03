<?php

class TextModel {
    private $bookContent;
    private $sentenceTokens;
    private $sentenceScores;

    public function __construct($bookContent = [], $sentenceTokens = [], $sentenceScores = []) {
        $this->bookContent = $bookContent;
        $this->sentenceTokens = $sentenceTokens;
        $this->sentenceScores = $sentenceScores;
    }

    public function findRelevantPassages($question) {
        // If sentence tokens and scores are not already calculated, calculate them
        if (empty($this->sentenceTokens) || empty($this->sentenceScores)) {
            $this->calculateSentenceTokensAndScores();
        }

        // Tokenize the question
        $questionTokens = array_unique(preg_split('/\s+/', strtolower($question)));

        // Find the most relevant passage to the question
        $maxScore = -1;
        $bestPassage = '';
        foreach ($this->bookContent as $index => $sentence) {
            // Calculate the score for this passage
            $score = 0;
            foreach ($questionTokens as $token) {
                if (in_array($token, $this->sentenceTokens[$index])) {
                    $score += $this->sentenceScores[$index];
                }
            }
            // Update the best passage if this one has a higher score
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestPassage = $sentence;
            }
        }

        // Return the most relevant passage
        return [$bestPassage];
    }

    private function calculateSentenceTokensAndScores() {
        // Split the book content into sentences
        $sentences = preg_split('/[.?!]/', $this->bookContent[0]);

        // Tokenize each sentence and count word frequencies
        foreach ($sentences as $sentence) {
            $tokens = preg_split('/\s+/', strtolower($sentence));
            $wordFreq = array_count_values($tokens);
            $totalWords = count($tokens);

            // Calculate TF-IDF scores for each sentence
            $score = 0;
            foreach ($wordFreq as $word => $freq) {
                // Term frequency (TF): how often a word appears in a sentence
                $tf = $freq / $totalWords;

                // Inverse Document Frequency (IDF): how rare the word is across all sentences
                $numSentencesWithWord = 0;
                foreach ($sentences as $s) {
                    if (stripos($s, $word) !== false) {
                        $numSentencesWithWord++;
                    }
                }
                $idf = log(count($sentences) / (1 + $numSentencesWithWord));

                // TF-IDF score for the word in the sentence
                $score += $tf * $idf;
            }

            // Save sentence tokens and score
            $this->sentenceTokens[] = $tokens;
            $this->sentenceScores[] = $score;
        }
        // Save book content
        $this->bookContent = $sentences;
    }

    public function generateAnswer($passages) {
        // For simplicity, just return the relevant passage.
        return implode(' ', $passages);
    }

    public function train($textData) {
        // Append the new text data to the existing book content.
        $this->bookContent[] = $textData;

        // Clear previously calculated sentence tokens and scores
        $this->sentenceTokens = [];
        $this->sentenceScores = [];
        $this->calculateSentenceTokensAndScores();
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
