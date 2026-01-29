# Análisis de Estructura de Inversiones - AEIA

## Estado Actual de la Base de Datos

### Tablas Existentes

1. **subscriptions** - Suscripciones de clientes

    - Vinculada a payment_id
    - Tiene plan_type_id
    - Puede tener profile_id o beneficiary_id

2. **investment_earnings** - Ganancias de inversión por suscripción

    - subscription_id + fund_id (unique)
    - initial_amount (monto inicial invertido)
    - current_amount (valor actual)

3. **investment_earnings_history** - Historial de fluctuaciones

    - earning_id
    - previous_amount, new_amount
    - fluctuation_percent
    - reason, recorded_at

4. **funds** - Fondos de inversión

    - category, name
    - initial_amount, current_amount
    - status (open/closed/paused)

5. **fund_histories** - Historial de fondos

    - fund_id
    - previous_amount, new_amount
    - fluctuation_percent
    - reason, recorded_at

6. **subscription_participants** - Participantes en una suscripción

    - subscription_id, profile_id, investment_earnings_id
    - role (owner/beneficiary/advisor)
    - share_percent
    - final_investment_amount

7. **payment_allocations** - Asignaciones de pagos a fondos
    - payment_id, subscription_id, fund_id
    - amount, percent
    - status (accrued/pending_payment/paid/cancelled)

## Problemas Identificados

### 1. Falta el 20% del Admin en las Ganancias

**Problema**: Cuando se actualiza el valor de un fondo, no se está separando el 20% de las ganancias para el admin.

**Ejemplo**:

- Cliente invierte: $1000
- Fondo reporta nuevo valor: $1200
- Ganancia total: $200
- Debería ser:
    - 80% cliente ($160) → Cliente recibe $1160
    - 20% admin ($40) → Admin recibe $40

**Actualmente**: Se está dando todo al cliente ($1200)

### 2. No hay Subscription para el Admin

**Problema**: El admin necesita su propia subscription para trackear su participación del 20%.

**Solución necesaria**:

- Crear un profile para "Admin AEIA" (ya existe: profile_id = 1)
- Crear subscription especial con tipo "admin_commission"
- Crear investment_earning para el admin por cada fondo
- Cuando se actualiza el valor del fondo, también actualizar el admin's earning

### 3. La Distribución de Ganancias es Incorrecta

**Problema**: En `updateFundValue()` se está distribuyendo el nuevo monto total proporcionalmente, pero debería:

1. Calcular ganancia total: `newAmount - fund.initial_amount`
2. Separar 20% para admin: `ganancia * 0.20`
3. Distribuir 80% restante entre clientes: `ganancia * 0.80`
4. Cliente final = `monto_inicial + (ganancia * 0.80 * proporción)`
5. Admin final = suma de todos los 20% de ganancias

## Estructura Correcta Propuesta

### Flujo Completo

#### 1. Cliente Hace Pago

```php
Payment::create([...]) → status: pending
```

#### 2. Admin Valida Pago

```php
PaymentService::validatePayment()
├─ Payment status → completed
├─ Crear Subscription (para el cliente)
├─ NO crear investment_earning aún (sin fondo asignado)
└─ Verificar referral y crear ReferralCommission si aplica
```

#### 3. Admin Crea Fondo con Pagos Seleccionados

```php
PaymentService::createFund($category, $name, $paymentIds)
├─ Crear Fund (initial_amount = suma de pagos)
├─ Crear FundHistory (evento: fund_created)
│
├─ Para cada pago/subscription del CLIENTE:
│   ├─ Crear InvestmentEarning (cliente)
│   │   └─ initial_amount = pago.amount
│   │   └─ current_amount = pago.amount
│   ├─ Crear SubscriptionParticipant (role: owner)
│   │   └─ final_investment_amount = pago.amount
│   ├─ Crear InvestmentEarningHistory
│   │   └─ event: initial_allocation
│   └─ Crear PaymentAllocation
│
└─ Crear estructura para ADMIN (20% de futuras ganancias):
    ├─ Crear Subscription especial para admin
    │   └─ payment_id = null (es virtual)
    │   └─ profile_id = 1 (Admin AEIA)
    │   └─ plan_type_id = tipo especial "admin_commission"
    ├─ Crear InvestmentEarning (admin)
    │   └─ initial_amount = 0
    │   └─ current_amount = 0
    ├─ Crear SubscriptionParticipant (role: advisor)
    │   └─ share_percent = 20
    │   └─ final_investment_amount = 0
    └─ Crear InvestmentEarningHistory
        └─ event: admin_commission_setup
```

#### 4. Admin Actualiza Valor del Fondo

```php
PaymentService::updateFundValue($fund, $newAmount, $reason)
├─ Calcular ganancia total
│   └─ totalProfit = newAmount - fund.initial_amount
│
├─ Separar comisiones
│   ├─ adminProfit = totalProfit * 0.20
│   └─ clientsProfit = totalProfit * 0.80
│
├─ Actualizar Fund
│   └─ current_amount = newAmount
│
├─ Crear FundHistory
│   └─ event: value_update
│   └─ metadata: { admin_profit, clients_profit }
│
├─ Actualizar Investment Earnings de CLIENTES:
│   └─ Para cada earning (excluyendo admin):
│       ├─ proportion = earning.initial_amount / fund.initial_amount
│       ├─ clientShare = clientsProfit * proportion
│       ├─ newAmount = earning.initial_amount + clientShare
│       ├─ earning.current_amount = newAmount
│       ├─ Crear InvestmentEarningHistory
│       └─ Actualizar SubscriptionParticipant.final_investment_amount
│
└─ Actualizar Investment Earning del ADMIN:
    ├─ earning_admin.current_amount += adminProfit
    ├─ Crear InvestmentEarningHistory (admin)
    └─ Actualizar SubscriptionParticipant (admin).final_investment_amount
```

## Ejemplo Numérico Completo

### Paso 1: Tres clientes hacen pagos

- Cliente A: $1000
- Cliente B: $2000
- Cliente C: $1500
- Total: $4500

### Paso 2: Admin crea fondo "Inversión Q1 2026"

```
Fund:
  initial_amount: $4500
  current_amount: $4500

InvestmentEarnings (Clientes):
  - Cliente A: initial=$1000, current=$1000, share=22.22%
  - Cliente B: initial=$2000, current=$2000, share=44.44%
  - Cliente C: initial=$1500, current=$1500, share=33.34%

InvestmentEarning (Admin):
  - Admin AEIA: initial=$0, current=$0, share=20% de ganancias
```

### Paso 3: Fondo crece a $5400 (+$900 ganancia)

```
Cálculo de distribución:
  Ganancia total: $900
  Admin (20%): $180
  Clientes (80%): $720

Distribución proporcional entre clientes:
  - Cliente A (22.22%): $1000 + ($720 * 0.2222) = $1160
  - Cliente B (44.44%): $2000 + ($720 * 0.4444) = $2320
  - Cliente C (33.34%): $1500 + ($720 * 0.3334) = $1740
  - Admin AEIA: $0 + $180 = $180

Verificación:
  $1160 + $2320 + $1740 + $180 = $5400 ✓
```

### Paso 4: Fondo baja a $5200 (-$200 pérdida desde último update)

```
Valor actual del fondo: $5200
Ganancia total desde inicio: $700 (vs $4500 inicial)
  Admin (20%): $140
  Clientes (80%): $560

Nueva distribución:
  - Cliente A: $1000 + ($560 * 0.2222) = $1124
  - Cliente B: $2000 + ($560 * 0.4444) = $2249
  - Cliente C: $1500 + ($560 * 0.3334) = $1687
  - Admin AEIA: $140

Verificación:
  $1124 + $2249 + $1687 + $140 = $5200 ✓
```

## Cambios Requeridos en el Código

### 1. Modificar `PaymentService::createFund()`

- Agregar lógica para crear subscription del admin
- Crear InvestmentEarning para admin con initial=0, current=0
- Crear SubscriptionParticipant para admin con role='advisor', share=20%

### 2. Modificar `PaymentService::updateFundValue()`

- Cambiar lógica de distribución proporcional simple
- Calcular ganancia total: `$profit = $newAmount - $fund->initial_amount`
- Separar: `$adminProfit = $profit * 0.20` y `$clientsProfit = $profit * 0.80`
- Distribuir `$clientsProfit` proporcionalmente entre clientes
- Actualizar earning del admin con `$adminProfit`

### 3. Crear Migration para Plan Type "admin_commission"

```sql
INSERT INTO plan_types (category, name, amount_min, amount_max, periodicity)
VALUES ('investment', 'Admin Commission', 0, 0, 'monthly');
```

### 4. Ajustar Dashboard del Admin

- Mostrar en Fund Details:
    - Total invertido por clientes
    - Valor actual del fondo
    - Ganancia total
    - Comisión admin (20%)
    - Ganancia neta clientes (80%)

## Consideraciones Adicionales

### ¿Qué pasa si el fondo pierde dinero?

- Si `newAmount < initial_amount`, la ganancia es negativa
- Admin commission = 0 (no cobra sobre pérdidas)
- Clientes absorben la pérdida completa

### ¿Cómo se visualiza en dashboards?

- **Cliente**: Ve su inversión inicial y valor actual (ya con el 80% aplicado)
- **Admin**: Ve:
    - Total recaudado en fondo
    - Valor actual del fondo
    - Comisión acumulada (20% de ganancias)
    - Cada fund y su fund_history

### Reportes y Analytics

- `investment_earnings_history`: Muestra fluctuaciones del cliente (80%)
- `fund_histories`: Muestra fluctuaciones del fondo completo (100%)
- Admin puede comparar ambos para auditoría

## Próximos Pasos

1. ✅ Documentar estructura actual
2. ⏳ Modificar `PaymentService::createFund()`
3. ⏳ Modificar `PaymentService::updateFundValue()`
4. ⏳ Crear seeder/migration para plan_type "admin_commission"
5. ⏳ Actualizar vistas del admin dashboard
6. ⏳ Agregar tests unitarios para la distribución 80/20
7. ⏳ Documentar API endpoints relacionados

---

**Fecha**: 2026-01-21
**Autor**: GitHub Copilot
**Estado**: Análisis completo - Pendiente implementación
