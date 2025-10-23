-- Migration: Add Kkiapay payment method
-- Date: 2025-10-17

USE `gamezone`;

INSERT INTO payment_methods (
  name, slug, provider,
  requires_online_payment, auto_confirm, is_active, display_order,
  instructions, api_endpoint, created_at, updated_at
) VALUES (
  'Kkiapay', 'kkiapay', 'kkiapay',
  1, 1, 1, 10,
  'Payer en ligne via Kkiapay. Une fois le paiement effectué, vous serez redirigé automatiquement.',
  'https://api.kkiapay.me', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
  provider = VALUES(provider),
  requires_online_payment = VALUES(requires_online_payment),
  auto_confirm = VALUES(auto_confirm),
  is_active = VALUES(is_active),
  instructions = VALUES(instructions),
  api_endpoint = VALUES(api_endpoint),
  updated_at = NOW();
