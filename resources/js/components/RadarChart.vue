<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue';
import {
  Chart as ChartJS,
  RadialLinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip,
  Legend,
  type ChartData,
  type ChartOptions
} from 'chart.js';
import { Radar } from 'vue-chartjs';

ChartJS.register(RadialLinearScale, PointElement, LineElement, Filler, Tooltip, Legend);

interface RadarChartData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor?: string;
    borderColor?: string;
    borderWidth?: number;
    pointBackgroundColor?: string;
    pointRadius?: number;
  }[];
}

interface Props {
  data: RadarChartData;
  title?: string;
  height?: number;
  minValue?: number;
  maxValue?: number;
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  height: 400,
  minValue: 1,
  maxValue: 4,
});

const chartRef = ref();

const chartData = ref<ChartData<'radar'>>({
  labels: [],
  datasets: []
});

const chartOptions = ref<ChartOptions<'radar'>>({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    title: {
      display: !!props.title,
      text: props.title,
      font: {
        size: 16,
        weight: 'bold'
      }
    },
    legend: {
      display: true,
      position: 'bottom'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const value = context.parsed.r;
          return `${context.label}: ${value.toFixed(2)}`;
        }
      }
    }
  },
  scales: {
    r: {
      min: props.minValue,
      max: props.maxValue,
      stepSize: 0.5,
      ticks: {
        display: true,
        stepSize: 0.5,
        callback: function(value) {
          return value.toString();
        }
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.1)'
      },
      angleLines: {
        color: 'rgba(0, 0, 0, 0.1)'
      },
      pointLabels: {
        font: {
          size: 12
        },
        color: '#374151',
        callback: function(label: string) {
          // Dividir texto largo en múltiples líneas para mejor visualización
          const words = label.split(' ');
          if (words.length > 4) {
            const mid = Math.ceil(words.length / 2);
            return [
              words.slice(0, mid).join(' '),
              words.slice(mid).join(' ')
            ];
          }
          return label;
        }
      }
    }
  },
  animation: {
    duration: 1000,
    easing: 'easeInOutQuart'
  }
});

// Función para actualizar los datos del gráfico
const updateChartData = () => {
  if (!props.data || !props.data.labels || !props.data.datasets) {
    return;
  }

  chartData.value = {
    labels: props.data.labels,
    datasets: props.data.datasets.map(dataset => ({
      ...dataset,
      backgroundColor: dataset.backgroundColor || 'rgba(251, 191, 36, 0.2)', // Color dorado/amarillo como en la imagen
      borderColor: dataset.borderColor || 'rgb(251, 191, 36)',
      borderWidth: dataset.borderWidth || 2,
      pointBackgroundColor: dataset.pointBackgroundColor || 'rgb(251, 191, 36)',
      pointRadius: dataset.pointRadius || 4,
    }))
  };
};

// Watchers para actualizar el gráfico cuando cambien las props
watch(() => props.data, () => {
  updateChartData();
}, { deep: true });

// Inicializar el gráfico cuando se monte el componente
onMounted(() => {
  nextTick(() => {
    updateChartData();
  });
});
</script>

<template>
  <div class="w-full" :style="{ height: `${height}px` }">
    <Radar
      ref="chartRef"
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>