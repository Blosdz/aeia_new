<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, TrendingDown, ArrowLeft, Calendar, DollarSign, Target, FileText, Users } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Fund {
  id: number;
  name: string;
  category: string;
  initial_amount: number;
  current_amount: number;
  earning: number;
  earning_percentage: number;
}

interface EarningHistory {
  id: number;
  fund_id: number;
  fund_name: string;
  previous_amount: number;
  new_amount: number;
  change: number;
  fluctuation_percent: number;
  reason: string;
  recorded_at: string;
}

interface Payment {
  id: number;
  amount: number;
  currency: string;
  status: string;
  transaction_id: string;
  membership_charge: number;
  total_paid: number;
  created_at: string;
}

interface Plan {
  id: number;
  name: string;
  category: string;
  amount_min: number;
  amount_max: number;
  membership: number;
}

interface Participant {
  id: number;
  role: string;
  share_percent: number;
  final_investment_amount: number;
  is_primary_owner: boolean;
  participating: boolean;
  started_at: string;
  ended_at: string | null;
  is_active: boolean;
  profile: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  investment_earning: {
    id: number;
    fund_id: number;
    fund_name: string;
    initial_amount: number;
    current_amount: number;
  } | null;
}

interface Subscription {
  id: number;
  unique_code: string;
  payment: Payment;
  plan: Plan;
  started_at: string;
  expires_at: string | null;
  total_invested: number;
  total_current_value: number;
  total_earnings: number;
  earnings_percentage: number;
  earnings_history: EarningHistory[];
  funds: Fund[];
  participants: Participant[];
}

const props = defineProps<{
  subscription: Subscription;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Suscripciones', href: route('clients.subscriptions') },
  { title: props.subscription.plan.name, href: '#' },
];

const formatCurrency = (amount: number, currency: string = 'USD') => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const getStatusBadge = (status: string) => {
  const statusMap = {
    pending: { label: 'Pendiente', variant: 'secondary' as const },
    completed: { label: 'Completado', variant: 'default' as const },
    failed: { label: 'Fallido', variant: 'destructive' as const },
    cancelled: { label: 'Cancelado', variant: 'destructive' as const },
  };
  return statusMap[status as keyof typeof statusMap] || { label: status, variant: 'secondary' as const };
};

const getEarningsColor = (percentage: number) => {
  if (percentage > 0) return 'text-green-600 dark:text-green-400';
  if (percentage < 0) return 'text-red-600 dark:text-red-400';
  return 'text-gray-600 dark:text-gray-400';
};

const getEarningsIcon = (percentage: number) => {
  return percentage >= 0 ? TrendingUp : TrendingDown;
};

const getRoleLabel = (role: string) => {
  const roleMap: Record<string, string> = {
    owner: 'Propietario',
    beneficiary: 'Beneficiario',
    advisor: 'Asesor',
  };
  return roleMap[role] || role;
};

const getRoleBadgeVariant = (role: string) => {
  const variantMap: Record<string, any> = {
    owner: 'default',
    beneficiary: 'secondary',
    advisor: 'outline',
  };
  return variantMap[role] || 'outline';
};
</script>

<template>
  <Head :title="`Suscripción - ${subscription.plan.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header con botón atrás -->
      <div class="flex items-center gap-4">
        <Link href="/clients/subscriptions">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold">{{ subscription.plan.name }}</h1>
          <p class="text-muted-foreground mt-1">Código: <span class="font-mono">{{ subscription.unique_code }}</span></p>
        </div>
        <Badge :variant="getStatusBadge(subscription.payment.status).variant">
          {{ getStatusBadge(subscription.payment.status).label }}
        </Badge>
      </div>

      <!-- Estadísticas principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Inversión inicial -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Inversión Inicial</p>
                <p class="text-2xl font-bold">{{ formatCurrency(subscription.total_invested, subscription.payment.currency) }}</p>
              </div>
              <DollarSign class="h-8 w-8 text-blue-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Valor actual -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Valor Actual</p>
                <p class="text-2xl font-bold">{{ formatCurrency(subscription.total_current_value, subscription.payment.currency) }}</p>
              </div>
              <Target class="h-8 w-8 text-purple-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Ganancias -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Ganancias</p>
                <p class="text-2xl font-bold" :class="getEarningsColor(subscription.earnings_percentage)">
                  {{ formatCurrency(subscription.total_earnings, subscription.payment.currency) }}
                </p>
                <p class="text-xs mt-1" :class="getEarningsColor(subscription.earnings_percentage)">
                  {{ subscription.earnings_percentage.toFixed(2) }}%
                </p>
              </div>
              <TrendingUp class="h-8 w-8 text-green-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Duración -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Inicio</p>
                <p class="text-sm font-semibold">{{ formatDate(subscription.started_at) }}</p>
                <p v-if="subscription.expires_at" class="text-xs text-muted-foreground mt-1">
                  Vence: {{ formatDate(subscription.expires_at) }}
                </p>
                <p v-else class="text-xs text-green-600 dark:text-green-400 mt-1">Sin vencimiento</p>
              </div>
              <Calendar class="h-8 w-8 text-orange-500 opacity-20" />
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información de pago (columna izquierda y central) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Detalles del pago -->
          <Card>
            <CardHeader>
              <CardTitle>Información de Pago</CardTitle>
              <CardDescription>Detalles de la transacción</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-muted-foreground mb-1">ID de Transacción</p>
                  <p class="font-mono text-sm font-semibold break-all">{{ subscription.payment.transaction_id }}</p>
                </div>
                <div>
                  <p class="text-sm text-muted-foreground mb-1">Fecha de Pago</p>
                  <p class="text-sm font-semibold">{{ formatDate(subscription.payment.created_at) }}</p>
                </div>
                <div>
                  <p class="text-sm text-muted-foreground mb-1">Inversión</p>
                  <p class="text-lg font-bold">{{ formatCurrency(subscription.payment.amount, subscription.payment.currency) }}</p>
                </div>
                <div>
                  <p class="text-sm text-muted-foreground mb-1">Membresía</p>
                  <p v-if="subscription.payment.membership_charge > 0" class="text-lg font-bold text-purple-600 dark:text-purple-400">
                    {{ formatCurrency(subscription.payment.membership_charge, subscription.payment.currency) }}
                  </p>
                  <p v-else class="text-sm text-muted-foreground">No aplica</p>
                </div>
                <div class="md:col-span-2 p-4 bg-green-50 dark:bg-green-950 rounded-lg border border-green-200 dark:border-green-800">
                  <p class="text-sm text-muted-foreground mb-1">Total Pagado</p>
                  <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ formatCurrency(subscription.payment.total_paid, subscription.payment.currency) }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Fondos asociados -->
          <Card v-if="subscription.funds.length > 0">
            <CardHeader>
              <CardTitle>Fondos Asociados</CardTitle>
              <CardDescription>Distribución de inversión por fondo</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="fund in subscription.funds" :key="fund.id" class="p-4 border rounded-lg space-y-3">
                  <div class="flex items-start justify-between">
                    <div>
                      <h4 class="font-semibold">{{ fund.name }}</h4>
                      <p class="text-xs text-muted-foreground capitalize">{{ fund.category }}</p>
                    </div>
                    <Badge variant="outline">{{ fund.earning_percentage.toFixed(2) }}%</Badge>
                  </div>

                  <div class="grid grid-cols-3 gap-4">
                    <div>
                      <p class="text-xs text-muted-foreground mb-1">Invertido</p>
                      <p class="font-semibold">{{ formatCurrency(fund.initial_amount, subscription.payment.currency) }}</p>
                    </div>
                    <div>
                      <p class="text-xs text-muted-foreground mb-1">Valor Actual</p>
                      <p class="font-semibold text-blue-600 dark:text-blue-400">{{ formatCurrency(fund.current_amount, subscription.payment.currency) }}</p>
                    </div>
                    <div>
                      <p class="text-xs text-muted-foreground mb-1">Ganancia</p>
                      <p class="font-semibold" :class="getEarningsColor(fund.earning_percentage)">
                        {{ formatCurrency(fund.earning, subscription.payment.currency) }}
                      </p>
                    </div>
                  </div>

                  <!-- Barra de progreso -->
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                    <div
                      class="h-full bg-gradient-to-r from-blue-500 to-purple-600"
                      :style="{ width: `${Math.min((fund.current_amount / fund.initial_amount) * 100, 150)}%` }"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Información del plan (columna derecha) -->
        <div class="space-y-4">
          <!-- Detalles del plan -->
          <Card>
            <CardHeader>
              <CardTitle>Detalles del Plan</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Nombre</p>
                <p class="font-semibold">{{ subscription.plan.name }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Categoría</p>
                <p class="font-semibold capitalize">{{ subscription.plan.category }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Rango de Inversión</p>
                <p class="font-semibold">{{ formatCurrency(subscription.plan.amount_min) }} - {{ formatCurrency(subscription.plan.amount_max) }}</p>
              </div>
              <div v-if="subscription.plan.membership > 0">
                <p class="text-xs text-muted-foreground mb-1 uppercase tracking-wide">Membresía</p>
                <p class="font-semibold text-purple-600 dark:text-purple-400">{{ formatCurrency(subscription.plan.membership) }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Resumen de ganancias -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Resumen de Ganancias</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center gap-2 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950 rounded-lg">
                <component :is="getEarningsIcon(subscription.earnings_percentage)" :class="`h-6 w-6 ${getEarningsColor(subscription.earnings_percentage)}`" />
                <div>
                  <p class="text-sm text-muted-foreground">Rendimiento</p>
                  <p class="text-2xl font-bold" :class="getEarningsColor(subscription.earnings_percentage)">
                    {{ subscription.earnings_percentage.toFixed(2) }}%
                  </p>
                </div>
              </div>

              <div class="space-y-2 pt-2">
                <div class="flex justify-between">
                  <span class="text-sm">Inversión Inicial:</span>
                  <span class="font-semibold">{{ formatCurrency(subscription.total_invested, subscription.payment.currency) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm">Ganancias:</span>
                  <span class="font-semibold" :class="getEarningsColor(subscription.earnings_percentage)">
                    + {{ formatCurrency(subscription.total_earnings, subscription.payment.currency) }}
                  </span>
                </div>
                <div class="flex justify-between border-t pt-2 mt-2">
                  <span class="font-semibold">Valor Total:</span>
                  <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                    {{ formatCurrency(subscription.total_current_value, subscription.payment.currency) }}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Participantes -->
          <Card v-if="subscription.participants && subscription.participants.length > 0">
            <CardHeader>
              <CardTitle class="text-lg">Participantes</CardTitle>
              <CardDescription>Personas con participación en esta suscripción</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div
                  v-for="participant in subscription.participants"
                  :key="participant.id"
                  class="p-4 border rounded-lg space-y-3"
                  :class="{ 'bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800': participant.is_primary_owner }"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 flex-wrap">
                        <Badge :variant="getRoleBadgeVariant(participant.role)">
                          {{ getRoleLabel(participant.role) }}
                        </Badge>
                        <Badge v-if="participant.is_primary_owner" variant="default" class="bg-blue-600">
                          Principal
                        </Badge>
                        <Badge v-if="!participant.is_active" variant="destructive">
                          Inactivo
                        </Badge>
                        <Badge v-if="!participant.participating" variant="outline">
                          No Participando
                        </Badge>
                      </div>
                      <p v-if="participant.profile" class="font-semibold mt-2">
                        {{ participant.profile.first_name }} {{ participant.profile.last_name }}
                      </p>
                      <p v-if="participant.profile" class="text-xs text-muted-foreground">
                        {{ participant.profile.email }}
                      </p>
                      <p v-else class="text-sm text-muted-foreground mt-2 italic">Sin perfil asignado</p>
                    </div>
                    <div class="text-right">
                      <p class="text-sm text-muted-foreground">Participación</p>
                      <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ participant.share_percent.toFixed(2) }}%
                      </p>
                    </div>
                  </div>

                  <!-- Información del monto invertido -->
                  <div class="grid grid-cols-2 gap-3 pt-2 border-t">
                    <div>
                      <p class="text-xs text-muted-foreground mb-1">Monto Final Invertido</p>
                      <p class="text-lg font-bold text-green-600 dark:text-green-400">
                        {{ formatCurrency(participant.final_investment_amount, subscription.payment.currency) }}
                      </p>
                    </div>
                    <div v-if="participant.investment_earning">
                      <p class="text-xs text-muted-foreground mb-1">Fondo Asociado</p>
                      <p class="font-semibold text-sm">{{ participant.investment_earning.fund_name }}</p>
                    </div>
                  </div>

                  <!-- Ganancia del participante (basado en su porcentaje) -->
                  <div v-if="participant.investment_earning && participant.share_percent > 0" class="p-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950 dark:to-pink-950 rounded-lg border border-purple-200 dark:border-purple-800">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                      <div>
                        <p class="text-muted-foreground mb-1">Ganancia del Fondo</p>
                        <p class="font-bold text-sm">
                          {{ formatCurrency(participant.investment_earning.current_amount - participant.investment_earning.initial_amount, subscription.payment.currency) }}
                        </p>
                      </div>
                      <div>
                        <p class="text-muted-foreground mb-1">Tu Parte ({{ participant.share_percent.toFixed(2) }}%)</p>
                        <p class="font-bold text-sm text-purple-600 dark:text-purple-400">
                          {{ formatCurrency((participant.investment_earning.current_amount - participant.investment_earning.initial_amount) * (participant.share_percent / 100), subscription.payment.currency) }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Fechas -->
                  <div class="grid grid-cols-2 gap-2 pt-2 border-t text-xs">
                    <div>
                      <p class="text-muted-foreground">Inicio</p>
                      <p class="font-semibold">{{ formatDate(participant.started_at) }}</p>
                    </div>
                    <div v-if="participant.ended_at">
                      <p class="text-muted-foreground">Fin</p>
                      <p class="font-semibold">{{ formatDate(participant.ended_at) }}</p>
                    </div>
                    <div v-else>
                      <p class="text-muted-foreground">Estado</p>
                      <p class="font-semibold text-green-600 dark:text-green-400">Activo</p>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Historial de ganancias -->
      <Card v-if="subscription.earnings_history.length > 0">
        <CardHeader>
          <CardTitle>Historial de Ganancias</CardTitle>
          <CardDescription>Últimas actualizaciones de ganancias</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b">
                <tr class="text-muted-foreground text-xs uppercase">
                  <th class="text-left py-2 px-2">Fondo</th>
                  <th class="text-left py-2 px-2">Fecha</th>
                  <th class="text-right py-2 px-2">Monto Anterior</th>
                  <th class="text-right py-2 px-2">Monto Nuevo</th>
                  <th class="text-right py-2 px-2">Cambio</th>
                  <th class="text-right py-2 px-2">% Fluctuación</th>
                  <th class="text-left py-2 px-2">Razón</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entry in subscription.earnings_history.slice(0, 20)" :key="entry.id" class="border-b hover:bg-muted/50">
                  <td class="py-3 px-2 font-semibold">{{ entry.fund_name }}</td>
                  <td class="py-3 px-2 text-muted-foreground">{{ formatDate(entry.recorded_at) }}</td>
                  <td class="py-3 px-2 text-right">{{ formatCurrency(entry.previous_amount, subscription.payment.currency) }}</td>
                  <td class="py-3 px-2 text-right">{{ formatCurrency(entry.new_amount, subscription.payment.currency) }}</td>
                  <td class="py-3 px-2 text-right font-semibold" :class="entry.change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ entry.change >= 0 ? '+' : '' }}{{ formatCurrency(entry.change, subscription.payment.currency) }}
                  </td>
                  <td class="py-3 px-2 text-right" :class="entry.fluctuation_percent >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ entry.fluctuation_percent >= 0 ? '+' : '' }}{{ entry.fluctuation_percent.toFixed(2) }}%
                  </td>
                  <td class="py-3 px-2 text-muted-foreground capitalize">{{ entry.reason || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
