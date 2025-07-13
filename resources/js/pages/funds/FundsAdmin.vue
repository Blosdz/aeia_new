<script setup lang="ts">
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

import CardHeader from '@/components/ui/card/CardHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Separator } from '@/components/ui/separator'
import { Card,CardContent,CardDescription,CardFooter,CardTitle} from '@/components/ui/card'
import { value } from '@/composables/adminFunctions/datePicker'
/**********************TABLE************************** */
import { FlexRender,getCoreRowModel,getExpandedRowModel,getFilteredRowModel,getPaginationRowModel,useVueTable,getSortedRowModel} from '@tanstack/vue-table';

import{Table,TableBody,TableCell,TableHead,TableHeader,TableRow}from '@/components/ui/table'
import type {
  ColumnDef,
  ColumnFiltersState,
  ExpandedState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'

import {h} from 'vue'

import { valueUpdater } from '@/utils';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {Input} from '@/components/ui/input'
import {dataSales,Sales} from '@/composables/adminFunctions/adminSalesTeamTable'
import DropdownAction from './funds.vue'

/************************************************ */
// donut
// donut
import { VisSingleContainer, VisDonut, VisBulletLegend,VisXYContainer,VisLine,VisAxis} from '@unovis/vue'
import { data, DataRecord } from '@/pages/sales/data'

//data picker
/************************************************************************************* */
import { cn } from '@/utils'

import {
  CalendarDate,
  type DateValue,
  isEqualMonth,
} from '@internationalized/date'

import {
  Calendar,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import { type DateRange, RangeCalendarRoot, useDateFormatter } from 'reka-ui'

import { createMonth, type Grid, toDate } from 'reka-ui/date'
import { onUpdated, type Ref, ref, watch } from 'vue'
import { Button, buttonVariants } from '@/components/ui/button'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  RangeCalendarCell,
  RangeCalendarCellTrigger,
  RangeCalendarGrid,
  RangeCalendarGridBody,
  RangeCalendarGridHead,
  RangeCalendarGridRow,
  RangeCalendarHeadCell,
} from '@/components/ui/range-calendar'
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import { row } from '@unovis/ts/components/timeline/style';
/************************************************************************************* */


const value = ref({
  start: new CalendarDate(2022, 1, 20),
  end: new CalendarDate(2022, 1, 20).add({ days: 20 }),
}) as Ref<DateRange>

const locale = ref('en-US')
const formatter = useDateFormatter(locale.value)

const placeholder = ref(value.value.start) as Ref<DateValue>
const secondMonthPlaceholder = ref(value.value.end) as Ref<DateValue>

const firstMonth = ref(
  createMonth({
    dateObj: placeholder.value,
    locale: locale.value,
    fixedWeeks: true,
    weekStartsOn: 0,
  }),
) as Ref<Grid<DateValue>>
const secondMonth = ref(
  createMonth({
    dateObj: secondMonthPlaceholder.value,
    locale: locale.value,
    fixedWeeks: true,
    weekStartsOn: 0,
  }),
) as Ref<Grid<DateValue>>

function updateMonth(reference: 'first' | 'second', months: number) {
  if (reference === 'first') {
    placeholder.value = placeholder.value.add({ months })
  }
  else {
    secondMonthPlaceholder.value = secondMonthPlaceholder.value.add({
      months,
    })
  }
}

watch(placeholder, (_placeholder) => {
  firstMonth.value = createMonth({
    dateObj: _placeholder,
    weekStartsOn: 0,
    fixedWeeks: false,
    locale: locale.value,
  })
  if (isEqualMonth(secondMonthPlaceholder.value, _placeholder)) {
    secondMonthPlaceholder.value = secondMonthPlaceholder.value.add({
      months: 1,
    })
  }
})

watch(secondMonthPlaceholder, (_secondMonthPlaceholder) => {
  secondMonth.value = createMonth({
    dateObj: _secondMonthPlaceholder,
    weekStartsOn: 0,
    fixedWeeks: false,
    locale: locale.value,
  })
  if (isEqualMonth(_secondMonthPlaceholder, placeholder.value))
    placeholder.value = placeholder.value.subtract({ months: 1 })
})
/************************************************************************** */
const legendItems = Object.entries(data).map(([_, data]) => ({
  name: data.key.charAt(0).toUpperCase() + data.key.slice(1)
}))



const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Fund',
        href: '/Funds',
    },
];

const tags = Array.from({ length: 50 }).map(
  (_, i, a) => `v1.2.0-beta.${a.length - i}`,
)
/*******************************************Table******************************************************* */
const columns:ColumnDef<Sales>[]=[
  {
    id:'select',
    header:({table})=> h(Checkbox,{'modelValue':table.getIsAllPageRowsSelected()||(table.getIsSomeRowsSelected()&&'indeterminate'),
    'onUpdate:modelValue':value=>table.toggleAllPageRowsSelected(!!value),
    'ariaLabel':'Select all',
    }),
    cell:({row})=>
    h(Checkbox,{
      'modelValue':row.getIsSelected(),
      'onUpdate:modelValue':value=>row.toggleSelected(!!value),
      'ariaLabel':'Select row',
    }),
    enableSorting:false,
    enableHiding:false,
  },
  {
    accessorKey:'email',
    header:'Email',
    cell: ({row})=>h('div',{class:'lowercase'},row.getValue('email')),
  },
  {
    accessorKey: 'name',
    header: 'Nombre',
    cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('name')),
  },
  {
    accessorKey: 'rol',
    header: 'Rol',
    cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('user_rol')),
  },
  {
    accessorKey: 'user_subs',
    header: 'Subscripciones',
    cell:({row})=> h('div',{},row.getValue('user_subs')),
  },

]

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

const table = useVueTable({
  data : dataSales,
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
/************************************************************************************************** */

</script>

<template>
    <Head title="Funds"/>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div class="lg:col-span-2 lg:col-start-1  lg:pr-8 lg:pb-16">
                <Card class="w-[100%]">
                    <CardHeader>
                      <div class="gap-y-1.5 p-3 flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle>Total Generado</CardTitle>
                        <Popover>
                            <PopoverTrigger as-child>
                              <Button
                                variant="outline"
                                :class="
                                  cn(
                                    'w-[280px] justify-start text-left font-normal',
                                    !value && 'text-muted-foreground',
                                  )
                                "
                              >
                                <Calendar class="mr-2 h-4 w-4" />
                                <template v-if="value.start">
                                  <template v-if="value.end">
                                    {{
                                      formatter.custom(toDate(value.start), {
                                        dateStyle: "medium",
                                      })
                                    }}
                                    -
                                    {{
                                      formatter.custom(toDate(value.end), {
                                        dateStyle: "medium",
                                      })
                                    }}
                                  </template>
                                
                                <template v-else>
                                    {{
                                      formatter.custom(toDate(value.start), {
                                        dateStyle: "medium",
                                      })
                                    }}
                                  </template>
                                </template>
                                <template v-else>
                                  Pick a date
                                </template>
                              </Button>
                            </PopoverTrigger>
                          <PopoverContent class="w-auto p-0">
                            <RangeCalendarRoot v-slot="{ weekDays }" v-model="value" v-model:placeholder="placeholder" class="p-3">
                              <div class="flex flex-col gap-y-4 mt-4 sm:flex-row sm:gap-x-4 sm:gap-y-0">
                                <div class="flex flex-col gap-4">
                                  <div class="flex items-center justify-between">
                                    <button
                                      :class="
                                        cn(
                                          buttonVariants({ variant: 'outline' }),
                                          'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',
                                        )
                                      "
                                      @click="updateMonth('first', -1)"
                                    >
                                      <ChevronLeft class="h-4 w-4" />
                                    </button>
                                    <div :class="cn('text-sm font-medium')">
                                      {{
                                        formatter.fullMonthAndYear(
                                          toDate(firstMonth.value),
                                        )
                                      }}
                                    </div>
                                    <button
                                      :class="
                                        cn(
                                          buttonVariants({ variant: 'outline' }),
                                          'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',
                                        )
                                      "
                                      @click="updateMonth('first', 1)"
                                    >
                                      <ChevronRight class="h-4 w-4" />
                                    </button>
                                  </div>
                                  <RangeCalendarGrid>
                                    <RangeCalendarGridHead>
                                      <RangeCalendarGridRow>
                                        <RangeCalendarHeadCell
                                          v-for="day in weekDays"
                                          :key="day"
                                          class="w-full"
                                        >
                                          {{ day }}
                                        </RangeCalendarHeadCell>
                                      </RangeCalendarGridRow>
                                    </RangeCalendarGridHead>
                                    <RangeCalendarGridBody>
                                      <RangeCalendarGridRow
                                        v-for="(
                                          weekDates, index
                                        ) in firstMonth.rows"
                                        :key="`weekDate-${index}`"
                                        class="mt-2 w-full"
                                      >
                                        <RangeCalendarCell
                                          v-for="weekDate in weekDates"
                                          :key="weekDate.toString()"
                                          :date="weekDate"
                                        >
                                          <RangeCalendarCellTrigger
                                            :day="weekDate"
                                            :month="firstMonth.value"
                                          />
                                        </RangeCalendarCell>
                                      </RangeCalendarGridRow>
                                    </RangeCalendarGridBody>
                                  </RangeCalendarGrid>
                                </div>
                                <div class="flex flex-col gap-4">
                                  <div class="flex items-center justify-between">
                                    <button :class="   cn(     buttonVariants({ variant: 'outline' }),     'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',   ) " @click="updateMonth('second', -1)" >
                                      <ChevronLeft class="h-4 w-4" />
                                    </button>
                                    <div :class="cn('text-sm font-medium')">
                                      {{
                                        formatter.fullMonthAndYear(
                                          toDate(secondMonth.value),
                                        )
                                      }}
                                    </div>
                                    <button :class="   cn(     buttonVariants({ variant: 'outline' }),     'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',   ) " @click="updateMonth('second', 1)">
                                      <ChevronRight class="h-4 w-4" />
                                    </button>
                                  </div>
                                  <RangeCalendarGrid>
                                    <RangeCalendarGridHead>
                                      <RangeCalendarGridRow>
                                        <RangeCalendarHeadCell
                                          v-for="day in weekDays"
                                          :key="day"
                                          class="w-full"
                                        >
                                          {{ day }}
                                        </RangeCalendarHeadCell>
                                      </RangeCalendarGridRow>
                                    </RangeCalendarGridHead>
                                    <RangeCalendarGridBody>
                                      <RangeCalendarGridRow v-for="(   weekDates, index ) in secondMonth.rows" :key="`weekDate-${index}`" class="mt-2 w-full">
                                        <RangeCalendarCell v-for="weekDate in weekDates" :key="weekDate.toString()" :date="weekDate">
                                          <RangeCalendarCellTrigger :day="weekDate" :month="secondMonth.value"/>
                                        </RangeCalendarCell>
                                      </RangeCalendarGridRow>
                                    </RangeCalendarGridBody>
                                  </RangeCalendarGrid>
                                </div>
                              </div>
                            </RangeCalendarRoot>
                          </PopoverContent>
                        </Popover>
                      
                      </div>
                    </CardHeader>
                    <CardContent>
                      <div class="gap-y-1.5 p-3 flex flex-row items-center justify-between space-y-0 pb-2 gap-5">
                        <Card class="w-[100%]  bg-muted">
                          <CardHeader>  
                            <div class="text-2xl font-bold">Total Generado</div>
                            <div class="text-lg font-bold"> $15,231.89 </div>
                            <CardDescription>
                              <p class="text-sm text-muted-foreground">
                                Desde Jan 20,2022 to july 2022
                              </p>

                            </CardDescription>
                          </CardHeader>

                        </Card>
                          <VisXYContainer :height="150">
                            <VisLine :data="data" :x="d => d.x" :y="d => d.y" />
                            <VisAxis type="x" />
                            <VisAxis type="y" />
                          </VisXYContainer>
                      </div> 
                    </CardContent>
                </Card>
              </div>
            </div>
            <div class="grid auto-rows-min gap-4 ">
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