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
    Users,
    Search,
    Filter,
    CheckCircle2,
    XCircle,
    Clock,
    FileText,
    User
} from 'lucide-vue-next';
import { ref, reactive } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Profile {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    user_created_at: string;
    type: string;
    first_name: string;
    last_name: string;
    dni: string;
    type_document: string;
    phone: string;
    phone_extension: string;
    nacionality: string;
    city: string;
    country: string;
    country_dni: string;
    birthdate: string;
    sex: string;
    verified: number;
    verified_status: string;
    has_documents: boolean;
    has_signature: boolean;
    created_at: string;
    updated_at: string;
}

interface PaginatedProfiles {
    data: Profile[];
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
    profiles: PaginatedProfiles;
    filters: {
        search: string;
        status: string;
        type: string;
    };
    statusOptions: Record<string, string>;
    typeOptions: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('support.dashboard') },
    { title: 'Validación de Usuarios', href: route('support.user-validation.index') },
];

// Form filters
const form = reactive({
    search: props.filters.search,
    status: props.filters.status || 'all',
    type: props.filters.type || 'all',
});

const searchProfiles = () => {
    router.get(route('support.user-validation.index'), {
        search: form.search,
        status: form.status === 'all' ? '' : form.status,
        type: form.type === 'all' ? '' : form.type,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.status = 'all';
    form.type = 'all';
    searchProfiles();
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

const getVerificationBadge = (verified: number) => {
    switch (verified) {
        case 0:
            return { variant: 'secondary' as const, label: 'Pendiente', icon: Clock };
        case 1:
            return { variant: 'default' as const, label: 'Verificado', icon: CheckCircle2 };
        case 2:
            return { variant: 'destructive' as const, label: 'Rechazado', icon: XCircle };
        default:
            return { variant: 'secondary' as const, label: 'Desconocido', icon: Clock };
    }
};

const getTypeBadge = (type: string) => {
    const typeMap = {
        user: { variant: 'default' as const, label: 'Usuario' },
        boss: { variant: 'secondary' as const, label: 'Jefe' },
        staff: { variant: 'outline' as const, label: 'Staff' },
    };
    return typeMap[type as keyof typeof typeMap] || { variant: 'secondary' as const, label: type };
};
</script>

<template>

    <Head title="Validación de Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold">Validación de Usuarios</h1>
                    <p class="text-muted-foreground mt-1">
                        Gestiona la verificación KYC/AML de usuarios registrados
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="secondary">
                        {{ profiles.total }} usuario{{ profiles.total !== 1 ? 's' : '' }}
                    </Badge>
                </div>
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
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Búsqueda -->
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input id="search" v-model="form.search" placeholder="Nombre, email, DNI..."
                                    class="pl-10" @keyup.enter="searchProfiles" />
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

                        <!-- Tipo -->
                        <div class="space-y-2">
                            <Label for="type">Tipo</Label>
                            <Select v-model="form.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem v-for="type in typeOptions" :key="type" :value="type">
                                        {{ getTypeBadge(type).label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Acciones -->
                        <div class="space-y-2">
                            <Label>&nbsp;</Label>
                            <div class="flex gap-2">
                                <Button @click="searchProfiles" class="flex-1">
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

            <!-- Lista de Usuarios -->
            <Card>
                <CardHeader>
                    <div class="flex justify-between items-center">
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Lista de Usuarios
                        </CardTitle>
                        <div v-if="profiles.total > 0" class="text-sm text-muted-foreground">
                            Mostrando {{ profiles.from }} - {{ profiles.to }} de {{ profiles.total }} resultados
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="profiles.data.length === 0" class="text-center py-12">
                        <Users class="h-12 w-12 mx-auto mb-4 text-muted-foreground opacity-50" />
                        <h3 class="text-lg font-semibold mb-2">No hay usuarios</h3>
                        <p class="text-muted-foreground">No se encontraron usuarios con los filtros aplicados.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="profile in profiles.data" :key="profile.id"
                            class="border rounded-lg p-4 hover:bg-muted/50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 space-y-2">
                                    <!-- Información Principal -->
                                    <div class="flex items-start gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="font-semibold">
                                                    {{ profile.first_name }} {{ profile.last_name }}
                                                </h3>
                                                <Badge :variant="getTypeBadge(profile.type).variant">
                                                    {{ getTypeBadge(profile.type).label }}
                                                </Badge>
                                            </div>
                                            <div class="text-sm text-muted-foreground space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <User class="h-3 w-3" />
                                                    {{ profile.user_name }} ({{ profile.user_email }})
                                                </div>
                                                <div v-if="profile.dni" class="flex items-center gap-2">
                                                    <FileText class="h-3 w-3" />
                                                    {{ profile.type_document }}: {{ profile.dni }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Estados y Badges -->
                                        <div class="text-right space-y-2">
                                            <div class="flex flex-col gap-1">
                                                <Badge :variant="getVerificationBadge(profile.verified).variant">
                                                    <component :is="getVerificationBadge(profile.verified).icon"
                                                        class="h-3 w-3 mr-1" />
                                                    {{ getVerificationBadge(profile.verified).label }}
                                                </Badge>
                                                <div class="flex gap-1">
                                                    <Badge v-if="profile.has_documents" variant="outline"
                                                        class="text-xs">
                                                        Docs
                                                    </Badge>
                                                    <Badge v-if="profile.has_signature" variant="outline"
                                                        class="text-xs">
                                                        Firma
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Información Adicional -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs text-muted-foreground">
                                        <div v-if="profile.phone">
                                            <strong>Teléfono:</strong> {{ profile.phone_extension }} {{ profile.phone }}
                                        </div>
                                        <div v-if="profile.country">
                                            <strong>País:</strong> {{ profile.country }}
                                        </div>
                                        <div>
                                            <strong>Creado:</strong> {{ formatDate(profile.created_at) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="ml-4">
                                    <Link :href="route('support.user-validation.show', profile.id)">
                                        <Button size="sm">
                                            Ver Detalles
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="profiles.last_page > 1" class="flex justify-center mt-6">
                        <div class="flex gap-1">
                            <template v-for="link in profiles.links" :key="link.label">
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