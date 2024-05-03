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
}

// Create a new instance of the TextModel class
$model = new TextModel(2); // Using bi-grams

// Example text data for training
$textData = "
The quick brown fox jumps over the lazy dog. 
A quick brown fox jumps over the lazy dog in the park. 
In a faraway land, there lived a princess. 
Once upon a time, in a kingdom far far away, there was a brave knight.
The knight fought against the dragon and saved the kingdom.
";

// Train the model with the example text data
$model->train($textData);

// Check if there's input from the command line arguments
if ($argc > 1) {
    // Get user input from command line arguments
    $input = implode(' ', array_slice($argv, 1));
    
    // Generate text based on the user input
    $generatedText = $model->generate(50, $input); // Generate 50 words starting from the user input
    echo $generatedText . PHP_EOL;
} else {
    echo "Please provide input text as command line arguments." . PHP_EOL;
}
