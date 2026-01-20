// adminTable.ts
export interface Payment {
    id: string
    amount: number
    status: 'success' | 'processing' | 'failed'
    plan: string
    email: string
  }
  
export const data: Payment[] = [
  {
    id: 'm5gr84i9',
    amount: 316,
    status: 'success',
    plan: 'Silver',
    email: 'ken99@yahoo.com',
  },
  {
    id: '3u1reuv4',
    amount: 242,
    status: 'success',

    plan: 'Silver',
    email: 'Abe45@gmail.com',
  },
  {
    id: 'derv1ws0',
    amount: 837,
    status: 'processing',

    plan: 'Silver',
    email: 'Monserrat44@gmail.com',
  },
  {
    id: '5kma53ae',
    amount: 874,
    status: 'success',
    plan: 'Bronze',
    email: 'Silas22@gmail.com',
  },
  {
    id: 'bhqecj4p',
    amount: 721,
    status: 'failed',
    plan: 'Gold',
    email: 'carmella@hotmail.com',
  },
]