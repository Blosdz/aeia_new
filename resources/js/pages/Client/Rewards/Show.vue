<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, DollarSign, Percent, TrendingUp, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Reward {
  id: number;
  fund_name: string;
  fund_description: string;
  status: 'pending' | 'closed' | 'paid';
  closed_at: string | null;
  paid_at: string | null;
  total_investment: number;
  total_earnings: number;
  company_percentage: number;
  company_deduction: number;
  was_referred: boolean;
  referral_percentage: number;
  referral_deduction: number;
  net_earnings: number;
  total_return: number;
  referrer_name: string | null;
}

interface Props {
  reward: Reward;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Recompensas', href: route('clients.rewards') },
  { title: props.reward.fund_name, href: route('clients.rewards.show', props.reward.id) },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount || 0);
};

const formatDate = (date: string | null) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'paid':
      return 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200';
    case 'closed':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-200';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-950 dark:text-gray-200';
  }
};

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'paid':
      return 'Pagado';
    case 'closed':
      return 'Cerrado';
    case 'pending':
      return 'Pendiente';
    default:
      return status;
  }
};
</script>

<template>
  <Head :title="`Recompensa - ${reward.fund_name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Encabezado con botón atrás -->
      <div class="flex justify-between items-start">
        <div class="flex items-center gap-4">
          <Link href="/clients/rewards">
            <Button variant="ghost" size="icon">
              <ArrowLeft class="h-4 w-4" />
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold">{{ reward.fund_name }}</h1>
            <p class="text-muted-foreground mt-1">{{ reward.fund_description }}</p>
          </div>
        </div>
        <Badge :class="getStatusColor(reward.status)" class="text-lg px-3 py-1">
          {{ getStatusLabel(reward.status) }}
        </Badge>
      </div>

      <!-- Información Principal -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <DollarSign class="h-4 w-4 text-blue-600" />
              Inversión
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ formatCurrency(reward.total_investment) }}</div>
            <p class="text-xs text-muted-foreground mt-1">Monto invertido</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <TrendingUp class="h-4 w-4 text-green-600" />
              Ganancia del Fondo
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">+{{ formatCurrency(reward.total_earnings) }}</div>
            <p class="text-xs text-muted-foreground mt-1">Generada en el período</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <Percent class="h-4 w-4 text-orange-600" />
              Deducciones Totales
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">-{{ formatCurrency(reward.company_deduction + reward.referral_deduction) }}</div>
            <p class="text-xs text-muted-foreground mt-1">Empresa y comisiones</p>
          </CardContent>
        </Card>

        <Card class="border-2 border-blue-500">
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <CheckCircle2 class="h-4 w-4 text-blue-600" />
              Total a Retornar
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(reward.total_return) }}</div>
            <p class="text-xs text-muted-foreground mt-1">Inversión + ganancia neta</p>
          </CardContent>
        </Card>
      </div>

      <!-- Desglose Detallado -->
      <Card>
        <CardHeader>
          <CardTitle>Desglose de Cálculo</CardTitle>
          <CardDescription>Detalle paso a paso de cómo se calcula tu recompensa</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <!-- Paso 1: Inversión -->
            <div class="border rounded-lg p-4 bg-gradient-to-r from-blue-50 to-transparent dark:from-blue-950/20">
              <div class="flex justify-between items-center mb-2">
                <p class="font-medium">1. Inversión Inicial</p>
                <p class="text-lg font-bold">{{ formatCurrency(reward.total_investment) }}</p>
              </div>
              <p class="text-sm text-muted-foreground">El monto que invertiste en el fondo</p>
            </div>

            <!-- Paso 2: Ganancia -->
            <div class="border rounded-lg p-4 bg-gradient-to-r from-green-50 to-transparent dark:from-green-950/20">
              <div class="flex justify-between items-center mb-2">
                <p class="font-medium">2. Ganancia Generada del Fondo</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">+{{ formatCurrency(reward.total_earnings) }}</p>
              </div>
              <p class="text-sm text-muted-foreground">Ganancia según performance del fondo</p>
            </div>

            <!-- Paso 3: Subtotal -->
            <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-950/50">
              <div class="flex justify-between items-center">
                <p class="font-medium">Subtotal (Inversión + Ganancia)</p>
                <p class="text-lg font-bold">{{ formatCurrency(reward.total_investment + reward.total_earnings) }}</p>
              </div>
            </div>

            <!-- Paso 4: Deducciones -->
            <div class="border rounded-lg p-4 space-y-3">
              <p class="font-medium mb-3">3. Deducciones Aplicadas</p>
              
              <div class="flex justify-between items-center pl-4 py-2 bg-orange-50 dark:bg-orange-950/20 rounded">
                <span>Deducción Empresa ({{ reward.company_percentage }}%)</span>
                <span class="font-bold text-orange-600 dark:text-orange-400">-{{ formatCurrency(reward.company_deduction) }}</span>
              </div>

              <div
                v-if="reward.was_referred && reward.referral_percentage > 0"
                class="flex justify-between items-center pl-4 py-2 bg-purple-50 dark:bg-purple-950/20 rounded"
              >
                <span>Comisión de Referencia ({{ reward.referral_percentage }}%)</span>
                <span class="font-bold text-purple-600 dark:text-purple-400">-{{ formatCurrency(reward.referral_deduction) }}</span>
              </div>

              <div
                v-if="reward.was_referred && reward.referrer_name"
                class="pl-4 py-2 bg-blue-50 dark:bg-blue-950/20 rounded text-sm text-muted-foreground"
              >
                <p>Referido por: <span class="font-semibold">{{ reward.referrer_name }}</span></p>
              </div>
            </div>

            <!-- Paso 5: Total Neto -->
            <div class="border rounded-lg p-4 bg-gradient-to-r from-blue-50 to-transparent dark:from-blue-950/20">
              <div class="flex justify-between items-center mb-2">
                <p class="font-medium">4. Ganancia Neta (después de deducciones)</p>
                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">+{{ formatCurrency(reward.net_earnings) }}</p>
              </div>
              <p class="text-sm text-muted-foreground">Ganancia que recibirás</p>
            </div>

            <!-- Resultado Final -->
            <div class="border-2 border-green-500 rounded-lg p-4 bg-green-50 dark:bg-green-950/20">
              <div class="flex justify-between items-center">
                <p class="font-bold text-lg">TOTAL A RETORNAR</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(reward.total_return) }}</p>
              </div>
              <p class="text-sm text-muted-foreground mt-2">Inversión inicial + Ganancia neta</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Información de Fechas -->
      <Card>
        <CardHeader>
          <CardTitle>Información de Fechas</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="reward.closed_at" class="flex justify-between items-center p-3 border rounded bg-muted/50">
            <span class="font-medium">Fecha de Cierre del Fondo:</span>
            <span>{{ formatDate(reward.closed_at) }}</span>
          </div>
          <div v-if="reward.paid_at" class="flex justify-between items-center p-3 border rounded bg-green-50 dark:bg-green-950/20">
            <span class="font-medium">Fecha de Pago:</span>
            <span class="text-green-600 dark:text-green-400">{{ formatDate(reward.paid_at) }}</span>
          </div>
          <div v-else-if="reward.status === 'closed'" class="flex justify-between items-center p-3 border rounded bg-yellow-50 dark:bg-yellow-950/20">
            <span class="font-medium">Estado:</span>
            <span class="text-yellow-600 dark:text-yellow-400">Esperando procesamiento de pago</span>
          </div>
        </CardContent>
      </Card>

      <!-- Botones de Acción -->
      <div class="flex gap-3">
        <Link href="/clients/rewards">
          <Button variant="outline">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Volver a Recompensas
          </Button>
        </Link>
        <Link href="/clients/dashboard">
          <Button variant="outline">
            Ir al Dashboard
          </Button>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
