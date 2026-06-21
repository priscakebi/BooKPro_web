# BookPro

## Présentation
BookPro est une application web de gestion de services et de prise de rendez-vous en ligne. Elle permet aux professionnels de proposer leurs services et aux clients de réserver facilement des créneaux disponibles.

---

## Objectifs
- Digitaliser la prise de rendez-vous
- Faciliter la gestion des services pour les professionnels
- Permettre une réservation rapide et intuitive pour les clients
- Centraliser les données dans une base MySQL
- Optimiser la gestion des disponibilités

---

## Fonctionnalités
- Gestion des utilisateurs (client, professionnel, admin)
- Création et gestion des services
- Gestion des disponibilités (créneaux horaires)
- Réservation de services en ligne
- Tableau de bord professionnel
- Gestion des statuts (disponible, réservé, annulé)
- Interface responsive

---

## Tests
- Connexion utilisateur → OK
- Inscription utilisateur → OK
- Création d’un service → OK
- Ajout de disponibilité → OK
- Réservation d’un créneau → OK
- Annulation d’une réservation → OK
- Mise à jour du statut → OK

---

## Base de données
Principales tables utilisées :

### disponibilites
- id : identifiant unique  
- service_id : service associé  
- date_disponible : date du créneau  
- heure_debut : heure de début  
- heure_fin : heure de fin  
- statut : disponible / réservé / annulé  

---

## UML (images)

### Diagramme de cas d’utilisation
![Use Case](assets/uml/usecase.png)

### Diagramme de classes
![Class Diagram](assets/uml/class.png)

### Diagramme de séquence (réservation)
![Sequence](assets/uml/sequence.png)

---

## Technologies
- PHP (backend)
- MySQL (base de données)
- HTML5 / CSS3
- JavaScript
- Bootstrap
- AJAX (AJAX permet de communiquer avec le serveur sans recharger la page)

---

## Installation


```bash
git clone https://github.com/priscakebi/BooKPro_web.git   