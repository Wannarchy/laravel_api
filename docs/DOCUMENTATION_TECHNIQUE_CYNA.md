# Documentation technique — CYNA

**Version :** 1.0  
**Date :** 12 juin 2026  
**Projets :** `cyna-api` (backend) + `Cyna_front` (frontend)  
**URL API production :** https://laravel-api-1-zb19.onrender.com

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture globale](#2-architecture-globale)
3. [Backend — cyna-api](#3-backend--cyna-api)
4. [Frontend — Cyna_front](#4-frontend--cyna_front)
5. [Référence API REST](#5-référence-api-rest)
6. [Authentification](#6-authentification)
7. [Paiements et abonnements (Stripe / Cashier)](#7-paiements-et-abonnements-stripe--cashier)
8. [Base de données](#8-base-de-données)
9. [Déploiement](#9-déploiement)
10. [Variables d'environnement](#10-variables-denvironnement)
11. [Sécurité](#11-sécurité)
12. [Annexes](#12-annexes)

---

## 1. Vue d'ensemble

CYNA est une plateforme e-commerce SaaS de cybersécurité (SOC, EDR, XDR) composée de **deux applications distinctes** :

| Composant | Dossier | Rôle |
|-----------|---------|------|
| **API Backend** | `cyna-api/` | API REST Laravel, logique métier, BDD, paiements |
| **Frontend web** | `Cyna_front/` | Site PHP consommateur de l'API via HTTP |

Les deux projets cohabitent dans le même dépôt Git mais sont **traités comme des applications séparées** : le frontend n'accède plus directement à MySQL pour le parcours principal ; il communique exclusivement avec l'API Laravel.

---

## 2. Architecture globale

```
┌─────────────────────────────────────────────────────────────────┐
│                        UTILISATEUR                               │
└────────────────────────────┬────────────────────────────────────┘
                             │ HTTPS
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  Cyna_front (PHP 7.4+)                                          │
│  ├── index.php, public/*.php                                    │
│  ├── includes/api_client.php  ──► cURL JSON                     │
│  ├── Session PHP (token Sanctum)                                │
│  └── Stripe.js (Payment Method côté client)                     │
└────────────────────────────┬────────────────────────────────────┘
                             │ HTTPS /api/*
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  cyna-api (Laravel 13 / PHP 8.4)                                │
│  ├── Routes REST (/api/*)                                       │
│  ├── Laravel Sanctum (auth token)                               │
│  ├── Laravel Cashier (Stripe)                                   │
│  ├── Queue worker (emails, notifications)                       │
│  └── Webhooks Stripe (/stripe/webhook)                          │
└────────────────────────────┬────────────────────────────────────┘
                             │ PostgreSQL (Supabase)
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  Supabase PostgreSQL                                            │
│  Pooler : aws-0-eu-west-1.pooler.supabase.com:5432              │
└─────────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  Stripe (paiements, abonnements, webhooks)                      │
└─────────────────────────────────────────────────────────────────┘
```

### Flux principal — commande

```
1. Utilisateur ajoute des produits au panier (session PHP)
2. Checkout → Stripe.js crée un Payment Method (pm_xxx)
3. Cyna_front → POST /api/orders { payment_method, items, billing_* }
4. cyna-api → Cashier crée abonnement Stripe + commande locale
5. Webhook Stripe → synchronise renouvellements / annulations
6. Cyna_front affiche confirmation + mes-commandes via GET /api/orders
```

---

## 3. Backend — cyna-api

### 3.1 Stack technique

| Technologie | Version | Usage |
|-------------|---------|-------|
| PHP | ^8.4 | Runtime |
| Laravel | ^13.8 | Framework API |
| Laravel Sanctum | ^4.3 | Authentification token |
| Laravel Cashier | ^16.5 | Paiements Stripe |
| PostgreSQL | 17.x | Base de données (Supabase) |
| Docker | PHP 8.4-cli | Conteneur Render |
| Stripe API | Basil (2025-06-30) | Paiements |

### 3.2 Structure des dossiers

```
cyna-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/          # Contrôleurs publics
│   │   ├── Controllers/Api/Admin/    # Contrôleurs admin
│   │   ├── Middleware/               # IsAdmin, EnsureUserIsActive
│   │   ├── Requests/                 # Validation FormRequest
│   │   └── Resources/                # Transformateurs JSON
│   ├── Models/                       # Eloquent (User, Product, Order…)
│   ├── Services/
│   │   └── OrderFulfillmentService.php
│   ├── Listeners/
│   │   └── StripeWebhookListener.php
│   └── Notifications/                # Emails (vérification, reset…)
├── bootstrap/app.php                 # Routing API prefix /api
├── config/
│   ├── cashier.php                   # Config Stripe
│   └── ...
├── database/migrations/              # Schéma PostgreSQL
├── docker/
│   └── entrypoint.sh                 # Migrations + cache au démarrage
├── Dockerfile                        # Image Render
├── render.yaml                       # Blueprint Render (web + worker)
└── routes/api.php                    # Toutes les routes API
```

### 3.3 Modèle utilisateur

- Table : `utilisateurs` (pas `users`)
- Modèle : `App\Models\User` avec trait `Billable` (Cashier)
- Authentification : mot de passe dans `mot_de_passe`
- Champs métier : `prenom`, `nom`, `est_confirme`, `is_admin`, `est_actif`

### 3.4 Middlewares

| Alias | Classe | Rôle |
|-------|--------|------|
| `auth:sanctum` | Sanctum | Token Bearer requis |
| `active` | `EnsureUserIsActive` | Compte actif (`est_actif = true`) |
| `admin` | `IsAdmin` | Utilisateur admin (`is_admin = true`) |

### 3.5 Services métier clés

**OrderFulfillmentService** — centralise :
- Calcul des lignes de commande et totaux
- Application des codes promo
- Création commande + `product_subscriptions`
- Fulfillment post-checkout Stripe (webhook)

**StripeWebhookListener** — écoute :
- `checkout.session.completed`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`

### 3.6 Tâches planifiées (Scheduler)

| Tâche | Fréquence | Action |
|-------|-----------|--------|
| `subscriptions:renewal-reminders` | Quotidien | Email J-1 avant renouvellement |
| `promo-codes:deactivate-expired` | Quotidien | Désactive promos expirées |
| `users:purge-expired-reset-tokens` | Quotidien | Nettoie tokens reset expirés |

### 3.7 Health check

```
GET /up  → 200 "Application up"
```

Utilisé par Render (`healthCheckPath: /up`).

---

## 4. Frontend — Cyna_front

### 4.1 Stack technique

| Technologie | Usage |
|-------------|-------|
| PHP 7.4+ | Pages serveur |
| Bootstrap 5.3 | UI responsive |
| Stripe.js v3 | Tokenisation cartes (Payment Method) |
| PHPMailer | Emails locaux (legacy admin) |
| cURL | Appels API JSON |
| Sessions PHP | Panier, auth, token API |

### 4.2 Structure des dossiers

```
Cyna_front/
├── index.php                 # Page d'accueil
├── config/
│   ├── api.php               # URL API + chargement .env
│   ├── config.php            # Bootstrap (sans MySQL)
│   └── logout.php            # Déconnexion API
├── includes/
│   ├── api_client.php        # Client HTTP ApiClient
│   ├── home_repository.php   # Données accueil (via API)
│   ├── catalog_repository.php
│   ├── product_repository.php
│   ├── cart_repository.php
│   ├── function.php          # Helpers session, CSRF
│   └── lang.php              # i18n (fr, en, ar, he)
├── public/
│   ├── connexion.php         # Login → API
│   ├── inscription.php       # Register → API
│   ├── catalogue.php
│   ├── produit.php
│   ├── panier.php
│   ├── checkout.php          # Stripe Elements
│   ├── checkout_submit.php   # POST /api/orders
│   ├── mes-commandes.php
│   ├── mes-abonnements.php
│   └── ...
├── admin/                    # Backoffice (MySQL local — legacy)
└── .env.example              # CYNA_API_URL=...
```

### 4.3 Client API (`includes/api_client.php`)

Classe `ApiClient` — méthodes principales :

| Méthode | Endpoint API |
|---------|-------------|
| `login()` | POST `/auth/login` |
| `register()` | POST `/auth/register` |
| `logout()` | POST `/auth/logout` |
| `getHomepage()` | GET `/homepage` |
| `getCategories()` | GET `/categories` |
| `getProducts()` | GET `/products` |
| `getProduct($id)` | GET `/products/{id}` |
| `createOrder()` | POST `/orders` |
| `getOrders()` | GET `/orders` |
| `getSubscriptions()` | GET `/subscriptions` |
| `cancelSubscription()` | POST `/subscriptions/{id}/cancel` |
| `getPaymentMethods()` | GET `/payment-methods` |
| `validatePromoCode()` | POST `/promo-codes/validate` |
| `getBillingConfig()` | GET `/billing/config` |
| `createCheckout()` | POST `/billing/checkout` |

### 4.4 Session PHP — clés utilisées

| Clé session | Contenu |
|-------------|---------|
| `api_token` | Token Sanctum Bearer |
| `utilisateur_id` | ID utilisateur |
| `utilisateur_prenom` | Prénom |
| `utilisateur_nom` | Nom |
| `utilisateur_email` | Email |
| `is_admin` | 0 ou 1 |
| `cart` | Panier `{ product_id: { cycle, qty } }` |
| `panier` | Panier legacy (accueil) |

### 4.5 Pages connectées à l'API

| Page | Statut |
|------|--------|
| Accueil, catalogue, produit | ✅ API |
| Connexion, inscription, logout | ✅ API |
| Checkout, commandes, abonnements | ✅ API |
| Codes promo | ✅ API |
| Admin (`admin/*`) | ⚠ MySQL local (non migré) |

### 4.6 Configuration frontend

Fichier `Cyna_front/.env` :

```env
CYNA_API_URL=https://laravel-api-1-zb19.onrender.com
```

---

## 5. Référence API REST

**Base URL :** `https://laravel-api-1-zb19.onrender.com/api`  
**Format :** JSON  
**Headers requis :**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}   # routes protégées
```

### 5.1 Auth (public)

| Méthode | Route | Body | Réponse |
|---------|-------|------|---------|
| POST | `/auth/register` | `prenom, nom, email, password, password_confirmation` | 201 `{ data: { user, token } }` |
| POST | `/auth/login` | `email, password` | 200 `{ data: { user, token } }` |
| POST | `/auth/forgot-password` | `email` | 200 message |
| POST | `/auth/reset-password` | `email, token, password, password_confirmation` | 200 message |
| POST | `/auth/verify-email` | `id, token` | 200 message |

### 5.2 Catalogue (public)

| Méthode | Route | Query params |
|---------|-------|-------------|
| GET | `/products` | `category_id`, `is_featured` |
| GET | `/products/{id}` | — |
| GET | `/categories` | — |
| GET | `/homepage` | — |
| GET | `/billing/config` | Retourne `stripe_key` |

### 5.3 Auth (protégé — token requis)

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/auth/logout` | Révoque le token |
| POST | `/auth/resend-verification` | Renvoie email confirmation |

### 5.4 Billing (protégé)

| Méthode | Route | Body |
|---------|-------|------|
| GET | `/billing/setup-intent` | — |
| POST | `/billing/checkout` | `billing_name, billing_address, success_url, cancel_url, items[]` |
| POST | `/billing/checkout/success` | `session_id` |

### 5.5 Commandes (protégé)

| Méthode | Route | Body (POST) |
|---------|-------|-------------|
| GET | `/orders` | — |
| GET | `/orders/{id}` | — |
| POST | `/orders` | Voir exemple ci-dessous |

**Exemple POST `/orders` :**
```json
{
  "billing_name": "Jean Dupont",
  "billing_address": "12 rue Example, 75008 Paris",
  "payment_method": "pm_1XXXXXXXX",
  "promo_code": "PROMO10",
  "items": [
    { "product_id": 1, "cycle": "monthly" },
    { "product_id": 2, "cycle": "yearly" }
  ]
}
```

### 5.6 Abonnements (protégé)

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/subscriptions` | Liste abonnements produits |
| POST | `/subscriptions/{id}/cancel` | Annule (Stripe + local) |

### 5.7 Adresses (protégé)

| Méthode | Route |
|---------|-------|
| GET | `/addresses` |
| POST | `/addresses` |
| PUT | `/addresses/{id}` |
| DELETE | `/addresses/{id}` |

### 5.8 Moyens de paiement (protégé)

| Méthode | Route | Body (POST) |
|---------|-------|-------------|
| GET | `/payment-methods` | — |
| POST | `/payment-methods` | `{ "payment_method": "pm_xxx" }` |
| DELETE | `/payment-methods/{id}` | — |

### 5.9 Promo codes (protégé)

| Méthode | Route | Body |
|---------|-------|------|
| POST | `/promo-codes/validate` | `{ "code": "PROMO10", "amount": 99.99 }` |

### 5.10 Chat (protégé)

| Méthode | Route |
|---------|-------|
| POST | `/chat` |
| GET | `/chat/history` |

### 5.11 Admin (protégé — admin requis)

Préfixe : `/admin`

| Ressource | CRUD |
|-----------|------|
| `/users` | GET, GET/{id}, PUT/{id}, DELETE/{id} |
| `/products` | GET, POST, PUT/{id}, DELETE/{id} |
| `/categories` | GET, POST, PUT/{id}, DELETE/{id} |
| `/orders` | GET, GET/{id}, PATCH/{id}/status |
| `/promo-codes` | GET, POST, PUT/{id}, DELETE/{id} |
| `/homepage/slides` | PUT |
| `/homepage/content` | PUT |
| `/chat-logs` | GET |

### 5.12 Webhook Stripe (Cashier)

```
POST /stripe/webhook
```

Géré automatiquement par Laravel Cashier.  
Secret : variable `STRIPE_WEBHOOK_SECRET`.

---

## 6. Authentification

### 6.1 Mécanisme

- **Laravel Sanctum** — tokens personnels (Bearer)
- Durée : pas d'expiration par défaut (token révocable via logout)
- Table : `personal_access_tokens`

### 6.2 Flux inscription

```
1. POST /api/auth/register
2. API crée utilisateur + envoie email vérification (queue)
3. Retourne token + user (est_confirme: false)
4. POST /api/auth/verify-email { id, token }
```

### 6.3 Flux connexion (frontend)

```
1. Utilisateur soumet connexion.php
2. api_client()->login(email, password)
3. Token stocké en $_SESSION['api_token']
4. Pages protégées envoient Authorization: Bearer {token}
```

### 6.4 Modèle User — champs auth

```php
// Table utilisateurs
id, prenom, nom, email, mot_de_passe,
est_confirme, token_confirmation,
is_admin, est_actif,
stripe_id, pm_type, pm_last_four,  // Cashier
trial_ends_at
```

---

## 7. Paiements et abonnements (Stripe / Cashier)

### 7.1 Architecture double table abonnements

| Table | Rôle |
|-------|------|
| `subscriptions` | Abonnements **Stripe** (Cashier) |
| `product_subscriptions` | Abonnements **métier CYNA** (lien produit/commande) |

### 7.2 Produits Stripe

Chaque produit CYNA doit avoir :

| Champ DB | Description |
|----------|-------------|
| `stripe_product_id` | ID produit Stripe |
| `stripe_price_id_monthly` | Price ID mensuel |
| `stripe_price_id_yearly` | Price ID annuel |

Configurables via admin API `PUT /admin/products/{id}`.

### 7.3 Flux paiement direct (frontend actuel)

```
1. GET /api/billing/config → clé publique Stripe
2. Stripe.js → createPaymentMethod → pm_xxx
3. POST /api/orders { payment_method: "pm_xxx", items: [...] }
4. Cashier : newSubscription('product-{id}', priceId)->create(pm)
5. OrderFulfillmentService crée order + product_subscription
```

### 7.4 Flux Stripe Checkout (alternative)

```
1. POST /api/billing/checkout → { checkout_url, session_id }
2. Redirection utilisateur vers Stripe
3. Webhook checkout.session.completed → fulfillCheckoutSession()
4. POST /api/billing/checkout/success { session_id } (fallback)
```

### 7.5 Webhooks écoutés

| Event | Action |
|-------|--------|
| `checkout.session.completed` | Crée commande + abonnements |
| `customer.subscription.deleted` | Marque product_subscription cancelled |
| `invoice.payment_succeeded` | Met à jour next_billing |

### 7.6 Devise

- `CASHIER_CURRENCY=eur`
- `CASHIER_CURRENCY_LOCALE=fr`

---

## 8. Base de données

### 8.1 Connexion production

```
Host : aws-0-eu-west-1.pooler.supabase.com
Port : 5432
Database : postgres
SSL : require
```

> Utiliser le **Session pooler** Supabase (IPv4), pas la connexion directe `db.xxx.supabase.co`.

### 8.2 Tables métier CYNA

| Table | Description |
|-------|-------------|
| `utilisateurs` | Comptes utilisateurs |
| `categories` | Catégories produits |
| `products` | Produits SaaS + IDs Stripe |
| `orders` | Commandes |
| `order_items` | Lignes de commande |
| `product_subscriptions` | Abonnements métier |
| `promo_codes` | Codes promotionnels |
| `user_addresses` | Adresses livraison/facturation |
| `user_payment_methods` | Legacy (remplacé par Stripe) |
| `homepage_slides` | Carrousel accueil |
| `homepage_content` | Texte accueil multilingue |
| `chat_logs` | Historique chatbot |

### 8.3 Tables Laravel / Cashier

| Table | Description |
|-------|-------------|
| `personal_access_tokens` | Tokens Sanctum |
| `subscriptions` | Abonnements Stripe (Cashier) |
| `subscription_items` | Items abonnement Cashier |
| `jobs` | File d'attente |
| `cache` | Cache database |
| `migrations` | Suivi migrations |

### 8.4 Schéma simplifié (relations)

```
utilisateurs ──┬── orders ──── order_items ──── products
               │                    │
               ├── product_subscriptions ── products
               ├── user_addresses
               └── subscriptions (Cashier/Stripe)

categories ──── products
promo_codes (standalone)
homepage_slides, homepage_content (standalone)
```

### 8.5 Migrations

```bash
php artisan migrate
```

Exécutées automatiquement au démarrage Docker (`docker/entrypoint.sh`).

---

## 9. Déploiement

### 9.1 cyna-api — Render

**Fichiers :** `Dockerfile`, `render.yaml`

| Service | Type | Commande |
|---------|------|----------|
| `cyna-api` | Web (Docker) | `php artisan serve --port=$PORT` |
| `cyna-api-queue` | Worker | `php artisan queue:work` |

**Région :** Frankfurt  
**Health check :** `/up`  
**Plan :** Free (cold start ~30-60s)

**Build :**
```bash
docker build -t cyna-api .
```

**Variables obligatoires Render :**
- `APP_URL`, `APP_KEY`
- `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`

### 9.2 Cyna_front — hébergement

Hébergement PHP classique (WAMP, Apache, Nginx + PHP-FPM).

**Prérequis :**
- PHP 7.4+ avec extensions `curl`, `json`, `session`
- Composer (PHPMailer)
- Accès HTTPS recommandé (Stripe)

**Configuration :**
```bash
cd Cyna_front
composer install
cp .env.example .env
# Éditer CYNA_API_URL
```

**Serveur local WAMP :**
```
http://localhost/Cyna_front/
```

### 9.3 Stripe — configuration webhook

```bash
# En local avec Stripe CLI
stripe listen --forward-to localhost:8000/stripe/webhook

# Ou via Cashier
php artisan cashier:webhook
```

URL production :
```
https://laravel-api-1-zb19.onrender.com/stripe/webhook
```

---

## 10. Variables d'environnement

### 10.1 cyna-api (`.env`)

| Variable | Description | Exemple |
|----------|-------------|---------|
| `APP_NAME` | Nom application | `Cyna API` |
| `APP_ENV` | Environnement | `production` |
| `APP_KEY` | Clé chiffrement Laravel | `base64:...` |
| `APP_DEBUG` | Mode debug | `false` |
| `APP_URL` | URL publique API | `https://laravel-api-1-zb19.onrender.com` |
| `DB_CONNECTION` | Driver BDD | `pgsql` |
| `DB_HOST` | Hôte Supabase pooler | `aws-0-eu-west-1.pooler.supabase.com` |
| `DB_PORT` | Port | `5432` |
| `DB_DATABASE` | Nom BDD | `postgres` |
| `DB_USERNAME` | User Supabase | `postgres.xxxxx` |
| `DB_PASSWORD` | Mot de passe | `***` |
| `DB_SSLMODE` | SSL PostgreSQL | `require` |
| `QUEUE_CONNECTION` | Driver queue | `database` |
| `SESSION_DRIVER` | Sessions | `database` |
| `CACHE_STORE` | Cache | `database` |
| `STRIPE_KEY` | Clé publique Stripe | `pk_test_...` |
| `STRIPE_SECRET` | Clé secrète Stripe | `sk_test_...` |
| `STRIPE_WEBHOOK_SECRET` | Secret webhook | `whsec_...` |
| `CASHIER_CURRENCY` | Devise | `eur` |
| `MAIL_*` | Config SMTP emails | — |

### 10.2 Cyna_front (`.env`)

| Variable | Description | Exemple |
|----------|-------------|---------|
| `CYNA_API_URL` | URL base API (sans /api) | `https://laravel-api-1-zb19.onrender.com` |

---

## 11. Sécurité

### 11.1 Backend

- Authentification Sanctum (tokens révocables)
- Middleware `active` — bloque comptes désactivés
- Middleware `admin` — routes admin protégées
- Throttle auth : 60 req/min sur `/auth/*`
- Validation FormRequest sur toutes les entrées
- Mots de passe hashés (bcrypt)
- Webhook Stripe signé (HMAC)
- `APP_DEBUG=false` en production
- SSL PostgreSQL obligatoire

### 11.2 Frontend

- CSRF token sur formulaires POST
- Token Sanctum en session serveur (jamais exposé en JS)
- Stripe.js — cartes tokenisées côté client (PCI DSS)
- `htmlspecialchars()` sur affichages
- Sessions régénérées après login

### 11.3 Recommandations production

- [ ] HTTPS obligatoire sur frontend et API
- [ ] Rotation régulière `STRIPE_WEBHOOK_SECRET`
- [ ] Limiter CORS si frontend JS direct (actuellement cURL serveur)
- [ ] Monitorer logs Render + Supabase
- [ ] Sauvegardes Supabase activées

---

## 12. Annexes

### 12.1 Codes HTTP API

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 201 | Créé (register, order) |
| 401 | Non authentifié |
| 403 | Compte désactivé / non admin |
| 404 | Ressource introuvable |
| 422 | Erreur validation |
| 429 | Rate limit (auth) |
| 500 | Erreur serveur |

### 12.2 Format réponse standard

**Succès :**
```json
{
  "data": { ... },
  "message": "Optionnel"
}
```

**Erreur validation :**
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 12.3 Exemple cURL — login

```bash
curl -X POST "https://laravel-api-1-zb19.onrender.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user@example.com","password":"motdepasse123"}'
```

### 12.4 Exemple cURL — produits

```bash
curl "https://laravel-api-1-zb19.onrender.com/api/products" \
  -H "Accept: application/json"
```

### 12.5 Commandes utiles — développement local API

```bash
# Installation
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Dev (serveur + queue + logs + vite)
composer dev

# Tests
composer test

# Cache production
php artisan config:cache
php artisan route:cache
```

### 12.6 Points d'attention connus

| Sujet | Détail |
|-------|--------|
| Cold start Render | 1ère requête lente (~30-60s) sur plan free |
| Admin frontend | Encore sur MySQL local, non connecté à l'API |
| IPv6 Supabase | Utiliser pooler IPv4, pas connexion directe |
| Queue worker | Obligatoire pour emails (register, reset password) |
| Produits vides | Configurer Stripe Price IDs avant paiement |

---

**Document généré pour le projet CYNA — SUP DE VINCI B3 — Groupe 2**

*CYNA-IT — 10 Rue de Penthièvre, 75008 Paris — SIRET : 91371103200015*
