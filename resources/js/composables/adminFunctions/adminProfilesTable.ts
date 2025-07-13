// adminTable.ts
export interface Profiles {
    id: string
    dni: number
    status: 'validado' | 'rechazado' | 'observación'
    email: string
    name: string
    info: 'sent' | 'not sent'
  }
  
export const data: Profiles[] = [
  {
    id: 'm5gr84i9',
    dni: 7216352,
    status: 'validado',
    email: 'ken99@yahoo.com',
    name: 'john doe',
    info: 'not sent',
  },
  {
    id: 'm5gr84i9',
    dni: 72163221,
    status: 'rechazado',
    email: 'pablodjn@yahoo.com',
    name: 'pablo serp',
    info: 'sent',
  },
  {
    id: 'm5gr84i9',
    dni: 7422352,
    status: 'observación',
    email: 'johnhns@yahoo.com',
    name: 'camile rick',
    info: 'sent',
  },
  {
    id: 'm5gr84i9',
    dni: 7123213,
    status: 'validado',
    email: 'john@yahoo.com',
    name: 'jose ime',
    info: 'sent',
  },
  
]