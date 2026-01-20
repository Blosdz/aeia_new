<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Plus, Trash2, DollarSign } from 'lucide-vue-next';
import { ref } from 'vue';

interface Payment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  created_at: string;
  payer_profile: {
    first_name: string;
    last_name: string;
    dni: string;
  };
  subscription?: {
    id: number;
    unique_code: string;
    plan_type?: {
      name: string;
      category: string;
    };
  };
}

interface Props {
  pendingPayments: Payment[];
}

const props = defineProps<Props>();

const form = useForm({
  name: '',
  category: 'investment',
  description: '',
  payment_ids: [] as number[],
});

const selectedPayments = ref<Set<number>>(new Set());

const togglePayment = (paymentId: number) => {
  if (selectedPayments.value.has(paymentId)) {
    selectedPayments.value.delete(paymentId);
  } else {
    selectedPayments.value.add(paymentId);
  }
  form.payment_ids = Array.from(selectedPayments.value);
};

const selectAll = () => {
  if (selectedPayments.value.size === props.pendingPayments.length) {
    selectedPayments.value.clear();
  } else {
    props.pendingPayments.forEach(p => selectedPayments.value.add(p.id));
  }
  form.payment_ids = Array.from(selectedPayments.value);
};

const getTotalAmount = () => {
  return props.pendingPayments
    .filter(p => selectedPayments.value.has(p.id))
    .reduce((sum, p) => sum + p.amount, 0);
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
};

const handleSubmit = () => {
  if (!form.name.trim()) {
    alert('Ingresa un nombre para el fondo');
    return;
  }
  if (form.payment_ids.length === 0) {
    alert('Selecciona al menos un pago');
    return;
  }
  form.post(route('admin.payments.create_fund'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      selectedPayments.value.clear();
    },
  });
};
</script>

<template>
  <Head title="Crear Fondo" />
  <AppLayout>
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/admin/payments/dashboard">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold">Crear Nuevo Fondo</h1>
          <p class="text-muted-foreground mt-1">Selecciona pagos validados para crear un fondo colectivo</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Formulario -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Información General -->
          <Card>
            <CardHeader>
              <CardTitle>Información del Fondo</CardTitle>
              <CardDescription>Completa los detalles básicos del fondo</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <Label for="name" class="text-sm font-semibold">Nombre del Fondo *</Label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  placeholder="Ej: Fondo Tecnología Q4 2025"
                  class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <Label for="category" class="text-sm font-semibold">Categoría *</Label>
                <select
                  id="category"
                  v-model="form.category"
                  class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                >
                  <option value="investment">Inversión</option>
                  <option value="coverage">Cobertura</option>
                </select>
              </div>

              <div>
                <Label for="description" class="text-sm font-semibold">Descripción (opcional)</Label>
                <textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Ej: Fondo de inversión colectiva en startups de tecnología..."
                  class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                  rows="3"
                ></textarea>
              </div>
            </CardContent>
          </Card>

          <!-- Selección de Pagos -->
          <Card>
            <CardHeader>
              <CardTitle>Seleccionar Pagos</CardTitle>
              <CardDescription>{{ selectedPayments.size }} de {{ pendingPayments.length }} pagos seleccionados</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Botón Seleccionar Todo -->
              <div class="flex justify-end">
                <Button
                  @click="selectAll"
                  variant="outline"
                  size="sm"
                  class="gap-2"
                >
                  <Plus class="h-4 w-4" />
                  {{ selectedPayments.size === pendingPayments.length ? 'Deseleccionar Todo' : 'Seleccionar Todo' }}
                </Button>
              </div>

              <!-- Lista de Pagos -->
              <div class="space-y-2 max-h-96 overflow-y-auto">
                <div
                  v-if="pendingPayments.length === 0"
                  class="text-center py-8 text-muted-foreground border rounded-lg"
                >
                  <DollarSign class="h-12 w-12 mx-auto mb-3 opacity-50" />
                  <p class="font-semibold mb-1">No hay pagos disponibles</p>
                  <p class="text-xs">Todos los pagos completados ya han sido asignados a fondos</p>
                </div>

                <div
                  v-for="payment in pendingPayments"
                  :key="payment.id"
                  @click="togglePayment(payment.id)"
                  :class="[
                    'p-4 border rounded-lg cursor-pointer transition',
                    selectedPayments.has(payment.id)
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-950'
                      : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="flex items-start gap-3">
                    <input
                      type="checkbox"
                      :checked="selectedPayments.has(payment.id)"
                      class="w-4 h-4 mt-1"
                    />
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between mb-1">
                        <p class="font-semibold text-sm">
                          {{ payment.payer_profile.first_name }} {{ payment.payer_profile.last_name }}
                        </p>
                        <Badge variant="outline" class="ml-2 font-bold">
                          {{ formatCurrency(payment.amount) }}
                        </Badge>
                      </div>
                      <p class="text-xs text-muted-foreground mb-1">DNI: {{ payment.payer_profile.dni }}</p>
                      <p class="text-xs text-muted-foreground font-mono mb-1">{{ payment.transaction_id }}</p>
                      <div class="flex items-center gap-2 mt-2">
                        <Badge v-if="payment.subscription?.plan_type" variant="secondary" class="text-xs">
                          {{ payment.subscription.plan_type.name }}
                        </Badge>
                        <span class="text-xs text-muted-foreground">
                          {{ new Date(payment.created_at).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Resumen -->
        <div class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle class="text-base">Resumen</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <p class="text-xs text-muted-foreground mb-1">Pagos Seleccionados</p>
                <p class="text-2xl font-bold">{{ selectedPayments.size }}</p>
                <p class="text-xs text-muted-foreground mt-1">de {{ pendingPayments.length }} disponibles</p>
              </div>

              <div class="border-t pt-4">
                <p class="text-xs text-muted-foreground mb-1">Monto Total del Fondo</p>
                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(getTotalAmount()) }}</p>
              </div>

              <div class="border-t pt-4">
                <p class="text-xs text-muted-foreground mb-1">Categoría</p>
                <Badge class="capitalize">
                  {{ form.category === 'investment' ? 'Inversión' : 'Cobertura' }}
                </Badge>
              </div>

              <!-- Desglose de Porcentajes (preview) -->
              <div v-if="selectedPayments.size > 0" class="border-t pt-4">
                <p class="text-xs text-muted-foreground mb-2">Vista Previa de Participación</p>
                <div class="space-y-1 max-h-32 overflow-y-auto">
                  <div
                    v-for="payment in pendingPayments.filter(p => selectedPayments.has(p.id))"
                    :key="payment.id"
                    class="flex justify-between items-center text-xs"
                  >
                    <span class="truncate">{{ payment.payer_profile.first_name }} {{ payment.payer_profile.last_name }}</span>
                    <span class="font-semibold ml-2">{{ ((payment.amount / getTotalAmount()) * 100).toFixed(2) }}%</span>
                  </div>
                </div>
              </div>

              <div class="border-t pt-4 space-y-2">
                <Button
                  @click="handleSubmit"
                  :disabled="form.processing || selectedPayments.size === 0 || !form.name.trim()"
                  class="w-full bg-green-600 hover:bg-green-700 gap-2"
                >
                  <Plus class="h-4 w-4" />
                  {{ form.processing ? 'Creando...' : 'Crear Fondo' }}
                </Button>
                <Link href="/admin/payments/dashboard">
                  <Button variant="outline" class="w-full">Cancelar</Button>
                </Link>
              </div>
            </CardContent>
          </Card>

          <!-- Info -->
          <Card>
            <CardHeader>
              <CardTitle class="text-sm">¿Cómo funciona?</CardTitle>
            </CardHeader>
            <CardContent class="text-xs space-y-2 text-muted-foreground">
              <p><strong>Al crear el fondo se generará automáticamente:</strong></p>
              <p>• Registro del fondo en la tabla <code>funds</code></p>
              <p>• Primera entrada en <code>fund_histories</code></p>
              <p>• Para cada pago seleccionado:</p>
              <div class="ml-4 space-y-1">
                <p>→ <code>subscription_participant</code> (owner)</p>
                <p>→ <code>investment_earnings</code> (con porcentaje)</p>
                <p>→ <code>investment_earnings_history</code></p>
                <p>→ <code>payment_allocations</code></p>
              </div>
              <p class="mt-2">• Los porcentajes se calculan según el monto invertido</p>
              <p>• El historial es inmutable para auditoría</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
