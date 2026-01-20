<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, TrendingUp, TrendingDown, Edit2, X, Lock, CheckCircle } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

interface FundHistory {
  id: number;
  fund_id: number;
  previous_amount: number;
  new_amount: number;
  change_amount: number;
  change_percent: number;
  metadata: any;
  created_at: string;
}

interface Fund {
  id: number;
  name: string;
  category: string;
  initial_amount: number;
  current_amount: number;
  status: string;
  created_at: string;
  history: FundHistory[];
  total_participants: number;
}

interface Reward {
  id: number;
  client_name: string;
  client_email: string;
  investment: number;
  individual_gain: number;
  company_percentage: number;
  company_deduction: number;
  was_referred: boolean;
  referrer_name: string | null;
  referral_percentage: number;
  referral_deduction: number;
  net_earnings: number;
  status: string;
  closed_at: string;
}

interface Distribution {
  total_investment: number;
  total_fund_earnings: number;
  company_total: number;
  company_percentage: number;
  referrals_total: number;
  referrals_percentage: number;
  clients_total: number;
  clients_percentage: number;
  total_participants: number;
  first_deposits_referred: number;
  status: string;
  closed_at: string;
}

interface Props {
  funds: Fund[];
}

const props = defineProps<Props>();
const page = usePage();

const selectedFundId = ref<number | null>(null);
const editingFundId = ref<number | null>(null);
const showRewardsTable = ref(false);
const loadingRewards = ref(false);
const rewards = ref<Reward[]>([]);
const distribution = ref<Distribution | null>(null);

const editForm = useForm({
  fund_id: '' as any,
  new_amount: '' as any,
  reason: '',
});

const closeForm = useForm({
  fund_yield: 20,
});

const selectedFund = ref<Fund | null>(null);

// Obtener fondos actualizados de la página
const currentFunds = computed(() => {
  return page.props.funds as Fund[];
});

// Actualizar selectedFund cuando cambien los fondos
watch(currentFunds, () => {
  if (selectedFundId.value) {
    const updated = currentFunds.value.find(f => f.id === selectedFundId.value);
    if (updated) {
      selectedFund.value = updated;
    }
  }
}, { deep: true });

const selectFund = (fund: Fund) => {
  selectedFundId.value = fund.id;
  selectedFund.value = fund;
  editingFundId.value = null;
  editForm.reset();
  editForm.new_amount = '';
  editForm.reason = '';
  showRewardsTable.value = false;
};

const startEdit = () => {
  if (selectedFund.value) {
    editingFundId.value = selectedFund.value.id;
    editForm.fund_id = selectedFund.value.id;
    editForm.new_amount = selectedFund.value.current_amount.toString();
  }
};

const cancelEdit = () => {
  editingFundId.value = null;
  editForm.reset();
};

const handleUpdate = () => {
  if (!editForm.new_amount) {
    alert('Ingresa el nuevo monto');
    return;
  }
  const newAmount = parseFloat(editForm.new_amount);
  if (isNaN(newAmount) || newAmount < 0) {
    alert('Ingresa un monto válido');
    return;
  }
  if (selectedFund.value && newAmount === selectedFund.value.current_amount) {
    alert('El nuevo monto debe ser diferente al actual');
    return;
  }
  
  // Convertir new_amount a número
  editForm.new_amount = newAmount;
  
  console.log('Enviando datos:', {
    fund_id: editForm.fund_id,
    new_amount: editForm.new_amount,
    reason: editForm.reason,
  });
  
  editForm.post(route('admin.payments.update_fund_value'), {
    preserveScroll: true,
    onSuccess: () => {
      editingFundId.value = null;
      editForm.reset();
    },
    onError: (errors) => {
      console.error('Error al actualizar:', errors);
      alert('Error: ' + JSON.stringify(errors));
    },
  });
};

const handleCloseFund = () => {
  if (!selectedFund.value) {
    alert('Selecciona un fondo');
    return;
  }

  if (isFundClosed.value) {
    alert('Este fondo ya está cerrado');
    return;
  }

  if (closeForm.processing) {
    alert('Por favor espera, el fondo se está cerrando...');
    return;
  }

  const confirmMessage = `¿Deseas cerrar el fondo "${selectedFund.value.name}" y distribuir las ganancias entre todos los participantes?\n\nEsta acción:\n✓ Cerrará el fondo permanentemente\n✓ Calculará ganancias individuales\n✓ Deducirá comisiones de empresa (20%)\n✓ Aplicará comisiones de referral (15% solo 1er pago)\n✓ Registrará rewards para todos los clientes`;

  if (!confirm(confirmMessage)) {
    return;
  }

  const fundId = selectedFund.value.id;
  const routeName = 'admin.payments.close_fund';
  const routeParams = { fund: fundId };
  
  console.log('🔄 Iniciando cierre de fondo:', {
    fundName: selectedFund.value.name,
    fundId: fundId,
    fundYield: closeForm.fund_yield,
    route: routeName,
    params: routeParams,
  });

  closeForm.post(route(routeName, routeParams), {
    preserveScroll: true,
    onBefore: () => {
      console.log('📤 Enviando solicitud de cierre...', {
        data: closeForm.data(),
      });
    },
    onSuccess: () => {
      console.log('✅ Fondo cerrado exitosamente');
      closeForm.reset();
      // Actualizar el estado del fondo a cerrado
      if (selectedFund.value) {
        selectedFund.value.status = 'closed';
      }
      // Cargar rewards después de cerrar (con pequeño delay para asegurar que se procesó)
      setTimeout(() => {
        console.log('📊 Cargando rewards del fondo...');
        loadFundRewards();
      }, 1500);
    },
    onError: (errors) => {
      console.error('❌ Error al cerrar:', errors);
      const errorMessage = typeof errors === 'object' 
        ? Object.values(errors).join('\n')
        : JSON.stringify(errors);
      alert('Error al cerrar el fondo:\n' + errorMessage);
    },
  });
};

const loadFundRewards = async () => {
  if (!selectedFund.value) return;

  loadingRewards.value = true;
  try {
    console.log('📊 Intentando cargar rewards para fondo ID:', selectedFund.value.id);
    const response = await fetch(route('admin.payments.fund_rewards', selectedFund.value.id));
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();

    if (data.success) {
      rewards.value = data.rewards;
      distribution.value = data.distribution;
      showRewardsTable.value = true;
      console.log('✓ Rewards cargados exitosamente', {
        totalRewards: data.rewards.length,
        distribution: data.distribution,
        rewards: data.rewards
      });
    } else {
      throw new Error(data.error || 'Error desconocido');
    }
  } catch (error) {
    console.error('❌ Error al cargar rewards:', error);
    const errorMessage = error instanceof Error ? error.message : 'Error desconocido';
    alert('Error al cargar la distribución de ganancias:\n\n' + errorMessage + '\n\nVerifica la consola para más detalles.');
  } finally {
    loadingRewards.value = false;
  }
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

const getChangePercent = (previous: number, current: number) => {
  if (previous === 0) return 0;
  return ((current - previous) / previous) * 100;
};

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    open: 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100',
    closed: 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100',
  };
  return colors[status] || colors.open;
};

const isFundClosed = computed(() => selectedFund.value?.status === 'closed');
</script>

<template>
  <Head title="Actualizar Valores de Fondos" />
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
          <h1 class="text-3xl font-bold">Actualizar Valores de Fondos</h1>
          <p class="text-muted-foreground mt-1">Gestiona los valores actuales y visualiza el historial de cambios</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Lista de Fondos -->
        <div class="lg:col-span-2">
          <Card class="flex flex-col h-full">
            <CardHeader>
              <CardTitle>Fondos Activos</CardTitle>
              <CardDescription>{{ currentFunds.length }} fondos disponibles</CardDescription>
            </CardHeader>
            <CardContent class="flex-1 overflow-y-auto">
              <div v-if="currentFunds.length === 0" class="text-center py-8 text-muted-foreground">
                <p>No hay fondos disponibles</p>
              </div>

              <div v-else class="space-y-3 pr-2">
                <div
                  v-for="fund in currentFunds"
                  :key="fund.id"
                  @click="selectFund(fund)"
                  :class="[
                    'p-4 border rounded-lg cursor-pointer transition',
                    selectedFundId === fund.id
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-950'
                      : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-2">
                        <h3 class="font-semibold text-lg">{{ fund.name }}</h3>
                        <Badge :class="getStatusColor(fund.status)" class="capitalize">
                          {{ fund.status }}
                        </Badge>
                      </div>
                      <p class="text-xs text-muted-foreground mb-3">{{ fund.category }}</p>

                      <div class="grid grid-cols-3 gap-4">
                        <div>
                          <p class="text-xs text-muted-foreground">Monto Inicial</p>
                          <p class="font-semibold">{{ formatCurrency(fund.initial_amount) }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-muted-foreground">Monto Actual</p>
                          <p class="font-semibold text-green-600">{{ formatCurrency(fund.current_amount) }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-muted-foreground">Cambio</p>
                          <p :class="[
                            'font-semibold',
                            fund.current_amount - fund.initial_amount >= 0
                              ? 'text-green-600'
                              : 'text-red-600'
                          ]">
                            {{ (fund.current_amount - fund.initial_amount >= 0 ? '+' : '') }}{{ formatCurrency(fund.current_amount - fund.initial_amount) }}
                          </p>
                        </div>
                      </div>

                      <p class="text-xs text-muted-foreground mt-2">{{ fund.total_participants }} inversores</p>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Panel de Edición y Resumen -->
        <div class="space-y-4">
          <!-- Fondo Seleccionado -->
          <Card v-if="selectedFund">
            <CardHeader>
              <CardTitle class="text-base">{{ selectedFund.name }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Información Actual -->
              <div v-if="editingFundId === null" class="space-y-4">
                <div>
                  <p class="text-xs text-muted-foreground mb-1">Monto Actual</p>
                  <p class="text-3xl font-bold text-green-600">{{ formatCurrency(selectedFund.current_amount) }}</p>
                </div>

                <div class="border-t pt-4">
                  <p class="text-xs text-muted-foreground mb-1">Cambio Total</p>
                  <div class="flex items-center gap-2">
                    <component
                      :is="selectedFund.current_amount - selectedFund.initial_amount >= 0 ? TrendingUp : TrendingDown"
                      :class="[
                        'h-5 w-5',
                        selectedFund.current_amount - selectedFund.initial_amount >= 0
                          ? 'text-green-600'
                          : 'text-red-600'
                      ]"
                    />
                    <span :class="[
                      'text-lg font-bold',
                      selectedFund.current_amount - selectedFund.initial_amount >= 0
                        ? 'text-green-600'
                        : 'text-red-600'
                    ]">
                      {{ (selectedFund.current_amount - selectedFund.initial_amount >= 0 ? '+' : '') }}{{ formatCurrency(selectedFund.current_amount - selectedFund.initial_amount) }}
                    </span>
                  </div>
                  <p class="text-xs text-muted-foreground mt-1">
                    ({{ getChangePercent(selectedFund.initial_amount, selectedFund.current_amount).toFixed(2) }}%)
                  </p>
                </div>

                <div class="border-t pt-4 space-y-2">
                  <Button @click="startEdit" class="w-full gap-2 bg-blue-600 hover:bg-blue-700">
                    <Edit2 class="h-4 w-4" />
                    Actualizar Valor
                  </Button>

                  <Button 
                    v-if="!isFundClosed"
                    @click="handleCloseFund" 
                    :disabled="closeForm.processing || editingFundId !== null"
                    class="w-full gap-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 disabled:cursor-not-allowed transition-all"
                  >
                    <Lock class="h-4 w-4" />
                    <span v-if="closeForm.processing" class="inline-flex items-center gap-2">
                      <span class="inline-block w-3 h-3 rounded-full bg-white animate-spin"></span>
                      Cerrando Fondo...
                    </span>
                    <span v-else>Terminar Fondo</span>
                  </Button>

                  <div v-if="isFundClosed" class="p-3 bg-green-50 dark:bg-green-950 rounded-lg border border-green-200 dark:border-green-800 space-y-2">
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                      <CheckCircle class="h-5 w-5" />
                      <span class="font-semibold">Fondo Cerrado</span>
                    </div>
                    <p class="text-xs text-green-600 dark:text-green-400">
                      Ganancias distribuidas entre clientes ✓
                    </p>
                    <Button
                      v-if="!showRewardsTable"
                      @click="loadFundRewards"
                      :disabled="loadingRewards"
                      class="w-full mt-2 text-xs bg-green-600 hover:bg-green-700"
                    >
                      <span v-if="loadingRewards" class="inline-flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-white animate-spin"></span>
                        Cargando Rewards...
                      </span>
                      <span v-else>📊 Ver Distribución de Rewards</span>
                    </Button>
                  </div>
                </div>
              </div>

              <!-- Formulario de Edición -->
              <div v-else class="space-y-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
                <div>
                  <Label for="new_amount" class="text-sm font-semibold">Nuevo Monto</Label>
                  <input
                    id="new_amount"
                    v-model="editForm.new_amount"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                  />
                  <p class="text-xs text-muted-foreground mt-1">Actual: {{ formatCurrency(selectedFund.current_amount) }}</p>
                </div>

                <div>
                  <Label for="reason" class="text-sm font-semibold">Razón del Cambio (opcional)</Label>
                  <textarea
                    id="reason"
                    v-model="editForm.reason"
                    placeholder="Ej: Revaluación de mercado, rendimiento Q4..."
                    class="w-full mt-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                    rows="2"
                  ></textarea>
                </div>

                <div class="flex gap-2">
                  <Button
                    @click="handleUpdate"
                    :disabled="editForm.processing"
                    class="flex-1 bg-green-600 hover:bg-green-700"
                  >
                    {{ editForm.processing ? 'Actualizando...' : 'Confirmar' }}
                  </Button>
                  <Button @click="cancelEdit" variant="outline" class="flex-1">
                    <X class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Sin selección -->
          <Card v-else>
            <CardContent class="pt-6 text-center text-muted-foreground">
              <p class="text-sm">Selecciona un fondo para editar</p>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Tabla de Distribución de Rewards -->
      <Card v-if="showRewardsTable && distribution">
        <CardHeader>
          <CardTitle>📊 Distribución de Ganancias - {{ selectedFund?.name }}</CardTitle>
          <CardDescription>Resumen de la repartición de ganancias entre empresa, staff (referidos) y clientes</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <!-- Resumen General -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-3 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
              <p class="text-xs text-muted-foreground font-semibold">💰 Inversión Total</p>
              <p class="text-lg font-bold text-blue-600 dark:text-blue-300 mt-1">{{ formatCurrency(distribution.total_investment) }}</p>
            </div>
            <div class="p-3 bg-green-50 dark:bg-green-950 rounded-lg border border-green-200 dark:border-green-800">
              <p class="text-xs text-muted-foreground font-semibold">📈 Ganancia del Fondo</p>
              <p class="text-lg font-bold text-green-600 dark:text-green-300 mt-1">{{ formatCurrency(distribution.total_fund_earnings) }}</p>
            </div>
            <div class="p-3 bg-purple-50 dark:bg-purple-950 rounded-lg border border-purple-200 dark:border-purple-800">
              <p class="text-xs text-muted-foreground font-semibold">👥 Participantes</p>
              <p class="text-lg font-bold text-purple-600 dark:text-purple-300 mt-1">{{ distribution.total_participants }}</p>
            </div>
            <div class="p-3 bg-orange-50 dark:bg-orange-950 rounded-lg border border-orange-200 dark:border-orange-800">
              <p class="text-xs text-muted-foreground font-semibold">🔗 Referencias (1er pago)</p>
              <p class="text-lg font-bold text-orange-600 dark:text-orange-300 mt-1">{{ distribution.first_deposits_referred }}</p>
            </div>
          </div>

          <!-- Distribución por Porcentaje -->
          <div class="border-t pt-6">
            <h3 class="font-semibold mb-4 text-lg">💵 Desglose de Ganancias Totales</h3>
            <div class="space-y-3">
              <!-- Empresa -->
              <div class="flex items-start justify-between p-4 bg-red-50 dark:bg-red-950 rounded-lg border border-red-200 dark:border-red-800">
                <div>
                  <p class="font-semibold text-red-900 dark:text-red-100 text-base">🏢 Empresa</p>
                  <p class="text-xs text-red-700 dark:text-red-300 mt-1">20% de cada ganancia individual</p>
                  <p class="text-xs text-red-600 dark:text-red-400 mt-2">
                    <span class="font-mono">Fórmula:</span> Individual Gain × 20%
                  </p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-red-600 dark:text-red-400 text-lg">{{ formatCurrency(distribution.company_total) }}</p>
                  <p class="text-sm text-red-600 dark:text-red-400 font-semibold">{{ distribution.company_percentage.toFixed(2) }}%</p>
                  <p class="text-xs text-red-500 mt-1">del total ganado</p>
                </div>
              </div>

              <!-- Staff/Referidos -->
              <div class="flex items-start justify-between p-4 bg-purple-50 dark:bg-purple-950 rounded-lg border border-purple-200 dark:border-purple-800">
                <div>
                  <p class="font-semibold text-purple-900 dark:text-purple-100 text-base">👔 Staff (Referidos)</p>
                  <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">15% ganancia del PRIMER DEPÓSITO referido</p>
                  <p class="text-xs text-purple-600 dark:text-purple-400 mt-2">
                    <span class="font-mono">Fórmula:</span> (Individual Gain × 15%) × es_primer_pago
                  </p>
                  <p class="text-xs text-purple-500 mt-1 font-semibold">⚠️ No aplica a depósitos posteriores</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-purple-600 dark:text-purple-400 text-lg">{{ formatCurrency(distribution.referrals_total) }}</p>
                  <p class="text-sm text-purple-600 dark:text-purple-400 font-semibold">{{ distribution.referrals_percentage.toFixed(2) }}%</p>
                  <p class="text-xs text-purple-500 mt-1">del total ganado</p>
                </div>
              </div>

              <!-- Clientes -->
              <div class="flex items-start justify-between p-4 bg-green-50 dark:bg-green-950 rounded-lg border border-green-200 dark:border-green-800">
                <div>
                  <p class="font-semibold text-green-900 dark:text-green-100 text-base">✅ Clientes</p>
                  <p class="text-xs text-green-700 dark:text-green-300 mt-1">Ganancia neta después de deducciones</p>
                  <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                    <span class="font-mono">Fórmula:</span> (Inv. × Yield) - Emp - Staff
                  </p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-green-600 dark:text-green-400 text-lg">{{ formatCurrency(distribution.clients_total) }}</p>
                  <p class="text-sm text-green-600 dark:text-green-400 font-semibold">{{ distribution.clients_percentage.toFixed(2) }}%</p>
                  <p class="text-xs text-green-500 mt-1">del total ganado</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Verificación de suma -->
          <div class="border-t pt-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
            <p class="text-xs text-muted-foreground mb-2 font-semibold">✓ Verificación (suma = ganancia total del fondo):</p>
            <p class="text-sm font-mono bg-white dark:bg-gray-900 p-2 rounded break-all">
              {{ formatCurrency(distribution.company_total) }} + 
              {{ formatCurrency(distribution.referrals_total) }} + 
              {{ formatCurrency(distribution.clients_total) }} = 
              <span class="font-bold text-green-600">{{ formatCurrency(distribution.company_total + distribution.referrals_total + distribution.clients_total) }}</span>
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Tabla de Rewards Detallada -->
      <Card v-if="showRewardsTable && rewards.length > 0">
        <CardHeader>
          <CardTitle>📋 Detalle de Rewards - {{ selectedFund?.name }}</CardTitle>
          <CardDescription>Desglose individual de ganancias para cada cliente. Cada fila muestra cómo se distribuyeron sus ganancias.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-xs md:text-sm">
              <thead class="border-b bg-gray-100 dark:bg-gray-800 sticky top-0">
                <tr>
                  <th class="text-left py-3 px-2 font-semibold">👤 Cliente</th>
                  <th class="text-right py-3 px-2 font-semibold">💵 Inversión</th>
                  <th class="text-right py-3 px-2 font-semibold">📊 Ganancia Ind.</th>
                  <th class="text-right py-3 px-2 font-semibold">🏢 Empresa (20%)</th>
                  <th class="text-right py-3 px-2 font-semibold">👔 Staff</th>
                  <th class="text-right py-3 px-2 font-semibold">✅ Cliente Recibe</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="reward in rewards" 
                  :key="reward.id" 
                  class="border-b hover:bg-blue-50 dark:hover:bg-blue-900 transition"
                >
                  <td class="py-3 px-2">
                    <div>
                      <p class="font-semibold text-gray-900 dark:text-white">{{ reward.client_name }}</p>
                      <p class="text-xs text-muted-foreground">{{ reward.client_email }}</p>
                      <p v-if="reward.was_referred" class="text-xs text-purple-600 dark:text-purple-400 font-semibold mt-1">
                        🔗 Ref: {{ reward.referrer_name }}
                      </p>
                      <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Sin referencia
                      </p>
                    </div>
                  </td>
                  <td class="text-right py-3 px-2 font-semibold text-blue-600 dark:text-blue-400">
                    {{ formatCurrency(reward.investment) }}
                  </td>
                  <td class="text-right py-3 px-2 font-semibold text-green-600 dark:text-green-400">
                    {{ formatCurrency(reward.individual_gain) }}
                  </td>
                  <td class="text-right py-3 px-2">
                    <div class="flex flex-col items-end">
                      <p class="text-red-600 dark:text-red-400 font-semibold">{{ formatCurrency(reward.company_deduction) }}</p>
                      <p class="text-xs text-muted-foreground">({{ reward.company_percentage }}%)</p>
                    </div>
                  </td>
                  <td class="text-right py-3 px-2">
                    <div class="flex flex-col items-end">
                      <p class="text-purple-600 dark:text-purple-400 font-semibold">
                        {{ formatCurrency(reward.referral_deduction) }}
                      </p>
                      <p v-if="reward.was_referred" class="text-xs text-muted-foreground">({{ reward.referral_percentage }}%)</p>
                      <p v-else class="text-xs text-muted-foreground">-</p>
                    </div>
                  </td>
                  <td class="text-right py-3 px-2 font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900 bg-opacity-50">
                    <div class="flex flex-col items-end">
                      <p class="text-base">{{ formatCurrency(reward.net_earnings) }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">100% del neto</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Leyenda de cálculo -->
          <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
            <p class="text-xs font-semibold text-blue-900 dark:text-blue-100 mb-2">📖 Cómo se calcula:</p>
            <div class="text-xs text-blue-800 dark:text-blue-200 space-y-1 font-mono">
              <p>1. <span class="text-green-600">Ganancia Individual</span> = Inversión × Rendimiento (20%)</p>
              <p>2. <span class="text-red-600">Empresa</span> = Ganancia × 20%</p>
              <p>3. <span class="text-purple-600">Staff</span> = Ganancia × 15% (solo si primer depósito referido)</p>
              <p>4. <span class="text-green-600">Cliente Recibe</span> = Ganancia - Empresa - Staff</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Sin Rewards -->
      <Card v-if="showRewardsTable && rewards.length === 0 && !loadingRewards">
        <CardContent class="pt-6 text-center text-muted-foreground">
          <p class="text-sm">No hay rewards registrados para este fondo</p>
        </CardContent>
      </Card>

      <!-- Cargando Rewards -->
      <Card v-if="loadingRewards && showRewardsTable === false">
        <CardContent class="pt-6 text-center">
          <div class="flex flex-col items-center justify-center py-8 gap-3">
            <div class="inline-block w-8 h-8 rounded-full border-4 border-blue-200 border-t-blue-600 animate-spin"></div>
            <p class="text-sm font-semibold text-blue-600">Cargando distribución de rewards...</p>
            <p class="text-xs text-muted-foreground">Por favor espera mientras se procesan los datos</p>
          </div>
        </CardContent>
      </Card>

      <!-- Historial de Cambios -->

      <Card v-if="selectedFund && selectedFund.history && selectedFund.history.length > 0">
        <CardHeader>
          <CardTitle>Historial de Cambios - {{ selectedFund.name }}</CardTitle>
          <CardDescription>Todos los cambios de valor registrados</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b">
                <tr>
                  <th class="text-left py-3 px-4 font-semibold">Fecha</th>
                  <th class="text-right py-3 px-4 font-semibold">Monto Anterior</th>
                  <th class="text-right py-3 px-4 font-semibold">Nuevo Monto</th>
                  <th class="text-right py-3 px-4 font-semibold">Cambio</th>
                  <th class="text-right py-3 px-4 font-semibold">%</th>
                  <th class="text-left py-3 px-4 font-semibold">Razón</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entry in selectedFund.history" :key="entry.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-900">
                  <td class="py-3 px-4">{{ formatDate(entry.created_at) }}</td>
                  <td class="text-right py-3 px-4">{{ formatCurrency(entry.previous_amount) }}</td>
                  <td class="text-right py-3 px-4">{{ formatCurrency(entry.new_amount) }}</td>
                  <td :class="[
                    'text-right py-3 px-4 font-semibold',
                    entry.change_amount >= 0 ? 'text-green-600' : 'text-red-600'
                  ]">
                    {{ (entry.change_amount >= 0 ? '+' : '') }}{{ formatCurrency(entry.change_amount) }}
                  </td>
                  <td :class="[
                    'text-right py-3 px-4 font-semibold',
                    entry.change_percent >= 0 ? 'text-green-600' : 'text-red-600'
                  ]">
                    {{ (entry.change_percent >= 0 ? '+' : '') }}{{ entry.change_percent.toFixed(2) }}%
                  </td>
                  <td class="py-3 px-4 text-muted-foreground text-xs">
                    {{ entry.metadata?.reason || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Sin Historial -->
      <Card v-else-if="selectedFund">
        <CardContent class="pt-6 text-center text-muted-foreground">
          <p class="text-sm">No hay cambios registrados aún</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
