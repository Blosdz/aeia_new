<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { TrendingUp, Percent, DollarSign, Users, Calendar, Plus, Eye } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { Chart, registerables, type ChartData, type ChartOptions } from 'chart.js';
Chart.register(...registerables);

interface Subscription {
  id: number;
  unique_code: string;
  started_at: string;
  expires_at: string | null;
  planType?: {
    id: number;
    name: string;
  };
}

interface DashboardProps {
  profile: any;
  subscriptions: Subscription[];
  totalInvested: number;
  totalCurrentValue: number;
  totalGains: number;
  gainPercent: number;
  activeSubscriptions: number;
  totalRevenue: number;
  closureDays: number;
  earningsChart: {
    date: string;
    gains: number;
    daily_gains: number;
    current_value: number;
  }[];
}

const props = defineProps<DashboardProps>();

const earningsChartRef = ref<HTMLCanvasElement | null>(null);
const earningsChartInstance = ref<Chart | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount || 0);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const buildEarningsChart = () => {
  if (!earningsChartRef.value) return;

  const labels = props.earningsChart.map(point => point.date);
  const cumulative = props.earningsChart.map(point => point.current_value ?? 0);
  const daily = props.earningsChart.map(point => point.daily_gains ?? 0);

  const data: ChartData<'line'> = {
    labels,
    datasets: [
      {
        label: 'Valor acumulado',
        data: cumulative,
        borderColor: '#8b5cf6',
        backgroundColor: 'rgba(139, 92, 246, 0.15)',
        tension: 0.35,
        fill: true,
        pointRadius: 0,
      },
      {
        label: 'Ganancia diaria',
        data: daily,
        borderColor: '#22c55e',
        backgroundColor: 'rgba(34, 197, 94, 0.12)',
        tension: 0.35,
        fill: true,
        pointRadius: 0,
        yAxisID: 'y1',
      },
    ],
  };

  const options: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { labels: { color: '#0f172a' } },
      tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${formatCurrency(ctx.parsed.y)}` } },
    },
    scales: {
      x: {
        ticks: { color: '#475569', maxTicksLimit: 10 },
        grid: { display: false },
      },
      y: {
        ticks: { color: '#475569', callback: (val) => `$${val}` },
        grid: { color: 'rgba(148, 163, 184, 0.25)' },
      },
      y1: {
        position: 'right',
        ticks: { color: '#475569', callback: (val) => `$${val}` },
        grid: { display: false },
      },
    },
  };

  if (earningsChartInstance.value) {
    earningsChartInstance.value.destroy();
  }

  if (!earningsChartRef.value) return;

  earningsChartInstance.value = new Chart(earningsChartRef.value, {
    type: 'line',
    data,
    options,
  });
};

onMounted(buildEarningsChart);
watch(() => props.earningsChart, buildEarningsChart, { deep: true });
onBeforeUnmount(() => earningsChartInstance.value?.destroy());
</script>

<template>

  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Bienvenida -->
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold">Bienvenido, {{ profile?.first_name || 'Cliente' }}</h1>
          <p class="text-muted-foreground mt-1">Resumen de tu portafolio de inversiones</p>
        </div>
      </div>

      <!-- Cards de Resumen -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-5">
        <!-- Inversión Total -->
        <Card class="border-l-4 border-l-blue-500">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Inversión Total</CardTitle>
            <DollarSign class="h-4 w-4 text-blue-600 dark:text-blue-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(totalInvested) }}</div>
            <p class="text-xs text-muted-foreground">En todos tus planes activos</p>
          </CardContent>
        </Card>

        <!-- Ganancia % -->
        <Card class="border-l-4 border-l-green-500">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">% Ganancia</CardTitle>
            <Percent class="h-4 w-4 text-green-600 dark:text-green-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">+{{ gainPercent }}%</div>
            <p class="text-xs text-muted-foreground">Retorno en tu inversión</p>
          </CardContent>
        </Card>

        <!-- Revenue Total -->
        <Card class="border-l-4 border-l-purple-500">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Ingresos</CardTitle>
            <TrendingUp class="h-4 w-4 text-purple-600 dark:text-purple-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(totalRevenue) }}</div>
            <p class="text-xs text-muted-foreground">Ganancias generadas</p>
          </CardContent>
        </Card>

        <!-- Suscripciones -->
        <Card class="border-l-4 border-l-orange-500">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Suscripciones</CardTitle>
            <Users class="h-4 w-4 text-orange-600 dark:text-orange-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ activeSubscriptions }}</div>
            <p class="text-xs text-muted-foreground">Planes activos</p>
          </CardContent>
        </Card>

        <!-- Próximo Cierre -->
        <Card class="border-l-4 border-l-rose-500">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Próximo Cierre</CardTitle>
            <Calendar class="h-4 w-4 text-rose-600 dark:text-rose-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ closureDays }} días</div>
            <p class="text-xs text-muted-foreground">Hasta el cierre de período</p>
          </CardContent>
        </Card>
      </div>

      <!-- Gráfico y Acciones -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-3">
        <!-- Gráfico de Evolución -->
        <Card
          class="col-span-2 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border-slate-700">
          <CardHeader>
            <CardTitle class="text-white">Evolución de Inversiones</CardTitle>
            <CardDescription class="text-slate-400">Últimos 30 días</CardDescription>
          </CardHeader>
          <CardContent class="pt-6 pb-8 px-2">
            <div class="h-80">
              <canvas ref="earningsChartRef"></canvas>
            </div>
          </CardContent>
          <text x="45" y="75" text-anchor="end" dy="0.3em" class="text-xs" fill="#64748b" font-size="11">$3k</text>
          <text x="45" y="15" text-anchor="end" dy="0.3em" class="text-xs" fill="#64748b" font-size="11">$3.5k</text>
          </svg>
          </CardContent>
        </Card> <!-- Acciones Rápidas -->
        <Card>
          <CardHeader>
            <CardTitle>Acciones Rápidas</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <Link :href="route('clients.rewards')">
              <Button variant="outline" class="w-full justify-start">
                <Plus class="h-4 w-4 mr-2" />
                Ver Recompensas
              </Button>
            </Link>
            <Link :href="route('clients.payments.select')">
              <Button variant="outline" class="w-full justify-start">
                <Plus class="h-4 w-4 mr-2" />
                Nueva Inversión
              </Button>
            </Link>
            <Link :href="route('clients.coverage.select')">
              <Button variant="outline" class="w-full justify-start">
                <Plus class="h-4 w-4 mr-2" />
                Nueva Cobertura
              </Button>
            </Link>
            <Link :href="route('clients.profile')">
              <Button variant="outline" class="w-full justify-start">
                <Eye class="h-4 w-4 mr-2" />
                Mi Perfil
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>

      <!-- Suscripciones Recientes -->
      <Card>
        <CardHeader>
          <CardTitle>Suscripciones Activas</CardTitle>
          <CardDescription>{{ subscriptions?.length || 0 }} suscripciones en tu portafolio</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!subscriptions || subscriptions.length === 0" class="text-center py-8">
            <p class="text-muted-foreground">No tienes suscripciones activas</p>
            <Link :href="route('clients.payments.select')">
              <Button class="mt-4">
                <Plus class="h-4 w-4 mr-2" />
                Crear Primera Inversión
              </Button>
            </Link>
          </div>

          <div v-else class="space-y-2">
            <div v-for="subscription in subscriptions.slice(0, 5)" :key="subscription.id"
              class="flex items-center justify-between p-3 border rounded-lg">
              <div>
                <p class="font-medium">{{ subscription.planType?.name || 'Suscripción' }}</p>
                <p class="text-xs text-muted-foreground">{{ subscription.unique_code }}</p>
              </div>
              <div class="text-right">
                <p class="font-medium">{{ formatDate(subscription.started_at) }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
