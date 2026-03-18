# UX Note Cloud 🖊

Outil de relecture collaborative de sites web — self-hosted, open source, PHP + SQLite + Docker.

Inspiré de [UX Note](https://github.com/ninefortyonestudio/uxnote) (MIT License).

## Fonctionnalités

- 📍 **Annotations pointées** — cliquez n'importe où sur la page pour laisser un commentaire
- 🔄 **Temps réel** — polling toutes les 15s, tous les membres voient les annotations à jour
- ✅ **Résolution** — marquez les annotations comme résolues depuis le site ou le dashboard
- 📊 **Dashboard** — vue globale de tous les projets, filtres, statistiques
- 👤 **Sans compte** — les clients annotent juste avec leur prénom (pas de création de compte)
- 🔒 **100% souverain** — vos données restent sur votre VPS

## Déploiement sur Coolify

### 1. Préparez le dépôt

Poussez ce dossier sur votre dépôt Git (GitHub, Gitea, etc.).

### 2. Dans Coolify

1. Nouveau projet → **New Resource** → **Docker Compose**
2. Pointez sur votre dépôt Git
3. Coolify détecte automatiquement le `docker-compose.yml`
4. Configurez votre domaine (ex: `uxnote.votre-agence.fr`)
5. Déployez ✓

### 3. Vérifiez la persistance

Dans Coolify, assurez-vous que le volume `uxnote_data` est bien monté — c'est là que SQLite stocke les données.

---

## Intégration sur vos sites WordPress

Ajoutez ce snippet **juste avant `</body>`** sur chaque site à annoter.

Via le plugin "Insert Headers and Footers" (WPCode) ou dans `functions.php` :

```html
<script
  src="https://uxnote.votre-agence.fr/js/uxnote-cloud.js"
  data-project-id="nom-du-site-client">
</script>
```

- `data-project-id` : identifiant du projet (ex: `client-dupont`, `site-mairie-xyz`)
- Un bouton "Annoter" apparaît en bas à droite de la page

---

## Structure des fichiers

```
uxnote-cloud/
├── Dockerfile
├── docker-compose.yml
├── README.md
├── api/
│   └── annotations.php      ← API REST (GET/POST/PATCH/DELETE)
├── public/
│   ├── .htaccess
│   ├── index.php             ← Dashboard de gestion
│   └── js/
│       └── uxnote-cloud.js  ← Script client à intégrer sur les sites
└── data/                    ← SQLite (créé automatiquement, monté en volume)
    └── uxnote.sqlite
```

## API

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/annotations.php?all=1` | Toutes les annotations (dashboard) |
| GET | `/api/annotations.php?project_id=X&page_url=Y` | Annotations d'une page |
| POST | `/api/annotations.php` | Créer une annotation |
| PATCH | `/api/annotations.php` | Changer le statut (open/resolved) |
| DELETE | `/api/annotations.php?id=X` | Supprimer une annotation |

## Licence

MIT — Faites-en ce que vous voulez.
