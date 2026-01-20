<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar, DollarSign, Info } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue';

interface Payment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  status: string;
  created_at: string;
  metadata?: {
    membership_applied?: number;
    membership_plan_id?: number;
    membership_description?: string;
  };
  client_account?: any;
  subscriptions?: any[];
  rewards?: any[];
}

const props = defineProps<{
  payment: Payment;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Pagos', href: route('clients.payments') },
  { title: props.payment?.transaction_id, href: '#' },
];

const getStatusBadge = (status: string) => {
  const statusMap: Record<string, { label: string; variant: any }> = {
    pending: { label: 'Pendiente', variant: 'secondary' },
    completed: { label: 'Completado', variant: 'default' },
    failed: { label: 'Fallido', variant: 'destructive' },
    refunded: { label: 'Reembolsado', variant: 'outline' },
  };
  return statusMap[status] || { label: status, variant: 'secondary' };
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: props.payment?.currency || 'USD',
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Calcular monto de membresía
const membershipCharge = computed(() => {
  return props.payment?.metadata?.membership_applied ?? 0;
});

// Calcular monto total incluyendo membresía
const totalAmount = computed(() => {
  return (props.payment?.amount ?? 0) + membershipCharge.value;
});
</script>

<template>
  <Head title="Detalle de Pago" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold">{{ payment?.transaction_id }}</h1>
          <p class="text-muted-foreground mt-1">Detalles de la transacción</p>
        </div>
        <Badge :variant="getStatusBadge(payment?.status).variant">
          {{ getStatusBadge(payment?.status).label }}
        </Badge>
      </div>

      <!-- Información Principal -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm flex items-center gap-2">
              <DollarSign class="h-4 w-4" />
              Monto de Inversión
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold">{{ formatCurrency(payment?.amount) }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm flex items-center gap-2">
              <DollarSign class="h-4 w-4" />
              {{ membershipCharge > 0 ? 'Total a Pagar' : 'Monto' }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p :class="['text-3xl font-bold', membershipCharge > 0 ? 'text-green-600 dark:text-green-400' : '']">
              {{ formatCurrency(totalAmount) }}
            </p>
            <p v-if="membershipCharge > 0" class="text-xs text-muted-foreground mt-1">
              + {{ formatCurrency(membershipCharge) }} membresía
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm flex items-center gap-2">
              <Calendar class="h-4 w-4" />
              Fecha
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-medium">{{ formatDate(payment?.created_at) }}</p>
          </CardContent>
        </Card>
      </div>

      <!-- Detalles Extendidos -->
      <Card>
        <CardHeader>
          <CardTitle>Información Detallada</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-muted-foreground">ID de Transacción</p>
                <p class="font-mono font-medium">{{ payment?.transaction_id }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Estado</p>
                <p class="font-medium capitalize">{{ payment?.status }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Monto de Inversión</p>
                <p class="font-bold">{{ formatCurrency(payment?.amount) }} {{ payment?.currency }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Fecha de Creación</p>
                <p class="font-medium">{{ formatDate(payment?.created_at) }}</p>
              </div>
            </div>

            <!-- Desglose de cargos si incluye membresía -->
            <div v-if="membershipCharge > 0" class="border-t pt-4">
              <p class="font-semibold mb-3">Desglose de Cargos</p>
              <div class="space-y-2 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Inversión:</span>
                  <span class="font-medium">{{ formatCurrency(payment?.amount) }}</span>
                </div>
                <div class="flex justify-between pb-2 border-b">
                  <span class="text-sm text-muted-foreground">Membresía (Pago Único):</span>
                  <span class="font-medium text-purple-600 dark:text-purple-400">+ {{ formatCurrency(membershipCharge) }}</span>
                </div>
                <div class="flex justify-between pt-2">
                  <span class="font-semibold">Total Cobrado:</span>
                  <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatCurrency(totalAmount) }}</span>
                </div>
              </div>
              <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1">
                <Info class="h-3 w-3" />
                El costo de membresía se cobra una sola vez por plan.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Suscripciones Asociadas -->
      <Card v-if="payment?.subscriptions && payment.subscriptions.length > 0">
        <CardHeader>
          <CardTitle>Suscripciones Asociadas</CardTitle>
          <CardDescription>{{ payment.subscriptions.length }} suscripciones vinculadas a este pago</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div v-for="sub in payment.subscriptions" :key="sub.id" class="p-3 border rounded-lg">
              <p class="font-medium">{{ sub.planType?.name }}</p>
              <p class="text-xs text-muted-foreground">Código: {{ sub.unique_code }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recompensas Asociadas -->
      <Card v-if="payment?.rewards && payment.rewards.length > 0">
        <CardHeader>
          <CardTitle>Recompensas Generadas</CardTitle>
          <CardDescription>{{ payment.rewards.length }} recompensa(s) generada(s)</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div v-for="reward in payment.rewards" :key="reward.id" class="p-3 border rounded-lg">
              <div class="flex justify-between">
                <div>
                  <p class="font-medium capitalize">{{ reward.reason }}</p>
                  <p class="text-xs text-muted-foreground">{{ formatDate(reward.created_at) }}</p>
                </div>
                <p class="font-bold">{{ formatCurrency(reward.amount) }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
