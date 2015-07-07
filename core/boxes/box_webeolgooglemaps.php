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
 *	\file       htdocs/webeol/core/boxes/box_webeolgooglemaps.php
 *	\ingroup    webeol
 *	\brief      Module to show box of link to google maps
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show links to maps
 */
class box_webeolgooglemaps extends ModeleBoxes
{
	var $boxcode="googlemaps";
	var $boximg="google@google";
	var $boxlabel="List of maps";
	var $depends = array("google@google");

	var $db;
	var $param;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
     *  @param	string	$param		More parameters
	 */
	function __construct($db,$param='')
	{
		global $conf, $user, $langs;

		$this->db = $db;

		$langs->load("google@google");
		$langs->load("webeol@webeol");
		$this->boxlabel=$langs->trans("ListOfMapsAvailable");

		// disable module for such cases
		$listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);
		if (! in_array('adherent',$listofmodulesforexternal) && ! in_array('societe',$listofmodulesforexternal) && ! empty($user->societe_id)) $this->enabled=0;	// disabled for external users
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;
		$langs->load("boxes");
		$langs->load("google@google");
		$langs->load("webeol@webeol");

        $this->info_box_head = array('text' => $langs->trans("ListOfMapsAvailable",$max));

        $i=0;
		if ($conf->societe->enabled && $user->rights->societe->lire && !$user->rights->webeol->telepro)
		{
			$something++;

			$url=dol_buildpath("/webeol/google/webeolgmaps_all.php",1)."?mode=allprospect";
			$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => 'object_company',
                    'url' => $url
			);
			$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => '<a href="'.$url.'">'.$langs->trans("MapOfAllProspect").'</a>',
					'url' => $url
			);

			$i++;
		}
		if ($conf->societe->enabled && $user->rights->societe->lire && $user->rights->webeol->telepro)
		{
			$something++;

			$url=dol_buildpath("/webeol/google/webeolgmaps_all.php",1)."?mode=selfprospect";
			$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
					'logo' => 'object_company',
					'url' => $url
			);
			$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' => '<a href="'.$url.'">'.$langs->trans("MapOfSelfProspect").'</a>',
					'url' => $url
			);

			$i++;
		}
		if ($conf->societe->enabled && $user->rights->societe->lire)
		{
			$something++;

			$url=dol_buildpath("/webeol/google/webeolgmaps_all.php",1)."?mode=joannaprospect";
			$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
					'logo' => 'object_company',
					'url' => $url
			);
			$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' => '<a href="'.$url.'">'.$langs->trans("MapOfJoannaProspect").'</a>',
					'url' => $url
			);

			$i++;
		}
		if ($conf->societe->enabled && $user->rights->societe->lire)
			{
				$something++;
	
				$url=dol_buildpath("/webeol/google/webeolgmaps_all.php",1)."?mode=stephaneprospect";
				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_company',
						'url' => $url
				);
				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => '<a href="'.$url.'">'.$langs->trans("MapOfStephaneProspect").'</a>',
						'url' => $url
				);
	
				$i++;
			}
			if ($conf->societe->enabled && $user->rights->societe->lire)
			{
				$something++;
			
				$url=dol_buildpath("/webeol/google/webeolgmaps_all.php",1)."?mode=patriceprospect";
				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_company',
						'url' => $url
				);
				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => '<a href="'.$url.'">'.$langs->trans("MapOfPatriceProspect").'</a>',
						'url' => $url
				);
			
				$i++;
			}
			if ($conf->societe->enabled && $user->rights->societe->lire)
			{
				$something++;
					
				$url=dol_buildpath("/webeol/google/webeolgmaps_all.php",1)."?mode=customercontractexpire";
				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_company',
						'url' => $url
				);
				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => '<a href="'.$url.'">'.$langs->trans("MapOfCustomerContractExpire").'</a>',
						'url' => $url
				);
					
				$i++;
			}
			if ($conf->societe->enabled && $user->rights->societe->lire)
			{
				$something++;
					
				$url=dol_buildpath("/webeol/google/webeolagendagmaps.php",1)."?mode=joannardv";
				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_company',
						'url' => $url
				);
				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => '<a href="'.$url.'">'.$langs->trans("MapOfJoannaRDV").'</a>',
						'url' => $url
				);
					
				$i++;
			}
			if ($conf->societe->enabled && $user->rights->societe->lire)
			{
				$something++;
					
				$url=dol_buildpath("/webeol/google/webeolagendagmaps.php",1)."?mode=stephanerdv";
				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_company',
						'url' => $url
				);
				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => '<a href="'.$url.'">'.$langs->trans("MapOfStephaneRDV").'</a>',
						'url' => $url
				);
					
				$i++;
			}
			if ($conf->societe->enabled && $user->rights->societe->lire)
			{
				$something++;
					
				$url=dol_buildpath("/webeol/google/webeolagendagmaps.php",1)."?mode=patricerdv";
				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_company',
						'url' => $url
				);
				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => '<a href="'.$url.'">'.$langs->trans("MapOfPatriceRDV").'</a>',
						'url' => $url
				);
					
				$i++;
			}
				

		if (! $something)
		{
			$this->info_box_contents[0][0] = array('align' => 'left',
            'text' => $langs->trans("No map available"));
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
