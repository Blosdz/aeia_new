<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { FileUp, Trash2, Download, File } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Document {
  id: number;
  name: string;
  file_path: string;
  file_extension: string;
  file_size: number;
  file_type: string;
  created_at: string;
  updated_at: string;
}

interface DocumentsData {
  data: Document[];
  links: any;
  meta: any;
}

const props = defineProps<{
  documents: DocumentsData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: route('clients.dashboard') },
  { title: 'Documentos', href: route('clients.documents') },
];

const form = useForm({
  file: null as File | null,
  name: '',
  file_type: '',
});

const submit = () => {
  const formData = new FormData();
  if (form.file) {
    formData.append('file', form.file);
  }
  formData.append('name', form.name);
  formData.append('file_type', form.file_type);

  form.post(route('clients.documents.store'), {
    data: formData,
    onSuccess: () => {
      form.reset();
    },
  });
};

const handleDelete = (documentId: number) => {
  if (confirm('¿Estás seguro de que deseas eliminar este documento?')) {
    useForm({}).delete(route('clients.documents.destroy', documentId));
  }
};

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};
</script>

<template>
  <Head title="Documentos" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Subir Nuevo Documento -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <FileUp class="h-5 w-5" />
            Subir Nuevo Documento
          </CardTitle>
          <CardDescription>Máximo 10 MB por archivo (PDF, JPG, PNG, DOC, DOCX, XLS, XLSX)</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="name">Nombre del Documento</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="Ej: Cédula de identidad"
                class="mt-2"
              />
              <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="file_type">Tipo de Archivo</Label>
                <Select v-model="form.file_type">
                  <SelectTrigger id="file_type" class="mt-2">
                    <SelectValue placeholder="Selecciona tipo" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectItem value="pdf">PDF</SelectItem>
                      <SelectItem value="jpg">JPG</SelectItem>
                      <SelectItem value="jpeg">JPEG</SelectItem>
                      <SelectItem value="png">PNG</SelectItem>
                      <SelectItem value="doc">DOC</SelectItem>
                      <SelectItem value="docx">DOCX</SelectItem>
                      <SelectItem value="xls">XLS</SelectItem>
                      <SelectItem value="xlsx">XLSX</SelectItem>
                      <SelectItem value="other">Otro</SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <span v-if="form.errors.file_type" class="text-sm text-red-500">{{ form.errors.file_type }}</span>
              </div>

              <div>
                <Label for="file">Archivo</Label>
                <Input
                  id="file"
                  type="file"
                  @change="(e: any) => form.file = e.target.files[0]"
                  class="mt-2"
                />
                <span v-if="form.errors.file" class="text-sm text-red-500">{{ form.errors.file }}</span>
                <p v-if="form.file" class="text-sm text-green-600 mt-2">✓ {{ form.file.name }} ({{ formatFileSize(form.file.size) }})</p>
              </div>
            </div>

            <div class="flex gap-2 justify-end">
              <Button variant="outline" type="button" @click="form.reset()">Cancelar</Button>
              <Button type="submit" :disabled="form.processing || !form.file">
                {{ form.processing ? 'Subiendo...' : 'Subir Documento' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Listado de Documentos -->
      <Card>
        <CardHeader>
          <CardTitle>Mis Documentos</CardTitle>
          <CardDescription>{{ props.documents?.data?.length || 0 }} documento(s) subido(s)</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!props.documents?.data || props.documents.data.length === 0" class="text-center py-8">
            <File class="h-12 w-12 mx-auto text-gray-400 mb-2" />
            <p class="text-muted-foreground">No tienes documentos subidos aún</p>
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="doc in props.documents.data"
              :key="doc.id"
              class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900"
            >
              <div class="flex items-center gap-4 flex-1">
                <File class="h-6 w-6 text-blue-500" />
                <div>
                  <p class="font-medium">{{ doc.name }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ formatFileSize(doc.file_size) }} • {{ formatDate(doc.created_at) }}
                  </p>
                </div>
              </div>

              <div class="flex gap-2">
                <Button
                  variant="ghost"
                  size="sm"
                  as-child
                >
                  <a :href="`/storage/${doc.file_path}`" target="_blank" class="flex items-center gap-2">
                    <Download class="h-4 w-4" />
                    Descargar
                  </a>
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  class="text-red-600 hover:text-red-700 hover:bg-red-50"
                  @click="handleDelete(doc.id)"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
