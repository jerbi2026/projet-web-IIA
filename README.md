# 🌍 Escales Tunisiennes — Site Web Touristique

> Projet Web réalisé dans le cadre du module **Programmation Web et Multimédia**  
> Binôme : **Jerbi Ahmed** & **Hazem Ben Henia** — 2025/2026

---

## 📋 Description du Projet

**Escales Tunisiennes** est un site web touristique complet présentant une agence de voyage spécialisée dans le tourisme en Tunisie. Le site permet aux visiteurs de découvrir les destinations, consulter les circuits disponibles, réserver un voyage en ligne, déposer des témoignages, et contacter l'agence. Le projet inclut un backend PHP complet avec système d'authentification administrateur et base de données MySQL.

---

## Structure des Fichiers

```
escales-tunisiennes/
│
├── Pages Frontend
│   ├── index.html          # Page d'accueil
│   ├── about.html          # Notre Agence (histoire & équipe)
│   ├── products.html       # Catalogue des circuits
│   ├── gallery.html        # Galerie photo
│   ├── order.html          # Formulaire de réservation (multi-étapes)
│   ├── faq.html            # Foire aux questions
│   ├── testimonials.html   # Avis clients
│   └── contact.html        # Formulaire de contact
│
├── Backend PHP
│   ├── admin/              # Panneau d'administration
│   │   ├── index.php       # Tableau de bord admin
│   │   ├── reservations.php # Gestion des réservations
│   │   ├── testimonials.php # Modération des témoignages
│   │   ├── messages.php    # Messages de contact
│   │   └── admin_style.css # Styles admin
│   │
│   ├── api/                # Endpoints API
│   │   ├── submit_order.php       # Traitement réservations
│   │   ├── submit_contact.php     # Messages contact
│   │   └── submit_testimonial.php # Dépôt témoignages
│   │
│   ├── auth/               # Authentification
│   │   ├── login.php       # Connexion admin
│   │   ├── logout.php      # Déconnexion
│   │   └── session_check.php # Vérification session
│   │
│   ├── config/             # Configuration
│   │   ├── database.php    # Connexion BDD
│   │   ├── db.php          # Configuration base
│   │   └── auth.php        # Configuration auth
│   │
│   └── ...
│
├── style.css           # Feuille de styles principale (CSS3)
├── database.sql        # Structure et données initiales MySQL
│
└── src/                # Ressources locales (images)
    ├── djerba.jpg
    ├── douz.jpg
    ├── Le port de Bizerte.jpg
    ├── Sidi-Bou-Said.jpg
    └── ...
```

---

## Pages du Site

| Page | Fichier | Contenu principal |
|------|---------|-------------------|
| Accueil | `index.html` | Hero, stats, destinations, circuits, témoignages |
| Notre Agence | `about.html` | Histoire, timeline, équipe |
| Nos Voyages | `products.html` | 6 circuits avec filtres dynamiques |
| Galerie | `gallery.html` | Galerie photo avec lightbox |
| Réserver | `order.html` | Formulaire 3 étapes avec validation JS |
| FAQ | `faq.html` | Questions par catégorie + recherche |
| Avis | `testimonials.html` | Témoignages + formulaire de dépôt d'avis |
| Contact | `contact.html` | Coordonnées + formulaire de contact |

---

## ✅ Éléments HTML Couverts

- Textes, titres (`h1` à `h4`), paragraphes
- Images (`<img>`) avec attributs `alt` et `loading="lazy"`
- Tableaux (`<table>`, `<thead>`, `<tbody>`) — FAQ, Contact, About
- Listes ordonnées et non ordonnées (`<ul>`, `<ol>`, `<li>`)
- Liens hypertextes internes et CTA (`<a href="...">`)
- Vidéos / vignettes multimédia — Galerie
- Formulaires complets : `<input>`, `<select>`, `<textarea>`, `<checkbox>`, `<radio>`, `<button>`
- Balises sémantiques : `<header>`, `<nav>`, `<section>`, `<footer>`, `<div>`, `<span>`

---

## 🎨 Technologies & CSS3

- **Flexbox** : navbar, footer, sections « pourquoi nous »
- **CSS Grid** : grille produits, galerie, équipe
- **Variables CSS** (`--ocre`, `--sapphire-dark`, `--cream`...) pour cohérence visuelle
- **Position** (`fixed`, `absolute`, `relative`) : navbar sticky, badges, overlays
- **Transitions & Animations** : hover cartes, parallaxe hero
- **Media Queries** : responsive tablette et mobile
- **Transform** : effets zoom au survol
- **Typographies Google Fonts** : Cormorant Garamond + Raleway

---

## ⚙️ Fonctionnalités JavaScript

| Fonctionnalité | Page |
|---------------|------|
| Filtres dynamiques (catégories) | `products.html`, `gallery.html` |
| Formulaire multi-étapes (3 étapes) | `order.html` |
| Validation de saisie (email, champs obligatoires) | `order`, `contact`, `testimonials` |
| Lightbox avec navigation clavier | `gallery.html` |
| Accordéon FAQ (toggle) | `faq.html` |
| Recherche FAQ en temps réel | `faq.html` |
| Notation étoilée interactive | `testimonials.html` |
| Messages de confirmation post-soumission | `contact`, `order`, `testimonials` |

---

## � Backend PHP & Base de Données

### Architecture Backend
- **PHP 8.x** : Traitement serveur, API REST
- **MySQL 8.x** : Stockage des données (réservations, témoignages, messages)
- **Sessions PHP** : Authentification administrateur sécurisée
- **PDO** : Connexion sécurisée à la base de données

### Fonctionnalités Backend
| Module | Fichiers | Fonctionnalités |
|--------|----------|-----------------|
| **API** | `api/submit_*.php` | Traitement des formulaires (réservations, contact, témoignages) |
| **Admin** | `admin/*.php` | Tableau de bord, gestion réservations, modération témoignages |
| **Auth** | `auth/*.php` | Connexion/déconnexion administrateur, vérification sessions |
| **Config** | `config/*.php` | Configuration BDD, paramètres authentification |

### Base de Données MySQL
- **admins** : Comptes administrateurs (id, username, password_hash)
- **circuits** : Catalogue des voyages (nom, catégorie, prix, description)
- **reservations** : Réservations clients (infos client, dates, statut)
- **testimonials** : Témoignages clients (note, message, validation)
- **contact_messages** : Messages du formulaire de contact

---

## 🚀 Lancement du Projet

### Prérequis
- **Serveur web** (Apache/Nginx) avec PHP 8.x
- **Base de données** MySQL 8.x
- **Navigateur web** moderne

### Installation

1. **Cloner le projet**
```bash
git clone [repository-url]
cd escales-tunisiennes
```

2. **Configurer la base de données**
```bash
mysql -u root -p < database.sql
```

3. **Configurer la connexion BDD**
Éditer `config/db.php` avec vos identifiants MySQL

4. **Lancer le serveur web**
```bash
# Avec PHP built-in server
php -S localhost:8000

# Ou avec Apache/Nginx
# Placer les fichiers dans le répertoire web du serveur
```

5. **Accéder à l'application**
- **Site public** : http://localhost:8000
- **Administration** : http://localhost:8000/admin/
  - Identifiant : `admin`
  - Mot de passe : `admin123`

### Utilisation sans backend
Pour tester uniquement le frontend (HTML/CSS/JS) :
```bash
# Ouvrir directement dans un navigateur
open index.html
```


---

## 👥 Auteurs

| Nom | Rôle |
|-----|------|
| **Jerbi Ahmed** | Développement HTML/CSS/JS, conception UI, backend PHP/MySQL |
| **Hazem Ben Henia** | Développement HTML/CSS/JS, conception UI, backend PHP/MySQL |

---

## 📅 Livrables

- **Livrable 1** (semaine du 06/04/2026) : ✅ Pages HTML + CSS complètes
- **Livrable 2** (semaine du 04/05/2026) : ✅ Interactivité JS + intégration PHP/MySQL

### 🎯 Projet Terminé
Le projet est maintenant **complètement fonctionnel** avec :
- Frontend responsive et interactif
- Backend PHP complet avec API REST
- Base de données MySQL intégrée
- Panneau d'administration sécurisé
- Système de réservations et témoignages

---

## 📝 Licence

Projet académique — ENSI 2025/2026. Tous droits réservés.