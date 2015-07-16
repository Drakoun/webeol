<?php
/* Webeol
 * Copyright (C) 2015  Boccara David <davidboccara333@yahoo.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file	core/boxes/box_acontacter.php
 * \ingroup	webeol
 * \brief	box prospect a contacter
 */

include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_acontacter extends ModeleBoxes
{

	public $boxcode = "acontacter";

	public $boximg = "webeol@webeol";

	public $boxlabel;

	public $depends = array(
		"webeol"
	);

	public $db;

	public $param;

	public $info_box_head = array();

	public $info_box_contents = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $langs;
		$langs->load("boxes");
		$langs->load("webeol@webeol");
		
		$this->boxlabel = $langs->transnoentitiesnoconv("AContacter");
	}

	/**
	 * Load data into info_box_contents array to show array later.
	 *
	 * @param int $max
	 *        	of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;
		
		$this->max = $max;
		
		//dol_include_once('/lead/class/lead.class.php');
		
		//$lead = new Lead($db);
		
		//$lead->fetch_all('DESC', 't.ref', $max, 0);
		
		$sql = "SELECT se.cc , s.rowid, s.nom, se.dda, se.rda, se.pr";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields as se ON s.rowid = se.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_stcomm as stc ON s.fk_stcomm = stc.id";
		$sql .= " WHERE se.telepro = ".$user->id." AND s.client = 2 AND se.tp != 1 AND s.fk_stcomm = 1 ORDER BY se.pr ASC";
		
		dol_syslog("WebeolBox::prospect_acontacter sql=" . $sql, LOG_DEBUG);
		$resql = $db->query($sql);
		
		$text = $langs->trans("ListProspectAContacter");
		$text .= " (" . $langs->trans("LastN", $max) . ")";
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text)
		);
		
		$i = 0;
		
		if ($resql)
		{
			while ($i < $max && $obj = $db->fetch_object($resql))
			{var_dump($obj);
				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_company',
						'url' => dol_buildpath("/webeol/webeol/comm/card.php",1) . '?id=' . $obj->s.rowid
				);
				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => $obj->nom,
						'url' => dol_buildpath("/webeol/webeol/comm/card.php",1) . '?id=' . $obj->s.rowid
				);
				$this->info_box_contents[$i][2] = array('td' => 'align="left"',
						'text' => dol_print_date($obj->dda,'dayhourtextshort'),
				);
				$this->info_box_contents[$i][3] = array('td' => 'align="left"',
						'text' => $obj->rda,
				);
				$this->info_box_contents[$i][4] = array('td' => 'align="left"',
						'text' => dol_print_date($obj->pr,'dayhourtextshort'),
				);
				$i++;
			}
		}
		
		/*
		foreach ($lead->lines as $line) {
			// FIXME: line is an array, not an object
			$line->fetch_thirdparty();
			// Ref
			$this->info_box_contents[$i][0] = array(
				'td' => 'align="left" width="16"',
				'logo' => $this->boximg,
				'url' => dol_buildpath('/lead/lead/card.php', 1) . '?id=' . $line->id
			);
			
			$this->info_box_contents[$i][1] = array(
				'td' => 'align="left"',
				'text' => $line->ref,
				'url' => dol_buildpath('/lead/lead/card.php', 1) . '?id=' . $line->id
			);
			
			$this->info_box_contents[$i][2] = array(
				'td' => 'align="left" width="16"',
				'logo' => 'company',
				'url' => DOL_URL_ROOT . "/comm/fiche.php?socid=" . $line->fk_soc
			);
			
			$this->info_box_contents[$i][3] = array(
				'td' => 'align="left"',
				'text' => dol_trunc($line->thirdparty->name, 40),
				'url' => DOL_URL_ROOT . "/comm/fiche.php?socid=" . $line->fk_soc
			);
			
			// Amount Guess
			
			$this->info_box_contents[$i][4] = array(
				'td' => 'align="left"',
				'text' => price($line->amount_prosp, 'HTML') . $langs->getCurrencySymbol($conf->currency)
			);
			
			// Amount real
			$this->info_box_contents[$i][5] = array(
				'td' => 'align="left"',
				'text' => $line->getRealAmount() . $langs->getCurrencySymbol($conf->currency)
			);
			
			$i ++;
		}*/
	}

	/**
	 * Method to show box
	 *
	 * @param array $head
	 *        	with properties of box title
	 * @param array $contents
	 *        	with properties of box lines
	 * @return void
	 */
	public function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
