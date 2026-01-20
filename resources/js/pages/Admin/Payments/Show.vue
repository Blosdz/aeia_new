<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { ArrowLeft, CheckCircle, XCircle, Clock, Copy, Download, AlertTriangle } from 'lucide-vue-next';
import { ref } from 'vue';

interface PayerProfile {
  id: number;
  first_name: string;
  last_name: string;
  dni: string;
  phone: string;
  phone_extension: string;
  country: string;
  city: string;
  verification_status: string;
  verified: boolean;
}

interface PlanType {
  id: number;
  name: string;
  category: string;
  amount_min: number;
  amount_max: number;
  membership: number;
  periodicity: string;
}

interface InvestmentEarning {
  id: number;
  initial_amount: number;
  current_amount: number;
  fund?: {
    id: number;
    name: string;
  };
}

interface Subscription {
  id: number;
  unique_code: string;
  started_at: string;
  expires_at: string | null;
  plan_type: PlanType;
  investment_earnings: InvestmentEarning[];
}

interface ClientAccount {
  id: number;
  bank_name: string;
  holder_name: string;
  last4: string;
  card_type: string;
  exp_month: number;
  exp_year: number;
}

interface Payment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  status: string;
  metadata?: {
    membership_applied?: number;
    [key: string]: any;
  };
  created_at: string;
  updated_at: string;
  is_refunded: boolean;
  payer_profile: PayerProfile;
  client_account: ClientAccount;
  subscription?: Subscription;
  payment_allocations: any[];
}

interface Props {
  payment: Payment;
  planTypes: PlanType[];
}

const props = defineProps<Props>();

const showValidateForm = ref(false);
const showRejectForm = ref(false);
const copiedField = ref<string | null>(null);

const validateForm = useForm({
  plan_type_id: props.payment.subscription?.plan_type?.id || '',
  notes: '',
});

const rejectForm = useForm({
  reason: '',
});

const handleValidate = () => {
  if (!validateForm.plan_type_id) {
    alert('No hay un plan asociado a este pago');
    return;
  }
  validateForm.post(route('admin.payments.validate', props.payment.id), {
    preserveScroll: true,
    onSuccess: () => {
      showValidateForm.value = false;
      validateForm.reset();
    },
    onError: (errors) => {
      console.error('Error validating payment:', errors);
      alert('Error al validar el pago: ' + (errors.error || 'Error desconocido'));
    },
  });
};

const handleReject = () => {
  if (!rejectForm.reason) {
    alert('Ingresa una razón de rechazo');
    return;
  }
  rejectForm.post(route('admin.payments.reject', props.payment.id), {
    preserveScroll: true,
    onSuccess: () => {
      showRejectForm.value = false;
      rejectForm.reset();
    },
  });
};

const copyToClipboard = (text: string, field: string) => {
  navigator.clipboard.writeText(text);
  copiedField.value = field;
  setTimeout(() => {
    copiedField.value = null;
  }, 2000);
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
};

const formatDate = (date: string | number) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getStatusIcon = (status: string) => {
  const icons: Record<string, any> = {
    pending: Clock,
    completed: CheckCircle,
    failed: XCircle,
  };
  return icons[status];
};

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'text-yellow-600',
    completed: 'text-green-600',
    failed: 'text-red-600',
  };
  return colors[status];
};
</script>

<template>
  <Head :title="`Pago ${payment.transaction_id}`" />
  <AppLayout>
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/admin/payments">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold">{{ payment.transaction_id }}</h1>
          <p class="text-muted-foreground mt-1">{{ formatDate(payment.created_at) }}</p>
        </div>
        <Badge :variant="payment.status === 'completed' ? 'default' : 'secondary'" class="flex items-center gap-1">
          <component :is="getStatusIcon(payment.status)" :class="`h-4 w-4 ${getStatusColor(payment.status)}`" />
          {{ payment.status }}
        </Badge>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información del Pago -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Información General -->
          <Card>
            <CardHeader>
              <CardTitle>Información General</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Monto</p>
                  <p class="text-2xl font-bold mt-1">{{ formatCurrency(payment.amount) }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Moneda</p>
                  <p class="text-lg font-bold mt-1">{{ payment.currency }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Cliente</p>
                  <p class="text-lg font-bold mt-1">{{ payment.payer_profile.first_name }} {{ payment.payer_profile.last_name }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Banco</p>
                  <p class="text-lg font-bold mt-1">{{ payment.client_account.bank_name }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Plan Asociado -->
          <Card v-if="payment.subscription?.plan_type">
            <CardHeader>
              <CardTitle>Plan Asociado</CardTitle>
              <CardDescription>Información del plan de inversión/cobertura</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Nombre del Plan</p>
                  <p class="text-lg font-bold mt-1">{{ payment.subscription.plan_type.name }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Categoría</p>
                  <Badge :variant="payment.subscription.plan_type.category === 'investment' ? 'default' : 'secondary'" class="mt-1">
                    {{ payment.subscription.plan_type.category === 'investment' ? 'Inversión' : 'Cobertura' }}
                  </Badge>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Rango de Inversión</p>
                  <p class="text-sm mt-1">{{ formatCurrency(payment.subscription.plan_type.amount_min) }} - {{ formatCurrency(payment.subscription.plan_type.amount_max) }}</p>
                </div>
                <div v-if="payment.subscription.plan_type.membership && payment.subscription.plan_type.membership > 0">
                  <p class="text-sm font-semibold text-muted-foreground">Membresía</p>
                  <p class="text-lg font-bold mt-1 text-purple-600 dark:text-purple-400">{{ formatCurrency(payment.subscription.plan_type.membership) }}</p>
                </div>
              </div>

              <!-- Mostrar si se aplicó membresía en este pago -->
              <div v-if="payment.metadata && payment.metadata.membership_applied"
                   class="mt-4 p-3 bg-purple-100 dark:bg-purple-900 rounded-lg border border-purple-200 dark:border-purple-800">
                <p class="text-sm font-semibold text-purple-900 dark:text-purple-100">Membresía Aplicada en este Pago</p>
                <p class="text-xl font-bold text-purple-600 dark:text-purple-400 mt-1">
                  {{ formatCurrency(payment.metadata.membership_applied) }}
                </p>
                <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">
                  Monto de inversión: {{ formatCurrency(payment.amount) }} + Membresía: {{ formatCurrency(payment.metadata.membership_applied) }}
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Detalles de la Transacción -->
          <Card>
            <CardHeader>
              <CardTitle>Detalles Técnicos</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <p class="text-sm font-semibold text-muted-foreground">ID de Transacción</p>
                <p class="font-mono text-sm mt-1 break-all">{{ payment.transaction_id }}</p>
              </div>
              <div>
                <p class="text-sm font-semibold text-muted-foreground">Metadata</p>
                <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-xs mt-1 overflow-auto">{{ JSON.stringify(payment.metadata, null, 2) }}</pre>
              </div>
            </CardContent>
          </Card>

          <!-- Suscripción si existe -->
          <Card v-if="payment.subscription">
            <CardHeader>
              <CardTitle>Suscripción Vinculada</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <p class="text-sm font-semibold text-muted-foreground">Código Único</p>
                <p class="font-bold text-lg mt-1">{{ payment.subscription.unique_code }}</p>
              </div>
              <div>
                <p class="text-sm font-semibold text-muted-foreground">Fecha de Inicio</p>
                <p class="text-lg mt-1">{{ formatDate(payment.subscription.started_at) }}</p>
              </div>
              <div v-if="payment.subscription.investment_earnings && payment.subscription.investment_earnings.length > 0">
                <p class="text-sm font-semibold text-muted-foreground mb-2">Inversiones en Fondos</p>
                <div class="space-y-2">
                  <div v-for="earning in payment.subscription.investment_earnings" :key="earning.id" class="p-3 border rounded">
                    <div class="flex justify-between items-center">
                      <span class="font-semibold">{{ earning.fund?.name || 'Fondo pendiente' }}</span>
                      <span class="font-bold">{{ formatCurrency(earning.current_amount) }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Inicial: {{ formatCurrency(earning.initial_amount) }}</p>
                  </div>
                </div>
              </div>
              <div v-else>
                <p class="text-sm font-semibold text-muted-foreground mb-2">Inversiones en Fondos</p>
                <p class="text-sm text-muted-foreground italic">No se encontraron inversiones en fondos</p>
              </div>
            </CardContent>
          </Card>

          <!-- Información del cliente -->
          <Card>
            <CardHeader>
              <CardTitle>Información del Cliente</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Nombre Completo</p>
                  <p class="text-lg font-bold mt-1">{{ payment.payer_profile.first_name }} {{ payment.payer_profile.last_name }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">DNI</p>
                  <p class="text-lg mt-1">{{ payment.payer_profile.dni }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Teléfono</p>
                  <p class="text-lg mt-1">+{{ payment.payer_profile.phone_extension }} {{ payment.payer_profile.phone }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">País</p>
                  <p class="text-lg mt-1">{{ payment.payer_profile.country }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Estado Verificación</p>
                  <Badge :variant="payment.payer_profile.verification_status === 'verified' ? 'default' : 'secondary'" class="mt-1">
                    {{ payment.payer_profile.verification_status }}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Información de la cuenta -->
          <Card>
            <CardHeader>
              <CardTitle>Información de la Cuenta de Pago</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Banco</p>
                  <p class="text-lg font-bold mt-1">{{ payment.client_account.bank_name }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Titular</p>
                  <p class="text-lg mt-1">{{ payment.client_account.holder_name }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Tipo de Tarjeta</p>
                  <p class="text-lg mt-1 uppercase">{{ payment.client_account.card_type }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Últimos 4 Dígitos</p>
                  <p class="text-lg font-mono mt-1">**** {{ payment.client_account.last4 }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-muted-foreground">Expiración</p>
                  <p class="text-lg mt-1">{{ payment.client_account.exp_month }}/{{ payment.client_account.exp_year }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Panel de Acciones -->
        <div class="space-y-4">
          <!-- Estado -->
          <Card>
            <CardHeader>
              <CardTitle class="text-base">Estado del Pago</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="payment.status === 'pending'" class="space-y-3">
                <!-- Verificar que existe un plan antes de mostrar el botón de validar -->
                <div v-if="!payment.subscription?.plan_type" class="p-3 bg-yellow-50 dark:bg-yellow-950 rounded-lg border border-yellow-200 dark:border-yellow-800">
                  <div class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                    <p class="text-sm text-yellow-900 dark:text-yellow-100">
                      Este pago no tiene un plan asociado. No se puede validar sin un plan.
                    </p>
                  </div>
                </div>

                <!-- Validar -->
                <div v-else-if="!showValidateForm">
                  <Button @click="showValidateForm = true" class="w-full gap-2 bg-green-600 hover:bg-green-700">
                    <CheckCircle class="h-5 w-5" />
                    Validar Pago
                  </Button>
                </div>
                <div v-else class="space-y-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg border border-green-200 dark:border-green-800">
                  <!-- Mostrar el plan asociado -->
                  <div class="p-3 bg-white dark:bg-gray-900 rounded-md border border-gray-300 dark:border-gray-600">
                    <Label class="text-sm text-muted-foreground">Plan Asociado</Label>
                    <p class="text-lg font-bold mt-1">{{ payment.subscription.plan_type.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ payment.subscription.plan_type.category === 'investment' ? 'Inversión' : 'Cobertura' }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                      Rango: {{ formatCurrency(payment.subscription.plan_type.amount_min) }} - {{ formatCurrency(payment.subscription.plan_type.amount_max) }}
                    </p>
                  </div>
                  <div>
                    <Label for="notes" class="text-sm">Notas (opcional)</Label>
                    <textarea
                      id="notes"
                      v-model="validateForm.notes"
                      placeholder="Ej: Pago verificado correctamente..."
                      class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                      rows="3"
                    ></textarea>
                  </div>
                  <div class="flex gap-2">
                    <Button @click="handleValidate" :disabled="validateForm.processing" class="flex-1 bg-green-600 hover:bg-green-700">
                      {{ validateForm.processing ? 'Validando...' : 'Confirmar Validación' }}
                    </Button>
                    <Button @click="showValidateForm = false" variant="outline" class="flex-1">Cancelar</Button>
                  </div>
                </div>

                <!-- Rechazar -->
                <div v-if="!showRejectForm">
                  <Button @click="showRejectForm = true" variant="destructive" class="w-full gap-2">
                    <XCircle class="h-5 w-5" />
                    Rechazar Pago
                  </Button>
                </div>
                <div v-else class="space-y-3 p-3 bg-red-50 dark:bg-red-950 rounded-lg border border-red-200 dark:border-red-800">
                  <div>
                    <Label for="reason" class="text-sm text-red-900 dark:text-red-100">Razón de Rechazo</Label>
                    <textarea
                      id="reason"
                      v-model="rejectForm.reason"
                      placeholder="Ej: Monto no coincide, cuenta bloqueada..."
                      class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                      rows="3"
                    ></textarea>
                  </div>
                  <div class="flex gap-2">
                    <Button @click="handleReject" :disabled="rejectForm.processing" variant="destructive" class="flex-1">
                      {{ rejectForm.processing ? 'Rechazando...' : 'Confirmar Rechazo' }}
                    </Button>
                    <Button @click="showRejectForm = false" variant="outline" class="flex-1">Cancelar</Button>
                  </div>
                </div>
              </div>

              <div v-else class="p-3 rounded-lg" :class="payment.status === 'completed' ? 'bg-green-50 dark:bg-green-950' : 'bg-red-50 dark:bg-red-950'">
                <p class="text-sm font-semibold mb-1">Este pago ya fue procesado</p>
                <p class="text-xs text-muted-foreground">Estado: {{ payment.status }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Resumen -->
          <Card>
            <CardHeader>
              <CardTitle class="text-base">Resumen</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Monto:</span>
                <span class="font-bold">{{ formatCurrency(payment.amount) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Suscripción:</span>
                <span class="font-bold">{{ payment.subscription ? 'Sí' : 'No' }}</span>
              </div>
              <div v-if="payment.subscription" class="flex justify-between border-t pt-2">
                <span>Fondos:</span>
                <span class="font-bold">{{ payment.subscription.investment_earnings?.length || 0 }}</span>
              </div>
              <div class="flex justify-between border-t pt-2">
                <span>Reembolsado:</span>
                <span class="font-bold">{{ payment.is_refunded ? 'Sí' : 'No' }}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
