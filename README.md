# BookPro

Plateforme de réservation en ligne entre **clients** et **professionnels** (plombiers, électriciens, mécaniciens, menuisiers, peintres…).

Les pros publient leur fiche, leurs services et tarifs, leurs horaires et leurs créneaux ; les clients comparent, réservent un créneau libre, puis laissent un avis.

## Fonctionnalités

**Clients**
- Recherche de pros par métier, ville ou mot-clé ; page publique de chaque pro (tarifs, horaires, avis, créneaux)
- Réservation en ligne, suivi, annulation, avis après le rendez-vous
- Application installable (PWA) : notifications de confirmation et rappel la veille

**Professionnels**
- Profil, services et tarifs, horaires d'ouverture, congés et fermetures
- Génération automatique des créneaux depuis les horaires
- Agenda (semaine / jour), confirmation ou refus des demandes, fiches clients avec notes privées
- Statistiques (chiffre d'affaires, remplissage, provenance des visites)
- Lien court `/p/mon-entreprise`, QR code, affichette et bouton à intégrer sur un site
- Application installable (PWA) : notification à chaque demande, avec Confirmer / Refuser

**Administrateurs**
- Tableau de bord de la plateforme, gestion des comptes (suspension, suppression), modération des avis

## Technique

- PHP 8.2+ (sans framework ni Composer), MySQL 8, Apache avec `.htaccess` (`mod_rewrite`)
- Bootstrap 5.3 et Bootstrap Icons (CDN)
- Extensions PHP : `pdo_mysql`, `openssl`, `curl`, `gd`, `fileinfo`
- Notifications Web Push implémentées dans `includes/webpush.php` (RFC 8291 / 8292)

## Installation locale (WAMP)

1. Placer le projet dans `C:\wamp64\www\` et démarrer WAMP.
2. Créer une base `bookpro` (utf8mb4) et y importer `sql/schema.sql`.
3. Vérifier les identifiants MySQL dans `config/database.php` et l'adresse du site dans `config/app.php`.
4. Ouvrir `http://localhost/<dossier>/index.php`.
5. Créer un compte, puis le passer administrateur si besoin :
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'vous@exemple.fr';
   ```

Au premier lancement, `config/secrets.php` (clés des notifications) est généré automatiquement. Il ne doit jamais être versionné.

Les fichiers `sql/migration_*.sql` retracent l'évolution de la base. Pour une installation neuve, `sql/schema.sql` suffit.

## Rappels automatiques

`cron/rappels.php` envoie les rappels de la veille (email + notification). À lancer toutes les heures :

- **Linux / hébergeur** : `5 * * * * php /chemin/du/site/cron/rappels.php`
- **Windows** : voir `cron/rappels-windows.bat` (Planificateur de tâches)

## Mise en ligne

- HTTPS obligatoire (installation des applications et notifications)
- Renseigner la base de données dans `config/database.php` et l'adresse publique dans `config/app.php`
- Rendre `uploads/pros/` et `config/` modifiables par le serveur
- Ajouter la tâche cron des rappels
- Ne pas envoyer les sauvegardes `sql/backup_*.sql`

## Structure

| Dossier | Contenu |
|---|---|
| `auth/` | Connexion (client, pro), inscription, mot de passe oublié |
| `user/` | Recherche, page publique des pros, espace client, application client |
| `pro/` | Espace pro, application pro, API des notifications pro |
| `admin/` | Espace administrateur |
| `api/` | Abonnement aux notifications |
| `includes/` | Fonctions communes, mises en page, composants |
| `config/` | Base de données, emails, adresse du site |
| `cron/` | Tâches planifiées |
| `assets/` | Styles, scripts, icônes, service worker |
| `sql/` | Schéma et migrations |
| `docs/maquettes/` | Maquettes de design d'origine |
