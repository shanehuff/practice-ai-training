<?php

class TextModel {
    private $chain = [];

    public function train($text) {
        $words = preg_split('/\s+/', strtolower(trim(preg_replace('/[^a-zA-Z\s]/', '', $text))));
        for ($i = 0; $i < count($words) - 1; $i++) {
            $word = $words[$i];
            $nextWord = $words[$i + 1];
            if (!isset($this->chain[$word])) {
                $this->chain[$word] = [];
            }
            if (!isset($this->chain[$word][$nextWord])) {
                $this->chain[$word][$nextWord] = 0;
            }
            $this->chain[$word][$nextWord]++;
        }
    }

    public function generate($length = 100) {
        $output = '';
        $word = array_rand($this->chain);
        for ($i = 0; $i < $length; $i++) {
            $output .= $word . ' ';
            if (!isset($this->chain[$word])) {
                break;
            }
            $nextWords = $this->chain[$word];
            $word = $this->chooseNextWord($nextWords);
        }
        return ucfirst(trim($output));
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
}

// Example usage
$model = new TextModel();
$text = "The quick brown fox jumps over the lazy dog. 
A quick brown fox jumps over the lazy dog in the park. 
In a faraway land, there lived a princess. 
Once upon a time, in a kingdom far far away, there was a brave knight.
The knight fought against the dragon and saved the kingdom.";
$model->train($text);
$generatedText = $model->generate(50);
echo $generatedText;
