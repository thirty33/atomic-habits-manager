# Claude Code Project Notes

## Environment

- **Runtime**: Laravel Sail (Docker)
- **Commands**: Use `./vendor/bin/sail` instead of direct `php artisan`
  - Example: `./vendor/bin/sail artisan migrate`
  - Example: `./vendor/bin/sail artisan optimize:clear`
  - Example: `./vendor/bin/sail composer install`

## Responsive Breakpoints

- **Mobile & Tablet** (`< 1024px` / `lg:`): Card view, burger menu, vertical filters
- **Desktop** (`>= 1024px`): Table view, sidebar visible, horizontal filters
