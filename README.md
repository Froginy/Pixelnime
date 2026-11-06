# Pixelnime

Devine le personnage d'anime caché derrière une image pixelisée. Tu as 5 essais, et l'image devient plus nette à chaque erreur. À la fin de la partie, elle s'affiche en pleine qualité.

- **Daily** : le même personnage pour tout le monde, un par jour (façon Wordle).
- **Mode infini** : enchaîne les personnages, filtrés par difficulté (popularité) ou par animes.

Application Laravel 13 + Livewire 4 + Tailwind CSS 4. Les personnages viennent de l'API [AniList](https://anilist.co).

## Prérequis

- PHP 8.3 ou plus, avec les extensions `pdo_sqlite` et `gd`
- Composer
- Node.js et npm
- Une connexion internet pour l'import des personnages

## Installation

```bash
git clone https://github.com/Froginy/Pixelnime.git
cd Pixelnime
composer run setup
```

`composer run setup` fait tout, dans cet ordre :

1. installe les dépendances PHP et JS ;
2. crée le fichier `.env` à partir de `.env.example` ;
3. génère la clé de l'application ;
4. crée la base SQLite (`database/database.sqlite`) en lançant les migrations ;
5. crée le lien `public/storage` ;
6. **fait un import rapide depuis AniList** (environ 300 personnages sur une trentaine d'animes, en 2 à 3 minutes), suffisant pour tester ;
7. compile les assets.

> La base de données, les images et le fichier `.env` ne sont pas versionnés : chaque personne qui clone le projet doit lancer cette étape.

### Import complet

```bash
php artisan pixelnime:import
```

Importe les 300 personnages les plus populaires, qui déterminent les animes du jeu (environ 130), puis complète chaque anime avec jusqu'à 80 personnages. On obtient environ 3 200 personnages, mais il faut compter une vingtaine de minutes à cause de la limite de requêtes d'AniList.

Autres options :

```bash
php artisan pixelnime:import --quick                      # import rapide (celui du setup)
php artisan pixelnime:import --anime="Gachiakuta"         # ajoute un anime précis
php artisan pixelnime:import --anime="Gachiakuta" --per-anime=200 --min-favourites=1   # avec tous ses personnages
```

La commande peut être relancée sans risque. Elle met à jour les personnages existants et recalcule les difficultés selon la popularité : les 15 % les plus populaires sont Faciles, les 35 % suivants Moyens, et le reste Difficile.

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
