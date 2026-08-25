# UniHup

A Laravel + [Filament](https://filamentphp.com) application for finding Italian university degree
programs. Register, set your desired subject and degree level (Bachelor's/Honours or Master's), and
browse matching universities with their admission info — all in one panel, with an admin side for
managing the underlying data.

## Tech Stack

- **Backend:** Laravel 13, Filament 3 (this *is* the app — there's no separate frontend)
- **Auth:** Spatie Permission + filament-shield roles, 2FA, "Sign in with Google"
- **Database:** SQLite by default (MySQL also supported)

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js + npm

### Setup

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Creates database/database.sqlite (the default DB_CONNECTION), runs migrations,
# and seeds roles + a super_admin (see ADMIN_EMAIL/ADMIN_PASSWORD in .env) plus
# the starter university/subject dataset
touch database/database.sqlite
php artisan migrate --seed

# Build frontend assets
npm run dev    # for local development
# or
npm run build  # for production
```

### Running the app

```bash
php artisan serve
```

Visit `http://localhost:8000`, register an account, and you'll land on **Find Universities**. Log in
with the seeded `ADMIN_EMAIL`/`ADMIN_PASSWORD` account to manage universities, subjects, and degree
programs from the admin navigation.

## Data sources & limitations

The requested source, `studyinitaly.esteri.it/Static/Procedureiscrizione`, turned out to be a static
procedural guide (visa steps, credential recognition) with no actual per-university data — it just
points to the [Universitaly](https://www.universitaly.it) portal. Universitaly itself is a
client-side JavaScript app with no discoverable public API, so it can't be scraped with plain HTTP
requests; pulling live data from it requires a headless-browser-capable environment.

Given that, this app ships with:

- A **curated seed dataset** (`database/seeders/UniversitySeeder.php`,
  `database/seeders/DegreeProgramSeeder.php`) of real Italian universities and degree programs, with
  general — not fabricated per-program — admission guidance. Every listing links back to
  `studyinitaly.esteri.it` and `universitaly.it` so a prospective student can verify current details.
- A **pluggable importer** (`App\Contracts\UniversityDataImporter`, run via
  `php artisan universities:import`) so a real Universitaly-backed importer can be dropped in later
  once browser/scraping tooling is available, without changing anything else in the app.
- Full **admin CRUD** for universities, subjects, and degree programs, so data can also be
  added/corrected by hand in the meantime.

## Publishing this project to GitHub

This project is a fresh Git repository. Before publishing it, check that no passwords, API keys, or
other confidential data are included. The `.env` file is ignored and must not be committed.

1. Sign in to [GitHub](https://github.com) and select **New repository**.
2. Enter a repository name, such as `unihup`, and choose **Private** or **Public**.
3. Leave **Add a README**, **Add .gitignore**, and **Choose a license** disabled, because this local
   project already contains Git files.
4. Create the repository, then copy its HTTPS URL.
5. From this project's directory, run the following commands, replacing the URL with the one GitHub
   provides:

```bash
git status
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/unihup.git
git push -u origin main
```

If Git reports that `origin` already exists, update it instead:

```bash
git remote set-url origin https://github.com/YOUR-USERNAME/unihup.git
git push -u origin main
```

GitHub may ask you to authenticate in a browser or use a personal access token; your normal GitHub
password cannot be used as an HTTPS Git password.

For later updates, use:

```bash
git add .
git commit -m "Describe the changes"
git push
```

Do not commit `.env`. Other developers should create it locally from `.env.example`, then install and
configure the application in their own environment.

## License

This project is proprietary. All rights reserved.
