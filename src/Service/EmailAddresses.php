<?php

namespace App\Service;

/**
 * Registre central des adresses d'expédition des emails du site. Un seul endroit à modifier
 * si les adresses changent, et ça évite d'oublier un "from" en dur dans chaque contrôleur.
 */
class EmailAddresses
{
    public const NO_REPLY = 'no-reply@kongobazar.com';       // Vérification email, mot de passe oublié : jamais de réponse attendue
    public const ORDERS = 'commandes@kongobazar.com';        // Confirmations de commande, livraison
    public const SUPPORT = 'support@kongobazar.com';         // Réponses du service client, contact
    public const SELLERS = 'vendeurs@kongobazar.com';        // Notifications aux boutiques / vendeurs Pro
    public const NEWSLETTER = 'newsletter@kongobazar.com';   // Envois en masse — séparés du reste
}