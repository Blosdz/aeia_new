<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Users, DollarSign, CreditCard, TrendingUp, Activity, Award, ArrowRight } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Stats {
  total_users: number;
  active_users: number;
  total_payments: number;
  completed_payments: number;
  total_invested: number;
  total_subscriptions: number;
  total_revenue: number;
}

interface RecentPayment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  status: string;
  user: string;
  plan: string;
  created_at: string;
}

interface TopPlan {
  id: number;
  name: string;
  subscriptions_count: number;
  amount_min: number;
  amount_max: number;
}

interface RevenueByDay {
  date: string;
  total: number;
}

const props = defineProps<{
  stats: Stats;
  recent_payments: RecentPayment[];
  top_plans: TopPlan[];
  users_by_role: Record<string, number>;
  revenue_by_day: RevenueByDay[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: '#' },
  { title: 'Dashboard', href: route('admin.dashboard') },
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
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
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
</script>

<template>
  <Head title="Admin Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold">Dashboard Admin</h1>
          <p class="text-muted-foreground mt-1">Bienvenido al panel de administración</p>
        </div>
        <Link href="/admin/users/create">
          <Button class="gap-2">
            <Users class="h-5 w-5" />
            Nuevo Usuario
          </Button>
        </Link>
      </div>

      <!-- Estadísticas principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total de usuarios -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Total de Usuarios</p>
                <p class="text-2xl font-bold">{{ stats.total_users }}</p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ stats.active_users }} activos</p>
              </div>
              <Users class="h-8 w-8 text-blue-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Total de pagos -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Total de Pagos</p>
                <p class="text-2xl font-bold">{{ stats.total_payments }}</p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ stats.completed_payments }} completados</p>
              </div>
              <CreditCard class="h-8 w-8 text-purple-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Ingresos totales -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Ingresos Totales</p>
                <p class="text-2xl font-bold">{{ formatCurrency(stats.total_revenue) }}</p>
                <p class="text-xs text-muted-foreground mt-1">USD</p>
              </div>
              <DollarSign class="h-8 w-8 text-green-500 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <!-- Total invertido -->
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Monto Invertido</p>
                <p class="text-2xl font-bold">{{ formatCurrency(stats.total_invested) }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ stats.total_subscriptions }} suscripciones</p>
              </div>
              <TrendingUp class="h-8 w-8 text-orange-500 opacity-20" />
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Pagos recientes (columna izquierda y central) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Últimos pagos -->
          <Card>
            <CardHeader class="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Pagos Recientes</CardTitle>
                <CardDescription>Últimos pagos procesados</CardDescription>
              </div>
              <Link href="/admin/payments">
                <Button variant="ghost" size="sm" class="gap-2">
                  Ver todos
                  <ArrowRight class="h-4 w-4" />
                </Button>
              </Link>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="payment in recent_payments" :key="payment.id" class="flex items-start justify-between pb-4 border-b last:border-0">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <p class="font-semibold">{{ payment.user }}</p>
                      <Badge :variant="getStatusBadge(payment.status).variant" class="text-xs">
                        {{ getStatusBadge(payment.status).label }}
                      </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ payment.plan }}</p>
                    <p class="text-xs text-muted-foreground mt-1">ID: {{ payment.transaction_id }}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold">{{ formatCurrency(payment.amount, payment.currency) }}</p>
                    <p class="text-xs text-muted-foreground">{{ formatDate(payment.created_at) }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Planes más utilizados -->
          <Card v-if="top_plans.length > 0">
            <CardHeader>
              <CardTitle>Planes Más Utilizados</CardTitle>
              <CardDescription>Planes con más suscripciones</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="plan in top_plans" :key="plan.id" class="flex items-start justify-between pb-4 border-b last:border-0">
                  <div>
                    <p class="font-semibold">{{ plan.name }}</p>
                    <p class="text-xs text-muted-foreground">Rango: {{ formatCurrency(plan.amount_min) }} - {{ formatCurrency(plan.amount_max) }}</p>
                  </div>
                  <div class="text-right">
                    <Badge variant="secondary">{{ plan.subscriptions_count }} suscripciones</Badge>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Panel derecho -->
        <div class="space-y-6">
          <!-- Distribución de usuarios por rol -->
          <Card v-if="Object.keys(users_by_role).length > 0">
            <CardHeader>
              <CardTitle>Usuarios por Rol</CardTitle>
              <CardDescription>Distribución de usuarios</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="(count, role) in users_by_role" :key="role" class="flex items-center justify-between">
                  <span class="text-sm capitalize">{{ role }}</span>
                  <Badge variant="outline">{{ count }}</Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Acciones rápidas -->
          <Card>
            <CardHeader>
              <CardTitle>Acciones Rápidas</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <Link href="/admin/users">
                <Button variant="outline" class="w-full justify-start">
                  <Users class="h-4 w-4 mr-2" />
                  Gestionar Usuarios
                </Button>
              </Link>
              <Link href="/admin">
                <Button variant="outline" class="w-full justify-start">
                  <CreditCard class="h-4 w-4 mr-2" />
                  Ver Pagos
                </Button>
              </Link>
              <Link href="/admin">
                <Button variant="outline" class="w-full justify-start">
                  <Activity class="h-4 w-4 mr-2" />
                  Ver Reportes
                </Button>
              </Link>
            </CardContent>
          </Card>

          <!-- Resumen rápido -->
          <Card class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-950 dark:to-purple-950 border-blue-200 dark:border-blue-800">
            <CardHeader>
              <CardTitle class="text-lg">Resumen</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Suscripciones:</span>
                <span class="font-semibold">{{ stats.total_subscriptions }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Tasa de conversión:</span>
                <span class="font-semibold">{{ stats.total_users > 0 ? ((stats.total_payments / stats.total_users) * 100).toFixed(1) : 0 }}%</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Pago promedio:</span>
                <span class="font-semibold">{{ stats.total_payments > 0 ? formatCurrency(stats.total_revenue / stats.total_payments) : formatCurrency(0) }}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
