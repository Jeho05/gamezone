-- Migration: Add composite index for anti-abuse on invoice_scans
-- Date: 2025-10-17
-- Purpose: Speed up rate-limit query filtering by ip and time window

USE `gamezone`;

CREATE INDEX idx_ip_time ON invoice_scans (ip_address, scanned_at);
