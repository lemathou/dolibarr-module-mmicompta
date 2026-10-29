# MMICompta — Comptabilité MMI : produits constatés d'avance, export comptable, correction des factures payées et gestion des acomptes.

> Module Dolibarr **MMICompta** (version 1.0), branche `20.0`.

## Fonctionnalités

### Fonctionnalités principales

- Produits Constatés d'Avance (PCA) : page `pca.php`, calcul et suivi.
- Export comptable : `export.php`, options de libellé de compte (`MMI_COMPTA_EXPORT_LABEL_AS_ACCOUNT`) et de clé alpha (`MMI_COMPTA_EXPORT_LIBALPHA_USE_1STLETTER`).
- Correction des factures payées : `fixpaid.php` (statut/encaissement).
- Acomptes : `FACTURE_DEPOSITS_ARE_JUST_PAYMENTS`, `INVOICE_VALIDATED_PERMIT_DEPOSIT`, TVA des acomptes (`MMI_INVOICE_DEPOSIT_USE_TVA_TX`).
- Page d'accueil `mmicomptaindex.php`.

### Écrans et menus

- **Produits Constatés d'Avance** — `/custom/mmicompta/pca.php` (menu mmicompta, droit `$user->rights->accounting->comptarapport->lire`)

### Pages

- `admin/about.php`
- `admin/setup.php`
- `export.php`
- `fixpaid.php`
- `mmicomptaindex.php`
- `pca.php`

### Configuration

| Constante | Type | Rôle |
|---|---|---|
| `FACTURE_DEPOSITS_ARE_JUST_PAYMENTS` | yesno | Les Factures d'acomptes sont des preuves de paiement qui n'entrent pas dans le CA et ne sont pas transférées en compta (option obsolète) |
| `MMI_INVOICE_DEPOSIT_USE_TVA_TX` | float | Taux de TVA à utiliser pour les acomptes en valeur |
| `MMI_COMPTA_EXPORT_LABEL_AS_ACCOUNT` | yesno | Utiliser le numéro de compte comme libellé pour l'export vers le logiciel comptable |
| `MMI_COMPTA_EXPORT_LIBALPHA_USE_1STLETTER` | yesno | Utiliser le premier caractère du compte comme libellé Alpha (clé d'appel) |
| `INVOICE_VALIDATED_PERMIT_DEPOSIT` | yesno | Autoriser les acomptes sur les factures validées (si pas encore en transférées en compta) |

### Classes et composants

- `lib/mmicompta.lib.php`

## Prérequis

- Dolibarr >= 11.0 (branche `20.0` : Dolibarr 20.0).
- Modules MMI : `mmicommon`. **`mmicommon` doit être activé en premier.**

## Installation

1. Copier le module dans `htdocs/custom/mmicompta` :

   ```sh
   cd htdocs/custom
   git clone <dépôt> mmicompta
   ```

2. Dans Dolibarr : **Configuration → Modules**, activer d'abord `mmicommon`, puis ce module.
3. Vérifier les permissions dans la fiche des utilisateurs concernés.

## Licence

GPLv3 ou ultérieure — voir `COPYING`. Documentation sous GFDL.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) (évolutions datées).
