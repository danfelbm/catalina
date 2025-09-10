<?php
require __DIR__ . '/vendor/autoload.php';

use App\Models\FormularioRespuesta;

echo "🚀 Corrigiendo mapeo de IDs en respuestas del formulario 3\n";

// Mapeo correcto de IDs del CSV a IDs del formulario
$mapeoIDs = [
    'likert_5' => 'dim1_q1',   // Primera pregunta Dim1
    'likert_6' => 'dim1_q2',   // Segunda pregunta Dim1
    'likert_7' => 'dim1_q3',   // Tercera pregunta Dim1
    'likert_8' => 'dim1_q4',   // etc...
    'likert_9' => 'dim1_q5',
    'likert_10' => 'dim1_q6',
    'likert_11' => 'dim1_q7',
    'likert_12' => 'dim1_q8',
    'likert_13' => 'dim1_q9',
    
    'likert_14' => 'dim2_q1',  // Primera pregunta Dim2
    'likert_15' => 'dim2_q2',
    'likert_16' => 'dim2_q3',
    'likert_17' => 'dim2_q4',
    'likert_18' => 'dim2_q5',
    'likert_19' => 'dim2_q6',
    'likert_20' => 'dim2_q7',
    
    'likert_21' => 'dim3_q1',  // Primera pregunta Dim3
    'likert_22' => 'dim3_q2',
    'likert_23' => 'dim3_q3',
    'likert_24' => 'dim3_q4',
    'likert_25' => 'dim3_q5',
    'likert_26' => 'dim3_q6',
    
    'likert_27' => 'dim4_q1',  // Primera pregunta Dim4
    'likert_28' => 'dim4_q2',
    'likert_29' => 'dim4_q3',
    'likert_30' => 'dim4_q4',
    'likert_31' => 'dim4_q5',
    'likert_32' => 'dim4_q6',
    
    'likert_33' => 'dim5_q1',  // Primera pregunta Dim5
    'likert_34' => 'dim5_q2',
    'likert_35' => 'dim5_q3',
    'likert_36' => 'dim5_q4',
    'likert_37' => 'dim5_q5',
    'likert_38' => 'dim5_q6',
    'likert_39' => 'dim5_q7',
    
    'likert_40' => 'dim6_q1',  // Primera pregunta Dim6
    'likert_41' => 'dim6_q2',
    'likert_42' => 'dim6_q3',
    'likert_43' => 'dim6_q4',
    'likert_44' => 'dim6_q5',
    'likert_45' => 'dim6_q6',
    'likert_46' => 'dim6_q7',
    'likert_47' => 'dim6_q8',
    
    'likert_48' => 'dim7_q1',  // Primera pregunta Dim7
    'likert_49' => 'dim7_q2',
    'likert_50' => 'dim7_q3',
    'likert_51' => 'dim7_q4',
    'likert_52' => 'dim7_q5',
    'likert_53' => 'dim7_q6',
    'likert_54' => 'dim7_q7',
];

$respuestas = FormularioRespuesta::where('formulario_id', 3)->get();
$respuestasActualizadas = 0;

echo "📝 Procesando " . $respuestas->count() . " respuestas...\n";

foreach ($respuestas as $respuesta) {
    $respuestasData = $respuesta->respuestas;
    $respuestasCorregidas = $respuestasData;
    $cambios = false;
    
    // Mapear cada ID del CSV al ID correcto del formulario
    foreach ($mapeoIDs as $idAntiguo => $idNuevo) {
        if (isset($respuestasData[$idAntiguo])) {
            $respuestasCorregidas[$idNuevo] = $respuestasData[$idAntiguo];
            unset($respuestasCorregidas[$idAntiguo]);
            $cambios = true;
        }
    }
    
    // Solo actualizar si hubo cambios
    if ($cambios) {
        $respuesta->respuestas = $respuestasCorregidas;
        $respuesta->save();
        $respuestasActualizadas++;
        
        if ($respuestasActualizadas % 20 == 0) {
            echo "✅ Actualizadas $respuestasActualizadas respuestas...\n";
        }
    }
}

echo "\n🎉 Proceso completado!\n";
echo "📊 Respuestas actualizadas: $respuestasActualizadas\n";

// Verificar que ahora las estadísticas funcionen
echo "\n📈 Verificando estadísticas...\n";
$formulario = \App\Models\Formulario::find(3);
$estadisticas = $formulario->getEstadisticasPorCategoria();

echo "✅ Categorías encontradas: " . count($estadisticas) . "\n";
foreach ($estadisticas as $id => $stats) {
    echo "   • {$stats['nombre']}: {$stats['promedio']}\n";
}

echo "\n✅ ¡Mapeo corregido exitosamente!\n";
echo "🔗 Ahora deberías ver el gráfico radar en: https://catalina.test/admin/formularios/3\n";
?>