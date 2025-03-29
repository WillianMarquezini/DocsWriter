<?php
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

// Configurações avançadas
$config = [
    'input' => 'original.pdf',
    'output' => 'enhanced_pdf_reconstructor.php',
    'images' => [
        'directory' => 'pdf_images/',
        'quality' => 85,
        'format' => 'jpg'
    ],
    'cache' => [
        'enabled' => true,
        'directory' => 'pdf_cache/'
    ],
    'tables' => [
        'detection_threshold' => 5, // pixels
        'min_columns' => 2,
        'header_bg_color' => [240, 240, 240]
    ]
];

// Inicializar componentes
$parser = new Parser();
$imageManager = new ImageManager(new Driver());
$pdf = $parser->parseFile($config['input']);
$pages = $pdf->getPages();

// Preparar diretórios
@mkdir($config['images']['directory'], 0777, true);
if ($config['cache']['enabled']) {
    @mkdir($config['cache']['directory'], 0777, true);
}

// Verificar cache
$cacheFile = $config['cache']['directory'] . md5_file($config['input']) . '.json';
if ($config['cache']['enabled'] && file_exists($cacheFile)) {
    $elements = json_decode(file_get_contents($cacheFile), true);
} else {
    $elements = processPages($pages, $config, $imageManager);
    if ($config['cache']['enabled']) {
        file_put_contents($cacheFile, json_encode($elements));
    }
}

// Gerar código PHP
$phpCode = generatePHPCode($elements, $config);
file_put_contents($config['output'], $phpCode);

echo "PDF reconstruído com sucesso!\n";
echo "- " . count($elements['images']) . " imagens processadas\n";
echo "- " . count($elements['tables']) . " tabelas detectadas\n";
echo "- " . count($elements['toc']) . " itens no índice\n";

// Funções principais
function processPages($pages, $config, $imageManager)
{
    $elements = [
        'metadata' => [],
        'pages' => [],
        'images' => [],
        'tables' => [],
        'toc' => []
    ];

    foreach ($pages as $pageNumber => $page) {
        $details = $page->getDetails();

        $pageData = [
            'number' => $pageNumber + 1,
            'width' => $details['Page width'] ?? 595, // Valor padrão A4 em pontos se não encontrado
            'height' => $details['Page height'] ?? 842, // Valor padrão A4 em pontos se não encontrado
            'items' => []
        ];

        // Processar imagens (nova forma de extração)
        $content = $page->getTextArray();
        $elementsData = $page->getDataTm();

        foreach ($elementsData as $element) {
            if (isset($element['image']) && $element['image']) {
                try {
                    $imgData = processImage([
                        'data' => $element['image'],
                        'x' => $element['x'],
                        'y' => $element['y'],
                        'w' => $element['width'] ?? 100, // valor padrão caso não exista
                        'h' => $element['height'] ?? 100,
                        'ext' => 'jpg' // assumindo JPG como padrão
                    ], $pageNumber, $config, $imageManager);

                    $elements['images'][] = $imgData;
                    $pageData['items'][] = [
                        'type' => 'image',
                        'data' => $imgData
                    ];
                } catch (Exception $e) {
                    // Log do erro sem interromper o processamento
                    error_log("Erro ao processar imagem: " . $e->getMessage());
                }
            }
        }

        // Processar texto e tabelas
        $textData = $page->getDataTm();
        usort($textData, function ($a, $b) {
            return $b['y'] <=> $a['y'];
        });

        $columns = detectColumns($pageData['width'], $textData);
        $pageData['columns'] = $columns;

        $tables = detectTables($textData, $config['tables']);
        foreach ($tables as $table) {
            $tableId = count($elements['tables']);
            $elements['tables'][$tableId] = $table;
            $pageData['items'][] = [
                'type' => 'table',
                'data' => $tableId
            ];
        }

        // Processar outros elementos textuais
        foreach ($textData as $item) {
            if (!isPartOfTable($item, $tables)) {
                $style = detectTextStyle($item['text']);
                if ($style['size'] > 11) {
                    $elements['toc'][] = [
                        'title' => $item['text'],
                        'page' => $pageNumber + 1,
                        'y' => $item['y']
                    ];
                }

                $pageData['items'][] = [
                    'type' => 'text',
                    'data' => $item,
                    'style' => $style
                ];
            }
        }

        $elements['pages'][] = $pageData;
    }

    return $elements;
}

function generatePHPCode($elements, $config)
{
    $code = '<?php
require_once(\'tcpdf/tcpdf.php\');

class EnhancedPDF extends TCPDF {
    protected $imageMap = [];
    protected $toc = [];
    
    public function registerImage($key, $file) {
        $this->imageMap[$key] = $file;
    }
    
    public function addTOCItem($title, $page, $y) {
        $this->toc[] = [
            "title" => $title,
            "page" => $page,
            "y" => $y
        ];
    }
    
    public function generateTOC() {
        $this->AddPage();
        $this->SetFont("helvetica", "B", 16);
        $this->Cell(0, 10, "Índice", 0, 1, "C");
        $this->Ln(10);
        
        foreach ($this->toc as $item) {
            $this->SetFont("helvetica", "", 12);
            $this->Cell(0, 6, $item["title"], 0, 1);
        }
    }
}

$pdf = new EnhancedPDF();
$pdf->SetCreator("PDF Reconstructor");
';

    // Processar cada página
    foreach ($elements['pages'] as $page) {
        $code .= "\n// Page {$page['number']}\n";
        $code .= '$pdf->AddPage();' . "\n";
        $code .= '$pdf->SetMargins(15, 20, 15);' . "\n";

        // Configurar colunas se detectadas
        if (!empty($page['columns'])) {
            $colWidth = ($page['width'] - 30) / count($page['columns']);
            $code .= '$pdf->setEqualColumns(' . count($page['columns']) . ', ' . $colWidth . ');' . "\n";
        }

        // Processar itens da página
        foreach ($page['items'] as $item) {
            switch ($item['type']) {
                case 'image':
                    $img = $item['data'];
                    $code .= <<<IMG
// Image {$img['id']}
\$pdf->registerImage('img_{$img['id']}', '{$img['path']}');
\$pdf->Image('{$img['path']}', {$img['x']}, {$img['y']}, {$img['w']}, {$img['h']}, '{$img['ext']}', '', 'T', false, 300, '', false, false, 0);

IMG;
                    break;

                case 'table':
                    $table = $elements['tables'][$item['data']];
                    $code .= generateTableCode($table, $config['tables']);
                    break;

                case 'text':
                    $text = addslashes($item['data']['text']);
                    $style = $item['style'];
                    $x = $item['data']['x'];
                    $y = $item['data']['y'];

                    if (preg_match('/https?:\/\/[^\s]+/', $text, $matches)) {
                        $url = $matches[0];
                        $text = str_replace($url, '', $text);
                        $code .= <<<TEXT
\$pdf->SetXY($x, $y);
\$pdf->SetFont('{$style['font']}', '{$style['weight']}', {$style['size']});
\$pdf->Write(0, '$text', '$url');

TEXT;
                    } else {
                        $code .= <<<TEXT
\$pdf->SetXY($x, $y);
\$pdf->SetFont('{$style['font']}', '{$style['weight']}', {$style['size']});
\$pdf->Write(0, '$text');

TEXT;
                    }

                    if ($style['size'] > 11) {
                        $code .= '$pdf->addTOCItem(\'' . addslashes($item['data']['text']) . '\', ' . $page['number'] . ', ' . $y . ');' . "\n";
                    }
                    break;
            }
        }

        // Resetar colunas no final da página
        if (!empty($page['columns'])) {
            $code .= '$pdf->resetColumns();' . "\n";
        }
    }

    // Adicionar índice
    $code .= "\n// Generate Table of Contents\n";
    $code .= '$pdf->generateTOC();' . "\n";

    // Saída final
    $code .= "\n\$pdf->Output('reconstructed.pdf', 'I');";

    return $code;
}

// Funções auxiliares melhoradas
function processImage($image, $pageNumber, $config, $imageManager)
{
    static $counter = 1;

    $ext = strtolower($image['ext'] ?? 'jpg');
    $imageFile = $config['images']['directory'] . 'img_' . $counter . '.' . $config['images']['format'];

    try {
        $img = $imageManager->make($image['data']);
        $img->encode($config['images']['format'], $config['images']['quality']);
        $img->save($imageFile);
    } catch (Exception $e) {
        file_put_contents($imageFile, $image['data']);
    }

    return [
        'id' => $counter++,
        'path' => $imageFile,
        'x' => $image['x'],
        'y' => $image['y'],
        'w' => $image['w'],
        'h' => $image['h'],
        'ext' => $ext,
        'page' => $pageNumber + 1
    ];
}

function detectTables($items, $config)
{
    $tables = [];
    $currentTable = [];
    $previousY = null;

    foreach ($items as $item) {
        if ($previousY !== null && abs($item['y'] - $previousY) < $config['detection_threshold']) {
            $currentTable[] = $item;
        } else {
            if (count($currentTable) >= $config['min_columns']) {
                if (isPotentialTable($currentTable)) {
                    $tables[] = formatTableData($currentTable);
                }
            }
            $currentTable = [$item];
        }
        $previousY = $item['y'];
    }

    // Verificar a última tabela potencial
    if (count($currentTable) >= $config['min_columns'] && isPotentialTable($currentTable)) {
        $tables[] = formatTableData($currentTable);
    }

    return $tables;
}

function formatTableData($items)
{
    $columns = array_unique(array_column($items, 'x'));
    sort($columns);

    $rows = [];
    $currentRow = [];
    $currentY = null;

    foreach ($items as $item) {
        if ($currentY !== null && abs($item['y'] - $currentY) > 5) {
            $rows[] = $currentRow;
            $currentRow = [];
        }
        $currentRow[] = $item;
        $currentY = $item['y'];
    }
    $rows[] = $currentRow;

    return [
        'columns' => $columns,
        'rows' => $rows,
        'x' => min(array_column($items, 'x')),
        'y' => min(array_column($items, 'y'))
    ];
}

function generateTableCode($table, $config)
{
    $code = "\n// Table at (" . $table['x'] . ", " . $table['y'] . ")\n";
    $code .= '$pdf->SetXY(' . $table['x'] . ', ' . $table['y'] . ');' . "\n";
    $code .= '$pdf->SetFillColor(' . implode(', ', $config['header_bg_color']) . ');' . "\n";
    $code .= '$pdf->SetFont("helvetica", "B", 10);' . "\n";

    // Cabeçalho
    foreach ($table['columns'] as $col) {
        $code .= '$pdf->SetXY(' . $col . ', ' . $table['y'] . ');' . "\n";
        $code .= '$pdf->Cell(40, 6, "Col ' . ($col / 10) . '", 1, 0, "C", 1);' . "\n";
    }

    // Conteúdo
    $code .= '$pdf->SetFont("helvetica", "", 10);' . "\n";
    $y = $table['y'] + 6;

    foreach ($table['rows'] as $row) {
        foreach ($row as $cell) {
            $code .= '$pdf->SetXY(' . $cell['x'] . ', ' . $y . ');' . "\n";
            $code .= '$pdf->Cell(40, 6, "' . addslashes($cell['text']) . '", 1, 0, "L");' . "\n";
        }
        $y += 6;
    }

    return $code . "\n";
}

// Funções de detecção melhoradas
function isPotentialTable($items)
{
    $xPositions = array_column($items, 'x');
    $uniqueX = array_unique($xPositions);

    if (count($uniqueX) < 2) return false;

    $yPositions = array_column($items, 'y');
    $yDifferences = [];

    for ($i = 1; $i < count($yPositions); $i++) {
        $yDifferences[] = round($yPositions[$i - 1] - $yPositions[$i], 2);
    }

    return count(array_unique($yDifferences)) === 1;
}

function detectColumns($pageWidth, $items)
{
    $xPositions = array_column($items, 'x');
    $histogram = array_count_values(array_map(
        fn($x) => round($x / ($pageWidth / 3)),
        $xPositions
    ));

    $columns = [];
    foreach ($histogram as $col => $count) {
        if ($count > 5) $columns[] = $col * ($pageWidth / 3);
    }

    return count($columns) > 1 ? $columns : null;
}

function detectTextStyle($text)
{
    $style = [
        'font' => 'helvetica',
        'weight' => '',
        'size' => 10
    ];

    // Títulos em caixa alta
    if (preg_match('/^[A-Z][A-Z0-9\s]+$/', $text)) {
        $style['weight'] = 'B';
        $style['size'] = 14;
    }
    // Títulos com formatação
    elseif (preg_match('/^(#+)\s*(.+)/', $text, $matches)) {
        $level = strlen($matches[1]);
        $style['weight'] = 'B';
        $style['size'] = 16 - ($level * 2);
        $text = $matches[2];
    }
    // Listas numeradas
    elseif (preg_match('/^\d+\./', $text)) {
        $style['weight'] = 'B';
    }
    // Marcadores
    elseif (preg_match('/^[•\-]/', $text)) {
        $style['font'] = 'zapfdingbats';
    }
    // Hiperlinks
    elseif (preg_match('/https?:\/\/[^\s]+/', $text)) {
        $style['color'] = [0, 0, 255];
    }

    return $style;
}

function isPartOfTable($item, $tables)
{
    foreach ($tables as $table) {
        foreach ($table['rows'] as $row) {
            foreach ($row as $cell) {
                if ($cell['x'] == $item['x'] && $cell['y'] == $item['y']) {
                    return true;
                }
            }
        }
    }
    return false;
}
