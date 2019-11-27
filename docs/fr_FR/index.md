Ce plugin Jeedom/Nextdom permet de récupérer les informations de la trottinette M365 en version 1.3.8 et de paramétrer la lumière arrière, le lock et le cruise control.

Attention : plugin validé et prévu seulement pour la version firmware 1.3.8 de la trottinette Xiaomi M365 / M365 Pro.

Configuration du plugin 
=======================

Après installation du plugin, il vous suffit de l’activer. Il n'y a aucune configuration particulière à faire.


Configuration des équipements 
=============================

La configuration des équipements m365 est accessible à partir du menu
Plugins puis Monitoring. Vous retrouvez ici :

-   un bouton pour créer un équipement manuellement

-   un bouton pour afficher la configuration du plugin

-   un filtre de recherhe de vos équipements

-	la liste de vos équipements

En cliquant sur un de vos équipements, vous arrivez sur la page
configuration de votre équipement comprenant 2 onglets, Equipement et
Commandes.

-   **Onglet Equipement** :

-   **Nom de l’équipement** : nom de votre équipement

-   **Activer** : permet de rendre votre équipement actif

-   **Visible** : le rend visible sur le dashboard

-   **Objet parent** : indique l’objet parent auquel appartient
    l’équipement

-   **Equipement blea** : équipement du plugin blea correspondant à la trottinette

-   **Reconnexion automatique** : permet de préciser si le plugin tente de se reconnecter automatiquement en cas d'échecs de connexion et de récupération des données de la trottinette.

-   **Alerte** : précise un pourcentage de batterie sous lequel une alerte est remontée. Il faut alors préciser le message à remonter et la commande permettant d'envoyer le message.

-   **Onglet Commandes** :

-   Les commandes Info affichent les informations récupérées de la trottinette.

-	Les commandes Action sont les suivantes :

-   **Cruise Off** : Désactive le mode Cruise

-   **Cruise On** : Active le mode Cruise

-   **Lock Off** : Débloque l'utilisation de la trottinette

-   **Lock On** : Bloque l'utilisation de la trottinette

-   **Lumière Off** : Eteins de manière permanente la lumière arrière

-   **Lumière On** : Allume de manière permanente la lumière arrière