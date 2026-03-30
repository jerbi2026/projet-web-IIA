# 🌍 Escales Tunisiennes — Site Web Touristique

> Projet Web réalisé dans le cadre du module **Programmation Web et Multimédia**  
> Binôme : **Jerbi Ahmed** & **Hazem Ben Henia** — 2025/2026

---

## 📋 Description du Projet

**Escales Tunisiennes** est un site web touristique présentant une agence de voyage spécialisée dans le tourisme en Tunisie. Le site permet aux visiteurs de découvrir les destinations, consulter les circuits disponibles, réserver un voyage en ligne, et contacter l'agence.

---

## 🗂️ Structure des Fichiers

```
escales-tunisiennes/
│
├── index.html          # Page d'accueil
├── about.html          # Notre Agence (histoire & équipe)
├── products.html       # Catalogue des circuits
├── gallery.html        # Galerie photo
├── order.html          # Formulaire de réservation (multi-étapes)
├── faq.html            # Foire aux questions
├── testimonials.html   # Avis clients
├── contact.html        # Formulaire de contact
│
├── style.css           # Feuille de styles principale (CSS3)
│
└── src/                # Ressources locales (images)
    ├── sidi-bou-said.jpg
    ├── douz.jpg
    ├── djerba.jpg
    ├── sahara.avif
    ├── tabarka.jpg
    ├── zaghouan.avif
    ├── escapade.webp
    ├── food.png
    └── ...
```

---

## 📄 Pages du Site

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

## 🚀 Lancement du Projet

Aucune installation requise. Le projet fonctionne en HTML/CSS/JS pur.


# Ouvrir dans un navigateur
open index.html


---

## 👥 Auteurs

| Nom | Rôle |
|-----|------|
| **Jerbi Ahmed** | Développement HTML/CSS/JS, conception UI |
| **Hazem Ben Henia** | Développement HTML/CSS/JS, conception UI |

---

## 📅 Livrables

- **Livrable 1** (semaine du 06/04/2026) : Pages HTML + CSS complètes
- **Livrable 2** (semaine du 04/05/2026) : Interactivité JS + intégration PHP/MySQL

---

## 📝 Licence

Projet académique — ENSI 2025/2026. Tous droits réservés.