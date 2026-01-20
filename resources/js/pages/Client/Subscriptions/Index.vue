<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, TrendingDown, DollarSign, Target, Calendar, Award, ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface EarningHistory {
  id: number;
  previous_amount: number;
  new_amount: number;
  change: number;
  fluctuation_percent: number;
  reason: string | null;
  recorded_at: string;
}

interface Fund {
  id: number;
  name: string;
  category: string;
  initial_amount: number;
  current_amount: number;
  earning_amount: number;
  earning_percentage: number;
  recent_history: EarningHistory[];
}

interface ParticipantProfile {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
}

interface ParticipantInvestmentEarning {
  id: number;
  fund_id: number;
  fund_name: string | null;
  initial_amount: number;
  current_amount: number;
  earning: number;
  participant_earning: number;
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
  profile: ParticipantProfile | null;
  investment_earning: ParticipantInvestmentEarning | null;
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

interface Subscription {
  id: number;
  unique_code: string;
  payment: Payment;
  plan: Plan;
  started_at: string;
  expires_at: string | null;
  associated_funds: Fund[];
  participants: Participant[];
  total_invested: number;
  total_current_value: number;
  total_earnings: number;
  earnings_percentage: number;
}

interface PaginatedSubscriptions {
  data: Subscription[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const props = defineProps<{
  subscriptions: PaginatedSubscriptions;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Suscripciones', href: '#' },
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

const averageEarningsPercentage = computed(() => {
  if (props.subscriptions.data.length === 0) return 0;
  return props.subscriptions.data.reduce((sum: number, s: Subscription) => sum + s.earnings_percentage, 0) / props.subscriptions.data.length;
});
</script>

<template>
  <Head title="Mis Suscripciones" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold">Mis Suscripciones</h1>
          <p class="text-muted-foreground mt-1">Monitorea tus inversiones y ganancias</p>
        </div>
        <Link href="/clients/payments/select">
          <Button class="gap-2">
            <Award class="h-5 w-5" />
            Nueva Inversión
          </Button>
        </Link>
      </div>

      <!-- Estadísticas generales -->
      <div v-if="subscriptions.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total invertido -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Total Invertido</p>
                <p class="text-2xl font-bold">{{ formatCurrency(subscriptions.data.reduce((sum: number, s: Subscription) => sum + s.total_invested, 0)) }}</p>
              </div>
              <DollarSign class="h-8 w-8 text-blue-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Total actual -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Valor Actual</p>
                <p class="text-2xl font-bold">{{ formatCurrency(subscriptions.data.reduce((sum: number, s: Subscription) => sum + s.total_current_value, 0)) }}</p>
              </div>
              <Target class="h-8 w-8 text-purple-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Ganancias totales -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Ganancias Totales</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(subscriptions.data.reduce((sum: number, s: Subscription) => sum + s.total_earnings, 0)) }}</p>
              </div>
              <TrendingUp class="h-8 w-8 text-green-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Rendimiento promedio -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Rendimiento Promedio</p>
                <p class="text-2xl font-bold">
                  {{ averageEarningsPercentage.toFixed(2) }}%
                </p>
              </div>
              <Award class="h-8 w-8 text-orange-500 opacity-20" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Sin suscripciones -->
      <div v-else class="text-center py-12">
        <Award class="h-16 w-16 mx-auto text-gray-300 dark:text-gray-700 mb-4" />
        <h3 class="text-xl font-semibold mb-2">No tienes suscripciones activas</h3>
        <p class="text-muted-foreground mb-6">Comienza tu primera inversión hoy</p>
        <Link href="/clients/payments/select">
          <Button class="gap-2">
            <Award class="h-5 w-5" />
            Crear Primera Inversión
          </Button>
        </Link>
      </div>

      <!-- Lista de suscripciones -->
      <div v-if="subscriptions.data.length > 0" class="space-y-4">
        <div v-for="subscription in subscriptions.data" :key="subscription.id" class="group">
          <Card class="overflow-hidden hover:shadow-lg transition-shadow">
            <!-- Header de la tarjeta -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950 dark:to-purple-950 px-6 py-4 border-b flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-bold">{{ subscription.plan.name }}</h3>
                  <Badge :variant="getStatusBadge(subscription.payment.status).variant">
                    {{ getStatusBadge(subscription.payment.status).label }}
                  </Badge>
                </div>
                <p class="text-sm text-muted-foreground">Código: <span class="font-mono">{{ subscription.unique_code }}</span></p>
              </div>
              <Link :href="`/clients/subscriptions/${subscription.id}`" class="opacity-0 group-hover:opacity-100 transition-opacity">
                <Button variant="ghost" size="icon">
                  <ArrowRight class="h-5 w-5" />
                </Button>
              </Link>
            </div>

            <CardContent class="pt-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Información de pago -->
                <div class="space-y-2">
                  <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Información de Pago</p>
                  <div class="space-y-1">
                    <p class="text-sm">
                      <span class="text-muted-foreground">Inversión:</span>
                      <span class="font-semibold">{{ formatCurrency(subscription.payment.amount, subscription.payment.currency) }}</span>
                    </p>
                    <p v-if="subscription.payment.membership_charge > 0" class="text-sm">
                      <span class="text-muted-foreground">Membresía:</span>
                      <span class="font-semibold text-purple-600 dark:text-purple-400">+ {{ formatCurrency(subscription.payment.membership_charge, subscription.payment.currency) }}</span>
                    </p>
                    <p class="text-sm border-t pt-1 mt-1">
                      <span class="text-muted-foreground">Total:</span>
                      <span class="font-bold text-green-600 dark:text-green-400">{{ formatCurrency(subscription.payment.total_paid, subscription.payment.currency) }}</span>
                    </p>
                  </div>
                </div>

                <!-- Información de plan -->
                <div class="space-y-2">
                  <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Plan</p>
                  <div class="space-y-1 text-sm">
                    <p>
                      <span class="text-muted-foreground">Rango:</span>
                      <span class="font-semibold">{{ formatCurrency(subscription.plan.amount_min) }} - {{ formatCurrency(subscription.plan.amount_max) }}</span>
                    </p>
                    <p>
                      <span class="text-muted-foreground">Categoría:</span>
                      <span class="font-semibold capitalize">{{ subscription.plan.category }}</span>
                    </p>
                    <p>
                      <span class="text-muted-foreground">Inicio:</span>
                      <span class="font-semibold">{{ formatDate(subscription.started_at) }}</span>
                    </p>
                  </div>
                </div>

                <!-- Inversión y valor -->
                <div class="space-y-2">
                  <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Inversión</p>
                  <div class="space-y-1">
                    <p class="text-sm">
                      <span class="text-muted-foreground">Invertido:</span>
                      <span class="font-semibold">{{ formatCurrency(subscription.total_invested) }}</span>
                    </p>
                    <p class="text-sm">
                      <span class="text-muted-foreground">Valor Actual:</span>
                      <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatCurrency(subscription.total_current_value) }}</span>
                    </p>
                  </div>
                </div>

                <!-- Ganancias -->
                <div class="space-y-2">
                  <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Ganancias</p>
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <component :is="getEarningsIcon(subscription.earnings_percentage)" :class="`h-5 w-5 ${getEarningsColor(subscription.earnings_percentage)}`" />
                      <div>
                        <p class="text-sm font-semibold" :class="getEarningsColor(subscription.earnings_percentage)">
                          {{ formatCurrency(subscription.total_earnings) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                          {{ subscription.earnings_percentage.toFixed(2) }}%
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Fondos asociados -->
              <div v-if="subscription.associated_funds.length > 0" class="mt-6 pt-6 border-t">
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Fondos Asociados</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                  <div v-for="fund in subscription.associated_funds" :key="fund.id" class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <p class="font-semibold text-sm">{{ fund.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ fund.category }}</p>
                    <div class="mt-2 space-y-1">
                      <div class="flex items-center justify-between">
                        <span class="text-xs text-muted-foreground">Invertido:</span>
                        <span class="text-xs font-semibold">{{ formatCurrency(fund.initial_amount) }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-xs text-muted-foreground">Actual:</span>
                        <span class="text-xs font-semibold">{{ formatCurrency(fund.current_amount) }}</span>
                      </div>
                      <div class="flex items-center justify-between border-t pt-1">
                        <span class="text-sm font-medium" :class="getEarningsColor(fund.earning_percentage)">
                          {{ formatCurrency(fund.earning_amount) }}
                        </span>
                        <span class="text-xs font-semibold" :class="getEarningsColor(fund.earning_percentage)">
                          {{ fund.earning_percentage.toFixed(2) }}%
                        </span>
                      </div>
                    </div>

                    <!-- Historial reciente del fondo -->
                    <div v-if="fund.recent_history && fund.recent_history.length > 0" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                      <p class="text-xs font-semibold text-muted-foreground mb-2">Movimientos Recientes</p>
                      <div class="space-y-1 max-h-32 overflow-y-auto">
                        <div v-for="history in fund.recent_history" :key="history.id" class="text-xs flex items-center justify-between py-1">
                          <div class="flex-1">
                            <span :class="history.change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="font-semibold">
                              {{ history.change >= 0 ? '+' : '' }}{{ formatCurrency(history.change) }}
                            </span>
                            <span class="text-muted-foreground ml-1">({{ history.fluctuation_percent.toFixed(2) }}%)</span>
                          </div>
                          <span class="text-muted-foreground text-xs">{{ new Date(history.recorded_at).toLocaleDateString('es-ES', { month: 'short', day: 'numeric' }) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Participantes -->
              <div v-if="subscription.participants && subscription.participants.length > 0" class="mt-6 pt-6 border-t">
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Participantes de la Suscripción</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div v-for="participant in subscription.participants" :key="participant.id"
                       class="p-4 rounded-lg border"
                       :class="participant.is_primary_owner ? 'bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-gray-900'">
                    <div class="flex items-start justify-between mb-2">
                      <div>
                        <p class="font-semibold text-sm">
                          {{ participant.profile ? `${participant.profile.first_name} ${participant.profile.last_name}` : 'Sin perfil' }}
                          <Badge v-if="participant.is_primary_owner" variant="default" class="ml-2 text-xs">Propietario</Badge>
                        </p>
                        <p class="text-xs text-muted-foreground">{{ participant.profile?.email }}</p>
                      </div>
                      <Badge :variant="participant.is_active ? 'default' : 'secondary'" class="text-xs">
                        {{ participant.is_active ? 'Activo' : 'Inactivo' }}
                      </Badge>
                    </div>

                    <div class="space-y-2 mt-3">
                      <div class="flex justify-between text-xs">
                        <span class="text-muted-foreground">Rol:</span>
                        <span class="font-semibold capitalize">{{ participant.role }}</span>
                      </div>
                      <div class="flex justify-between text-xs">
                        <span class="text-muted-foreground">Porcentaje:</span>
                        <span class="font-semibold">{{ participant.share_percent.toFixed(2) }}%</span>
                      </div>
                      <div class="flex justify-between text-xs">
                        <span class="text-muted-foreground">Inversión Final:</span>
                        <span class="font-semibold">{{ formatCurrency(participant.final_investment_amount) }}</span>
                      </div>

                      <!-- Información del fondo del participante -->
                      <div v-if="participant.investment_earning" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-semibold text-muted-foreground mb-2">Fondo Asignado</p>
                        <div class="space-y-1">
                          <p class="text-xs font-semibold">{{ participant.investment_earning.fund_name }}</p>
                          <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Monto Inicial:</span>
                            <span class="font-semibold">{{ formatCurrency(participant.investment_earning.initial_amount) }}</span>
                          </div>
                          <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Monto Actual:</span>
                            <span class="font-semibold">{{ formatCurrency(participant.investment_earning.current_amount) }}</span>
                          </div>
                          <div class="flex justify-between text-xs border-t pt-1 mt-1">
                            <span class="text-muted-foreground">Ganancia Total Fondo:</span>
                            <span class="font-semibold" :class="getEarningsColor(participant.investment_earning.earning > 0 ? 1 : participant.investment_earning.earning < 0 ? -1 : 0)">
                              {{ formatCurrency(participant.investment_earning.earning) }}
                            </span>
                          </div>
                          <div v-if="participant.share_percent > 0" class="flex justify-between text-xs bg-green-50 dark:bg-green-950 p-2 rounded mt-2">
                            <span class="text-green-900 dark:text-green-100 font-semibold">Tu Parte ({{ participant.share_percent.toFixed(2) }}%):</span>
                            <span class="font-bold text-green-600 dark:text-green-400">
                              {{ formatCurrency(participant.investment_earning.participant_earning) }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
