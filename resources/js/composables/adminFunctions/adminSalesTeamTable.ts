// adminTable.ts
export interface Sales{
    id: string
    email: string
    name: string
    user_rol: 'Gerente' | 'Suscriptor'
    users_subs: number
}
  
export const dataSales: Sales[] = [
  {
    id: 'm5gr84i9',
    email: 'ken99@yahoo.com',
    name: 'kenya',
    user_rol: 'Gerente',
    users_subs: 400,
  },
  {
    id: '3u1reuv4',
    email: 'Abe45@gmail.com',
    name: 'Abe som',
    user_rol: 'Suscriptor',
    users_subs: 242,
  },
  {
    id: 'derv1ws0',
    email: 'Monserrat44@gmail.com',
    name: 'Monserat',
    user_rol: 'Suscriptor',
    users_subs: 837,
  },
  {
    id: '5kma53ae',
    email: 'Silas22@gmail.com',
    name: 'Sylas',
    user_rol: 'Gerente',
    users_subs: 874,
  },
  {
    id: 'bhqecj4p',
    email: 'carmella@hotmail.com',
    name: 'carmella',
    user_rol: 'Gerente',
    users_subs: 721,
  },
]