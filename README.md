<p align="center">
  <img src="media/images/logo.svg" alt="Movie Listings" width="460">
</p>

<p align="center">
  <a href="https://github.com/npsaltakis/Movie-Listings/releases"><img src="https://img.shields.io/github/v/release/npsaltakis/Movie-Listings?label=release" alt="Latest release"></a>
  <img src="https://img.shields.io/badge/Joomla-5.x%20%7C%206.x-5091cd" alt="Joomla 5 / 6">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777bb4" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/license-GPL--2.0--or--later-blue" alt="License">
</p>

# Movie Listings (com_movielist)

A custom **Joomla 5 / 6** component for **film‑festival** listing sites — built as a modern replacement for Mosets Tree. Multiple directories, unlimited hierarchical categories, a unified custom‑field manager, movie galleries, and pretty SEF URLs.

> Originally built for the **Athens International Digital Film Festival**.

## ✨ Features

- **Multiple directories** — each one a top‑level container (e.g. a festival, or a single edition/year).
- **Unlimited hierarchical categories** — adjacency‑list tree (parent / level / path), scoped per directory.
- **Movies** — poster, original title, year, duration, country, language, synopsis, trailer, and full director profile (name, photo, bio).
- **Unified Field Manager** — system fields (year, director, synopsis…) and your own custom fields live in **one orderable list**. Every field is drag‑sortable, can be renamed, and toggled *Show in list* / *Show in detail*. Nothing is locked.
- **Repeatable & group fields** — any custom field can repeat (with an optional max), either as a single value per row or a **group** of sub‑fields per row (e.g. *Cast = Name + Role*).
- **Image gallery** — per‑movie stills / behind‑the‑scenes / extra posters, managed from the movie editor and rendered on the detail page.
- **Frontend search & filter** — free‑text search (title, director, and searchable custom fields) plus category and year filters.
- **SEF router** — clean URLs for the categories grid, movie lists, and single movies.
- **Live updates** — upgrade straight from GitHub releases via Joomla's **Extensions → Update**.

## ✅ Requirements

| | |
|---|---|
| Joomla | 5.x or 6.x |
| PHP | 8.2 or newer |
| Database | MySQL 5.7+ / MariaDB 10.4+ (JSON columns) |

## 📦 Installation

1. Download `com_movielist.zip` from the [latest release](https://github.com/npsaltakis/Movie-Listings/releases/latest).
2. In Joomla: **System → Install → Extensions → Upload Package File**, then drop the zip in.

## 🔄 Updating

This component ships an update server pointed at its GitHub releases, so you can update from inside Joomla:

1. Go to **System → Update → Extensions** (Find Updates if needed).
2. When a new release is published, *Movie Listings* appears in the list — select it and click **Update**.

Each new tagged release (`vX.Y.Z`) that attaches a `com_movielist.zip` asset and is reflected in [`updates.xml`](updates.xml) is offered automatically.

## 🚀 Usage

After installing, open **Components → Movie Listings** in the admin:

1. Create a **Directory** (e.g. your festival).
2. Add **Categories** under it (e.g. *Competition*, *Out of Competition*, or per year).
3. Define any extra **Custom Fields** and order them as you want them to appear.
4. Add **Movies**, fill in the details, the gallery, and custom fields.

On the front end, create menu items of type **Listings (Categories Grid)** or **Movies List** (which can be scoped to a directory or category).

## 🛠️ Building from source

The repository layout *is* the package layout. To build an installable zip:

```bash
zip -r com_movielist.zip movielist.xml script.php admin site media updates.xml
```

(or use the equivalent on Windows). Bump `<version>` in `movielist.xml` and add a matching `sql/updates/mysql/<version>.sql` when the schema changes; `script.php`'s postflight reseeds the system fields idempotently.

## 📁 Structure

```
movielist.xml          Manifest (incl. <updateservers>)
updates.xml            Update server feed for GitHub releases
script.php             Install/update script (seeds system fields)
admin/                 Backend: MVC, forms, SQL, language
site/                  Frontend: MVC, templates, SEF router, language
media/                 CSS / JS / images (logo)
```

## 📝 License

GNU General Public License v2.0 or later — see [`LICENSE`](LICENSE) if present, or <https://www.gnu.org/licenses/gpl-2.0.html>.

---

<sub>© 2026 Nick Psaltakis</sub>
