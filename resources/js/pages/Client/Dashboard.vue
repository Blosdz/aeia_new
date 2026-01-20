el <script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { TrendingUp, Percent, DollarSign, Users, Calendar, Plus, Eye } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Subscription {
  id: number;
  unique_code: string;
  started_at: string;
  expires_at: string | null;
  planType?: {
    id: number;
    name: string;
  };
}

interface DashboardProps {
  profile: any;
  subscriptions: Subscription[];
  totalInvested: number;
  totalCurrentValue: number;
  totalGains: number;
  gainPercent: number;
  activeSubscriptions: number;
  totalRevenue: number;
  closureDays: number;
}

const props = defineProps<DashboardProps>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
];

// Mock data para el gráfico
const chartData = [
  { name: 'Ene', value: 1000 },
  { name: 'Feb', value: 1500 },
  { name: 'Mar', value: 2000 },
  { name: 'Abr', value: 2500 },
  { name: 'May', value: 3000 },
  { name: 'Jun', value: 3500 },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(amount || 0);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Bienvenida -->
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold">Bienvenido, {{ profile?.first_name || 'Cliente' }}</h1>
          <p class="text-muted-foreground mt-1">Resumen de tu portafolio de inversiones</p>
        </div>
      </div>

      <!-- Cards de Resumen -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-5">
        <!-- Inversión Total -->
        <Card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Inversión Total</CardTitle>
            <DollarSign class="h-4 w-4 text-blue-600 dark:text-blue-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(totalInvested) }}</div>
            <p class="text-xs text-muted-foreground">En todos tus planes activos</p>
          </CardContent>
        </Card>

        <!-- Ganancia % -->
        <Card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">% Ganancia</CardTitle>
            <Percent class="h-4 w-4 text-green-600 dark:text-green-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">+{{ gainPercent }}%</div>
            <p class="text-xs text-muted-foreground">Retorno en tu inversión</p>
          </CardContent>
        </Card>

        <!-- Revenue Total -->
        <Card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Ingresos</CardTitle>
            <TrendingUp class="h-4 w-4 text-purple-600 dark:text-purple-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(totalRevenue) }}</div>
            <p class="text-xs text-muted-foreground">Ganancias generadas</p>
          </CardContent>
        </Card>

        <!-- Suscripciones -->
        <Card class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Suscripciones</CardTitle>
            <Users class="h-4 w-4 text-orange-600 dark:text-orange-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ activeSubscriptions }}</div>
            <p class="text-xs text-muted-foreground">Planes activos</p>
          </CardContent>
        </Card>

        <!-- Próximo Cierre -->
        <Card class="bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-950 dark:to-rose-900">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Próximo Cierre</CardTitle>
            <Calendar class="h-4 w-4 text-rose-600 dark:text-rose-300" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ closureDays }} días</div>
            <p class="text-xs text-muted-foreground">Hasta el cierre de período</p>
          </CardContent>
        </Card>
      </div>

      <!-- Gráfico y Acciones -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-3">
        <!-- Gráfico de Evolución -->
        <Card class="col-span-2 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border-slate-700">
          <CardHeader>
            <CardTitle class="text-white">Evolución de Inversiones</CardTitle>
            <CardDescription class="text-slate-400">Últimos 6 meses</CardDescription>
          </CardHeader>
          <CardContent class="pt-6 pb-8 px-0">
            <svg viewBox="0 0 500 280" class="w-full h-80" preserveAspectRatio="xMidYMid meet" style="min-width: 100%;">
              <defs>
                <!-- Gradiente multicolor para el área bajo la línea -->
                <linearGradient id="chartAreaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.4" />
                  <stop offset="30%" style="stop-color:#06b6d4;stop-opacity:0.3" />
                  <stop offset="60%" style="stop-color:#8b5cf6;stop-opacity:0.2" />
                  <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0" />
                </linearGradient>
                
                <!-- Gradiente para la línea principal con múltiples colores -->
                <linearGradient id="lineGradientMulti" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" style="stop-color:#3b82f6" />
                  <stop offset="25%" style="stop-color:#06b6d4" />
                  <stop offset="50%" style="stop-color:#10b981" />
                  <stop offset="75%" style="stop-color:#8b5cf6" />
                  <stop offset="100%" style="stop-color:#ec4899" />
                </linearGradient>
                
                <!-- Filtro de brillo y sombra -->
                <filter id="glowShadow" x="-50%" y="-50%" width="200%" height="200%">
                  <feGaussianBlur in="SourceGraphic" stdDeviation="2" />
                  <feDropShadow dx="0" dy="3" stdDeviation="4" flood-color="#3b82f6" flood-opacity="0.4" />
                </filter>
              </defs>
              
              <!-- Fondo oscuro -->
              <rect width="500" height="280" fill="none" />
              
              <!-- Líneas de cuadrícula horizontales sutiles -->
              <line x1="50" y1="60" x2="480" y2="60" stroke="#64748b" stroke-width="0.5" opacity="0.3" />
              <line x1="50" y1="120" x2="480" y2="120" stroke="#64748b" stroke-width="0.5" opacity="0.3" />
              <line x1="50" y1="180" x2="480" y2="180" stroke="#64748b" stroke-width="0.5" opacity="0.3" />
              
              <!-- Área de relleno con gradiente multicolor -->
              <path 
                d="M 60 200 Q 95 175 130 150 T 200 110 T 270 70 T 340 45 T 410 30 L 410 250 L 60 250 Z" 
                fill="url(#chartAreaGradient)" 
                opacity="0.8"
              />
              
              <!-- Línea de brillo trasera (más gruesa y suave) -->
              <path
                d="M 60 200 Q 95 175 130 150 T 200 110 T 270 70 T 340 45 T 410 30"
                fill="none"
                stroke="rgba(59, 130, 246, 0.1)"
                stroke-width="8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              
              <!-- Línea principal con gradiente y efecto de brillo -->
              <path
                d="M 60 200 Q 95 175 130 150 T 200 110 T 270 70 T 340 45 T 410 30"
                fill="none"
                stroke="url(#lineGradientMulti)"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
                filter="url(#glowShadow)"
              />
              
              <!-- Puntos con aura colorida -->
              <g>
                <!-- Punto 1 - Azul -->
                <circle cx="60" cy="200" r="6" fill="rgba(59, 130, 246, 0.2)" />
                <circle cx="60" cy="200" r="4" fill="#3b82f6" />
                <circle cx="60" cy="200" r="3" fill="white" opacity="0.7" />
                
                <!-- Punto 2 - Cian -->
                <circle cx="130" cy="150" r="6" fill="rgba(6, 182, 212, 0.2)" />
                <circle cx="130" cy="150" r="4" fill="#06b6d4" />
                <circle cx="130" cy="150" r="3" fill="white" opacity="0.7" />
                
                <!-- Punto 3 - Verde -->
                <circle cx="200" cy="110" r="6" fill="rgba(16, 185, 129, 0.2)" />
                <circle cx="200" cy="110" r="4" fill="#10b981" />
                <circle cx="200" cy="110" r="3" fill="white" opacity="0.7" />
                
                <!-- Punto 4 - Púrpura -->
                <circle cx="270" cy="70" r="6" fill="rgba(139, 92, 246, 0.2)" />
                <circle cx="270" cy="70" r="4" fill="#8b5cf6" />
                <circle cx="270" cy="70" r="3" fill="white" opacity="0.7" />
                
                <!-- Punto 5 - Rosa -->
                <circle cx="340" cy="45" r="6" fill="rgba(236, 72, 153, 0.2)" />
                <circle cx="340" cy="45" r="4" fill="#ec4899" />
                <circle cx="340" cy="45" r="3" fill="white" opacity="0.7" />
                
                <!-- Punto 6 - Rojo -->
                <circle cx="410" cy="30" r="6" fill="rgba(239, 68, 68, 0.2)" />
                <circle cx="410" cy="30" r="4" fill="#ef4444" />
                <circle cx="410" cy="30" r="3" fill="white" opacity="0.7" />
              </g>
              
              <!-- Ejes -->
              <line x1="50" y1="20" x2="50" y2="250" stroke="#64748b" stroke-width="1.5" opacity="0.6" />
              <line x1="50" y1="250" x2="480" y2="250" stroke="#64748b" stroke-width="1.5" opacity="0.6" />
              
              <!-- Etiquetas del eje X -->
              <text x="60" y="270" text-anchor="middle" class="text-sm font-medium" fill="#94a3b8" font-size="13">Ene</text>
              <text x="130" y="270" text-anchor="middle" class="text-sm font-medium" fill="#94a3b8" font-size="13">Feb</text>
              <text x="200" y="270" text-anchor="middle" class="text-sm font-medium" fill="#94a3b8" font-size="13">Mar</text>
              <text x="270" y="270" text-anchor="middle" class="text-sm font-medium" fill="#94a3b8" font-size="13">Abr</text>
              <text x="340" y="270" text-anchor="middle" class="text-sm font-medium" fill="#94a3b8" font-size="13">May</text>
              <text x="410" y="270" text-anchor="middle" class="text-sm font-medium" fill="#94a3b8" font-size="13">Jun</text>
              
              <!-- Etiquetas del eje Y -->
              <text x="45" y="255" text-anchor="end" dy="0.3em" class="text-xs" fill="#64748b" font-size="11">$0</text>
              <text x="45" y="195" text-anchor="end" dy="0.3em" class="text-xs" fill="#64748b" font-size="11">$1k</text>
              <text x="45" y="135" text-anchor="end" dy="0.3em" class="text-xs" fill="#64748b" font-size="11">$2k</text>
              <text x="45" y="75" text-anchor="end" dy="0.3em" class="text-xs" fill="#64748b" font-size="11">$3k</text>
              <text x="45" y="15" text-anchor="end" dy="0.3em" class="text-xs" fill="#64748b" font-size="11">$3.5k</text>
            </svg>
          </CardContent>
        </Card>        <!-- Acciones Rápidas -->
        <Card>
          <CardHeader>
            <CardTitle>Acciones Rápidas</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <Link :href="route('clients.rewards')">
              <Button variant="outline" class="w-full justify-start">
                <Plus class="h-4 w-4 mr-2" />
                Ver Recompensas
              </Button>
            </Link>
            <Link :href="route('clients.payments.select')">
              <Button variant="outline" class="w-full justify-start">
                <Plus class="h-4 w-4 mr-2" />
                Nueva Inversión
              </Button>
            </Link>
            <Link :href="route('clients.coverage.select')">
              <Button variant="outline" class="w-full justify-start">
                <Plus class="h-4 w-4 mr-2" />
                Nueva Cobertura
              </Button>
            </Link>
            <Link :href="route('clients.profile')">
              <Button variant="outline" class="w-full justify-start">
                <Eye class="h-4 w-4 mr-2" />
                Mi Perfil
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>

      <!-- Suscripciones Recientes -->
      <Card>
        <CardHeader>
          <CardTitle>Suscripciones Activas</CardTitle>
          <CardDescription>{{ subscriptions?.length || 0 }} suscripciones en tu portafolio</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!subscriptions || subscriptions.length === 0" class="text-center py-8">
            <p class="text-muted-foreground">No tienes suscripciones activas</p>
            <Link :href="route('clients.payments.select')">
              <Button class="mt-4">
                <Plus class="h-4 w-4 mr-2" />
                Crear Primera Inversión
              </Button>
            </Link>
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="subscription in subscriptions.slice(0, 5)"
              :key="subscription.id"
              class="flex items-center justify-between p-3 border rounded-lg"
            >
              <div>
                <p class="font-medium">{{ subscription.planType?.name || 'Suscripción' }}</p>
                <p class="text-xs text-muted-foreground">{{ subscription.unique_code }}</p>
              </div>
              <div class="text-right">
                <p class="font-medium">{{ formatDate(subscription.started_at) }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
