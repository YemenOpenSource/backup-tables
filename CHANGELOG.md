# Changelog

All notable changes to `backup-tables` will be documented in this file.

## 1.3.0 - 2025-01-28

### What's Changed

- Using the artisan command for one or more tables/models

```bash
php artisan backup:tables users posts # users_backup_2024_08_22_17_40_01, posts_backup_2024_08_22_17_40_01
php artisan backup:tables \\App\\Models\\User \\App\\Models\\Post # users_backup_2024_08_22_17_40_01, posts_backup_2024_08_22_17_40_01

```
**Full Changelog**: https://github.com/WatheqAlshowaiter/backup-tables/compare/1.2.2...1.3.0

## 1.2.2 - 2024-08-26

- move models and migrations to test folder because they are used only in the test

**Full Changelog**: https://github.com/WatheqAlshowaiter/backup-tables/compare/1.2.1...1.2.2

## 1.2.1 - 2024-08-24

Refactoring and improving docs.

**Full Changelog**: https://github.com/WatheqAlshowaiter/backup-tables/compare/1.2.0...1.2.1

## 1.2.0 - 2024-08-24

Fix a some tests bugs

**Full Changelog**: https://github.com/WatheqAlshowaiter/backup-tables/compare/1.0.1...1.2.0

## 1.0.1 - 2024-08-23

**Full Changelog**: https://github.com/WatheqAlshowaiter/backup-tables/compare/1.0.0...1.0.1

## 1.0.0 - 2024-08-22

**Full Changelog**: https://github.com/WatheqAlshowaiter/backup-tables/commits/1.0.0
