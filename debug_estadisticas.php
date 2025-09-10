<?php
require __DIR__ . '/vendor/autoload.php';

use App\Models\Formulario;

// Obtener el formulario ID 3
$formulario = Formulario::find(3);

if (!$formulario) {
    die("No se encontró el formulario\n");
}

echo "Formulario encontrado: {$formulario->titulo}\n";
echo "Total respuestas: " . $formulario->respuestas()->where('estado', 'enviado')->count() . "\n";

// Probar método de estadísticas por categoría
echo "\n=== Probando getEstadisticasPorCategoria ===\n";
try {
    $estadisticas = $formulario->getEstadisticasPorCategoria();
    echo "Estadísticas encontradas: " . count($estadisticas) . " categorías\n";
    
    foreach ($estadisticas as $id => $stats) {
        echo "- {$stats['nombre']}: promedio {$stats['promedio']} ({$stats['total_respuestas']} respuestas)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Probar método de datos radar
echo "\n=== Probando getDatosRadarPorCategoria ===\n";
try {
    $datosRadar = $formulario->getDatosRadarPorCategoria();
    echo "Labels: " . count($datosRadar['labels']) . "\n";
    echo "Datasets: " . count($datosRadar['datasets']) . "\n";
    
    if (!empty($datosRadar['labels'])) {
        echo "Dimensiones:\n";
        foreach ($datosRadar['labels'] as $i => $label) {
            $valor = $datosRadar['datasets'][0]['data'][$i] ?? 'N/A';
            echo "- $label: $valor\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>