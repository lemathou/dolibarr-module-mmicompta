<?php

// Load Dolibarr environment
require_once 'env.inc.php';
require_once 'main_load.inc.php';

if (!$conf->mmicompta->enabled) {
	die('MMICompta Disabled');
}

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (empty($user->rights->accounting->chartofaccount)) {
	accessforbidden();
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "admin", "accountancy", "salaries", "hrm", "errors"));

$sql = 'SELECT DISTINCT subledger_account, subledger_label
	FROM `'.MAIN_DB_PREFIX.'accounting_bookkeeping`
	WHERE numero_compte=411 AND subledger_account LIKE "0%"
	GROUP BY subledger_account  
	ORDER BY `llx_accounting_bookkeeping`.`subledger_account` ASC';
echo '<pre>'.$sql.'</pre>';
$res = $db->query($sql);

// 
echo '<table>';
// Quadratus
while ($data=$res->fetch_object()) {
	echo '<tr><td>'.dol_trunc($data->subledger_account, 8).'</td><td>'.dol_trunc(dol_string_unaccent($data->subledger_label), 30, 'right', 'UTF-8', 1).'</td></tr>';
}
echo '</table>';
