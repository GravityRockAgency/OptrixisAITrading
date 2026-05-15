# Oceara Sun Concierge

**Version :** 1.0.0 — **Oceara Sun Concierge MVP**  
**Plugin slug :** `oceara-sun-concierge`  
**Text domain :** `oceara-sun-concierge`  
**PHP minimum :** 8.0

Oceara Sun Concierge est une conciergerie digitale premium pour une marque skincare solaire. Le plugin ajoute un widget flottant mobile-first permettant de recommander une protection solaire, collecter des demandes, orienter vers WhatsApp, suivre une commande WooCommerce et préparer l’automatisation n8n.

## Structure du plugin

```text
oceara-sun-concierge/
├── oceara-sun-concierge.php
├── README.md
├── includes/
│   ├── class-osc-plugin.php
│   ├── class-osc-activator.php
│   ├── class-osc-database.php
│   ├── class-osc-widget.php
│   ├── class-osc-admin.php
│   ├── class-osc-settings.php
│   ├── class-osc-woocommerce.php
│   ├── class-osc-rest-api.php
│   ├── class-osc-n8n.php
│   └── class-osc-security.php
├── assets/
│   ├── css/widget.css
│   ├── css/admin.css
│   ├── js/widget.js
│   └── js/admin.js
├── templates/
│   ├── widget.php
│   ├── admin-dashboard.php
│   ├── admin-conversations.php
│   ├── admin-recommendations.php
│   └── admin-settings.php
└── languages/
    └── oceara-sun-concierge-fr_FR.po
```

## Installation

1. Depuis un terminal, placez-vous dans le dossier parent du plugin.
2. Créez l’archive :

```bash
zip -r oceara-sun-concierge.zip oceara-sun-concierge -x "*.DS_Store"
```

3. Dans WordPress : **Extensions > Ajouter > Téléverser une extension**.
4. Sélectionnez `oceara-sun-concierge.zip`.
5. Cliquez sur **Installer maintenant**, puis **Activer**.

À l’activation, le plugin crée ses tables et ses options par défaut.

## Configuration

Dans l’admin WordPress, ouvrez **Oceara Concierge > Réglages**.

Paramètres disponibles :

- logo du widget ;
- couleur principale et secondaire ;
- message d’accueil ;
- numéro WhatsApp ;
- email support ;
- horaires ;
- message offline ;
- lien vers la politique de confidentialité ;
- webhook n8n ;
- IDs produits WooCommerce ;
- activation/désactivation du quiz, WhatsApp, suivi commande et n8n.

## Connexion WooCommerce

WooCommerce est optionnel. Si WooCommerce est désactivé :

- le widget reste actif ;
- le quiz reste actif ;
- WhatsApp reste actif ;
- le formulaire reste actif ;
- les fonctions produit/panier/suivi commande sont masquées proprement.

Pour lier les produits, renseignez les IDs WooCommerce pour :

1. `Mineral Sunscreen Stick SPF50+` ;
2. `Mineral Sunscreen Cream SPF50+` ;
3. `Sun Hair Protection Oil — UV + Salt Protection` ;
4. `Oceara Full Sun Routine Pack`.

La logique de recommandation se trouve dans `OSC_WooCommerce::get_recommended_product($answers)`.

## Configuration WhatsApp

Dans les réglages, indiquez le numéro au format lisible, par exemple `+212600000000`. Le plugin nettoie automatiquement le numéro pour générer un lien `wa.me`.

Messages préremplis :

- conseil produit avec réponses du quiz ;
- suivi commande avec email et numéro ;
- revendeur/collaboration.

Chaque clic WhatsApp est enregistré comme événement `whatsapp_click` et envoyé à n8n si activé.

## Configuration n8n

1. Créez un workflow n8n avec un Webhook Trigger.
2. Copiez l’URL de production du webhook.
3. Collez-la dans **Oceara Concierge > Réglages > Webhook n8n**.
4. Activez **Activer n8n**.

Événements envoyés :

- `new_conversation` ;
- `new_recommendation` ;
- `whatsapp_click` ;
- `order_tracking_request`.

Les erreurs webhook ne bloquent jamais l’utilisateur. Chaque tentative est enregistrée dans `osc_webhook_logs`.

## Tables créées

Le plugin crée les tables avec le préfixe WordPress :

### `{prefix}osc_conversations`

Stocke les demandes de contact : nom, email, téléphone, sujet, message, statut, page source, dates.

### `{prefix}osc_recommendations`

Stocke les réponses du quiz, le produit recommandé, l’action cliquée, l’email optionnel et la page source.

### `{prefix}osc_webhook_logs`

Stocke les événements locaux et les tentatives webhook n8n.

## Routes REST API

Namespace : `/wp-json/osc/v1/`

- `POST submit-conversation` : enregistre une demande de contact.
- `POST save-recommendation` : calcule et enregistre une recommandation.
- `POST track-whatsapp-click` : trace un clic WhatsApp.
- `POST track-order` : recherche une commande WooCommerce par email + numéro.
- `GET get-settings` : retourne les réglages publics utiles au widget.

Toutes les routes vérifient le nonce REST `X-WP-Nonce`.


## Audit de livraison V1

Contrôles réalisés avant packaging :

- syntaxe PHP validée sur tous les fichiers du plugin ;
- vérification de présence de tous les fichiers attendus ;
- vérification du chargement des classes `includes/` depuis le fichier principal ;
- vérification des hooks d’activation `register_activation_hook` et `dbDelta` ;
- vérification des assets chargés uniquement côté frontend ou uniquement sur les pages admin Oceara ;
- vérification des routes REST et du nonce `X-WP-Nonce` ;
- vérification de la détection WooCommerce avant les appels `wc_*` ;
- vérification du nettoyage du numéro WhatsApp avant génération du lien `wa.me` ;
- vérification du mode n8n asynchrone pour éviter de bloquer le parcours utilisateur.

## Tests à effectuer

1. Activer le plugin sur une installation WordPress récente.
2. Vérifier la création des tables `osc_*`.
3. Ouvrir **Oceara Concierge > Réglages** et enregistrer les options.
4. Vérifier que le bouton flottant apparaît côté frontend.
5. Ouvrir/fermer le widget sur desktop et mobile.
6. Compléter le quiz et vérifier le résultat.
7. Cliquer sur “Voir le produit” et “Ajouter au panier” si WooCommerce est actif.
8. Envoyer une demande de contact.
9. Vérifier la conversation dans l’admin.
10. Vérifier la recommandation dans l’admin.
11. Tester le suivi commande avec un email correct et incorrect.
12. Configurer WhatsApp et vérifier l’ouverture du message prérempli.
13. Configurer un webhook n8n de test et vérifier les logs.

## Limites connues de la V1

- Les réglages produits utilisent des IDs WooCommerce manuels plutôt qu’un sélecteur AJAX avancé.
- Le widget ne remplace pas un outil de support conversationnel temps réel.
- Les textes livraison/promo sont volontairement génériques en V1.
- Le tracking des actions produit met à jour la recommandation courante ; il ne constitue pas encore un journal analytique détaillé.
- Aucun export CSV n’est fourni dans cette version.
- Le fichier `.po` est minimal et peut être complété avec un outil i18n.

## Roadmap

### V1.5

- Sélecteur produit WooCommerce avec recherche AJAX.
- Export CSV conversations/recommandations.
- Filtres admin par statut/date/produit.
- Paramètres avancés pour les textes livraison et promo.

### V2

- Scoring produit plus avancé.
- Segmentation leads et tags CRM.
- Templates n8n configurables.
- Analytics de conversion du widget.

### V3

- Concierge hybride avec base de connaissance contrôlée.
- Personnalisation par pays/langue.
- Recommandations dynamiques selon panier, météo ou UV index.
