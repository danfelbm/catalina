<?php

namespace Database\Seeders;

use App\Models\ReporteMadurezCategoria;
use App\Models\ReporteMadurezElemento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReporteMadurezSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías con sus colores según la imagen
        $categorias = [
            [
                'nombre' => 'CUESTIONES ESTRATÉGICAS',
                'codigo' => 'cuestiones_estrategicas',
                'orden' => 1,
                'color' => '#f3f4f6', // Gris claro
            ],
            [
                'nombre' => 'SISTEMA DE GESTIÓN',
                'codigo' => 'sistema_gestion',
                'orden' => 2,
                'color' => '#0891b2', // Azul/cyan
            ],
            [
                'nombre' => 'LIDERAZGO Y COMPORTAMIENTO',
                'codigo' => 'liderazgo_comportamiento',
                'orden' => 3,
                'color' => '#f59e0b', // Amarillo/naranja
            ],
            [
                'nombre' => 'HABILIDADES Y COMPETENCIAS',
                'codigo' => 'habilidades_competencias',
                'orden' => 4,
                'color' => '#059669', // Verde
            ],
            [
                'nombre' => 'COMPORTAMIENTO',
                'codigo' => 'comportamiento',
                'orden' => 5,
                'color' => '#1e40af', // Azul oscuro
            ],
        ];

        foreach ($categorias as $categoriaData) {
            $categoria = ReporteMadurezCategoria::create($categoriaData);
            $this->crearElementosParaCategoria($categoria);
        }
    }

    /**
     * Crear elementos específicos para cada categoría según la imagen
     */
    private function crearElementosParaCategoria(ReporteMadurezCategoria $categoria): void
    {
        $elementos = [];

        switch ($categoria->codigo) {
            case 'cuestiones_estrategicas':
                $elementos = [
                    ['numero' => 1, 'nombre' => 'Operación equilibrada', 'orden' => 1],
                    ['numero' => 2, 'nombre' => 'Estructura', 'orden' => 2],
                    ['numero' => 3, 'nombre' => 'Desempeño', 'orden' => 3],
                    ['numero' => 4, 'nombre' => 'Gestión de riesgos', 'orden' => 4],
                    ['numero' => 5, 'nombre' => 'Partes interesadas', 'orden' => 5],
                    ['numero' => 6, 'nombre' => 'Comunicación', 'orden' => 6],
                ];
                break;

            case 'sistema_gestion':
                $elementos = [
                    ['numero' => 7, 'nombre' => 'Gestión integral de la SST', 'orden' => 1],
                    ['numero' => 8, 'nombre' => 'Gestión de la salud', 'orden' => 2],
                    ['numero' => 9, 'nombre' => 'Gestión de peligros y riesgos', 'orden' => 3],
                    ['numero' => 10, 'nombre' => 'Gestión de amenazas', 'orden' => 4],
                    ['numero' => 11, 'nombre' => 'Verificación', 'orden' => 5],
                    ['numero' => 12, 'nombre' => 'Mejoramiento', 'orden' => 6],
                ];
                break;

            case 'liderazgo_comportamiento':
                $elementos = [
                    ['numero' => 13, 'nombre' => 'Visión en SST', 'orden' => 1],
                    ['numero' => 14, 'nombre' => 'Desarrollo del liderazgo en SST', 'orden' => 2],
                    ['numero' => 15, 'nombre' => 'Visibilidad', 'orden' => 3],
                    ['numero' => 16, 'nombre' => 'Contribución y construcción', 'orden' => 4],
                    ['numero' => 17, 'nombre' => 'Apropiación de SST', 'orden' => 5],
                ];
                break;

            case 'habilidades_competencias':
                $elementos = [
                    ['numero' => 18, 'nombre' => 'Perfil integral de competencias y habilidades', 'orden' => 1],
                    ['numero' => 19, 'nombre' => 'Formación y entrenamiento', 'orden' => 2],
                    ['numero' => 20, 'nombre' => 'Habilidades blandas', 'orden' => 3],
                ];
                break;

            case 'comportamiento':
                $elementos = [
                    ['numero' => 21, 'nombre' => 'Participación', 'orden' => 1],
                    ['numero' => 22, 'nombre' => 'Resiliencia', 'orden' => 2],
                    ['numero' => 23, 'nombre' => 'Acciones de cuidado', 'orden' => 3],
                    ['numero' => 24, 'nombre' => 'Retroalimentación y reconocimiento', 'orden' => 4],
                ];
                break;
        }

        foreach ($elementos as $elementoData) {
            ReporteMadurezElemento::create([
                'categoria_id' => $categoria->id,
                'numero' => $elementoData['numero'],
                'nombre' => $elementoData['nombre'],
                'orden' => $elementoData['orden'],
            ]);
        }
    }
}
