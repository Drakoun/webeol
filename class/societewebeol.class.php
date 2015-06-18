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
 *	\file       webeol/class/societewebeol.class.php
 *	\ingroup    webeol
 *	\brief      File for third party class
 */
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class SocieteWebeol extends Societe
{
	public $sql;
	protected $liste = array();
	
	protected function sqltoliste()
	{
		if ($this->sql)
		{
			dol_syslog("societewebeol.class.php"."::sql=".$sql);
			$result = $this->db->query($this->sql);
			
			while ($objet = $this->db->fetch_object($result))
			{
				array_push($this->liste,$objet->s_rowid);
			}
			//return http_build_query(array('liste' => $this->liste));
		}
	}
	
	function getNomUrl($withpicto=0,$option='',$maxlen=0)
	{
		global $conf,$langs;
	
		$name=$this->name?$this->name:$this->nom;
	
		if ($conf->global->SOCIETE_ADD_REF_IN_LIST && (!empty($withpicto))) {
			if (($this->client) && (! empty ( $this->code_client ))) {
				$code = $this->code_client . ' - ';
			}
			if (($this->fournisseur) && (! empty ( $this->code_fournisseur ))) {
				$code .= $this->code_fournisseur . ' - ';
			}
			$name =$code.' '.$name;
		}
	
		$result='';
		$lien=$lienfin='';
		
		// from module myList
		if ($this->sql)
		{
			$this->sqltoliste();
			$_SESSION[liste] = $this->liste;
		}
	
		if ($option == 'customer' || $option == 'compta')
		{
			$lien = '<a href="'.dol_buildpath('/webeol/webeol/comm/card.php',1).'?socid='.$this->id;
		}
		else if ($option == 'prospect' && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
		{
			$lien = '<a href="'.dol_buildpath('/webeol/webeol/comm/card.php',1).'?socid='.$this->id;
		}
		else if ($option == 'supplier')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$this->id;
		}
		else if ($option == 'category')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/categories/categorie.php?id='.$this->id.'&type=2';
		}
		else if ($option == 'category_supplier')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/categories/categorie.php?id='.$this->id.'&type=1';
		}
	
		// By default
		if (empty($lien))
		{
			$lien = '<a href="'.dol_buildpath('/webeol/webeol/comm/card.php',1).'?socid='.$this->id;
		}
	
		// Add type of canvas
		$lien.=(!empty($this->canvas)?'&canvas='.$this->canvas:'').'">';
		$lienfin='</a>';
	
		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowCompany").': '.$name,'company').$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.($maxlen?dol_trunc($name,$maxlen):$name).$lienfin;
	
		return $result;
	}
}
