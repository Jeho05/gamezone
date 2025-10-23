-- Migration: Add deactivation reason field to users table
-- Run this script to add the new field for storing deactivation/deletion reasons

USE `gamezone`;

-- Add deactivation_reason column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_reason TEXT NULL AFTER status;

-- Add deactivation_date column to track when the account was deactivated
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_date DATETIME NULL AFTER deactivation_reason;

-- Add admin_id who performed the deactivation
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivated_by INT NULL AFTER deactivation_date;
