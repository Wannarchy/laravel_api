<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conservation des logs (jours)
    |--------------------------------------------------------------------------
    | Au-delà de cette durée, les entrées sont supprimées (art. 5 RGPD).
    */
    'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Pages PHP journalisées (parcours commerce / compte uniquement)
    |--------------------------------------------------------------------------
    | Pas de suivi du catalogue, recherche ou pages légales (minimisation).
    */
    'allowed_page_views' => [
        'produit.php',
        'panier.php',
        'panier_add.php',
        'checkout.php',
        'checkout_submit.php',
        'confirmation.php',
        'mon-compte.php',
        'mes-commandes.php',
        'mes-abonnements.php',
        'adresses.php',
        'paiements.php',
        'paiement_refuse.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions conservées sans identifiant utilisateur après suppression compte
    |--------------------------------------------------------------------------
    | Intérêt légitime / obligation comptable (commandes).
    */
    'retain_actions_after_erasure' => [
        'order.create',
        'billing.checkout',
        'billing.checkout_success',
        'account.self_deleted',
    ],

];
