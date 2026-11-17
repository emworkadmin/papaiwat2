# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**papaiwat2** is a Thai government content management system built on **Zend Framework 1.x** (legacy PHP MVC). It features multi-language support and is structured as a dual-portal application with a shared library layer. A Laravel migration exists in the sibling `d2msp-laravel-2` repository.

## Architecture

### Dual-Portal Design

- **Frontend** (`applicationfront/`) — Public-facing site with language-based URL routing (`/{lang}/...`)
- **Admin/Backoffice** (`applicationadmin/`) — Content and system management panel

Each portal has its own `Bootstrap.php`, `configs/application.ini`, controllers, modules, models, views, and layouts. They share the `library/` directory.

### Entry Points

- Frontend: `public/index.php` → loads `applicationfront/configs/application.ini`
- Admin: `public/admin/index.php` → loads `applicationadmin/configs/application.ini`

### Module System

Both portals use Zend modules (20+ each) located in their respective `modules/` directories. Each module follows this structure:

```
modules/{module-name}/
├── Bootstrap.php
├── controllers/
├── models/
├── services/
└── views/scripts/
```

Key modules: content, banner, calendar, gallery, event, faq, newsletter, orgchart, user, system, setting, report.

### Shared Libraries (`library/`)

- **`Emwork/`** — Custom framework utilities:
  - `Emwork_Grid` — Table/grid generation
  - `Emwork_Acl` — Access control
  - `Emwork_Translate` — Multi-language translation (INI-based)
  - `Emwork_Utility_DateTime` — Thai/English date conversion
  - `Emwork_Controller_Plugin_Language` — Language routing plugin
  - `Emwork_Jasper` — Report generation
- **`App/`** — Application-specific classes (DB adapters, forms, SMS integration, rights/permissions)
- **`Zend/`** — Zend Framework 1.x core classes (vendored)
- **`PHPExcel/`** — Excel processing library (vendored)

### Database

- MySQL via `Mysqli` adapter (Zend_Db)
- No ORM — uses Zend_Db query builder and raw SQL
- Session storage in database (`Zend_Session_SaveHandler_DbTable`)
- Configuration in each portal's `application.ini`

### External Integrations

- Facebook Graph SDK (~5.0)
- Google API Client (^2.0)
- Thai Bulk SMS (`App_ThaibulkSms`)
- PromptPay QR code generation
- HTML-to-PDF (spipu/html2pdf)
- BIRT Reports

## Development

### Requirements

- PHP with MySQL
- Apache with mod_rewrite (`.htaccess` routing)
- Composer dependencies in `library/` (`cd library && composer install`)

### No Modern Tooling

This project has no Node/npm, Docker, CI/CD pipeline, or test framework. Assets (jQuery, CKEditor, Bootstrap plugins) are static files in `public/plugins/` and `public/admin/plugins/`.

## Key Patterns

- **Language routing**: URL prefix determines language; handled by `Emwork_Controller_Plugin_Language` and frontend `Bootstrap.php`
- **Widget system**: JSON-configured widgets with custom view helper rendering
- **File management**: Upload via `qqFileUploader`, metadata tracked in database, files stored in `public/` and `files/`
- **Authentication**: `Zend_Auth` with session-based auth and role-based ACL
- **Controller naming**: `{Module}_{ActionName}Controller` convention (Zend 1.x standard)
