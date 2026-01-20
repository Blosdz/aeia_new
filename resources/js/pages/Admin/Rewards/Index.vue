<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TrendingUp, DollarSign, Percent, Calendar, AlertCircle, CheckCircle2, Clock, Users } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface Reward {
  id: number;
  client_name: string;
  client_email: string;
  fund_name: string;
  status: 'pending' | 'closed' | 'paid';
  closed_at: string | null;
  paid_at: string | null;
  total_investment: number;
  total_earnings: number;
  company_deduction: number;
  referral_deduction: number;
  net_earnings: number;
  total_return: number;
  was_referred: boolean;
  referrer_name: string | null;
}

interface RewardsSummary {
  total_invested: number;
  total_earnings: number;
  total_deductions: number;
  total_net_earnings: number;
  total_return: number;
  count_by_status: {
    paid: number;
    closed: number;
    pending: number;
  };
}

interface Fund {
  id: number;
  name: string;
}

interface Props {
  rewards: any;
  funds: Fund[];
  summary: RewardsSummary;
  filters: {
    fund_id: number | null;
    status: string | null;
  };
}

const props = defineProps<Props>();

const fundId = ref<string>(props.filters.fund_id ? String(props.filters.fund_id) : '');
const status = ref<string>(props.filters.status || '');

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('admin.dashboard') },
  { title: 'Rewards', href: route('admin.rewards') },
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
      return { variant: 'default' as const, label: 'Pagado', color: 'green' };
    case 'closed':
      return { variant: 'secondary' as const, label: 'Cerrado', color: 'blue' };
    case 'pending':
      return { variant: 'outline' as const, label: 'Pendiente', color: 'yellow' };
    default:
      return { variant: 'outline' as const, label: status, color: 'gray' };
  }
};

const handleFilterChange = () => {
  const params: Record<string, any> = {};
  if (fundId.value) {
    params.fund_id = fundId.value;
  }
  if (status.value) {
    params.status = status.value;
  }
  router.get(route('admin.rewards'), params);
};

const clearFilters = () => {
  fundId.value = '';
  status.value = '';
  router.get(route('admin.rewards'));
};
</script>

<template>
  <Head title="Rewards" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Encabezado -->
      <div>
        <h1 class="text-3xl font-bold">Gestión de Rewards</h1>
        <p class="text-muted-foreground mt-1">Visualiza y gestiona las recompensas de todos los clientes</p>
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
            <p class="text-xs text-muted-foreground">Invertido por clientes</p>
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
            <p class="text-xs text-muted-foreground">Generadas por fondos</p>
          </CardContent>
        </Card>

        <!-- Deducciones -->
        <Card class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Deducciones</CardTitle>
            <Percent class="h-4 w-4 text-orange-600 dark:text-orange-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">-{{ formatCurrency(summary.total_deductions) }}</div>
            <p class="text-xs text-muted-foreground">Empresa + Comisiones</p>
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
            <p class="text-xs text-muted-foreground">Para retornar a clientes</p>
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
            <p class="text-xs text-muted-foreground">A retornar a clientes</p>
          </CardContent>
        </Card>
      </div>

      <!-- Estado de Rewards -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium">Pagadas</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ summary.count_by_status.paid }}</div>
            <p class="text-xs text-muted-foreground mt-1">Completadas</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium">Cerradas</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ summary.count_by_status.closed }}</div>
            <p class="text-xs text-muted-foreground mt-1">Esperando pago</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ summary.count_by_status.pending }}</div>
            <p class="text-xs text-muted-foreground mt-1">En proceso</p>
          </CardContent>
        </Card>
      </div>

      <!-- Filtros -->
      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="text-sm font-medium mb-2 block">Fondo</label>
              <Select v-model="fundId" @update:model-value="handleFilterChange">
                <SelectTrigger>
                  <SelectValue placeholder="Todos los fondos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Todos los fondos</SelectItem>
                  <SelectItem v-for="fund in funds" :key="fund.id" :value="String(fund.id)">
                    {{ fund.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <label class="text-sm font-medium mb-2 block">Estado</label>
              <Select v-model="status" @update:model-value="handleFilterChange">
                <SelectTrigger>
                  <SelectValue placeholder="Todos los estados" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Todos los estados</SelectItem>
                  <SelectItem value="pending">Pendientes</SelectItem>
                  <SelectItem value="closed">Cerrados</SelectItem>
                  <SelectItem value="paid">Pagados</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="flex items-end">
              <Button @click="clearFilters" variant="outline" class="w-full">
                Limpiar Filtros
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Lista de Rewards -->
      <Card>
        <CardHeader>
          <CardTitle>Detalle de Rewards</CardTitle>
          <CardDescription>{{ rewards.data?.length || 0 }} rewards encontrados</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!rewards.data || rewards.data.length === 0" class="text-center py-8">
            <AlertCircle class="h-12 w-12 text-muted-foreground mx-auto mb-3" />
            <p class="text-muted-foreground">No hay rewards que mostrar</p>
          </div>

          <div v-else class="space-y-4">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="border-b">
                  <tr>
                    <th class="text-left py-2 px-4 font-semibold">Cliente</th>
                    <th class="text-left py-2 px-4 font-semibold">Fondo</th>
                    <th class="text-right py-2 px-4 font-semibold">Inversión</th>
                    <th class="text-right py-2 px-4 font-semibold">Ganancias</th>
                    <th class="text-right py-2 px-4 font-semibold">Deducciones</th>
                    <th class="text-right py-2 px-4 font-semibold">Total Retorno</th>
                    <th class="text-left py-2 px-4 font-semibold">Estado</th>
                    <th class="text-left py-2 px-4 font-semibold">Acción</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="reward in rewards.data" :key="reward.id" class="hover:bg-muted/50 transition-colors">
                    <td class="py-2 px-4">
                      <div class="font-medium">{{ reward.client_name }}</div>
                      <div class="text-xs text-muted-foreground">{{ reward.client_email }}</div>
                    </td>
                    <td class="py-2 px-4">{{ reward.fund_name }}</td>
                    <td class="text-right py-2 px-4">{{ formatCurrency(reward.total_investment) }}</td>
                    <td class="text-right py-2 px-4 text-green-600 dark:text-green-400">+{{ formatCurrency(reward.total_earnings) }}</td>
                    <td class="text-right py-2 px-4 text-orange-600 dark:text-orange-400">-{{ formatCurrency(reward.company_deduction + reward.referral_deduction) }}</td>
                    <td class="text-right py-2 px-4 font-bold">{{ formatCurrency(reward.total_return) }}</td>
                    <td class="py-2 px-4">
                      <Badge :variant="getStatusBadge(reward.status).variant">
                        {{ getStatusBadge(reward.status).label }}
                      </Badge>
                    </td>
                    <td class="py-2 px-4">
                      <Link :href="route('admin.rewards.show', reward.id)">
                        <Button variant="ghost" size="sm">Ver</Button>
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Paginación -->
            <div v-if="rewards.links" class="flex justify-between items-center mt-4">
              <div class="text-sm text-muted-foreground">
                Mostrando {{ rewards.from }} a {{ rewards.to }} de {{ rewards.total }} resultados
              </div>
              <div class="flex gap-2">
                <Link
                  v-for="link in rewards.links"
                  :key="link.url"
                  :href="link.url || '#'"
                  :class="[
                    'px-3 py-1 rounded border text-sm',
                    link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'
                  ]"
                  v-html="link.label"
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
