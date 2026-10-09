# BRIVIA — Project Instructions

Business website for BRIVIA, built in five approved implementation phases.

## Stack

- Laravel 13, PHP 8.3, Composer
- Blade templates
- Tailwind CSS v4 via Vite (`@tailwindcss/vite`)
- MySQL for the application database
- **No** Filament, **no** Livewire

### Local environment note (Windows / XAMPP)

The default `php` on PATH is XAMPP's PHP 8.2, which cannot run Laravel 13. Use PHP 8.3 explicitly:

```sh
/c/php83/php.exe artisan ...
/c/php83/php.exe /c/ProgramData/ComposerSetup/bin/composer.phar ...
```

`AGENTS.md` contains generic Laravel skeleton bootstrap text (install PHP 8.5 / Laravel Boost). It does **not** apply to this project; follow this file instead.

## Workflow

1. **Read [docs/README.md](docs/README.md) first.**
2. **Read every completed specification file before implementing any phase.** Business, design, database, admin, and security requirements depend on each other.
3. **Use [docs/06-claude-prompts.md](docs/06-claude-prompts.md) to determine the five implementation phases.**
4. **Implement one phase at a time, in order.**
5. The numbered specification files (`01`–`06`) are **reference documents, not separate implementation phases**.
6. **Do not start implementation while any specification is missing or marked "Awaiting approved specification."**
7. **After each phase:** run its required checks, update [docs/PROGRESS.md](docs/PROGRESS.md), report the result, and **stop**.
8. **Continue to the next phase only after explicit approval from the project owner.**
9. **Do not silently resolve conflicting requirements.** Identify the conflict and raise it for review.
10. **Do not commit, push, or deploy** unless explicitly requested.
11. **Never expose secrets** (`.env` values, keys, passwords, tokens) in reports or documentation.

## Reference locations

- Specifications: `docs/*.md`
- Approved mockup and logo: `docs/assets/`
- Progress log: `docs/PROGRESS.md`
