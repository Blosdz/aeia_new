<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { CheckCircle, XCircle, Clock, Eye, AlertCircle, ChevronDown } from 'lucide-vue-next';
import { ref } from 'vue';

interface Payment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  status: string;
  created_at: string;
  payer_profile: {
    first_name: string;
    last_name: string;
    email: string;
  };
  client_account: {
    bank_name: string;
    holder_name: string;
  };
}

interface Props {
  payments: {
    data: Payment[];
    meta: any;
    links: any;
  };
  filters: {
    status: string | null;
    search: string | null;
  };
  summary: {
    count: number;
    total_amount: number;
  };
}

const props = defineProps<Props>();

const searchForm = useForm({
  search: props.filters.search || '',
  status: props.filters.status || '',
});

const expandedRows = ref<Set<number>>(new Set());
const quickActionForms = ref<Record<number, any>>({});

const handleSearch = () => {
  searchForm.get(route('admin.payments.index'), {
    preserveScroll: true,
  });
};

const toggleRow = (paymentId: number) => {
  if (expandedRows.value.has(paymentId)) {
    expandedRows.value.delete(paymentId);
  } else {
    expandedRows.value.add(paymentId);
  }
};

const initializeQuickAction = (payment: Payment) => {
  if (!quickActionForms.value[payment.id]) {
    quickActionForms.value[payment.id] = {
      validate: useForm({
        plan_type_id: '',
        notes: '',
      }),
      reject: useForm({
        reason: '',
      }),
      showValidate: false,
      showReject: false,
    };
  }
  return quickActionForms.value[payment.id];
};

const handleQuickValidate = (payment: Payment) => {
  const form = quickActionForms.value[payment.id];
  if (!form.validate.plan_type_id) {
    alert('Selecciona un plan type');
    return;
  }
  form.validate.post(route('admin.payments.validate', payment.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.showValidate = false;
      form.validate.reset();
    },
  });
};

const handleQuickReject = (payment: Payment) => {
  const form = quickActionForms.value[payment.id];
  if (!form.reject.reason) {
    alert('Ingresa una razón de rechazo');
    return;
  }
  form.reject.post(route('admin.payments.reject', payment.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.showReject = false;
      form.reject.reset();
    },
  });
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
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
    pending: 'text-yellow-600 dark:text-yellow-400',
    completed: 'text-green-600 dark:text-green-400',
    failed: 'text-red-600 dark:text-red-400',
  };
  return colors[status];
};

const getStatusBadge = (status: string) => {
  const variants: Record<string, string> = {
    pending: 'secondary',
    completed: 'default',
    failed: 'destructive',
  };
  return variants[status];
};
</script>

<template>
  <Head title="Gestión de Pagos" />
  <AppLayout>
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold">Gestión de Pagos</h1>
          <p class="text-muted-foreground mt-1">{{ summary.count }} pagos pendientes • Total: {{ formatCurrency(summary.total_amount) }}</p>
        </div>
        <Link :href="route('admin.payments.dashboard')">
          <Button>Ver Dashboard</Button>
        </Link>
      </div>

      <!-- Filtros -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">Filtros y Búsqueda</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label for="search">Buscar</Label>
              <input
                id="search"
                v-model="searchForm.search"
                type="text"
                placeholder="Nombre, email, transaction_id..."
                class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              />
            </div>
            <div>
              <Label for="status">Estado</Label>
              <select
                id="status"
                v-model="searchForm.status"
                class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option value="">Todos</option>
                <option value="pending">Pendiente</option>
                <option value="completed">Completado</option>
                <option value="failed">Fallido</option>
              </select>
            </div>
            <div class="flex items-end">
              <Button @click="handleSearch" class="w-full">Buscar</Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Tabla de Pagos con Acciones Rápidas -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <AlertCircle class="h-5 w-5" />
            Listado de Pagos
          </CardTitle>
          <CardDescription>Haz clic en expandir para validar o denegar pagos rápidamente</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th class="text-left py-3 px-2 font-semibold w-8"></th>
                  <th class="text-left py-3 px-2 font-semibold">Referencia</th>
                  <th class="text-left py-3 px-2 font-semibold">Cliente</th>
                  <th class="text-left py-3 px-2 font-semibold">Email</th>
                  <th class="text-left py-3 px-2 font-semibold">Monto</th>
                  <th class="text-left py-3 px-2 font-semibold">Estado</th>
                  <th class="text-left py-3 px-2 font-semibold">Fecha</th>
                  <th class="text-left py-3 px-2 font-semibold">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="payment in payments.data" :key="payment.id">
                  <!-- Fila principal -->
                  <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-900 transition">
                    <td class="py-3 px-2">
                      <button
                        v-if="payment.status === 'pending'"
                        @click="toggleRow(payment.id)"
                        class="hover:bg-gray-200 dark:hover:bg-gray-700 p-1 rounded transition"
                      >
                        <ChevronDown
                          class="h-4 w-4 transition-transform"
                          :class="expandedRows.has(payment.id) ? 'rotate-180' : ''"
                        />
                      </button>
                    </td>
                    <td class="py-3 px-2 font-mono text-xs font-semibold">{{ payment.transaction_id.substring(0, 12) }}</td>
                    <td class="py-3 px-2">
                      <p class="font-semibold text-sm">{{ payment.payer_profile.first_name }} {{ payment.payer_profile.last_name }}</p>
                      <p class="text-xs text-muted-foreground">{{ payment.client_account.bank_name }}</p>
                    </td>
                    <td class="py-3 px-2 text-xs break-all">{{ payment.payer_profile.email }}</td>
                    <td class="py-3 px-2 font-bold text-base">{{ formatCurrency(payment.amount) }}</td>
                    <td class="py-3 px-2">
                      <Badge :variant="getStatusBadge(payment.status)" class="flex items-center gap-1 w-fit">
                        <component :is="getStatusIcon(payment.status)" :class="`h-3 w-3 ${getStatusColor(payment.status)}`" />
                        <span class="capitalize">{{ payment.status }}</span>
                      </Badge>
                    </td>
                    <td class="py-3 px-2 text-xs whitespace-nowrap">{{ formatDate(payment.created_at) }}</td>
                    <td class="py-3 px-2">
                      <div class="flex gap-2">
                        <Link :href="route('admin.payments.show', payment.id)">
                          <Button variant="ghost" size="sm" class="gap-1">
                            <Eye class="h-4 w-4" />
                            Detalle
                          </Button>
                        </Link>
                      </div>
                    </td>
                  </tr>

                  <!-- Fila expandible con acciones rápidas -->
                  <tr v-if="expandedRows.has(payment.id) && payment.status === 'pending'" class="border-b bg-blue-50 dark:bg-blue-950">
                    <td colspan="8" class="p-4">
                      <div class="space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                          <!-- VALIDAR PAGO -->
                          <div class="space-y-3 p-4 rounded-lg bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700">
                            <h4 class="font-bold text-green-900 dark:text-green-100 flex items-center gap-2">
                              <CheckCircle class="h-5 w-5" />
                              Validar Pago
                            </h4>

                            <div v-if="!initializeQuickAction(payment).showValidate">
                              <Button
                                @click="initializeQuickAction(payment).showValidate = true"
                                class="w-full bg-green-600 hover:bg-green-700 text-white"
                              >
                                Abrir Validación
                              </Button>
                            </div>

                            <div v-else class="space-y-3">
                              <div>
                                <Label class="text-sm font-semibold">Seleccionar Plan Type</Label>
                                <select
                                  v-model="initializeQuickAction(payment).validate.plan_type_id"
                                  class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                >
                                  <option value="">Elige un plan...</option>
                                  <option value="1">Plan Básico - Investment</option>
                                  <option value="2">Plan Plus - Investment</option>
                                  <option value="3">Plan Premium - Coverage</option>
                                </select>
                              </div>

                              <div>
                                <Label class="text-sm font-semibold">Notas (opcional)</Label>
                                <textarea
                                  v-model="initializeQuickAction(payment).validate.notes"
                                  placeholder="Ej: Pago verificado correctamente..."
                                  class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                  rows="2"
                                ></textarea>
                              </div>

                              <div class="flex gap-2">
                                <Button
                                  @click="handleQuickValidate(payment)"
                                  :disabled="initializeQuickAction(payment).validate.processing"
                                  class="flex-1 bg-green-600 hover:bg-green-700 text-white"
                                >
                                  {{ initializeQuickAction(payment).validate.processing ? 'Validando...' : 'Confirmar Validación' }}
                                </Button>
                                <Button
                                  @click="initializeQuickAction(payment).showValidate = false"
                                  variant="outline"
                                  class="flex-1"
                                >
                                  Cancelar
                                </Button>
                              </div>
                            </div>
                          </div>

                          <!-- RECHAZAR PAGO -->
                          <div class="space-y-3 p-4 rounded-lg bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700">
                            <h4 class="font-bold text-red-900 dark:text-red-100 flex items-center gap-2">
                              <XCircle class="h-5 w-5" />
                              Rechazar Pago
                            </h4>

                            <div v-if="!initializeQuickAction(payment).showReject">
                              <Button
                                @click="initializeQuickAction(payment).showReject = true"
                                variant="destructive"
                                class="w-full"
                              >
                                Abrir Rechazo
                              </Button>
                            </div>

                            <div v-else class="space-y-3">
                              <div>
                                <Label class="text-sm font-semibold text-red-900 dark:text-red-100">Razón de Rechazo</Label>
                                <textarea
                                  v-model="initializeQuickAction(payment).reject.reason"
                                  placeholder="Ej: Monto no coincide, cuenta bloqueada, documento inválido..."
                                  class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500"
                                  rows="2"
                                ></textarea>
                              </div>

                              <div class="flex gap-2">
                                <Button
                                  @click="handleQuickReject(payment)"
                                  :disabled="initializeQuickAction(payment).reject.processing"
                                  variant="destructive"
                                  class="flex-1"
                                >
                                  {{ initializeQuickAction(payment).reject.processing ? 'Rechazando...' : 'Confirmar Rechazo' }}
                                </Button>
                                <Button
                                  @click="initializeQuickAction(payment).showReject = false"
                                  variant="outline"
                                  class="flex-1"
                                >
                                  Cancelar
                                </Button>
                              </div>
                            </div>
                          </div>
                        </div>

                        <p class="text-xs text-gray-600 dark:text-gray-400 italic">
                          O usa el botón "Detalle" para una validación más completa
                        </p>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>

            <div v-if="payments.data.length === 0" class="py-12 text-center">
              <Clock class="h-12 w-12 mx-auto text-gray-400 mb-4" />
              <p class="text-gray-600 dark:text-gray-400">No hay pagos pendientes</p>
            </div>
          </div>

          <!-- Paginación -->
          <div v-if="payments.meta && payments.meta.total > 20" class="mt-6 flex justify-center gap-2 flex-wrap">
            <Link
              v-for="link in payments.links || []"
              :key="link.url"
              :href="link.url || '#'"
              :class="[
                'px-3 py-2 border rounded text-sm transition',
                link.active 
                  ? 'bg-blue-600 text-white border-blue-600' 
                  : 'border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800'
              ]"
              v-html="link.label"
            />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
