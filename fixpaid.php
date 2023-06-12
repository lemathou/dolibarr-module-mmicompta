<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Mathieu Moulin <mathieu@dercya.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    mmicompta/fixpaid.php
 * \ingroup mmicompta
 * \brief   MMICompta fix
 */

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
if (empty($user->rights->facture->invoice_advance->reopen)) {
	accessforbidden();
}

$sql = 'SELECT f.*, SUM(p.amount) paid_amount
	FROM '.MAIN_DB_PREFIX.'facture f
	INNER JOIN '.MAIN_DB_PREFIX.'paiement_facture p
		ON p.fk_facture=f.rowid
	WHERE f.fk_statut=1 AND total_ttx=paid_amount
	GROUP BY f.rowid';
echo '<pre>'.$sql.'</pre>';
$q = $db->query($sql);
