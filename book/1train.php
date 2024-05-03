<?php

class TextModel {
    private $bookContent;

    public function __construct($bookContent) {
        $this->bookContent = $bookContent;
    }

    public function train($textData) {
        // Append the new text data to the existing book content
        $this->bookContent .= ' ' . $textData;
    }

    public function findRelevantPassages($question) {
        // Split the book content into sentences
        $sentences = preg_split('/[.?!]/', $this->bookContent);

        // Find the most relevant sentence to the question
        $relevantSentence = '';
        $maxSimilarity = -1;
        foreach ($sentences as $sentence) {
            $similarity = similar_text(strtolower($question), strtolower($sentence));
            if ($similarity > $maxSimilarity) {
                $maxSimilarity = $similarity;
                $relevantSentence = $sentence;
            }
        }

        // Return the relevant sentence
        return [$relevantSentence];
    }

    public function generateAnswer($passages) {
        // Just return the relevant passage for now
        return $passages[0];
    }

    public function saveModel($filename) {
        // Save the model to a file
        file_put_contents($filename, serialize($this->bookContent));
    }

    public function loadModel($filename) {
        // Load the model from a file
        $this->bookContent = unserialize(file_get_contents($filename));
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
