<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { TrendingUp, TrendingDown, DollarSign, Users, Zap, AlertCircle, Plus } from 'lucide-vue-next';

interface Payment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  created_at: string;
  payer_profile: {
    first_name: string;
    last_name: string;
  };
  subscription?: {
    plan_type?: {
      name: string;
      category: string;
    };
  };
}

interface Props {
  pendingSummary: {
    count: number;
    total_amount: number;
    payments: any[];
  };
  subscriptionsSummary: {
    count: number;
    total_invested: number;
    total_current: number;
    total_gain_loss: number;
    subscriptions: any[];
  };
  fundsSummary: {
    count: number;
    total_invested: number;
    total_current: number;
    funds: any[];
  };
  completedPaymentsWithoutFund: Payment[];
}

defineProps<Props>();

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount);
};

const formatPercent = (value: number) => {
  return `${value >= 0 ? '+' : ''}${value.toFixed(2)}%`;
};
</script>

<template>
  <Head title="Dashboard de Pagos" />
  <AppLayout>
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold">Dashboard de Pagos y Fondos</h1>
        <p class="text-muted-foreground mt-1">Gestión de pagos, suscripciones e inversiones</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pagos Pendientes -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <AlertCircle class="h-4 w-4 text-yellow-600" />
              Pagos Pendientes
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ pendingSummary.count }}</div>
            <p class="text-xs text-muted-foreground mt-1">Total: {{ formatCurrency(pendingSummary.total_amount) }}</p>
          </CardContent>
        </Card>

        <!-- Suscripciones Activas -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <Users class="h-4 w-4 text-blue-600" />
              Suscripciones Activas
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ subscriptionsSummary.count }}</div>
            <p class="text-xs text-muted-foreground mt-1">Invertido: {{ formatCurrency(subscriptionsSummary.total_invested) }}</p>
          </CardContent>
        </Card>

        <!-- Fondos Activos -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <DollarSign class="h-4 w-4 text-green-600" />
              Fondos Activos
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ fundsSummary.count }}</div>
            <p class="text-xs text-muted-foreground mt-1">Total: {{ formatCurrency(fundsSummary.total_current) }}</p>
          </CardContent>
        </Card>

        <!-- Ganancia Total -->
        <Card :class="subscriptionsSummary.total_gain_loss >= 0 ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800'">
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <component :is="subscriptionsSummary.total_gain_loss >= 0 ? TrendingUp : TrendingDown" class="h-4 w-4" :class="subscriptionsSummary.total_gain_loss >= 0 ? 'text-green-600' : 'text-red-600'" />
              Ganancia/Pérdida
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold" :class="subscriptionsSummary.total_gain_loss >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatCurrency(subscriptionsSummary.total_gain_loss) }}
            </div>
            <p class="text-xs text-muted-foreground mt-1">{{ formatPercent((subscriptionsSummary.total_gain_loss / subscriptionsSummary.total_invested) * 100) }}</p>
          </CardContent>
        </Card>
      </div>

      <!-- Acciones Rápidas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Button @click="router.visit(route('admin.payments.index'))" class="w-full gap-2" size="lg">
          <Zap class="h-5 w-5" />
          Ver Pagos
        </Button>
        <Button @click="router.visit(route('admin.payments.create_fund_form'))" variant="outline" class="w-full gap-2" size="lg">
          <DollarSign class="h-5 w-5" />
          Crear Fondo
        </Button>
        <Button @click="router.visit(route('admin.payments.update_fund_value_form'))" variant="outline" class="w-full gap-2" size="lg">
          <TrendingUp class="h-5 w-5" />
          Actualizar Valores
        </Button>
      </div>

      <!-- Detalles por Categoría -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pagos Pendientes Detalle -->
        <Card v-if="pendingSummary.count > 0">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <AlertCircle class="h-5 w-5" />
              Últimos Pagos Pendientes
            </CardTitle>
            <CardDescription>{{ pendingSummary.count }} pagos requieren validación</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="payment in pendingSummary.payments.slice(0, 5)" :key="payment.id" class="flex justify-between items-center p-3 border rounded">
                <div>
                  <p class="font-semibold text-sm">{{ payment.payer_profile.first_name }} {{ payment.payer_profile.last_name }}</p>
                  <p class="text-xs text-muted-foreground">{{ payment.transaction_id }}</p>
                </div>
                <Badge variant="outline">{{ formatCurrency(payment.amount) }}</Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Fondos Detalle -->
        <Card v-if="fundsSummary.count > 0">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <DollarSign class="h-5 w-5" />
              Fondos Principales
            </CardTitle>
            <CardDescription>{{ fundsSummary.count }} fondos activos</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="fund in fundsSummary.funds.slice(0, 5)" :key="fund.id" class="flex justify-between items-center p-3 border rounded">
                <div>
                  <p class="font-semibold text-sm">{{ fund.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ fund.category }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold">{{ formatCurrency(fund.current_amount) }}</p>
                  <p :class="fund.current_amount - fund.initial_amount >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-semibold">
                    {{ formatPercent(((fund.current_amount - fund.initial_amount) / fund.initial_amount) * 100) }}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Pagos Completados Sin Asignar a Fondos -->
      <Card v-if="completedPaymentsWithoutFund.length > 0">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <DollarSign class="h-5 w-5 text-blue-600" />
            Pagos Completados Sin Asignar a Fondos
          </CardTitle>
          <CardDescription>{{ completedPaymentsWithoutFund.length }} pagos disponibles para crear fondos</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b">
                  <th class="text-left py-3 px-4 font-semibold text-sm">Cliente</th>
                  <th class="text-left py-3 px-4 font-semibold text-sm">Transaction ID</th>
                  <th class="text-left py-3 px-4 font-semibold text-sm">Plan</th>
                  <th class="text-right py-3 px-4 font-semibold text-sm">Monto</th>
                  <th class="text-center py-3 px-4 font-semibold text-sm">Fecha</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="payment in completedPaymentsWithoutFund"
                  :key="payment.id"
                  class="border-b hover:bg-gray-50 dark:hover:bg-gray-900 transition"
                >
                  <td class="py-3 px-4">
                    <p class="font-medium text-sm">{{ payment.payer_profile.first_name }} {{ payment.payer_profile.last_name }}</p>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-xs text-muted-foreground font-mono">{{ payment.transaction_id }}</p>
                  </td>
                  <td class="py-3 px-4">
                    <Badge v-if="payment.subscription?.plan_type" variant="outline" class="text-xs">
                      {{ payment.subscription.plan_type.name }}
                    </Badge>
                    <span v-else class="text-xs text-muted-foreground">Sin plan</span>
                  </td>
                  <td class="py-3 px-4 text-right">
                    <span class="font-bold text-sm">{{ formatCurrency(payment.amount) }}</span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <span class="text-xs text-muted-foreground">{{ new Date(payment.created_at).toLocaleDateString('es-ES') }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-4 flex justify-between items-center pt-4 border-t">
            <div>
              <p class="text-sm font-semibold">Total disponible para fondos:</p>
              <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(completedPaymentsWithoutFund.reduce((sum, p) => sum + p.amount, 0)) }}</p>
            </div>
            <Button @click="router.visit(route('admin.payments.create_fund_form'))" class="gap-2 bg-blue-600 hover:bg-blue-700">
              <Plus class="h-4 w-4" />
              Crear Fondo con Pagos
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
