<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { h, ref } from 'vue'

import { Checkbox } from '@/components/ui/checkbox'
import {data,Profiles} from '@/composables/adminFunctions/adminProfilesTable';

import { Button } from '@/components/ui/button'
import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import { Separator } from '@/components/ui/separator';
import DropdownAction from '../profiles/ProfileDropDown.vue';
import type {
  ColumnDef,
  ColumnFiltersState,
  ExpandedState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'

import {
  FlexRender,
  getCoreRowModel,
  getExpandedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

import { valueUpdater } from '@/utils';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {Input} from '@/components/ui/input'




const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profiles',
        href: '/profiles',
    },
];

const tags = Array.from({ length: 50 }).map(
  (_, i, a) => `v1.2.0-beta.${a.length - i}`,
)

const columns: ColumnDef<Profiles>[] = [
  {
    id: 'select',
    header: ({ table }) =>
      h(Checkbox, {
        'modelValue': table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
        'onUpdate:modelValue': value => table.toggleAllPageRowsSelected(!!value),
        'ariaLabel': 'Select all',
      }),
    cell: ({ row }) =>
      h(Checkbox, {
        'modelValue': row.getIsSelected(),
        'onUpdate:modelValue': value => row.toggleSelected(!!value),
        'ariaLabel': 'Select row',
      }),
    enableSorting: false,

    enableHiding: false,
  },
  {
    accessorKey: 'dni',
    header: 'DNI',
    cell: ({ row }) => h('div', {}, row.getValue('dni')),
  },
  {
    accessorKey: 'name',
    header: 'Nombre',
    cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('name')),
  },
  {
    accessorKey: 'email',
    header: 'Email',
    cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('email')),
  },
  {
    accessorKey: 'status',
    header: 'Estado',
    cell: ({ row }) => {
      const status = row.getValue('status')  as string || undefined
      const statusColor = {
        validado: 'text-green-600',
        rechazado: 'text-red-600',
        'observación': 'text-yellow-600',
      }

      return h(
        'span',
        {
          class: `capitalize font-semibold ${statusColor[status as keyof typeof statusColor]}`
        },status 
      )
    },
  },
  {
    accessorKey: 'info',
    header: 'Datos',
    cell: ({ row }) => {
      const value = row.getValue('info') as string || undefined
      return h('span', { class: 'capitalize' },value)
    },
  },
  {
    id: 'actions',
    header: 'Acciones',
    enableHiding: false,
    cell: ({ row }) =>
      h(DropdownAction, {
        profile: row.original,
      }),
  },
]

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

const table = useVueTable({
  data,
  columns,
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getExpandedRowModel: getExpandedRowModel(),
  onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
  onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
  onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
  onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
  onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
    get expanded() { return expanded.value },
  },
})

</script>



<template>
    <Head title="Profiles" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="w-full">
                <div class="flex items-center py-4">
                    <Input
                      class="max-w-sm"
                      placeholder="Filter emails..."
                      :model-value="table.getColumn('dni')?.getFilterValue() as number"
                      @update:model-value=" table.getColumn('dni')?.setFilterValue($event)"
                    />
                    <DropdownMenu>
                     <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="ml-auto">
                          Columns <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuCheckboxItem
                          v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                          :key="column.id"
                          class="capitalize"
                          :model-value="column.getIsVisible()"
                          @update:model-value="(value) => {
                            column.toggleVisibility(!!value)
                          }"
                        >
                          {{ column.id }}
                        </DropdownMenuCheckboxItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead v-for="header in headerGroup.headers" :key="header.id">
                              <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                            </TableHead>
                          </TableRow>
                        </TableHeader> 
                        <TableBody>
                            <template v-if="table.getRowModel().rows?.length">
                                <template v-for="row in table.getRowModel().rows" :key="row.id">
                                  <TableRow :data-state="row.getIsSelected() && 'selected'">
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                      <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                  </TableRow>
                                  <TableRow v-if="row.getIsExpanded()">
                                    <TableCell :colspan="row.getAllCells().length">
                                      {{ JSON.stringify(row.original) }}
                                    </TableCell>
                                  </TableRow>
                                </template>
                            </template>
            
                          <TableRow v-else>
                            <TableCell
                              :colspan="columns.length"
                              class="h-24 text-center"
                            >
                              No results.
                            </TableCell>
                          </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex items-center justify-end space-x-2 py-4">
                    <div class="flex-1 text-sm text-muted-foreground">
                      {{ table.getFilteredSelectedRowModel().rows.length }} of
                      {{ table.getFilteredRowModel().rows.length }} row(s) selected.
                    </div>
                    <div class="space-x-2">
                      <Button
                        variant="outline"
                        size="sm"
                        :disabled="!table.getCanPreviousPage()"
                        @click="table.previousPage()"
                      >
                        Previous
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        :disabled="!table.getCanNextPage()"
                        @click="table.nextPage()"
                      >
                        Next
                      </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
