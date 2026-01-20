<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { ArrowLeft, CheckCircle, Clock, XCircle, FileText, CreditCard, DollarSign } from 'lucide-vue-next';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface ClientData {
  id: number;
  name: string;
  email: string;
  unique_code: string;
  is_active: boolean;
  roles: string[];
  last_login: string | null;
  created_at: string;
}

interface ProfileData {
  id: number;
  first_name: string | null;
  last_name: string | null;
  type: string | null;
  type_document: string | null;
  dni: string | null;
  phone: string | null;
  phone_extension: string | null;
  nacionality: string | null;
  city: string | null;
  country: string | null;
  job: string | null;
  country_dni: string | null;
  state: string | null;
  birthdate: string | null;
  sex: string | null;
  photos_dni: string[] | null;
  photo_id_type: string | null;
  signature_digital: string | null;
  verified: boolean;
  bio: string | null;
  verification_status: 'pending' | 'verified' | 'rejected';
  verified_at: string | null;
  verified_by: number | null;
  verification_notes: string | null;
}

interface Document {
  id: number;
  document_type: string;
  file_path: string;
  created_at: string;
}

interface CreditCard {
  id: number;
  bank_name: string;
  holder_name: string;
  last_four: string;
  is_default: boolean;
}

interface Payment {
  id: number;
  transaction_id: string;
  amount: number;
  currency: string;
  status: string;
  plan: string;
  membership_charge: number;
  total_paid: number;
  created_at: string;
}

const props = defineProps<{
  client: ClientData;
  profile: ProfileData | null;
  documents: Document[];
  credit_cards: CreditCard[];
  payments: Payment[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: route('admin.dashboard') },
  { title: 'Clientes', href: route('admin.clients.index') },
  { title: props.client.name, href: '#' },
];

const showVerifyForm = ref(false);
const showRejectForm = ref(false);
const verifyNotes = ref('');
const rejectNotes = ref('');

const verifyForm = useForm({
  verification_notes: '',
});

const rejectForm = useForm({
  verification_notes: '',
});

const handleVerify = () => {
  verifyForm.patch(route('admin.clients.verify', props.client.id), {
    preserveScroll: true,
    onSuccess: () => {
      showVerifyForm.value = false;
      verifyNotes.value = '';
    },
  });
};

const handleReject = () => {
  rejectForm.patch(route('admin.clients.reject', props.client.id), {
    preserveScroll: true,
    onSuccess: () => {
      showRejectForm.value = false;
      rejectNotes.value = '';
    },
  });
};

const handleReset = () => {
  useForm({}).patch(route('admin.clients.reset_verification', props.client.id), {
    preserveScroll: true,
  });
};

const formatCurrency = (amount: number, currency: string = 'USD') => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
  }).format(amount);
};

const formatFileSize = (bytes: number | null) => {
  if (!bytes) return '-';
  const units = ['B', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex++;
  }
  
  return `${size.toFixed(2)} ${units[unitIndex]}`;
};

const formatDate = (date: string | null) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getStatusIcon = (status: string) => {
  const icons: Record<string, any> = {
    pending: Clock,
    verified: CheckCircle,
    rejected: XCircle,
  };
  return icons[status];
};

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'text-yellow-600 dark:text-yellow-400',
    verified: 'text-green-600 dark:text-green-400',
    rejected: 'text-red-600 dark:text-red-400',
  };
  return colors[status];
};
</script>

<template>
  <Head :title="`Cliente - ${client.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/admin/clients">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold">{{ client.name }}</h1>
          <p class="text-muted-foreground mt-1">{{ client.email }}</p>
        </div>
        <Badge v-if="profile" :variant="profile.verification_status === 'verified' ? 'default' : 'secondary'">
          {{ profile.verification_status === 'verified' ? 'Verificado' : profile.verification_status === 'rejected' ? 'Rechazado' : 'Pendiente' }}
        </Badge>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información de Validación (lado izquierdo, 2/3) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- 🔍 INFORMACIÓN PARA VALIDAR -->
          <Card class="border-2 border-blue-400 dark:border-blue-600 bg-blue-50 dark:bg-blue-950">
            <CardHeader>
              <CardTitle class="text-lg flex items-center gap-2 text-blue-900 dark:text-blue-100">
                <FileText class="h-5 w-5" />
                INFORMACIÓN DEL USUARIO PARA VALIDAR
              </CardTitle>
              <CardDescription class="text-blue-700 dark:text-blue-300">
                Estos son los datos que el usuario registró en el sistema
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Fila 1: Nombres -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pb-4 border-b">
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Nombre</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.first_name || 'NO REGISTRADO' }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Apellido</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.last_name || 'NO REGISTRADO' }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Email</p>
                  <p class="text-sm font-bold mt-1 break-all">{{ client.email }}</p>
                </div>
              </div>

              <!-- Fila 2: Documento -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pb-4 border-b">
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Tipo Documento</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.type_document || 'NO REGISTRADO' }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Número Documento</p>
                  <p class="text-sm font-mono font-bold mt-1">{{ profile?.dni || 'NO REGISTRADO' }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">País Documento</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.country_dni || 'NO REGISTRADO' }}</p>
                </div>
              </div>

              <!-- Fila 3: Contacto -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pb-4 border-b">
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Teléfono</p>
                  <p class="text-sm font-bold mt-1">
                    {{ (profile?.phone_extension || '') + ' ' + (profile?.phone || 'NO REGISTRADO') }}
                  </p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Ciudad</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.city || 'NO REGISTRADO' }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Estado/Provincia</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.state || 'NO REGISTRADO' }}</p>
                </div>
              </div>

              <!-- Fila 3.5: País -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pb-4 border-b">
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">País</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.country || 'NO REGISTRADO' }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Nacionalidad</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.nacionality || 'NO REGISTRADO' }}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Fecha Nacimiento</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.birthdate ? formatDate(profile.birthdate) : 'NO REGISTRADO' }}</p>
                </div>
              </div>

              <!-- Fila 4: Datos adicionales -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Género</p>
                  <p class="text-sm font-bold mt-1">
                    {{
                      profile?.sex === 'M'
                        ? 'Masculino'
                        : profile?.sex === 'F'
                        ? 'Femenino'
                        : profile?.sex === 'O'
                        ? 'Otro'
                        : 'NO REGISTRADO'
                    }}
                  </p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-950 rounded border border-blue-200 dark:border-blue-800">
                  <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Ocupación</p>
                  <p class="text-sm font-bold mt-1">{{ profile?.job || 'NO REGISTRADO' }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- 📸 DOCUMENTOS ENVIADOS -->
          <Card v-if="documents.length > 0">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <FileText class="h-5 w-5" />
                Documentos Adjuntos
              </CardTitle>
              <CardDescription>Archivos cargados por el usuario</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <div v-for="doc in documents" :key="doc.id" class="flex items-center justify-between p-3 border rounded hover:bg-gray-50 dark:hover:bg-gray-900">
                  <div class="flex-1">
                    <p class="font-semibold text-sm">{{ doc.name || 'Documento' }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ doc.file_type }} • {{ formatFileSize(doc.file_size) }} • {{ formatDate(doc.created_at) }}
                    </p>
                  </div>
                  <a :href="`/storage/${doc.file_path}`" target="_blank" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                    Ver
                  </a>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- 📷 FOTOS DE DOCUMENTO -->
          <Card v-if="profile?.photos_dni && profile.photos_dni.length > 0">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <FileText class="h-5 w-5" />
                Fotografías del Documento
              </CardTitle>
              <CardDescription>Imágenes enviadas por el usuario</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="profile.photos_dni[0]" class="border rounded-lg overflow-hidden">
                  <img :src="profile.photos_dni[0]" alt="DNI Frente" class="w-full h-64 object-cover" />
                  <p class="p-2 text-xs text-muted-foreground bg-gray-50 dark:bg-gray-900 font-semibold">Frente del Documento</p>
                </div>
                <div v-if="profile.photos_dni[1]" class="border rounded-lg overflow-hidden">
                  <img :src="profile.photos_dni[1]" alt="DNI Reverso" class="w-full h-64 object-cover" />
                  <p class="p-2 text-xs text-muted-foreground bg-gray-50 dark:bg-gray-900 font-semibold">Reverso del Documento</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- 💳 TARJETAS DE CRÉDITO (SIN CVV) -->
          <Card v-if="credit_cards.length > 0">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <CreditCard class="h-5 w-5" />
                Tarjetas de Crédito Registradas
              </CardTitle>
              <CardDescription>Sin información de CVV (por seguridad)</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div v-for="card in credit_cards" :key="card.id" class="p-4 border rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <p class="font-semibold">{{ card.bank_name }}</p>
                      <p class="text-xs text-muted-foreground">{{ card.holder_name }}</p>
                    </div>
                    <span v-if="card.is_default" class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">Por defecto</span>
                  </div>
                  <p class="text-sm font-mono">•••• {{ card.last_four }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- 💰 HISTORIAL DE PAGOS -->
          <Card v-if="payments.length > 0">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <DollarSign class="h-5 w-5" />
                Historial de Pagos/Inversiones
              </CardTitle>
              <CardDescription>Últimas transacciones</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="border-b">
                    <tr>
                      <th class="text-left py-2 px-2 font-semibold">Plan</th>
                      <th class="text-left py-2 px-2 font-semibold">Inversión</th>
                      <th class="text-left py-2 px-2 font-semibold">Membresía</th>
                      <th class="text-left py-2 px-2 font-semibold">Total</th>
                      <th class="text-left py-2 px-2 font-semibold">Estado</th>
                      <th class="text-left py-2 px-2 font-semibold">Fecha</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="payment in payments" :key="payment.id" class="border-b">
                      <td class="py-2 px-2">{{ payment.plan }}</td>
                      <td class="py-2 px-2">{{ formatCurrency(payment.amount, payment.currency) }}</td>
                      <td class="py-2 px-2">
                        {{ payment.membership_charge > 0 ? formatCurrency(payment.membership_charge, payment.currency) : '-' }}
                      </td>
                      <td class="py-2 px-2 font-semibold">{{ formatCurrency(payment.total_paid, payment.currency) }}</td>
                      <td class="py-2 px-2">
                        <Badge :variant="payment.status === 'completed' ? 'default' : 'secondary'">
                          {{ payment.status }}
                        </Badge>
                      </td>
                      <td class="py-2 px-2 text-xs text-muted-foreground">{{ formatDate(payment.created_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Panel de verificación (columna derecha) -->
        <div class="space-y-4">
          <!-- Estado de verificación -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <component :is="getStatusIcon(profile?.verification_status || 'pending')" :class="`h-5 w-5 ${getStatusColor(profile?.verification_status || 'pending')}`" />
                Estado de Verificación
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <p class="text-xs font-semibold text-muted-foreground mb-1">Estado Actual</p>
                <Badge 
                  :variant="profile?.verification_status === 'verified' ? 'default' : profile?.verification_status === 'rejected' ? 'destructive' : 'secondary'"
                  class="w-full justify-center"
                >
                  {{
                    profile?.verification_status === 'verified'
                      ? 'VERIFICADO'
                      : profile?.verification_status === 'rejected'
                      ? 'RECHAZADO'
                      : 'PENDIENTE'
                  }}
                </Badge>
              </div>

              <div v-if="profile?.verified_at">
                <p class="text-xs font-semibold text-muted-foreground mb-1">Verificado el</p>
                <p class="text-sm">{{ formatDate(profile.verified_at) }}</p>
              </div>

              <div v-if="profile?.verification_notes">
                <p class="text-xs font-semibold text-muted-foreground mb-1">Notas</p>
                <p class="text-sm p-2 bg-gray-50 dark:bg-gray-900 rounded">{{ profile.verification_notes }}</p>
              </div>

              <!-- Acciones de verificación -->
              <div class="space-y-2 pt-4 border-t">
                <!-- Verificar -->
                <div v-if="profile?.verification_status !== 'verified'">
                  <Button
                    v-if="!showVerifyForm"
                    @click="showVerifyForm = true"
                    class="w-full gap-2 bg-green-600 hover:bg-green-700"
                  >
                    <CheckCircle class="h-5 w-5" />
                    Verificar Cliente
                  </Button>

                  <div v-else class="space-y-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg border border-green-200 dark:border-green-800">
                    <div>
                      <Label for="verify_notes" class="text-sm">Notas (opcional)</Label>
                      <textarea
                        id="verify_notes"
                        v-model="verifyForm.verification_notes"
                        placeholder="Ej: Documentos verificados correctamente..."
                        class="mt-2 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600"
                        rows="4"
                      ></textarea>
                    </div>
                    <div class="flex gap-2">
                      <Button
                        @click="handleVerify"
                        :disabled="verifyForm.processing"
                        class="flex-1 bg-green-600 hover:bg-green-700"
                      >
                        {{ verifyForm.processing ? 'Verificando...' : 'Confirmar Verificación' }}
                      </Button>
                      <Button
                        @click="showVerifyForm = false"
                        variant="outline"
                        class="flex-1"
                      >
                        Cancelar
                      </Button>
                    </div>
                  </div>
                </div>

                <!-- Rechazar -->
                <div v-if="profile?.verification_status !== 'rejected'">
                  <Button
                    v-if="!showRejectForm"
                    @click="showRejectForm = true"
                    variant="destructive"
                    class="w-full gap-2"
                  >
                    <XCircle class="h-5 w-5" />
                    Rechazar Cliente
                  </Button>

                  <div v-else class="space-y-3 p-3 bg-red-50 dark:bg-red-950 rounded-lg border border-red-200 dark:border-red-800">
                    <div>
                      <Label for="reject_notes" class="text-sm text-red-900 dark:text-red-100">Razón de rechazo</Label>
                      <textarea
                        id="reject_notes"
                        v-model="rejectForm.verification_notes"
                        placeholder="Ej: Documentos no legibles, DNI expirado..."
                        class="mt-2 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-600"
                        rows="4"
                      ></textarea>
                      <span v-if="rejectForm.errors.verification_notes" class="text-xs text-red-600 dark:text-red-400">
                        {{ rejectForm.errors.verification_notes }}
                      </span>
                    </div>
                    <div class="flex gap-2">
                      <Button
                        @click="handleReject"
                        :disabled="rejectForm.processing"
                        variant="destructive"
                        class="flex-1"
                      >
                        {{ rejectForm.processing ? 'Rechazando...' : 'Confirmar Rechazo' }}
                      </Button>
                      <Button
                        @click="showRejectForm = false"
                        variant="outline"
                        class="flex-1"
                      >
                        Cancelar
                      </Button>
                    </div>
                  </div>
                </div>

                <!-- Resetear -->
                <Button
                  v-if="profile?.verification_status !== 'pending'"
                  @click="handleReset"
                  variant="outline"
                  class="w-full gap-2"
                >
                  <Clock class="h-5 w-5" />
                  Resetear Verificación
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Resumen -->
          <Card class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-950 dark:to-purple-950">
            <CardHeader>
              <CardTitle class="text-base">Resumen</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Total de documentos:</span>
                <span class="font-semibold">{{ documents.length }}</span>
              </div>
              <div class="flex justify-between">
                <span>Tarjetas registradas:</span>
                <span class="font-semibold">{{ credit_cards.length }}</span>
              </div>
              <div class="flex justify-between">
                <span>Pagos realizados:</span>
                <span class="font-semibold">{{ payments.length }}</span>
              </div>
              <div class="flex justify-between border-t pt-2 mt-2">
                <span class="font-semibold">Total invertido:</span>
                <span class="font-bold text-blue-600 dark:text-blue-400">
                  {{ payments.length > 0 ? formatCurrency(payments.reduce((sum, p) => sum + p.total_paid, 0)) : '$0.00' }}
                </span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
