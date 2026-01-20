<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { AlertCircle, CheckCircle, ArrowRight, CreditCard, Info, Building2 } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Plan {
  id: number;
  name: string;
  amount_min: number;
  amount_max: number;
  membership?: number | null;
  category?: string;
  img_url?: string | null;
}

interface ClientAccount {
  id: number;
  bank_name: string;
  holder_name: string;
  last4: string;
}

interface Payment {
  investment_amount: number;
  membership_charge: number;
  total_amount: number;
  includes_membership: boolean;
  currency: string;
  client_account_id: number;
  plan_type_id: number;
  transaction_id: string;
}

const props = defineProps<{
  plan: Plan;
  clientAccount: ClientAccount;
  payment: Payment;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Pagos', href: route('clients.payments') },
  { title: 'Seleccionar Plan', href: route('clients.payments.select') },
  { title: 'Confirmación', href: '#' },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: props.payment.currency || 'USD',
    minimumFractionDigits: 2,
  }).format(amount);
};

// Formulario para confirmar y procesar el pago
const confirmForm = useForm({
  plan_type_id: props.payment.plan_type_id,
  investment_amount: props.payment.investment_amount,
  membership_charge: props.payment.membership_charge,
  client_account_id: props.payment.client_account_id,
  currency: props.payment.currency,
  transaction_id: props.payment.transaction_id,
});

const processPayment = () => {
  confirmForm.post(route('clients.payments.confirm'), {
    preserveScroll: true,
    onSuccess: () => {
      // Redirigir al dashboard o a la página de éxito (manejado por el controlador)
    },
    onError: (errors) => {
      console.error('Error al procesar el pago:', errors);
    },
  });
};


</script>

<template>
  <Head :title="`Confirmación de Pago - ${plan?.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div>
        <h1 class="text-3xl font-bold">Confirmación de Pago</h1>
        <p class="text-muted-foreground mt-1">Revisa los detalles antes de procesar el pago</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Información principal -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Plan -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <CheckCircle class="h-5 w-5 text-blue-600" />
                Plan Seleccionado
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div>
                <p class="text-sm text-muted-foreground">Nombre del Plan</p>
                <p class="text-lg font-semibold">{{ plan.name }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Rango de Inversión</p>
                <p class="text-sm">{{ formatCurrency(plan.amount_min) }} - {{ formatCurrency(plan.amount_max) }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Cuenta de pago -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <CheckCircle class="h-5 w-5 text-green-600" />
                Cuenta de Pago
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div>
                <p class="text-sm text-muted-foreground">Banco</p>
                <p class="text-lg font-semibold">{{ clientAccount.bank_name }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Titular</p>
                <p class="text-sm">{{ clientAccount.holder_name }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Últimos 4 dígitos</p>
                <p class="text-sm font-mono">•••• {{ clientAccount.last4 }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Aviso importante -->
          <div class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4 flex gap-3">
            <AlertCircle class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
            <div>
              <p class="font-medium text-blue-900 dark:text-blue-100">Transacción Segura</p>
              <p class="text-sm text-blue-700 dark:text-blue-200 mt-1">Tu pago será procesado de forma segura. Tu información de pago no será compartida con terceros.</p>
            </div>
          </div>
        </div>

        <!-- Resumen de pago -->
        <div class="space-y-4">
          <!-- Resumen detallado -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Resumen de Pago</CardTitle>
              <CardDescription>Detalles del monto a cobrar</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Inversión -->
              <div class="flex justify-between pb-3 border-b">
                <span class="text-muted-foreground">Inversión:</span>
                <span class="font-medium">{{ formatCurrency(payment.investment_amount) }}</span>
              </div>

              <!-- Membresía (si aplica) -->
              <div v-if="payment.includes_membership && payment.membership_charge > 0" class="flex justify-between pb-3 border-b">
                <div>
                  <span class="text-muted-foreground">Membresía:</span>
                  <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                    <CreditCard class="h-3 w-3" />
                    Pago único
                  </p>
                </div>
                <span class="font-medium text-purple-600 dark:text-purple-400">+ {{ formatCurrency(payment.membership_charge) }}</span>
              </div>

              <!-- Total -->
              <div class="flex justify-between pt-2">
                <span class="font-semibold text-lg">Total a Pagar:</span>
                <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(payment.total_amount) }}</span>
              </div>

              <!-- Descripción de membresía -->
              <div v-if="payment.includes_membership && payment.membership_charge > 0" class="mt-4 p-3 bg-purple-100 dark:bg-purple-900 rounded-lg text-sm text-purple-800 dark:text-purple-200">
                <p class="font-medium flex items-center gap-2">
                  <Info class="h-4 w-4" />
                  Nota sobre Membresía
                </p>
                <p class="mt-1">El costo de membresía se cobra una sola vez. Los próximos pagos en este plan no incluirán este cargo.</p>
              </div>

              <!-- Transacción ID -->
              <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded text-xs font-mono text-gray-600 dark:text-gray-400">
                <p class="text-gray-500 dark:text-gray-400 mb-1">ID Transacción:</p>
                {{ payment.transaction_id }}
              </div>
            </CardContent>
          </Card>

          <!-- Botones de acción -->
          <div class="space-y-2">
            <Button
              @click="processPayment"
              :disabled="confirmForm.processing"
              class="w-full bg-green-600 hover:bg-green-700 text-lg py-6"
            >
              <CheckCircle class="h-5 w-5 mr-2" />
              {{ confirmForm.processing ? 'Procesando...' : 'Procesar Pago' }}
            </Button>
            <Link href="/clients/payments/select" class="block">
              <Button variant="outline" class="w-full">
                Cambiar Plan
              </Button>
            </Link>
          </div>

          <!-- Métodos de pago -->
          <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg text-xs text-muted-foreground text-center">
            <p>Métodos de pago disponibles:</p>
            <p class="mt-1 flex items-center justify-center gap-3">
              <span class="flex items-center gap-1">
                <CreditCard class="h-3 w-3" />
                Tarjeta de Crédito
              </span>
              <span>•</span>
              <span class="flex items-center gap-1">
                <Building2 class="h-3 w-3" />
                Transferencia Bancaria
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
