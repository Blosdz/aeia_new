<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
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
    RotateCcw,
    User,
    FileText,
    Phone,
    MapPin,
    Calendar,
    Camera,
    Edit,
    Eye
} from 'lucide-vue-next';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    first_name: string;
    last_name: string;
    unique_code: string;
    referral_code: string;
    referred_by_user_id: number | null;
    last_login: string | null;
    created_at: string;
    is_active: boolean;
}

interface Profile {
    id: number;
    type: string;
    first_name: string;
    last_name: string;
    type_document: string;
    dni: string;
    phone_extension: string;
    phone: string;
    nacionality: string;
    city: string;
    country: string;
    country_dni: string;
    state: string;
    job: string;
    birthdate: string;
    sex: string;
    photos_dni: any[];
    photo_id_type: string;
    signature_digital: string;
    verified: number;
    verified_status: string;
    created_at: string;
    updated_at: string;
}

interface Beneficiary {
    id: number;
    first_name: string;
    last_name: string;
    dni: string;
    type_document: string;
    verification_status: string;
    verification_notes: string;
    verified_at: string | null;
}

const props = defineProps<{
    user: User;
    profile: Profile;
    beneficiaries: Beneficiary[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Support', href: route('support.dashboard') },
    { title: 'Validación de Usuarios', href: route('support.user-validation.index') },
    { title: 'Detalles del Usuario', href: '#' },
];

// Forms
const approveForm = useForm({
    notes: '',
});

const rejectForm = useForm({
    reason: '',
});

// Dialogs
const approveDialogOpen = ref(false);
const rejectDialogOpen = ref(false);
const resetDialogOpen = ref(false);

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

const getVerificationBadge = (verified: number) => {
    switch (verified) {
        case 0:
            return { variant: 'secondary' as const, label: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' };
        case 1:
            return { variant: 'default' as const, label: 'Verificado', class: 'bg-green-100 text-green-800' };
        case 2:
            return { variant: 'destructive' as const, label: 'Rechazado', class: 'bg-red-100 text-red-800' };
        default:
            return { variant: 'secondary' as const, label: 'Desconocido', class: 'bg-gray-100 text-gray-800' };
    }
};

const approveUser = () => {
    approveForm.post(route('support.user-validation.approve', props.profile.id), {
        onSuccess: () => {
            approveDialogOpen.value = false;
            approveForm.reset();
        },
    });
};

const rejectUser = () => {
    rejectForm.post(route('support.user-validation.reject', props.profile.id), {
        onSuccess: () => {
            rejectDialogOpen.value = false;
            rejectForm.reset();
        },
    });
};

const resetVerification = () => {
    router.post(route('support.user-validation.reset', props.profile.id), {}, {
        onSuccess: () => {
            resetDialogOpen.value = false;
        },
    });
};

const viewDocument = (document: any) => {
    if (document && document.url) {
        window.open(document.url, '_blank');
    }
};
</script>

<template>

    <Head title="Detalles del Usuario" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold">{{ profile.first_name }} {{ profile.last_name }}</h1>
                    <p class="text-muted-foreground mt-1">{{ user.email }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <Badge :variant="getVerificationBadge(profile.verified).variant">
                            {{ getVerificationBadge(profile.verified).label }}
                        </Badge>
                        <Badge variant="outline">{{ profile.type }}</Badge>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <Dialog v-model:open="approveDialogOpen">
                        <DialogTrigger asChild>
                            <Button :disabled="profile.verified === 1" class="gap-2">
                                <CheckCircle2 class="h-4 w-4" />
                                Aprobar
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Aprobar Usuario</DialogTitle>
                                <DialogDescription>
                                    ¿Estás seguro de que quieres aprobar la verificación de este usuario?
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4">
                                <div>
                                    <Label for="notes">Notas (opcional)</Label>
                                    <Textarea id="notes" v-model="approveForm.notes"
                                        placeholder="Comentarios adicionales sobre la aprobación..." />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="approveDialogOpen = false">Cancelar</Button>
                                <Button @click="approveUser" :disabled="approveForm.processing">
                                    <CheckCircle2 class="h-4 w-4 mr-2" />
                                    Aprobar Usuario
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="rejectDialogOpen">
                        <DialogTrigger asChild>
                            <Button variant="destructive" :disabled="profile.verified === 2" class="gap-2">
                                <XCircle class="h-4 w-4" />
                                Rechazar
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Rechazar Usuario</DialogTitle>
                                <DialogDescription>
                                    ¿Estás seguro de que quieres rechazar la verificación de este usuario?
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
                                <Button variant="destructive" @click="rejectUser" :disabled="rejectForm.processing">
                                    <XCircle class="h-4 w-4 mr-2" />
                                    Rechazar Usuario
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="resetDialogOpen" v-if="profile.verified !== 0">
                        <DialogTrigger asChild>
                            <Button variant="outline" class="gap-2">
                                <RotateCcw class="h-4 w-4" />
                                Resetear
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Resetear Verificación</DialogTitle>
                                <DialogDescription>
                                    Esto volverá el estado de verificación a "Pendiente". ¿Continuar?
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" @click="resetDialogOpen = false">Cancelar</Button>
                                <Button @click="resetVerification">
                                    <RotateCcw class="h-4 w-4 mr-2" />
                                    Resetear Estado
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información del Usuario -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Datos Personales -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Datos Personales
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Nombre</Label>
                                    <p class="font-medium">{{ profile.first_name }} {{ profile.last_name }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                                    <p class="font-medium">{{ user.email }}</p>
                                </div>
                                <div v-if="profile.dni">
                                    <Label class="text-sm font-medium text-muted-foreground">{{ profile.type_document ||
                                        'Documento' }}</Label>
                                    <p class="font-medium">{{ profile.dni }}</p>
                                </div>
                                <div v-if="profile.birthdate">
                                    <Label class="text-sm font-medium text-muted-foreground">Fecha de Nacimiento</Label>
                                    <p class="font-medium">{{ formatDate(profile.birthdate) }}</p>
                                </div>
                                <div v-if="profile.sex">
                                    <Label class="text-sm font-medium text-muted-foreground">Género</Label>
                                    <p class="font-medium">{{ profile.sex }}</p>
                                </div>
                                <div v-if="profile.job">
                                    <Label class="text-sm font-medium text-muted-foreground">Trabajo</Label>
                                    <p class="font-medium">{{ profile.job }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información de Contacto -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Phone class="h-5 w-5" />
                                Información de Contacto
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="profile.phone">
                                    <Label class="text-sm font-medium text-muted-foreground">Teléfono</Label>
                                    <p class="font-medium">{{ profile.phone_extension }} {{ profile.phone }}</p>
                                </div>
                                <div v-if="profile.country">
                                    <Label class="text-sm font-medium text-muted-foreground">País</Label>
                                    <p class="font-medium">{{ profile.country }}</p>
                                </div>
                                <div v-if="profile.city">
                                    <Label class="text-sm font-medium text-muted-foreground">Ciudad</Label>
                                    <p class="font-medium">{{ profile.city }}</p>
                                </div>
                                <div v-if="profile.state">
                                    <Label class="text-sm font-medium text-muted-foreground">Estado/Provincia</Label>
                                    <p class="font-medium">{{ profile.state }}</p>
                                </div>
                                <div v-if="profile.nacionality">
                                    <Label class="text-sm font-medium text-muted-foreground">Nacionalidad</Label>
                                    <p class="font-medium">{{ profile.nacionality }}</p>
                                </div>
                                <div v-if="profile.country_dni">
                                    <Label class="text-sm font-medium text-muted-foreground">País del Documento</Label>
                                    <p class="font-medium">{{ profile.country_dni }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Documentos -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Documentos
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-if="profile.photos_dni && profile.photos_dni.length > 0">
                                    <Label class="text-sm font-medium text-muted-foreground">Fotos del DNI</Label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                                        <div v-for="(photo, index) in profile.photos_dni" :key="index"
                                            class="border rounded-lg p-2 hover:bg-muted/50 cursor-pointer"
                                            @click="viewDocument(photo)">
                                            <div class="flex items-center gap-2">
                                                <Camera class="h-4 w-4" />
                                                <span class="text-sm">Documento {{ index + 1 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="profile.signature_digital">
                                    <Label class="text-sm font-medium text-muted-foreground">Firma Digital</Label>
                                    <div class="border rounded-lg p-2 hover:bg-muted/50 cursor-pointer mt-2 inline-block"
                                        @click="viewDocument({ url: profile.signature_digital })">
                                        <div class="flex items-center gap-2">
                                            <Edit class="h-4 w-4" />
                                            <span class="text-sm">Ver Firma</span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="(!profile.photos_dni || profile.photos_dni.length === 0) && !profile.signature_digital">
                                    <p class="text-muted-foreground">No hay documentos subidos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Beneficiarios -->
                    <Card v-if="beneficiaries.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Beneficiarios
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-for="beneficiary in beneficiaries" :key="beneficiary.id"
                                    class="border rounded-lg p-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium">{{ beneficiary.first_name }} {{
                                                beneficiary.last_name }}</h4>
                                            <p class="text-sm text-muted-foreground">{{ beneficiary.type_document }}: {{
                                                beneficiary.dni }}</p>
                                        </div>
                                        <Badge
                                            :variant="beneficiary.verification_status === 'verified' ? 'default' : 'secondary'">
                                            {{ beneficiary.verification_status }}
                                        </Badge>
                                    </div>
                                    <div v-if="beneficiary.verification_notes"
                                        class="mt-2 text-sm text-muted-foreground">
                                        <strong>Notas:</strong> {{ beneficiary.verification_notes }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Estado de Verificación -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Estado de Verificación</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="text-center">
                                    <div
                                        :class="['inline-flex items-center px-3 py-2 rounded-full text-sm font-medium', getVerificationBadge(profile.verified).class]">
                                        {{ getVerificationBadge(profile.verified).label }}
                                    </div>
                                </div>

                                <div class="text-xs text-muted-foreground space-y-1">
                                    <div><strong>Creado:</strong> {{ formatDate(profile.created_at) }}</div>
                                    <div><strong>Actualizado:</strong> {{ formatDate(profile.updated_at) }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información de la Cuenta -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de la Cuenta</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Usuario</Label>
                                    <p class="font-medium">{{ user.name }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Código Único</Label>
                                    <p class="font-medium font-mono">{{ user.unique_code || 'No disponible' }}</p>
                                </div>
                                <div v-if="user.referral_code">
                                    <Label class="text-sm font-medium text-muted-foreground">Código de Referido</Label>
                                    <p class="font-medium font-mono">{{ user.referral_code }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Último Login</Label>
                                    <p class="font-medium">{{ formatDate(user.last_login) }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Estado</Label>
                                    <Badge :variant="user.is_active ? 'default' : 'secondary'">
                                        {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>