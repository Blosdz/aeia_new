<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, DollarSign, Percent, Calendar, AlertCircle, CheckCircle2, Clock } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Reward {
  id: number;
  fund_name: string;
  fund_description: string;
  status: 'pending' | 'closed' | 'paid';
  closed_at: string | null;
  paid_at: string | null;
  total_investment: number;
  total_earnings: number;
  company_percentage: number;
  company_deduction: number;
  was_referred: boolean;
  referral_percentage: number;
  referral_deduction: number;
  net_earnings: number;
  total_return: number;
  referrer_name: string | null;
}

interface RewardsSummary {
  total_invested: number;
  total_earnings: number;
  total_deductions: number;
  total_net_earnings: number;
  total_return: number;
  count: number;
  by_status: {
    paid: number;
    closed: number;
    pending: number;
  };
}

interface Props {
  rewards: Reward[];
  summary: RewardsSummary;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Recompensas', href: route('clients.rewards') },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount || 0);
};

const formatDate = (date: string | null) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'paid':
      return { variant: 'default' as const, label: 'Pagado', icon: CheckCircle2 };
    case 'closed':
      return { variant: 'secondary' as const, label: 'Cerrado', icon: Clock };
    case 'pending':
      return { variant: 'outline' as const, label: 'Pendiente', icon: Clock };
    default:
      return { variant: 'outline' as const, label: status, icon: AlertCircle };
  }
};

const getDeductionBreakdown = (reward: Reward) => {
  const deductions = [];
  if (reward.company_percentage > 0) {
    deductions.push(`Empresa ${reward.company_percentage}%: ${formatCurrency(reward.company_deduction)}`);
  }
  if (reward.was_referred && reward.referral_percentage > 0) {
    deductions.push(`Referencia ${reward.referral_percentage}%: ${formatCurrency(reward.referral_deduction)}`);
  }
  return deductions.length > 0 ? deductions.join(' | ') : 'Sin deducciones';
};
</script>

<template>
  <Head title="Recompensas" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Encabezado -->
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold">Tus Recompensas</h1>
          <p class="text-muted-foreground mt-1">Resumen de tus inversiones y ganancias distribuidas</p>
        </div>
      </div>

      <!-- Cards de Resumen -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-5">
        <!-- Inversión Total -->
        <Card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Inversión Total</CardTitle>
            <DollarSign class="h-4 w-4 text-blue-600 dark:text-blue-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(summary.total_invested) }}</div>
            <p class="text-xs text-muted-foreground">En {{ summary.count }} inversiones</p>
          </CardContent>
        </Card>

        <!-- Ganancias Totales -->
        <Card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Ganancias</CardTitle>
            <TrendingUp class="h-4 w-4 text-green-600 dark:text-green-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">+{{ formatCurrency(summary.total_earnings) }}</div>
            <p class="text-xs text-muted-foreground">Generadas por los fondos</p>
          </CardContent>
        </Card>

        <!-- Deducciones Totales -->
        <Card class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Deducciones</CardTitle>
            <Percent class="h-4 w-4 text-orange-600 dark:text-orange-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">-{{ formatCurrency(summary.total_deductions) }}</div>
            <p class="text-xs text-muted-foreground">Empresa y comisiones</p>
          </CardContent>
        </Card>

        <!-- Ganancia Neta -->
        <Card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Ganancia Neta</CardTitle>
            <TrendingUp class="h-4 w-4 text-purple-600 dark:text-purple-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">+{{ formatCurrency(summary.total_net_earnings) }}</div>
            <p class="text-xs text-muted-foreground">Después de deducciones</p>
          </CardContent>
        </Card>

        <!-- Total a Retornar -->
        <Card class="bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-950 dark:to-rose-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Retorno</CardTitle>
            <DollarSign class="h-4 w-4 text-rose-600 dark:text-rose-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-rose-700 dark:text-rose-300">{{ formatCurrency(summary.total_return) }}</div>
            <p class="text-xs text-muted-foreground">Inversión + Ganancia neta</p>
          </CardContent>
        </Card>
      </div>

      <!-- Estado de Recompensas -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium">Pagadas</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ summary.by_status.paid }}</div>
            <p class="text-xs text-muted-foreground mt-1">Completadas y retornadas</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium">Cerradas</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ summary.by_status.closed }}</div>
            <p class="text-xs text-muted-foreground mt-1">Esperando pago</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ summary.by_status.pending }}</div>
            <p class="text-xs text-muted-foreground mt-1">En proceso de cierre</p>
          </CardContent>
        </Card>
      </div>

      <!-- Lista de Recompensas -->
      <Card>
        <CardHeader>
          <CardTitle>Detalle de Recompensas</CardTitle>
          <CardDescription>{{ rewards.length }} inversiones con sus recompensas asociadas</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="rewards.length === 0" class="text-center py-8">
            <AlertCircle class="h-12 w-12 text-muted-foreground mx-auto mb-3" />
            <p class="text-muted-foreground">No tienes recompensas registradas aún</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="reward in rewards"
              :key="reward.id"
              class="border rounded-lg p-4 hover:bg-muted/50 transition-colors"
            >
              <!-- Encabezado de la Recompensa -->
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h3 class="font-semibold text-lg">{{ reward.fund_name }}</h3>
                  <p class="text-sm text-muted-foreground">{{ reward.fund_description }}</p>
                </div>
                <Badge :variant="getStatusBadge(reward.status).variant">
                  {{ getStatusBadge(reward.status).label }}
                </Badge>
              </div>

              <!-- Datos Principales -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div>
                  <p class="text-xs text-muted-foreground font-medium">Inversión</p>
                  <p class="text-lg font-bold">{{ formatCurrency(reward.total_investment) }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted-foreground font-medium">Ganancia del Fondo</p>
                  <p class="text-lg font-bold text-green-600 dark:text-green-400">+{{ formatCurrency(reward.total_earnings) }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted-foreground font-medium">Deducciones</p>
                  <p class="text-lg font-bold text-orange-600 dark:text-orange-400">-{{ formatCurrency(reward.company_deduction + reward.referral_deduction) }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted-foreground font-medium">Total a Retornar</p>
                  <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(reward.total_return) }}</p>
                </div>
              </div>

              <!-- Detalles de Deducciones -->
              <div class="bg-muted/50 rounded p-3 mb-4">
                <p class="text-xs font-medium text-muted-foreground mb-1">Desglose de Deducciones:</p>
                <p class="text-sm">{{ getDeductionBreakdown(reward) }}</p>
              </div>

              <!-- Información Adicional -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-muted-foreground">
                <div v-if="reward.closed_at" class="flex items-center gap-2">
                  <Calendar class="h-4 w-4" />
                  <span>Cerrado: {{ formatDate(reward.closed_at) }}</span>
                </div>
                <div v-if="reward.paid_at" class="flex items-center gap-2">
                  <Calendar class="h-4 w-4" />
                  <span>Pagado: {{ formatDate(reward.paid_at) }}</span>
                </div>
                <div v-if="reward.was_referred && reward.referrer_name" class="col-span-2">
                  <p class="text-xs">Referido por: <span class="font-medium">{{ reward.referrer_name }}</span></p>
                </div>
              </div>

              <!-- Enlace a Detalles -->
              <div class="mt-4">
                <Link :href="route('clients.rewards.show', reward.id)">
                  <Button variant="outline" size="sm">Ver Detalles</Button>
                </Link>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Notas Informativas -->
      <Card class="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-sm">
            <AlertCircle class="h-4 w-4" />
            Información Importante
          </CardTitle>
        </CardHeader>
        <CardContent class="text-sm text-muted-foreground space-y-2">
          <p>
            • <strong>Inversión:</strong> El monto que invertiste en el fondo
          </p>
          <p>
            • <strong>Ganancia del Fondo:</strong> Las ganancias generadas por el fondo durante el período
          </p>
          <p>
            • <strong>Deducciones:</strong> Porcentaje para la empresa (20%) y comisión de referencia (5% si aplica)
          </p>
          <p>
            • <strong>Total a Retornar:</strong> Tu inversión más la ganancia neta después de deducciones
          </p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
