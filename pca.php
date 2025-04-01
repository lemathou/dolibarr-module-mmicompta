<?php

// Load Dolibarr environment
require 'env.inc.php';
require 'main_load.inc.php';


require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));


/*
 * Actions
 */



/*
 * View
 */


$title = $langs->trans('MMIComptaPCA');
$help_url = '';

llxHeader('', $title, $help_url);

// Rechercher les factures en anomalie :
// Qui ne seont pas marquées comme traitées totalement (livrées)
// Associées à des commandes qui ne matchent pas en terme de produit
// Associées à des commandes qui ne sont pas marquées comme totalement expédiées

// Rechercher les factures en PCA
// Pas marquées expédiées
// Associer aux commandes
// Associer aux lignes de commandes
// Associer aux lignes d'expéditions
// Associer aux produits

// Compter :
// Le montant facturé des produits non expédiés des commandes associées

?>
<style type="text/css">
  .price {
    text-align: right;
  }
  .alert {
    color: red;
  }
</style>
<h1>PCA</h1>
<?php

$su_list = [
  14 => 'APF',
  4 => 'JM Cover',
  5 => 'Fluidra Industry',
  25559 => 'Aqualux', 
  //walter
  15288 => 'Albon',
];

$debug = GETPOST('debug');

$search_user = GETPOST('search_user', 'int');
$surmesure = GETPOST('surmesure', 'int');
$fsurmesure = GETPOST('fsurmesure', 'int');
$cffact = GETPOST('cffact', 'int');
$nocffact = GETPOST('nocffact', 'int');
$year = GETPOST('year', 'int');
if (empty($year) || !is_numeric($year) || $year < 2000) {
  $year = date('Y');
}

$fk_c_type_contact = NULL;
$sql = 'SELECT rowid
  FROM `'.MAIN_DB_PREFIX.'c_type_contact`
  WHERE source="internal"
    AND element="commande"
    AND code="SALESREPFOLL"
  LIMIT 1';
$q = $db->query($sql);
if ($q) {
	if ($obj = $db->fetch_object($q)) {
		$fk_c_type_contact = $obj->rowid;
	}
}

$sql = 'SELECT 
    f.rowid AS facture_id,
    f.ref AS facture_ref,
    f.total_ht AS facture_total_ht,
    f.datef AS facture_date,
    s.rowid AS thirdparty_id,
    s.nom AS thirdparty_nom,
    c.rowid AS commande_id,
    c.ref AS commande_ref,
    c.total_ht AS commande_total_ht,
    c.date_commande AS commande_date,
    AVG(cd.price) AS commande_prix_unitaire,
    AVG(cd.buy_price_ht) AS commande_prix_achat_unitaire,
    p.rowid AS product_id,
    p.ref AS product_ref,
    p.label AS product_label,
    su.rowid AS supplier_id,
    su.nom AS supplier_nom,
    COUNT(DISTINCT e.rowid) AS expeditions_nb,
    GROUP_CONCAT(e.ref SEPARATOR ",") AS expeditions_ref,
    SUM(cd.qty) AS quantite_commandee,
    SUM(IFNULL(ed.qty, 0)) AS quantite_expediee
FROM 
    '.MAIN_DB_PREFIX.'facture AS f
    LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s
		  ON s.rowid = f.fk_soc
    INNER JOIN '.MAIN_DB_PREFIX.'element_element ee
		  ON (ee.fk_target = f.rowid AND ee.targettype = "facture" AND ee.sourcetype = "commande")
    INNER JOIN '.MAIN_DB_PREFIX.'commande AS c
		  ON ee.fk_source = c.rowid
    '.($search_user>0 ?' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact AS ec ON ec.element_id=c.rowid AND ec.fk_c_type_contact='.$fk_c_type_contact :'').' -- Commecial
    INNER JOIN '.MAIN_DB_PREFIX.'commandedet AS cd
		  ON c.rowid = cd.fk_commande
    INNER JOIN '.MAIN_DB_PREFIX.'product AS p
		  ON cd.fk_product = p.rowid
    INNER JOIN '.MAIN_DB_PREFIX.'product_extrafields AS p2
		  ON p2.fk_object = p.rowid
    LEFT JOIN '.MAIN_DB_PREFIX.'societe AS su
		  ON su.rowid = p2.fk_soc_fournisseur
    LEFT JOIN '.MAIN_DB_PREFIX.'expeditiondet AS ed
		  ON cd.rowid = ed.fk_origin_line
    LEFT JOIN '.MAIN_DB_PREFIX.'expedition AS e
		  ON e.rowid = ed.fk_expedition
WHERE
    f.type IN (0, 3) AND f.paye = 1 -- Factures acompte ou normale payées
    AND p.fk_product_type = 0 -- Produit expédiable
    '.($fsurmesure ?' AND p2.fk_soc_fournisseur IN ('.implode(',', array_keys($su_list)).')' :'').' -- Fournisseurs de produits sur mesure
    '.($surmesure ?' AND p2.custom = 1' :'').' -- Produits sur mesure
    '.($search_user>0 ?' AND ec.fk_socpeople = '.$search_user :'').' -- Commecial
    AND c.fk_statut != 3 AND c.fk_statut != -1 -- Commande non clôturée/expédiée
    AND DATE(f.datef) >= "'.$year.'-01-01" AND DATE(f.datef) <= "'.$year.'-12-31" -- Factures depuis 2024
GROUP BY 
	c.rowid, f.rowid, p.rowid
HAVING quantite_commandee != quantite_expediee
ORDER BY c.rowid ASC';

$q = $db->query($sql);
if ($debug)
  var_dump($q, $db->lasterror);

$TData = array();
if ($q) {
	while ($obj = $db->fetch_object($q)) {
		$TData[] = $obj;
	}
}

$pca = 0;
$pca_achat = 0;

$form = new Form($db);

?>

<p>Fournisseurs de produits sur mesure : <?php echo implode(', ', $su_list) ?></p>
<form method="GET">
  <?php echo $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200'); ?>
  <input type="text" name="year" value="<?= $year ?>" />
  <input type="checkbox" name="fsurmesure" value="1"<?php if ($fsurmesure) echo " checked"; ?> /> Fournisseurs de produits sur mesure
  <input type="checkbox" name="surmesure" value="1"<?php if ($surmesure) echo " checked"; ?> /> Produits sur mesure
  <input type="checkbox" name="cffact" value="1"<?php if ($cffact) echo " checked"; ?> /> Sans commmande fournisseur facturée
  <input type="checkbox" name="nocffact" value="1"<?php if ($nocffact) echo " checked"; ?> /> Avec commmande fournisseur facturée
  <input type="submit" value="Filtrer" />
</form>

<table border="1">
  <thead>
    <tr>
      <th>Client</th>
      <th colspan="3">Facture</th>
      <th colspan="3">Commande</th>
      <th colspan="2">Commande Fourn</th>
      <th colspan="2">Produit</th>
      <th>Fournisseur</th>
      <th>Expéditions</th>
      <th>QTE commandée</th>
      <th>QTE Expédiée</th>
      <th colspan="2">PCA</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $c_list = [];
    $fc_list = [];
    $cp_list = [];
    $fcp_list = [];
    $su_list = [];
    $col_nb = 17;
    $c_prev = NULL;
    $c_pca = 0;
    $c_pca_achat = 0;
    $nb = 0;
    foreach($TData as $obj) {
      // On zappe si on a une commande fournisseur facturée
      if ($cffact && !empty($cf_list[$obj->commande_id]) && $cf_list[$obj->commande_id]->facture_ref) {
        continue;
      }
      // On zappe si on a pas de commande fournisseur facturée
      if ($nocffact && in_array($obj->commande_id, $c_list) && (empty($cf_list[$obj->commande_id]) || empty($cf_list[$obj->commande_id]->facture_ref))) {
        continue;
      }

      // Commandes fournisseur associées aux commandes
      $sql = 'SELECT cf.rowid, cf.ref, cf.date_commande, cf2.facture_ref
        FROM '.MAIN_DB_PREFIX.'element_element AS ee
        INNER JOIN '.MAIN_DB_PREFIX.'commande_fournisseur AS cf
          ON cf.rowid = ee.fk_target
        INNER JOIN '.MAIN_DB_PREFIX.'commande_fournisseur_extrafields AS cf2
          ON cf2.fk_object = ee.fk_target
        WHERE ee.targettype = "order_supplier"
          AND ee.sourcetype = "commande"
          AND ee.fk_source = '.$obj->commande_id.'
        ORDER BY cf2.facture_ref ASC';
      $q2 = $db->query($sql);
      //var_dump($sql, $q2);
      if ($q2) {
        if ($obj2 = $db->fetch_object($q2)) {
          $cf_list[$obj->commande_id] = $obj2;
        }
      }
      else {
        $obj2 = NULL;
      }
      // On zappe si on a une commande fournisseur facturée
      if ($cffact && $obj2 && $obj2->facture_ref) {
        continue;
      }
      // On zappe si on a pas de commande fournisseur facturée
      if ($nocffact && (!$obj2 || !$obj2->facture_ref)) {
        continue;
      }

      $nb++;
      // Real supplier list
      if (!empty($obj->supplier_id)) {
        if (!isset($su_list[$obj->supplier_id])) $su_list[$obj->supplier_id] = ['id'=> $obj->supplier_id, 'name' => $obj->supplier_nom, 'nb'=>0];
        $su_list[$obj->supplier_id]['nb']++;
      }
      // Total par comande
      if (!empty($c_prev) && $c_prev != $obj->commande_id) {
        echo '<tr><td colspan="'.($col_nb-2).'" style="border:0;">&nbsp;</td> <td class="price">'.$c_pca.'€</td> <td class="price">'.$c_pca_achat.'€</td></tr>';
        $c_pca = 0;
        $c_pca_achat = 0;
      }
      $c_prev = $obj->commande_id;

      // Dédoublonnage lignes
      $ref = $obj->commande_id.'-'.$obj->product_id;
      $ref2 = $obj->commande_id.'-'.$obj->facture_id;
      if (!in_array($obj->commande_id, $c_list)) {
        $c_list[] = $obj->commande_id;
        echo '<tr><td colspan="'.$col_nb.'" style="border:0;">&nbsp;</td></tr>';
      }
      if (!in_array($ref, $cp_list)) {
        $cp_new = true;
        $cp_list[] = $ref;
        if ($obj->quantite_commandee-$obj->quantite_expediee>0) {
          $pca += $l_pca = round($obj->commande_prix_unitaire*$obj->quantite_commandee-$obj->quantite_expediee);
          $pca_achat += $l_pca_achat = round($obj->commande_prix_achat_unitaire*$obj->quantite_commandee-$obj->quantite_expediee);
          $c_pca += $l_pca;
          $c_pca_achat += $l_pca_achat;
        }
        else {
          $l_pca = 0;
          $l_pca_achat = 0;
        }

        if (!in_array($ref2, $fc_list)) {
          $fc_list[] = $ref2;
        }
      }
      else {
        $cp_new = false;
        if (!in_array($ref2, $fc_list)) {
          $fc_list[] = $ref2;
        }
        else {
          continue;
        }
      }
      ?>
      <tr>
        <td><a href="/societe/card.php?id=<?= $obj->thirdparty_id ?>"><?= $obj->thirdparty_nom ?></a></td>
        <td><a href="/compta/facture/card.php?id=<?= $obj->facture_id ?>"><?= $obj->facture_ref ?></a></td>
        <td><?= $obj->facture_date ?></td>
        <td class="price<?php if($obj->facture_total_ht != $obj->commande_total_ht) echo ' alert'; ?>"><?= round($obj->facture_total_ht).'€' ?></td>
        <td><a href="/commande/card.php?id=<?= $obj->commande_id ?>"><?= $obj->commande_ref ?></a></td>
        <td><?= $obj->commande_date ?></td>
        <td class="price"><?= round($obj->commande_total_ht).'€' ?></td>
        <td><?php if ($obj2) { echo '<a href="/fourn/commande/card.php?id='.$obj2->rowid.'">'.$obj2->ref.'</a>'; } ?></td>
        <td><?php if ($obj2) { echo $obj2->facture_ref; } ?></td>
        <?php if ($cp_new) { ?>
        <td><a href="/product/card.php?id=<?= $obj->product_id ?>"><?= $obj->product_ref ?></a></td>
        <td><?= $obj->product_label ?></td>
        <td><a href="/societe/card.php?id=<?= $obj->supplier_id ?>"><?= $obj->supplier_nom ?></a></td>
        <td><?= str_replace(',', '<br />', $obj->expeditions_ref) ?></td>
        <td><?= $obj->quantite_commandee ?></td>
        <td><?= $obj->quantite_expediee ?></td>
        <td class="price"><?= $l_pca.'€' ?></td>
        <td class="price"><?= $l_pca_achat.'€' ?></td>
        <?php } else { ?>
        <td colspan="8"></td> 
        <?php } ?>
      </tr>
      <?php
    }
    ?>
  <tr>
    <td colspan="<?php echo $col_nb-2; ?>"><?php echo $nb.' enregistrements'; ?></td>
    <td class="price"><?= $pca.'€' ?></td>
    <td class="price"><?= $pca_achat.'€' ?></td>
  </tr>
  </tbody>
</table>

<?php
//var_dump($su_list);
