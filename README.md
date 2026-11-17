<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## MyBatchoy setup

Configure the database connection in the local environment, then create the schema and initial Owner/Admin account:

```bash
php artisan migrate
php artisan db:seed --class=ProductionSeeder
php artisan app:create-owner
php artisan app:create-system-admin
```

The owner and system-admin setup commands securely prompt for strong 12+ character credentials and refuse to create duplicates once initialized. `ProductionSeeder` calls `RoleSeeder` and initializes baseline reference settings without creating default passwords, test users, or sample inventory. `InventorySeeder`, `OwnerSeeder`, `StaffSeeder`, and `TestDataSeeder` are development/demo-only seeders guarded against execution in `APP_ENV=production`.

There is no public registration. An authenticated Owner/Admin creates staff and supplier portal accounts cleanly. To send password-reset codes and Owner email MFA challenges, configure Laravel mail with a real SMTP provider; do not use `log`, `array`, or `failover` mail transports.

Session cookies are HttpOnly and SameSite=Lax. Secure cookies default on in production (`SESSION_SECURE_COOKIE=true`). The default idle timeout is 20 minutes and an Owner/Admin can adjust it from Settings (5–240 minutes). Configure trusted proxies appropriately when deploying behind TLS reverse proxies.

Business logos and hosted APK distribution support configured Laravel storage disks (`MEDIA_DISK=public` and `APK_DISK=local` on persistent storage, or `MEDIA_DISK=s3` / `APK_DISK=s3` object storage plus `MOBILE_APK_URL` on serverless platforms — see `DEPLOYMENT.md`). Run `php artisan storage:link` once on persistent hosting when storing public files locally, and install `league/flysystem-aws-s3-v3` before enabling S3/R2 storage. Owner MFA supports both Authenticator TOTP (`TotpService`) and email verification codes (`OwnerMfaService`).

### Backup restore safety

The in-app backup is streamed to the administrator and is not retained on the server. Keep each downloaded file in an access-controlled location. Before restoring a MySQL/MariaDB export, first create a disposable database on an isolated test instance (for example `mybatchoy_restore_test`), then import the file only into that target:

```bash
mysql --host=TEST_DB_HOST --user=TEST_DB_USER --password mybatchoy_restore_test < mybatchoy_db_backup.sql
```

The client prompts for the password; do not put credentials in the command line. Point a separate local/staging application configuration at that disposable database, run smoke checks, and verify representative table counts and records. Never use the production database credentials or target when testing restoration. The automated SQLite test restores an export to a separate temporary SQLite file; MySQL/MariaDB restoration still requires a staging-instance test. SQLite exports use SQLite syntax and must be restored with SQLite tooling.

## Business-owner assessment

MyBatchoy is a suitable foundation for organizing store inventory. Before relying on it for daily operations, prioritize verifying stock transaction accuracy, staff permissions, audit coverage, and backup restoration.

POS, multi-branch management, and automatic purchasing are not current goals and should remain future enhancements unless the business needs them. When evaluating the system, distinguish confirmed features from workflows that still need verification and from proposed enhancements.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# mybatchoyv2
