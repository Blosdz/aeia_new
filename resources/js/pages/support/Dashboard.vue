<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Users, CreditCard, CheckCircle, XCircle, Clock, AlertCircle, TrendingUp } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Stats {
    pending_validations: number;
    verified_users: number;
    rejected_users: number;
    pending_payments: number;
    completed_payments: number;
    failed_payments: number;
    total_users_today: number;
    total_payments_today: number;
}

interface PendingProfile {
    id: number;
    user_name: string;
    user_email: string;
    type: string;
    first_name: string;
    last_name: string;
    dni: string;
    created_at: string;
    has_documents: boolean;
}

interface PendingPayment {
    id: number;
    transaction_id: string;
    amount: number;
    currency: string;
    payer_name: string;
    payer_email: string;
    created_at: string;
}

const props = defineProps<{
    stats: Stats;
    pending_profiles: PendingProfile[];
    pending_payments: PendingPayment[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('support.dashboard') },
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
</script>

<template>

    <Head title="Support Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold">Dashboard Support</h1>
                    <p class="text-muted-foreground mt-1">Panel de control para validación de usuarios y pagos</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Validaciones Pendientes -->
                <Card class="border-l-4 border-l-yellow-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Validaciones Pendientes</CardTitle>
                        <Clock class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_validations }}</div>
                        <p class="text-xs text-muted-foreground">Usuarios esperando validación</p>
                    </CardContent>
                </Card>

                <!-- Usuarios Verificados -->
                <Card class="border-l-4 border-l-green-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Usuarios Verificados</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.verified_users }}</div>
                        <p class="text-xs text-muted-foreground">Verificaciones completadas</p>
                    </CardContent>
                </Card>

                <!-- Pagos Pendientes -->
                <Card class="border-l-4 border-l-orange-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pagos Pendientes</CardTitle>
                        <CreditCard class="h-4 w-4 text-orange-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_payments }}</div>
                        <p class="text-xs text-muted-foreground">Pagos esperando validación</p>
                    </CardContent>
                </Card>

                <!-- Actividad Hoy -->
                <Card class="border-l-4 border-l-blue-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Actividad Hoy</CardTitle>
                        <TrendingUp class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_users_today + stats.total_payments_today }}</div>
                        <p class="text-xs text-muted-foreground">Nuevos registros y pagos</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Acciones Rápidas -->
                <Card>
                    <CardHeader>
                        <CardTitle>Acciones Rápidas</CardTitle>
                        <CardDescription>Herramientas principales de soporte</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <Link href="/support/user-validation">
                            <Button class="w-full justify-start gap-2">
                                <Users class="h-4 w-4" />
                                Validar Usuarios
                                <Badge v-if="stats.pending_validations > 0" variant="secondary" class="ml-auto">
                                    {{ stats.pending_validations }}
                                </Badge>
                            </Button>
                        </Link>

                        <Link href="/support/payment-validation">
                            <Button class="w-full justify-start gap-2" variant="outline">
                                <CreditCard class="h-4 w-4" />
                                Validar Pagos
                                <Badge v-if="stats.pending_payments > 0" variant="secondary" class="ml-auto">
                                    {{ stats.pending_payments }}
                                </Badge>
                            </Button>
                        </Link>
                    </CardContent>
                </Card>

                <!-- Resumen de Estados -->
                <Card>
                    <CardHeader>
                        <CardTitle>Resumen de Estados</CardTitle>
                        <CardDescription>Estado general del sistema</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Verificados:</span>
                                    <span class="font-medium text-green-600">{{ stats.verified_users }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Rechazados:</span>
                                    <span class="font-medium text-red-600">{{ stats.rejected_users }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">P. Completados:</span>
                                    <span class="font-medium text-green-600">{{ stats.completed_payments }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">P. Fallidos:</span>
                                    <span class="font-medium text-red-600">{{ stats.failed_payments }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pending Items -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Usuarios Pendientes -->
                <Card>
                    <CardHeader>
                        <div class="flex justify-between items-center">
                            <div>
                                <CardTitle>Usuarios Pendientes</CardTitle>
                                <CardDescription>Últimas validaciones por procesar</CardDescription>
                            </div>
                            <Link href="/support/user-validation">
                                <Button variant="outline" size="sm">Ver todos</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="pending_profiles.length === 0" class="text-center py-8 text-muted-foreground">
                            <Users class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p>No hay usuarios pendientes</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="profile in pending_profiles" :key="profile.id"
                                class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                                <div class="space-y-1">
                                    <div class="font-medium">{{ profile.first_name }} {{ profile.last_name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ profile.user_email }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ profile.type }} • {{ formatDate(profile.created_at) }}
                                    </div>
                                </div>
                                <div class="text-right space-y-1">
                                    <Badge :variant="profile.has_documents ? 'default' : 'secondary'">
                                        {{ profile.has_documents ? 'Con docs' : 'Sin docs' }}
                                    </Badge>
                                    <Link :href="`/support/user-validation/${profile.id}`">
                                        <Button size="sm" variant="outline">Revisar</Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagos Pendientes -->
                <Card>
                    <CardHeader>
                        <div class="flex justify-between items-center">
                            <div>
                                <CardTitle>Pagos Pendientes</CardTitle>
                                <CardDescription>Últimos pagos por validar</CardDescription>
                            </div>
                            <Link href="/support/payment-validation">
                                <Button variant="outline" size="sm">Ver todos</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="pending_payments.length === 0" class="text-center py-8 text-muted-foreground">
                            <CreditCard class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p>No hay pagos pendientes</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="payment in pending_payments" :key="payment.id"
                                class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                                <div class="space-y-1">
                                    <div class="font-medium">{{ payment.payer_name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ payment.transaction_id }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ formatDate(payment.created_at) }}
                                    </div>
                                </div>
                                <div class="text-right space-y-1">
                                    <div class="font-medium">{{ formatCurrency(payment.amount, payment.currency) }}
                                    </div>
                                    <Link :href="`/support/payment-validation/${payment.id}`">
                                        <Button size="sm" variant="outline">Revisar</Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>