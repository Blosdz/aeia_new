<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    CreditCard,
    Search,
    Filter,
    CheckCircle2,
    XCircle,
    Clock,
    DollarSign,
    Calendar,
    RefreshCw
} from 'lucide-vue-next';
import { ref, reactive } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Payment {
    id: number;
    transaction_id: string;
    amount: number;
    currency: string;
    status: string;
    status_label: string;
    payer_name: string;
    payer_email: string;
    payer_dni: string;
    bank_name: string;
    holder_name: string;
    is_refunded: boolean;
    has_subscription: boolean;
    metadata: any;
    created_at: string;
    updated_at: string;
}

interface PaginatedPayments {
    data: Payment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const props = defineProps<{
    payments: PaginatedPayments;
    summary: {
        pending_count: number;
        pending_amount: number;
        completed_today: number;
        failed_today: number;
    };
    filters: {
        search: string;
        status: string;
        date_from: string;
        date_to: string;
    };
    statusOptions: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('support.dashboard') },
    { title: 'Validación de Pagos', href: route('support.payment-validation.index') },
];

// Form filters
const form = reactive({
    search: props.filters.search,
    status: props.filters.status || 'all',
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const searchPayments = () => {
    router.get(route('support.payment-validation.index'), {
        search: form.search,
        status: form.status === 'all' ? '' : form.status,
        date_from: form.date_from,
        date_to: form.date_to,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.status = 'all';
    form.date_from = '';
    form.date_to = '';
    searchPayments();
};

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
        pending: { variant: 'secondary' as const, label: 'Pendiente', icon: Clock, class: 'bg-yellow-100 text-yellow-800' },
        completed: { variant: 'default' as const, label: 'Completado', icon: CheckCircle2, class: 'bg-green-100 text-green-800' },
        failed: { variant: 'destructive' as const, label: 'Fallido', icon: XCircle, class: 'bg-red-100 text-red-800' },
        refunded: { variant: 'outline' as const, label: 'Reembolsado', icon: RefreshCw, class: 'bg-blue-100 text-blue-800' },
    };
    return statusMap[status as keyof typeof statusMap] || {
        variant: 'secondary' as const,
        label: status,
        icon: Clock,
        class: 'bg-gray-100 text-gray-800'
    };
};
</script>

<template>

    <Head title="Validación de Pagos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold">Validación de Pagos</h1>
                    <p class="text-muted-foreground mt-1">
                        Procesa y valida pagos pendientes de los usuarios
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="secondary">
                        {{ payments.total }} pago{{ payments.total !== 1 ? 's' : '' }}
                    </Badge>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card class="border-l-4 border-l-yellow-500">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <Clock class="h-4 w-4 text-yellow-500" />
                            Pendientes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary.pending_count }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatCurrency(summary.pending_amount) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-green-500">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <CheckCircle2 class="h-4 w-4 text-green-500" />
                            Completados Hoy
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary.completed_today }}</div>
                        <p class="text-xs text-muted-foreground">Pagos aprobados</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-red-500">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <XCircle class="h-4 w-4 text-red-500" />
                            Fallidos Hoy
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary.failed_today }}</div>
                        <p class="text-xs text-muted-foreground">Pagos rechazados</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-blue-500">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <DollarSign class="h-4 w-4 text-blue-500" />
                            Total
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ payments.total }}</div>
                        <p class="text-xs text-muted-foreground">Todos los pagos</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filtros de Búsqueda
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Búsqueda -->
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input id="search" v-model="form.search" placeholder="ID, email, nombre..."
                                    class="pl-10" @keyup.enter="searchPayments" />
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="space-y-2">
                            <Label for="status">Estado</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem v-for="(label, value) in statusOptions" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Fecha Desde -->
                        <div class="space-y-2">
                            <Label for="date_from">Desde</Label>
                            <Input id="date_from" v-model="form.date_from" type="date" />
                        </div>

                        <!-- Fecha Hasta -->
                        <div class="space-y-2">
                            <Label for="date_to">Hasta</Label>
                            <Input id="date_to" v-model="form.date_to" type="date" />
                        </div>

                        <!-- Acciones -->
                        <div class="space-y-2">
                            <Label>&nbsp;</Label>
                            <div class="flex gap-2">
                                <Button @click="searchPayments" class="flex-1">
                                    <Search class="h-4 w-4 mr-2" />
                                    Buscar
                                </Button>
                                <Button variant="outline" @click="clearFilters">
                                    Limpiar
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Lista de Pagos -->
            <Card>
                <CardHeader>
                    <div class="flex justify-between items-center">
                        <CardTitle class="flex items-center gap-2">
                            <CreditCard class="h-5 w-5" />
                            Lista de Pagos
                        </CardTitle>
                        <div v-if="payments.total > 0" class="text-sm text-muted-foreground">
                            Mostrando {{ payments.from }} - {{ payments.to }} de {{ payments.total }} resultados
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="payments.data.length === 0" class="text-center py-12">
                        <CreditCard class="h-12 w-12 mx-auto mb-4 text-muted-foreground opacity-50" />
                        <h3 class="text-lg font-semibold mb-2">No hay pagos</h3>
                        <p class="text-muted-foreground">No se encontraron pagos con los filtros aplicados.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="payment in payments.data" :key="payment.id"
                            class="border rounded-lg p-4 hover:bg-muted/50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 space-y-2">
                                    <!-- Información Principal -->
                                    <div class="flex items-start gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="font-semibold">{{ payment.payer_name }}</h3>
                                                <Badge :class="getStatusBadge(payment.status).class">
                                                    <component :is="getStatusBadge(payment.status).icon"
                                                        class="h-3 w-3 mr-1" />
                                                    {{ getStatusBadge(payment.status).label }}
                                                </Badge>
                                                <Badge v-if="payment.is_refunded" variant="outline">
                                                    Reembolsado
                                                </Badge>
                                                <Badge v-if="payment.has_subscription" variant="secondary">
                                                    Con Suscripción
                                                </Badge>
                                            </div>
                                            <div class="text-sm text-muted-foreground space-y-1">
                                                <div class="font-mono">{{ payment.transaction_id }}</div>
                                                <div>{{ payment.payer_email }}</div>
                                                <div v-if="payment.payer_dni">DNI: {{ payment.payer_dni }}</div>
                                            </div>
                                        </div>

                                        <!-- Información del Pago -->
                                        <div class="text-right space-y-2">
                                            <div class="text-2xl font-bold">
                                                {{ formatCurrency(payment.amount, payment.currency) }}
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                <div v-if="payment.bank_name">{{ payment.bank_name }}</div>
                                                <div v-if="payment.holder_name">{{ payment.holder_name }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Información Adicional -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs text-muted-foreground">
                                        <div>
                                            <strong>Creado:</strong> {{ formatDate(payment.created_at) }}
                                        </div>
                                        <div>
                                            <strong>Actualizado:</strong> {{ formatDate(payment.updated_at) }}
                                        </div>
                                        <div v-if="payment.metadata && payment.metadata.validated_at">
                                            <strong>Validado:</strong> {{ formatDate(payment.metadata.validated_at) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="ml-4">
                                    <Link :href="route('support.payment-validation.show', payment.id)">
                                        <Button size="sm">
                                            Ver Detalles
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="payments.last_page > 1" class="flex justify-center mt-6">
                        <div class="flex gap-1">
                            <template v-for="link in payments.links" :key="link.label">
                                <Button v-if="link.url" variant="outline" size="sm" :class="{
                                    'bg-primary text-primary-foreground': link.active,
                                }" @click="router.visit(link.url)" v-html="link.label" />
                                <Button v-else variant="outline" size="sm" disabled v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>