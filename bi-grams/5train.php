<?php

class TextModel {
    private $chain = [];
    private $nGrams = 2; // Using bi-grams as default

    public function __construct($nGrams = 2) {
        $this->nGrams = $nGrams;
    }

    public function train($text) {
        $words = preg_split('/\s+/', strtolower(trim(preg_replace('/[^a-zA-Z\s]/', '', $text))));
        $nGrams = $this->generateNGrams($words, $this->nGrams);
        
        foreach ($nGrams as $nGram) {
            $prefix = implode(' ', array_slice($nGram, 0, -1));
            $nextWord = end($nGram);
            
            if (!isset($this->chain[$prefix])) {
                $this->chain[$prefix] = [];
            }
            if (!isset($this->chain[$prefix][$nextWord])) {
                $this->chain[$prefix][$nextWord] = 0;
            }
            $this->chain[$prefix][$nextWord]++;
        }
    }

    public function generate($length = 100, $startWord = null) {
        $output = '';
        if ($startWord === null || !isset($this->chain[$startWord])) {
            $prefix = array_rand($this->chain);
        } else {
            $prefix = $startWord;
        }

        $output .= $prefix . ' ';

        for ($i = 0; $i < $length; $i++) {
            if (!isset($this->chain[$prefix])) {
                break;
            }
            $nextWords = $this->chain[$prefix];
            $word = $this->chooseNextWord($nextWords);
            $output .= $word . ' ';
            $prefix = $this->getNextPrefix($prefix, $word);
        }
        return ucfirst(trim($output));
    }

    private function generateNGrams($words, $n) {
        $nGrams = [];
        for ($i = 0; $i < count($words) - $n + 1; $i++) {
            $nGrams[] = array_slice($words, $i, $n);
        }
        return $nGrams;
    }

    private function chooseNextWord($nextWords) {
        $total = array_sum($nextWords);
        $rand = mt_rand(1, $total);
        foreach ($nextWords as $word => $count) {
            $rand -= $count;
            if ($rand <= 0) {
                return $word;
            }
        }
    }

    private function getNextPrefix($prefix, $nextWord) {
        $words = explode(' ', $prefix);
        array_shift($words);
        $words[] = $nextWord;
        return implode(' ', $words);
    }

    public function saveModel($filename) {
        file_put_contents($filename, serialize($this->chain));
    }

    public function loadModel($filename) {
        $this->chain = unserialize(file_get_contents($filename));
    }

public function getTrainedData() {
  return $this->chain;
}
}

// Create a new instance of the TextModel class
$model = new TextModel(2); // Using bi-grams

// Check for command line arguments
if ($argc < 2) {
    echo "Usage: php script.php [train|generate|save|load] [args]\n";
    exit(1);
}

// Parse command line arguments
$command = $argv[1];
$args = array_slice($argv, 2);

// Execute the appropriate command
switch ($command) {
    case 'train':
        if (empty($args)) {
            echo "Error: Missing text data for training.\n";
            exit(1);
        }
        $textData = implode(' ', $args);
        $model->train($textData);
        $model->saveModel('trained_model.txt'); // Save the trained model immediately after training
        echo "Model trained and saved successfully.\n";
        break;
    case 'generate':
        if (empty($args) || count($args) < 2) {
            echo "Error: Missing filename and/or starting word for generating text.\n";
            exit(1);
        }
        $filename = $args[0];
        $startWord = $args[1];
        $model->loadModel($filename);
        $length = isset($args[2]) ? (int)$args[2] : 100;
        echo $model->generate($length, $startWord) . "\n";
        break;
    case 'save':
        if (empty($args)) {
            echo "Error: Missing filename for saving the model.\n";
            exit(1);
        }
        $filename = $args[0];
        $model->saveModel($filename);
        echo "Model saved to $filename.\n";
        break;
    case 'load':
        if (empty($args)) {
            echo "Error: Missing filename for loading the model.\n";
            exit(1);
        }
        $filename = $args[0];
        $model->loadModel($filename);
        echo "Model loaded from $filename.\n";
var_dump($model->getTrainedData());
        break;
    default:
        echo "Error: Invalid command.\n";
        exit(1);
}
?>
