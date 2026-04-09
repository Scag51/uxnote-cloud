# UX Note Cloud ✏️

**Outil de relecture collaborative de sites web** — développé par [Équinoxes](https://equinoxes.fr)

Un widget léger à intégrer sur n'importe quel site WordPress qui permet à vos clients de laisser des annotations directement sur les pages, avec un dashboard de gestion centralisé.

---

## Fonctionnalités

- **Widget flottant** — bouton "Annoter" en bas à gauche, panel qui s'ouvre à droite
- **Annotations pointées** — clic sur la page pour placer un pin numéroté
- **Numérotation par position** — le commentaire le plus haut sur la page = #1
- **Mot de passe optionnel** — via `data-password` dans le snippet
- **Pièces jointes** — upload de fichiers jusqu'à 5 Mo par annotation
- **Répondre** à un commentaire
- **Supprimer ses propres annotations** — identification par token localStorage
- **Archivage de projet** — widget grisé côté client, onglet Archives dans le dashboard
- **Dashboard protégé** — écran de login simple
- **Journal des actions** — toutes les actions loggées avec filtre par date
- **Sauvegarde automatique** — vers S3 OVH toutes les heures via `offen/docker-volume-backup`

---

## Stack technique

- **Backend** : PHP 8.2 + SQLite (PDO)
- **Frontend** : JS vanilla + CSS (aucune dépendance)
- **Serveur** : Apache (Docker)
- **Déploiement** : Docker Compose + Coolify
- **Polices** : Raleway / Montserrat (Google Fonts)
- **Charte** : Équinoxes (`#222339`, `#3ce65f`, `#757686`)

---

## Installation sur WordPress

Via **WPCode**, ajoutez ce snippet juste avant `</body>` :

```html
<!-- Sans mot de passe -->
<script
  src="https://uxnote.qoma.fr/js/uxnote-cloud.js"
  data-project-id="nom-du-site">
</script>

<!-- Avec mot de passe -->
<script
  src="https://uxnote.qoma.fr/js/uxnote-cloud.js"
  data-project-id="nom-du-site"
  data-password="moncode">
</script>
```

---

## Déploiement (Coolify)

### Prérequis
- VPS avec Coolify installé
- Dépôt GitHub connecté à Coolify

### Étapes
1. **New Resource** → **Application** → **GitHub**
2. Dépôt `uxnote-cloud` → branche `main`
3. Build Pack → **Docker Compose**
4. Docker Compose Location → `docker-compose.yml`
5. Domaine → `https://uxnote.qoma.fr`
6. Variables d'environnement :
   - `BACKUP_S3_KEY` — Access Key S3
   - `BACKUP_S3_SECRET` — Secret Key S3
7. **Deploy**

### Variables d'environnement disponibles

| Variable | Description | Défaut |
|---|---|---|
| `BACKUP_S3_KEY` | Access Key S3 | — |
| `BACKUP_S3_SECRET` | Secret Key S3 | — |
| `BACKUP_S3_BUCKET` | Nom du bucket | `familiar-couper` |
| `BACKUP_S3_ENDPOINT` | Endpoint S3 | `https://s3.gra.io.cloud.ovh.net/` |

### Changer le mot de passe du dashboard

Dans `public/index.php`, ligne 2 :
```php
define('DASHBOARD_PASSWORD', 'votre-mot-de-passe');
```

---

## Structure du projet

```
uxnote-cloud/
├── api/
│   └── annotations.php     # API REST PHP (GET/POST/PATCH/DELETE)
├── public/
│   ├── index.php            # Dashboard
│   └── js/
│       └── uxnote-cloud.js  # Script client widget
├── data/                    # Volume persistant (SQLite + uploads)
│   ├── uxnote.sqlite
│   └── uploads/
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## Branches

| Branche | Description |
|---|---|
| `main` | Version stable — SQLite + sauvegarde S3 |
| `postgresql` | Version expérimentale — PostgreSQL (en développement) |

---

## Historique des versions

### v4 — Archivage & Journal (avril 2026)
- **Archivage de projet** — bouton "Archiver" dans le dashboard
- **Widget grisé** côté client quand un projet est archivé (lecture seule)
- **Onglet Archives** — annotations archivées, liste des projets, journal archivé
- **Bouton "Réouvrir"** depuis l'onglet Archives
- **Filtre logs par plage de dates** dans le Journal
- **Sauvegarde S3 automatique** via `offen/docker-volume-backup` (toutes les heures)
- Table `projects` ajoutée en base de données

### v3 — Corrections & UX (avril 2026)
- **Bug "Répondre" corrigé** — textarea avec ID unique par annotation
- **Panel à droite** — bouton "Annoter" à gauche, panel qui s'ouvre à droite
- **Numérotation par position Y** — le commentaire le plus haut = #1
- **Boutons blancs** — "Résoudre" et poubelle discrets
- **Icône œil** sur le bouton "Voir"
- **Filtre par auteur** dans le dashboard
- **Lignes dépliables** dans le dashboard pour lire le commentaire en entier
- **Réponses affichées** dans le détail déplié

### v2 — Fonctionnalités collaboratives (avril 2026)
- **Charte graphique Équinoxes** — `#222339`, `#3ce65f`, Raleway/Montserrat
- **Mot de passe dashboard** — écran de login simple
- **Mot de passe widget** — via `data-password` dans le snippet
- **Upload fichier** — pièce jointe jusqu'à 5 Mo par annotation
- **Bouton télécharger** dans le dashboard
- **Répondre à un commentaire** — fil de réponses
- **Suppression de ses propres annotations** — via token localStorage
- **Journal des actions** — onglet dédié dans le dashboard
- Table `replies`, `logs` ajoutées en base de données
- Colonnes `author_token`, `file_name`, `file_path`, `file_size` ajoutées

### v1 — Version initiale (avril 2026)
- Widget JS vanilla avec bouton flottant
- Annotations pointées sur la page (pins numérotés)
- API REST PHP avec SQLite
- Dashboard simple avec tableau des annotations
- Filtre par projet et statut
- Résolution des annotations (open/resolved)
- Déploiement Docker sur Coolify
- Basé sur [UX Note](https://github.com/nicholasgasior/uxnote) (MIT)

---

## Licence

MIT — Développé par [Équinoxes](https://equinoxes.fr)
