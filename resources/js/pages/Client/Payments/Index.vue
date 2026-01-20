<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { CreditCard, Plus, Eye } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Payment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  created_at: string;
  metadata?: {
    membership_applied?: number;
  };
  client_account?: {
    id: number;
    bank_name: string;
    holder_name: string;
  };
}

interface PaginatedData {
  data: Payment[];
  links: any;
  meta: any;
}

const props = defineProps<{
  payments: PaginatedData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Pagos', href: route('clients.payments') },
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

const formatCurrency = (amount: number, currency: string) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: currency || 'USD',
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

const getPaymentTotal = (payment: Payment) => {
  let total = payment.amount;
  if (payment.metadata?.membership_applied) {
    total += payment.metadata.membership_applied;
  }
  return total;
};

const hasMembership = (payment: Payment) => {
  return payment.metadata?.membership_applied && payment.metadata.membership_applied > 0;
};
</script>

<template>
  <Head title="Mis Pagos" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header con Botón -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold">Mis Pagos</h1>
          <p class="text-muted-foreground">Historial de transacciones e inversiones</p>
        </div>
        <Link :href="route('clients.payments.select')">
          <Button class="flex items-center gap-2">
            <Plus class="h-4 w-4" />
            Nuevo Pago
          </Button>
        </Link>
      </div>

      <!-- Listado de Pagos -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <CreditCard class="h-5 w-5" />
            Historial de Transacciones
          </CardTitle>
          <CardDescription>{{ props.payments?.data?.length || 0 }} transacciones encontradas</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!props.payments?.data || props.payments.data.length === 0" class="text-center py-12">
            <CreditCard class="h-12 w-12 mx-auto text-gray-400 mb-2" />
            <p class="text-muted-foreground">No tienes pagos registrados</p>
            <p class="text-sm text-gray-500 mt-2">Crea tu primer pago para empezar a invertir</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="payment in props.payments.data"
              :key="payment.id"
              class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 transition"
            >
              <div class="flex items-center gap-4 flex-1">
                <div class="bg-blue-100 dark:bg-blue-950 p-3 rounded-lg">
                  <CreditCard class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                </div>
                <div class="flex-1">
                  <p class="font-medium">{{ payment.transaction_id }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ payment.client_account?.bank_name }} • {{ payment.client_account?.holder_name }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-4">
                <div class="text-right">
                  <div>
                    <p class="font-bold text-lg">{{ formatCurrency(getPaymentTotal(payment), payment.currency) }}</p>
                    <p v-if="hasMembership(payment)" class="text-xs text-purple-600 dark:text-purple-400">
                      ({{ formatCurrency(payment.amount, payment.currency) }} + membresía)
                    </p>
                  </div>
                  <p class="text-xs text-muted-foreground mt-1">{{ formatDate(payment.created_at) }}</p>
                </div>

                <Badge :variant="getStatusBadge(payment.status).variant">
                  {{ getStatusBadge(payment.status).label }}
                </Badge>

                <Link :href="route('clients.payments.show', payment.id)">
                  <Button variant="ghost" size="sm">
                    <Eye class="h-4 w-4" />
                  </Button>
                </Link>
              </div>
            </div>
          </div>

          <!-- Paginación -->
          <div v-if="props.payments?.meta?.total > 15" class="flex gap-2 mt-6 justify-center">
            <div v-for="link in props.payments.links" :key="link.label">
              <Link v-if="link.url && link.label !== '&laquo; Anterior' && link.label !== 'Siguiente &raquo;'" :href="link.url" class="text-blue-600">
                <Button :variant="link.active ? 'default' : 'outline'" size="sm">{{ link.label }}</Button>
              </Link>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
