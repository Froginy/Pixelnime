# Pixelnime

Application Laravel 13 + Livewire 4 + Tailwind CSS 4.

## Prérequis

- PHP 8.3 ou plus (avec l'extension `pdo_sqlite`)
- Composer
- Node.js et npm

## Installation

```bash
git clone https://github.com/Froginy/Pixelnime.git
cd Pixelnime
composer run setup
```

`composer run setup` installe les dépendances PHP et JS, crée le fichier `.env` à partir de `.env.example`, génère la clé de l'application, crée la base SQLite (`database/database.sqlite`) en lançant les migrations, puis compile les assets.

> La base de données et le fichier `.env` ne sont pas versionnés : chaque personne qui clone le projet doit lancer cette étape.

### Données de test (optionnel)

```bash
php artisan db:seed
```

Crée un utilisateur `test@example.com` avec le mot de passe `password`.

## Lancer le projet

```bash
composer run dev
```

## Après un `git pull`

Si quelqu'un a ajouté des migrations ou des dépendances :

```bash
composer install
npm install
php artisan migrate
```

## Tests

```bash
php artisan test
```
