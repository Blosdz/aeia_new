<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AlertCircle, CheckCircle, ChevronLeft, ChevronRight, CreditCard } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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

const props = defineProps<{
  plan: Plan;
  plans: Plan[];
  clientAccounts: ClientAccount[];
  userMembershipStatus?: Record<number, boolean>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Pagos', href: route('clients.payments') },
  { title: 'Seleccionar Plan', href: route('clients.payments.select') },
];

// Estado del carrusel
const currentPlan = ref<Plan>(props.plan);
const currentIndex = ref(props.plans.findIndex(p => p.id === props.plan.id) || 0);

const form = useForm({
  plan_type_id: currentPlan.value.id,
  amount: 0,
  client_account_id: props.clientAccounts[0]?.id || '',
  currency: 'USD',
});

// Computadas del carrusel
const previousPlan = computed(() => {
  const prevIndex = (currentIndex.value - 1 + props.plans.length) % props.plans.length;
  return props.plans[prevIndex];
});

const nextPlan = computed(() => {
  const nextIndex = (currentIndex.value + 1) % props.plans.length;
  return props.plans[nextIndex];
});

// Cambiar plan en el carrusel
const goToPlan = (plan: Plan) => {
  currentPlan.value = plan;
  currentIndex.value = props.plans.findIndex(p => p.id === plan.id) || 0;
  form.plan_type_id = plan.id;
  form.amount = plan.amount_min;
};

const nextCarousel = () => {
  goToPlan(nextPlan.value);
};

const prevCarousel = () => {
  goToPlan(previousPlan.value);
};

// Navegar al siguiente paso (confirmación)
const goToConfirmation = () => {
  form.post(route('clients.payments.store'));
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount);
};

// Helper para convertir a número entero
const toInteger = (value: any): number => {
  return parseInt(String(value), 10) || 0;
};

// Calcular si el usuario debe pagar membresía
const mustPayMembership = computed(() => {
  const hasMembership = currentPlan.value?.membership && currentPlan.value.membership > 0;
  const alreadyPaid = props.userMembershipStatus?.[currentPlan.value?.id] ?? false;
  return hasMembership && !alreadyPaid;
});

// Calcular monto total con membresía
const totalAmount = computed(() => {
  const amount = toInteger(form.amount);
  const membership = mustPayMembership.value ? toInteger(currentPlan.value?.membership || 0) : 0;
  return amount + membership;
});

// Validaciones reactivas del monto
const amountError = ref('');
const amountWarning = ref('');

watch(
  () => form.amount,
  (newAmount) => {
    amountError.value = '';
    amountWarning.value = '';
    
    const amount = toInteger(newAmount);
    
    if (!amount || amount === 0) {
      return;
    }
    
    const minAmount = toInteger(currentPlan.value?.amount_min || 0);
    const maxAmount = toInteger(currentPlan.value?.amount_max || 0);
    
    if (amount < minAmount) {
      amountError.value = `El monto mínimo es ${formatCurrency(minAmount)}`;
    } else if (amount > maxAmount) {
      amountError.value = `El monto máximo es ${formatCurrency(maxAmount)}`;
    }
  }
);
</script>

<template>
  <Head :title="`Inversión en ${currentPlan?.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div>
        <h1 class="text-3xl font-bold">{{ currentPlan?.name }}</h1>
        <p class="text-muted-foreground mt-1">Configura los detalles de tu inversión</p>
      </div>

      <!-- Advertencia si no tiene cuentas -->
      <div v-if="!clientAccounts || clientAccounts.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex gap-3">
        <AlertCircle class="h-5 w-5 text-yellow-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-yellow-900">No tienes cuentas de pago registradas</p>
          <p class="text-sm text-yellow-700 mt-1">Debes agregar una cuenta bancaria antes de realizar un pago</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Carrusel de Planes -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Plan Actual -->
          <Card class="overflow-hidden">
            <div v-if="currentPlan.img_url" class="relative h-64 overflow-hidden bg-gradient-to-br from-blue-400 to-purple-600">
              <img :src="currentPlan.img_url" :alt="currentPlan.name" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
            </div>
            <div v-else class="h-64 bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center">
              <div class="text-center text-white">
                <div class="text-5xl font-bold mb-2">{{ currentPlan.name?.charAt(0) }}</div>
                <p class="text-sm">{{ currentPlan.name }}</p>
              </div>
            </div>

            <CardHeader class="pb-3">
              <div class="flex justify-between items-start">
                <div>
                  <CardTitle class="text-2xl">{{ currentPlan.name }}</CardTitle>
                  <CardDescription>Plan de inversión flexible</CardDescription>
                </div>
                <div class="text-right">
                  <p class="text-xs text-muted-foreground mb-1">Rango:</p>
                  <p class="font-bold text-sm">{{ formatCurrency(currentPlan.amount_min) }} - {{ formatCurrency(currentPlan.amount_max) }}</p>
                </div>
              </div>
            </CardHeader>

            <CardContent class="space-y-4">
              <!-- Información del plan -->
              <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-950 rounded-lg">
                  <p class="text-xs text-muted-foreground mb-1">Inversión Mínima</p>
                  <p class="font-bold text-lg">{{ formatCurrency(currentPlan.amount_min) }}</p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-950 rounded-lg">
                  <p class="text-xs text-muted-foreground mb-1">Inversión Máxima</p>
                  <p class="font-bold text-lg">{{ formatCurrency(currentPlan.amount_max) }}</p>
                </div>
              </div>

              <!-- Membresía si existe -->
              <div v-if="currentPlan?.membership" class="p-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950 dark:to-pink-950 rounded-lg border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-2 mb-2">
                  <p class="text-xs text-muted-foreground uppercase font-semibold">Costo de Membresía</p>
                  <Badge 
                    v-if="props.userMembershipStatus?.[currentPlan?.id]"
                    variant="outline"
                    class="ml-auto bg-green-500/20 text-green-400 border-green-500/50 text-xs"
                  >
                    ✓ Ya Pagada
                  </Badge>
                </div>
                <p v-if="mustPayMembership" class="font-bold text-lg text-purple-600 dark:text-purple-400">{{ formatCurrency(currentPlan.membership || 0) }}</p>
                <p v-else class="font-bold text-lg text-green-600 dark:text-green-400">Incluida en tu suscripción</p>
                <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                  <CreditCard class="h-3 w-3" />
                  Pago único
                </p>
              </div>

              <!-- Características -->
              <div class="space-y-2 text-sm border-t pt-4">
                <p class="flex items-center gap-2">
                  <span class="text-green-600">✓</span>
                  <span>Rentabilidad competitiva</span>
                </p>
                <p class="flex items-center gap-2">
                  <span class="text-green-600">✓</span>
                  <span>Retiros flexibles</span>
                </p>
                <p class="flex items-center gap-2">
                  <span class="text-green-600">✓</span>
                  <span>Soporte dedicado</span>
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Controles del Carrusel -->
          <div class="flex items-center justify-between gap-3">
            <Button variant="outline" size="icon" @click="prevCarousel" :disabled="props.plans.length <= 1">
              <ChevronLeft class="h-5 w-5" />
            </Button>

            <!-- Planes del carrusel (Anterior, Actual, Siguiente) -->
            <div class="flex gap-2 flex-1 justify-center">
              <!-- Plan anterior (pequeño) -->
              <button
                v-if="props.plans.length > 1"
                @click="prevCarousel"
                class="opacity-50 hover:opacity-75 transition-opacity"
              >
                <Card class="w-20 h-20 overflow-hidden cursor-pointer hover:shadow-md transition-shadow">
                  <div class="w-full h-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-xs font-bold text-center p-1">
                    <span class="line-clamp-2">{{ previousPlan.name }}</span>
                  </div>
                </Card>
              </button>

              <!-- Plan actual (grande) -->
              <Card class="w-28 h-20 overflow-hidden border-2 border-primary cursor-pointer shadow-md">
                <div class="w-full h-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-xs font-bold text-white text-center p-1">
                  <span class="line-clamp-2">{{ currentPlan.name }}</span>
                </div>
              </Card>

              <!-- Plan siguiente (pequeño) -->
              <button
                v-if="props.plans.length > 1"
                @click="nextCarousel"
                class="opacity-50 hover:opacity-75 transition-opacity"
              >
                <Card class="w-20 h-20 overflow-hidden cursor-pointer hover:shadow-md transition-shadow">
                  <div class="w-full h-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-xs font-bold text-center p-1">
                    <span class="line-clamp-2">{{ nextPlan.name }}</span>
                  </div>
                </Card>
              </button>
            </div>

            <Button variant="outline" size="icon" @click="nextCarousel" :disabled="props.plans.length <= 1">
              <ChevronRight class="h-5 w-5" />
            </Button>
          </div>

          <!-- Indicadores de página -->
          <div v-if="props.plans.length > 1" class="flex justify-center gap-1">
            <button
              v-for="(plan, idx) in props.plans"
              :key="plan.id"
              @click="goToPlan(plan)"
              :class="{
                'w-2 h-2 rounded-full transition-all': true,
                'bg-primary w-6': idx === currentIndex,
                'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400': idx !== currentIndex,
              }"
              :title="plan.name"
            />
          </div>
        </div>

        <!-- Formulario de Inversión -->
        <div class="space-y-4">
          <!-- Card de Inversión -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <CheckCircle class="h-5 w-5 text-green-600" />
                Detalles de Inversión
              </CardTitle>
            </CardHeader>
            <CardContent>
              <form @submit.prevent="goToConfirmation" class="space-y-4">
                <!-- Monto de Inversión -->
                <div>
                  <Label for="amount" class="text-sm font-semibold">Monto a Invertir</Label>
                  <div class="mt-2 space-y-2">
                    <Input
                      id="amount"
                      v-model.number="form.amount"
                      type="number"
                      :min="currentPlan?.amount_min"
                      :max="currentPlan?.amount_max"
                      placeholder="Ingresa el monto"
                      :class="{ 'border-red-500': amountError }"
                    />
                    <p class="text-xs text-muted-foreground">
                      Min: {{ formatCurrency(currentPlan?.amount_min) }} • Max: {{ formatCurrency(currentPlan?.amount_max) }}
                    </p>
                  </div>
                  <span v-if="amountError" class="text-sm text-red-500">{{ amountError }}</span>
                  <span v-else-if="form.errors.amount" class="text-sm text-red-500">{{ form.errors.amount }}</span>
                </div>

                <!-- Moneda -->
                <div>
                  <Label for="currency" class="text-sm font-semibold">Moneda</Label>
                  <Select v-model="form.currency">
                    <SelectTrigger id="currency" class="mt-2">
                      <SelectValue placeholder="Selecciona moneda" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value="USD">USD - Dólar</SelectItem>
                        <SelectItem value="EUR">EUR - Euro</SelectItem>
                        <SelectItem value="PEN">PEN - Sol Peruano</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <span v-if="form.errors.currency" class="text-sm text-red-500">{{ form.errors.currency }}</span>
                </div>

                <!-- Cuenta de Pago -->
                <div>
                  <Label for="client_account_id" class="text-sm font-semibold">Cuenta de Pago</Label>
                  <Select v-model="form.client_account_id">
                    <SelectTrigger id="client_account_id" class="mt-2">
                      <SelectValue placeholder="Selecciona una cuenta" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem
                          v-for="account in clientAccounts"
                          :key="account.id"
                          :value="String(account.id)"
                        >
                          {{ account.bank_name }} - {{ account.holder_name }}
                        </SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <span v-if="form.errors.client_account_id" class="text-sm text-red-500">{{ form.errors.client_account_id }}</span>
                </div>

                <!-- Botones -->
                <div class="flex gap-2 pt-4 border-t">
                  <Link href="/clients/payments/select" class="flex-1">
                    <Button variant="outline" class="w-full">Atrás</Button>
                  </Link>
                  <Button type="submit" :disabled="form.processing || !clientAccounts?.length" class="flex-1">
                    {{ form.processing ? 'Procesando...' : 'Continuar al Pago' }}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>

          <!-- Resumen de Pago -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Resumen</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
              <!-- Inversión -->
              <div class="flex justify-between pb-2 border-b">
                <span class="text-muted-foreground">Inversión:</span>
                <span class="font-medium">{{ form.amount > 0 ? formatCurrency(form.amount) : '—' }}</span>
              </div>

              <!-- Membresía (si aplica) -->
              <div v-if="currentPlan?.membership && currentPlan.membership > 0" class="flex justify-between pb-2 border-b">
                <span class="text-muted-foreground">Membresía:</span>
                <span v-if="mustPayMembership" class="font-medium text-purple-600 dark:text-purple-400">+ {{ formatCurrency(currentPlan.membership) }}</span>
                <span v-else class="font-medium text-green-600 dark:text-green-400">✓ Incluida</span>
              </div>

              <!-- Total -->
              <div class="flex justify-between pt-2">
                <span class="font-semibold">Total:</span>
                <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatCurrency(totalAmount) }}</span>
              </div>

              <!-- Info -->
              <div v-if="mustPayMembership" class="mt-3 p-2 bg-purple-100 dark:bg-purple-900 rounded text-xs text-purple-800 dark:text-purple-200 flex items-center gap-1">
                <CreditCard class="h-3 w-3" />
                Incluye membresía (pago único)
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
