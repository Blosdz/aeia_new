<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Users,
  TrendingUp,
  DollarSign,
  Zap,
  Copy,
  BarChart3,
  LineChart as LineChartIcon,
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';

interface Stats {
  total_referrals: number;
  converted_referrals: number;
  conversion_rate: number;
  total_commissions: number;
  pending_commissions: number;
  calculated_commissions: number;
  paid_commissions: number;
  staff_referral_code: string;
}

interface ChartData {
  date?: string;
  month?: string;
  referrals?: number;
  conversions?: number;
  amount?: number;
}

interface Commission {
  id: number;
  subscription_code: string;
  amount: number;
  percentage: number;
  status: string;
  calculated_at: string;
  paid_at: string;
}

interface Referral {
  id: number;
  name: string;
  email: string;
  created_at: string;
  is_converted: boolean;
  payment_count: number;
  total_invested: number;
  status: string;
}

const props = defineProps<{
  stats: Stats;
  charts: {
    conversion: ChartData[];
    commissions: ChartData[];
  };
  latest_commissions: Commission[];
  referrals: Referral[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('staff.dashboard') },
];

const copiedCode = ref(false);

const referralUrl = computed(() => {
  return `${window.location.origin}/register/${props.stats.staff_referral_code}`;
});

const copyReferralCode = () => {
  navigator.clipboard.writeText(referralUrl.value);
  copiedCode.value = true;
  setTimeout(() => {
    copiedCode.value = false;
  }, 2000);
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getStatusBadge = (status: string) => {
  const statusMap = {
    pending: { label: 'Pendiente', variant: 'secondary' as const },
    calculated: { label: 'Calculada', variant: 'outline' as const },
    paid: { label: 'Pagada', variant: 'default' as const },
  };
  return statusMap[status as keyof typeof statusMap] || { label: status, variant: 'secondary' as const };
};

// Calcular máximo para normalizar gráficos
const maxConversions = computed(() => {
  return Math.max(...props.charts.conversion.map(d => (d.referrals ?? 0) + (d.conversions ?? 0)), 1);
});

const maxCommissions = computed(() => {
  return Math.max(...props.charts.commissions.map(d => d.amount ?? 0), 1);
});
</script>

<template>
  <Head title="Staff Dashboard - Comisiones y Referidos" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold">Dashboard Staff</h1>
          <p class="text-muted-foreground mt-1">Gestión de referidos y comisiones</p>
        </div>
      </div>

      <!-- Código de Referral -->
      <Card class="border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-950">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Zap class="h-5 w-5 text-purple-600" />
            Tu Enlace de Referral
          </CardTitle>
          <CardDescription>Comparte este enlace para referir clientes y ganar comisiones</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <code class="flex-1 p-3 bg-white dark:bg-gray-900 rounded-lg font-mono text-sm border break-all">
                {{ referralUrl }}
              </code>
              <Button @click="copyReferralCode" variant="outline" size="icon" class="h-12 w-12 flex-shrink-0">
                <Copy class="h-5 w-5" :class="{ 'text-green-600': copiedCode }" />
              </Button>
            </div>
            <p v-if="copiedCode" class="text-green-600 text-sm">✓ Enlace copiado al portapapeles</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">Tu código: <strong>{{ stats.staff_referral_code }}</strong></p>
          </div>
        </CardContent>
      </Card>

      <!-- KPIs Principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Referidos -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm flex items-center gap-2">
              <Users class="h-4 w-4" />
              Total Referidos
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold">{{ stats.total_referrals }}</p>
            <p class="text-xs text-muted-foreground mt-1">Usuarios referidos por ti</p>
          </CardContent>
        </Card>

        <!-- Conversión -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm flex items-center gap-2">
              <TrendingUp class="h-4 w-4" />
              Tasa de Conversión
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold">{{ stats.conversion_rate.toFixed(1) }}%</p>
            <p class="text-xs text-muted-foreground mt-1">{{ stats.converted_referrals }} / {{ stats.total_referrals }} convertidos</p>
          </CardContent>
        </Card>

        <!-- Total Comisiones -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm flex items-center gap-2">
              <DollarSign class="h-4 w-4" />
              Total Comisiones
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(stats.total_commissions) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Generadas hasta ahora</p>
          </CardContent>
        </Card>

        <!-- Comisiones Pendientes -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm flex items-center gap-2">
              <Zap class="h-4 w-4" />
              Pendiente de Pago
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ formatCurrency(stats.pending_commissions) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Por cobrar</p>
          </CardContent>
        </Card>
      </div>

      <!-- Gráficos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gráfico de Conversión -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <LineChartIcon class="h-5 w-5" />
              Conversión últimos 30 días
            </CardTitle>
            <CardDescription>Referidos vs Conversiones</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="data in props.charts.conversion" :key="data.date" class="space-y-1">
                <div class="flex justify-between text-xs">
                  <span class="font-semibold">{{ data.date }}</span>
                  <span class="text-muted-foreground">Ref: {{ data.referrals }} | Conv: {{ data.conversions }}</span>
                </div>
                <div class="flex gap-1 h-6">
                  <div
                    class="bg-blue-500 rounded-sm"
                    :style="{ width: `${((data.referrals ?? 0) / maxConversions) * 100}%` }"
                    :title="`Referidos: ${data.referrals ?? 0}`"
                  />
                  <div
                    class="bg-green-500 rounded-sm"
                    :style="{ width: `${((data.conversions ?? 0) / maxConversions) * 100}%` }"
                    :title="`Conversiones: ${data.conversions ?? 0}`"
                  />
                </div>
              </div>
            </div>
            <div class="flex gap-6 text-xs mt-4 pt-4 border-t">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-blue-500 rounded-sm" />
                <span>Referidos</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-green-500 rounded-sm" />
                <span>Conversiones</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Gráfico de Comisiones -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <BarChart3 class="h-5 w-5" />
              Comisiones últimos 12 meses
            </CardTitle>
            <CardDescription>Tendencia de ganancias</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="data in props.charts.commissions" :key="data.month" class="space-y-1">
                <div class="flex justify-between text-xs">
                  <span class="font-semibold">{{ data.month }}</span>
                  <span class="text-green-600 dark:text-green-400 font-semibold">{{ formatCurrency(data.amount ?? 0) }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-sm h-6 overflow-hidden">
                  <div
                    class="bg-purple-500 h-full flex items-center justify-end pr-2"
                    :style="{ width: `${((data.amount ?? 0) / maxCommissions) * 100}%` }"
                  >
                    <span v-if="(data.amount ?? 0) > 0" class="text-xs font-bold text-white">{{ formatCurrency(data.amount ?? 0) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Últimas Comisiones -->
      <Card>
        <CardHeader>
          <CardTitle>Últimas Comisiones</CardTitle>
          <CardDescription>5 comisiones más recientes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="commission in latest_commissions" :key="commission.id" class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50">
              <div class="flex-1">
                <p class="font-semibold">{{ commission.subscription_code }}</p>
                <p class="text-xs text-muted-foreground">{{ formatDate(commission.calculated_at) }}</p>
              </div>
              <div class="text-right">
                <p class="font-bold text-green-600 dark:text-green-400">{{ formatCurrency(commission.amount) }}</p>
                <p class="text-xs text-muted-foreground">{{ commission.percentage }}%</p>
              </div>
              <Badge :variant="getStatusBadge(commission.status).variant" class="ml-3">
                {{ getStatusBadge(commission.status).label }}
              </Badge>
            </div>
          </div>

          <div class="mt-4">
            <Link href="/staff/commissions">
              <Button variant="outline" class="w-full">Ver todas las comisiones →</Button>
            </Link>
          </div>
        </CardContent>
      </Card>

      <!-- Referidos Activos -->
      <Card>
        <CardHeader>
          <CardTitle>Referidos Activos</CardTitle>
          <CardDescription>{{ referrals.length }} referidos en tu red</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2 max-h-96 overflow-y-auto">
            <div v-for="referral in referrals.slice(0, 10)" :key="referral.id" class="flex items-center justify-between p-3 border rounded-lg">
              <div class="flex-1">
                <p class="font-semibold">{{ referral.name }}</p>
                <p class="text-xs text-muted-foreground">{{ referral.email }}</p>
              </div>
              <div class="text-right text-sm">
                <p class="font-semibold">{{ referral.payment_count }} pago(s)</p>
                <p v-if="referral.total_invested > 0" class="text-xs text-green-600">{{ formatCurrency(referral.total_invested) }}</p>
              </div>
              <Badge :variant="referral.is_converted ? 'default' : 'secondary'" class="ml-3">
                {{ referral.status }}
              </Badge>
            </div>
          </div>

          <div class="mt-4">
            <Link href="/staff/referrals">
              <Button variant="outline" class="w-full">Ver todos los referidos →</Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
