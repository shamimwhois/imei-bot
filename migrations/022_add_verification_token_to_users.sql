-- Add verification_token column to users table for email verification
ALTER TABLE `users` 
ADD COLUMN `verification_token` VARCHAR(64) NULL AFTER `email_verified_at`,
ADD COLUMN `verification_token_expires_at` TIMESTAMP NULL AFTER `verification_token`;
