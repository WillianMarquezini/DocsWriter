 <?php

    class Dictionary
    {
        public array $translated = [];
        public function translate(array $pdfPages)
        {
            foreach ($pdfPages as $page => $teste) {
                // var_dump($teste[1]);exit;
                if (isset($teste[1])) {
                    $this->translated['letters'][] = $teste[1];
                }
            }
            var_dump($this->translated);
        }
    }
