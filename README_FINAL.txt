DANSLEBUENO — VERSION PHP/MYSQL FINALE (MVP complet)

1. Pré-requis
- PHP 8+
- MySQL / MariaDB
- Apache, XAMPP, MAMP ou WAMP

2. Installation
- Place le dossier Leboncoin dans htdocs / www.
- Crée la base avec le fichier schema.sql.
- Mets tes accès MySQL dans includes/db.php.
- Vérifie que le dossier uploads/ est accessible en écriture.

3. Comptes de démo
- Admin : admin@danslebueno.local / Admin123!
- User : marie@example.com / Admin123!
- User : jean@example.com / Admin123!

4. Pages disponibles
- index.php : accueil + recherche + filtres
- auth.php : connexion / inscription
- deposer-annonce.php : créer / modifier une annonce
- detail-annonce.php : voir une annonce + contacter vendeur + favoris
- profil.php : modifier profil + voir annonces + favoris
- messagerie.php : discussions par annonce
- admin.php : dashboard admin + gestion utilisateurs

5. Ce qui est déjà fonctionnel
- Authentification complète
- Sessions
- CSRF
- CRUD annonces
- Upload image
- Favoris
- Messagerie simple
- Tableau de bord admin

6. Remarques
- Les anciennes pages .html étaient des maquettes. La version exploitable est en .php.
- Si tu veux déployer ensuite, pense à sécuriser davantage les uploads et la config de prod.
