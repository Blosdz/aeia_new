<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    CheckCircle2,
    XCircle,
    RefreshCw,
    CreditCard,
    User,
    Building,
    Calendar,
    DollarSign,
    FileText,
    AlertTriangle
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Payment {
    id: number;
    transaction_id: string;
    amount: number;
    currency: string;
    status: string;
    status_label: string;
    is_refunded: boolean;
    metadata: any;
    created_at: string;
    updated_at: string;
}

interface Payer {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    first_name: string;
    last_name: string;
    type_document: string;
    dni: string;
    phone: string;
    country: string;
    verified: number;
}

interface Account {
    id: number;
    bank_name: string;
    card_type: string;
    holder_name: string;
    last4: string;
    exp_month: number;
    exp_year: number;
}

interface Subscription {
    id: number;
    unique_code: string;
    plan_type: {
        id: number;
        name: string;
        category: string;
        amount_min: number;
        amount_max: number;
    } | null;
    started_at: string;
    expires_at: string | null;
}

interface PlanType {
    id: number;
    name: string;
    category: string;
    amount_min: number;
    amount_max: number;
}

const props = defineProps<{
    payment: Payment;
    payer: Payer | null;
    account: Account | null;
    subscription: Subscription | null;
    plan_types: PlanType[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Support', href: route('support.dashboard') },
    { title: 'Validación de Pagos', href: route('support.payment-validation.index') },
    { title: 'Detalles del Pago', href: '#' },
];

// Forms
const approveForm = useForm({
    plan_type_id: props.subscription?.plan_type?.id || '',
    notes: '',
});

const rejectForm = useForm({
    reason: '',
});

const refundForm = useForm({
    reason: '',
    refund_amount: props.payment.amount,
});

// Dialogs
const approveDialogOpen = ref(false);
const rejectDialogOpen = ref(false);
const refundDialogOpen = ref(false);

// Computed
const canApprove = computed(() => props.payment.status === 'pending');
const canReject = computed(() => props.payment.status === 'pending');
const canRefund = computed(() => ['completed', 'pending'].includes(props.payment.status) && !props.payment.is_refunded);

const formatDate = (date: string | null) => {
    if (!date) return 'No disponible';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (amount: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
    }).format(amount);
};

const getStatusBadge = (status: string) => {
    const statusMap = {
        pending: { variant: 'secondary' as const, label: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' },
        completed: { variant: 'default' as const, label: 'Completado', class: 'bg-green-100 text-green-800' },
        failed: { variant: 'destructive' as const, label: 'Fallido', class: 'bg-red-100 text-red-800' },
        refunded: { variant: 'outline' as const, label: 'Reembolsado', class: 'bg-blue-100 text-blue-800' },
    };
    return statusMap[status as keyof typeof statusMap] || {
        variant: 'secondary' as const,
        label: status,
        class: 'bg-gray-100 text-gray-800'
    };
};

const getVerificationBadge = (verified: number) => {
    switch (verified) {
        case 0:
            return { variant: 'secondary' as const, label: 'Pendiente' };
        case 1:
            return { variant: 'default' as const, label: 'Verificado' };
        case 2:
            return { variant: 'destructive' as const, label: 'Rechazado' };
        default:
            return { variant: 'secondary' as const, label: 'Desconocido' };
    }
};

const approvePayment = () => {
    approveForm.post(route('support.payment-validation.approve', props.payment.id), {
        onSuccess: () => {
            approveDialogOpen.value = false;
            approveForm.reset();
        },
    });
};

const rejectPayment = () => {
    rejectForm.post(route('support.payment-validation.reject', props.payment.id), {
        onSuccess: () => {
            rejectDialogOpen.value = false;
            rejectForm.reset();
        },
    });
};

const refundPayment = () => {
    refundForm.post(route('support.payment-validation.refund', props.payment.id), {
        onSuccess: () => {
            refundDialogOpen.value = false;
            refundForm.reset();
        },
    });
};

const availablePlans = computed(() => {
    return props.plan_types.filter(plan =>
        props.payment.amount >= plan.amount_min && props.payment.amount <= plan.amount_max
    );
});
</script>

<template>

    <Head title="Detalles del Pago" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold">{{ payment.transaction_id }}</h1>
                    <p class="text-muted-foreground mt-1">
                        {{ formatCurrency(payment.amount, payment.currency) }}
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <Badge :class="getStatusBadge(payment.status).class">
                            {{ getStatusBadge(payment.status).label }}
                        </Badge>
                        <Badge v-if="payment.is_refunded" variant="outline">
                            Reembolsado
                        </Badge>
                        <Badge v-if="subscription" variant="secondary">
                            Con Suscripción
                        </Badge>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <!-- Aprobar -->
                    <Dialog v-model:open="approveDialogOpen">
                        <DialogTrigger asChild>
                            <Button :disabled="!canApprove" class="gap-2">
                                <CheckCircle2 class="h-4 w-4" />
                                Aprobar
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>Aprobar Pago</DialogTitle>
                                <DialogDescription>
                                    Confirma la aprobación de este pago y asigna un plan de inversión.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4">
                                <div>
                                    <Label for="plan_type">Tipo de Plan *</Label>
                                    <Select v-model="approveForm.plan_type_id" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un plan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="plan in availablePlans" :key="plan.id"
                                                :value="plan.id.toString()">
                                                {{ plan.name }} ({{ plan.category }}) -
                                                {{ formatCurrency(plan.amount_min) }} a {{
                                                formatCurrency(plan.amount_max) }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="approveForm.errors.plan_type_id" class="text-sm text-red-600 mt-1">
                                        {{ approveForm.errors.plan_type_id }}
                                    </div>
                                    <div v-if="availablePlans.length === 0"
                                        class="text-sm text-amber-600 mt-1 flex items-center gap-1">
                                        <AlertTriangle class="h-3 w-3" />
                                        No hay planes compatibles con el monto del pago
                                    </div>
                                </div>
                                <div>
                                    <Label for="notes">Notas (opcional)</Label>
                                    <Textarea id="notes" v-model="approveForm.notes"
                                        placeholder="Comentarios adicionales sobre la aprobación..." />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="approveDialogOpen = false">Cancelar</Button>
                                <Button @click="approvePayment"
                                    :disabled="approveForm.processing || !approveForm.plan_type_id">
                                    <CheckCircle2 class="h-4 w-4 mr-2" />
                                    Aprobar Pago
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <!-- Rechazar -->
                    <Dialog v-model:open="rejectDialogOpen">
                        <DialogTrigger asChild>
                            <Button variant="destructive" :disabled="!canReject" class="gap-2">
                                <XCircle class="h-4 w-4" />
                                Rechazar
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Rechazar Pago</DialogTitle>
                                <DialogDescription>
                                    ¿Estás seguro de que quieres rechazar este pago?
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4">
                                <div>
                                    <Label for="reason">Motivo del rechazo *</Label>
                                    <Textarea id="reason" v-model="rejectForm.reason"
                                        placeholder="Especifica el motivo del rechazo..." required />
                                    <div v-if="rejectForm.errors.reason" class="text-sm text-red-600 mt-1">
                                        {{ rejectForm.errors.reason }}
                                    </div>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="rejectDialogOpen = false">Cancelar</Button>
                                <Button variant="destructive" @click="rejectPayment" :disabled="rejectForm.processing">
                                    <XCircle class="h-4 w-4 mr-2" />
                                    Rechazar Pago
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <!-- Reembolsar -->
                    <Dialog v-model:open="refundDialogOpen">
                        <DialogTrigger asChild>
                            <Button variant="outline" :disabled="!canRefund" class="gap-2">
                                <RefreshCw class="h-4 w-4" />
                                Reembolsar
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Procesar Reembolso</DialogTitle>
                                <DialogDescription>
                                    Procesa un reembolso para este pago. Esta acción es irreversible.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4">
                                <div>
                                    <Label for="refund_amount">Monto a reembolsar</Label>
                                    <Input id="refund_amount" v-model.number="refundForm.refund_amount" type="number"
                                        :max="payment.amount" step="0.01" required />
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Monto máximo: {{ formatCurrency(payment.amount, payment.currency) }}
                                    </div>
                                </div>
                                <div>
                                    <Label for="refund_reason">Motivo del reembolso *</Label>
                                    <Textarea id="refund_reason" v-model="refundForm.reason"
                                        placeholder="Especifica el motivo del reembolso..." required />
                                    <div v-if="refundForm.errors.reason" class="text-sm text-red-600 mt-1">
                                        {{ refundForm.errors.reason }}
                                    </div>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="refundDialogOpen = false">Cancelar</Button>
                                <Button @click="refundPayment" :disabled="refundForm.processing">
                                    <RefreshCw class="h-4 w-4 mr-2" />
                                    Procesar Reembolso
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información Principal -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Detalles del Pago -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5" />
                                Detalles del Pago
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">ID de Transacción</Label>
                                    <p class="font-medium font-mono">{{ payment.transaction_id }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Monto</Label>
                                    <p class="font-medium text-lg">{{ formatCurrency(payment.amount, payment.currency)
                                        }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Estado</Label>
                                    <Badge :class="getStatusBadge(payment.status).class">
                                        {{ getStatusBadge(payment.status).label }}
                                    </Badge>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Moneda</Label>
                                    <p class="font-medium">{{ payment.currency }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Creado</Label>
                                    <p class="font-medium">{{ formatDate(payment.created_at) }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Actualizado</Label>
                                    <p class="font-medium">{{ formatDate(payment.updated_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información del Pagador -->
                    <Card v-if="payer">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Información del Pagador
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Nombre</Label>
                                    <p class="font-medium">{{ payer.first_name }} {{ payer.last_name }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                                    <p class="font-medium">{{ payer.user_email }}</p>
                                </div>
                                <div v-if="payer.dni">
                                    <Label class="text-sm font-medium text-muted-foreground">{{ payer.type_document ||
                                        'Documento' }}</Label>
                                    <p class="font-medium">{{ payer.dni }}</p>
                                </div>
                                <div v-if="payer.phone">
                                    <Label class="text-sm font-medium text-muted-foreground">Teléfono</Label>
                                    <p class="font-medium">{{ payer.phone }}</p>
                                </div>
                                <div v-if="payer.country">
                                    <Label class="text-sm font-medium text-muted-foreground">País</Label>
                                    <p class="font-medium">{{ payer.country }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Estado de
                                        Verificación</Label>
                                    <Badge :variant="getVerificationBadge(payer.verified).variant">
                                        {{ getVerificationBadge(payer.verified).label }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información de la Cuenta -->
                    <Card v-if="account">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Building class="h-5 w-5" />
                                Información de la Cuenta
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Banco</Label>
                                    <p class="font-medium">{{ account.bank_name }}</p>
                                </div>
                                <div v-if="account.card_type">
                                    <Label class="text-sm font-medium text-muted-foreground">Tipo de Tarjeta</Label>
                                    <p class="font-medium">{{ account.card_type }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Titular</Label>
                                    <p class="font-medium">{{ account.holder_name }}</p>
                                </div>
                                <div v-if="account.last4">
                                    <Label class="text-sm font-medium text-muted-foreground">Últimos 4 dígitos</Label>
                                    <p class="font-medium font-mono">****{{ account.last4 }}</p>
                                </div>
                                <div v-if="account.exp_month && account.exp_year">
                                    <Label class="text-sm font-medium text-muted-foreground">Vencimiento</Label>
                                    <p class="font-medium">{{ account.exp_month.toString().padStart(2, '0') }}/{{
                                        account.exp_year }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Suscripción -->
                    <Card v-if="subscription">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Suscripción Asociada
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Código de
                                        Suscripción</Label>
                                    <p class="font-medium font-mono">{{ subscription.unique_code }}</p>
                                </div>
                                <div v-if="subscription.plan_type">
                                    <Label class="text-sm font-medium text-muted-foreground">Plan</Label>
                                    <p class="font-medium">{{ subscription.plan_type.name }} ({{
                                        subscription.plan_type.category }})</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Inicio</Label>
                                    <p class="font-medium">{{ formatDate(subscription.started_at) }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Vencimiento</Label>
                                    <p class="font-medium">{{ formatDate(subscription.expires_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Estado Actual -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Estado Actual</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="text-center">
                                    <div
                                        :class="['inline-flex items-center px-3 py-2 rounded-full text-sm font-medium', getStatusBadge(payment.status).class]">
                                        {{ getStatusBadge(payment.status).label }}
                                    </div>
                                </div>

                                <div v-if="payment.is_refunded" class="text-center">
                                    <Badge variant="outline" class="text-blue-600">
                                        <RefreshCw class="h-3 w-3 mr-1" />
                                        Reembolsado
                                    </Badge>
                                </div>

                                <div v-if="payment.metadata && Object.keys(payment.metadata).length > 0"
                                    class="text-xs text-muted-foreground space-y-1">
                                    <div v-if="payment.metadata.validated_at">
                                        <strong>Validado:</strong> {{ formatDate(payment.metadata.validated_at) }}
                                    </div>
                                    <div v-if="payment.metadata.validated_by">
                                        <strong>Por usuario ID:</strong> {{ payment.metadata.validated_by }}
                                    </div>
                                    <div v-if="payment.metadata.validation_notes">
                                        <strong>Notas:</strong> {{ payment.metadata.validation_notes }}
                                    </div>
                                    <div v-if="payment.metadata.rejection_reason">
                                        <strong>Motivo rechazo:</strong> {{ payment.metadata.rejection_reason }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Acciones Disponibles -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Acciones Disponibles</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span>Aprobar</span>
                                    <Badge :variant="canApprove ? 'default' : 'secondary'">
                                        {{ canApprove ? 'Disponible' : 'No disponible' }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span>Rechazar</span>
                                    <Badge :variant="canReject ? 'destructive' : 'secondary'">
                                        {{ canReject ? 'Disponible' : 'No disponible' }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span>Reembolsar</span>
                                    <Badge :variant="canRefund ? 'outline' : 'secondary'">
                                        {{ canRefund ? 'Disponible' : 'No disponible' }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Planes Compatibles -->
                    <Card v-if="availablePlans.length > 0">
                        <CardHeader>
                            <CardTitle>Planes Compatibles</CardTitle>
                            <CardDescription>
                                Planes que coinciden con el monto del pago
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div v-for="plan in availablePlans" :key="plan.id" class="text-sm border rounded p-2">
                                    <div class="font-medium">{{ plan.name }}</div>
                                    <div class="text-muted-foreground">
                                        {{ plan.category }} •
                                        {{ formatCurrency(plan.amount_min) }} - {{ formatCurrency(plan.amount_max) }}
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