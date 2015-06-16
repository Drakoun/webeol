<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani            <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014 Juanjo Menent               <jmenent@2byte.es>
 * Copyright (C) 2013      Alexandre Spangaro          <alexandre.spangaro@gmail.com>
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
 *       \file       htdocs/comm/card.php
 *       \ingroup    commercial compta
 *       \brief      Page to show customer card of a third party
 */

$res = @include "../../main.inc.php"; // From htdocs directory
if (! $res) {
	$res = @include "../../../main.inc.php"; // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (! empty($conf->facture->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->contrat->enabled)) require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->ficheinter->enabled)) require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

$langs->load("companies");
if (! empty($conf->contrat->enabled))  $langs->load("contracts");
if (! empty($conf->commande->enabled)) $langs->load("orders");
if (! empty($conf->facture->enabled)) $langs->load("bills");
if (! empty($conf->projet->enabled))  $langs->load("projects");
if (! empty($conf->ficheinter->enabled)) $langs->load("interventions");
if (! empty($conf->notification->enabled)) $langs->load("mails");

// Security check
$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
if ($user->societe_id > 0) $id=$user->societe_id;
$result = restrictedArea($user,'societe',$id,'&societe');

$action		= GETPOST('action');
$mode		= GETPOST("mode");
$modesearch	= GETPOST("mode_search");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";
$cancelbutton = GETPOST('cancel');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('commcard','globalcard'));

$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

$modelmail='thirdparty';

/*
 * Actions
 */

$parameters = array('socid' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Update third party
if ($action == 'update')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	$ret=$object->fetch($id);
	$object->oldcopy=dol_clone($object);
	
	$object->email                 = GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
	$object->url                   = GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);

	$object->forme_juridique_code  = GETPOST('forme_juridique_code', 'int');
	$object->effectif_id           = GETPOST('effectif_id', 'int');
	$object->typent_id             = GETPOST('typent_id');

	$object->commercial_id         = GETPOST('commercial_id', 'int');
	$object->default_lang          = GETPOST('default_lang');
	
	$object->array_options ["options_nrc"] = GETPOST("options_nrc");
	$object->array_options ["options_rda"] = GETPOST("options_rda");
	$object->array_options ["options_pr"] = dol_mktime($_POST["options_prhour"], $_POST["options_prmin"], 0, $_POST["options_prmonth"], $_POST["options_prday"], $_POST["options_pryear"]);
	$object->array_options ["options_bgpj"] = GETPOST("options_bgpj");
	$object->array_options ["options_ddr"] = dol_mktime($_POST["options_ddrhour"], $_POST["options_ddrmin"], 0, $_POST["options_ddrmonth"], $_POST["options_ddrday"], $_POST["options_ddryear"]);
	$object->array_options ["options_rpj"] = GETPOST("options_rpj");
	$object->array_options ["options_rpja"] = GETPOST("options_rpja");
	$object->array_options ["options_bsi"] = GETPOST("options_bsi");
	$object->array_options ["options_rsi"] = GETPOST("options_rsi");
	$object->array_options ["options_rsi"] = GETPOST("options_ersi");
	$object->array_options ["options_cdc"] = GETPOST("options_cdc");
	$object->array_options ["options_rvp"] = dol_mktime($_POST["options_rvphour"], $_POST["options_rvpmin"], 0, $_POST["options_rvpmonth"], $_POST["options_rvpday"], $_POST["options_rvpyear"]);
	$object->array_options ["options_rvc"] = GETPOST("options_rvc");
	$object->array_options ["options_dtc"] = dol_mktime($_POST["options_dtchour"], $_POST["options_dtcmin"], 0, $_POST["options_dtcmonth"], $_POST["options_dtcday"], $_POST["options_dtcyear"]);
	$object->array_options ["options_tsp"] = GETPOST("options_tsp");
	$object->array_options ["options_com"] = GETPOST("options_com");
	
	// Date et heure d'appel, Nombre d'appels en automatique si le dernier appel s'est fait au moins il y a 4 heures
	if (strtotime($object->array_options['options_dda']) + (4* 60 * 60) < time()) 
	{
		$datetime = new Datetime();
		$object->array_options['options_dda'] = dol_mktime($datetime->format('H'), $datetime->format('i'), $datetime->format('s') , $datetime->format('m'), $datetime->format('d'), $datetime->format('Y'));
		$object->array_options['options_nba'] += 1;		
	}
	// Sinon il faut changer le type de la date de string à time
	else 
	{
		$object->array_options['options_dda'] = strtotime($object->array_options['options_dda']);
	}
	
	// Mise à jour dans la base
	$result = $object->update($id, $user, 1, $object->oldcopy->codeclient_modifiable(), $object->oldcopy->codefournisseur_modifiable(), 'update', 0);
	if ($result <=  0)
	{
		$error = $object->error; $errors = $object->errors;
	}
}

// Update communication level
if ($action == 'cstc')
{
	$object->fetch($id);
	$object->stcomm_id=GETPOST('stcomm','int');
	$result=$object->set_commnucation_level($user);
	if ($result < 0) setEventMessage($object->error,'errors');
}

// set prospect level
if ($action == 'setprospectlevel')
{
	$object->fetch($id);
	$object->fk_prospectlevel=GETPOST('prospect_level_id','alpha');
	$result=$object->set_prospect_level($user);
	if ($result < 0) setEventMessage($object->error,'errors');
}

// Actions to send emails
$actiontypecode='AC_OTH_AUTO';
$trigger_name='COMPANY_SENTBYMAIL';
$paramname='socid';
$mode='emailfromthirdparty';
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


/*
 * View
 */

llxHeader('',$langs->trans('CustomerCard'));


$contactstatic = new Contact($db);
$userstatic=new User($db);
$form = new Form($db);
$formcompany=new FormCompany($db);

if ($action == 'askmailmodels' && ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)))
{
	
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
		$newlang = $_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->default_lang;
	
	
	$result = $formmail->fetchAllEMailTemplate($modelmail, $user, $newlang);
	if ($result<0) {
		setEventMessage($formmail->error,'errors');
	}
	$modelmail_array=array();
	foreach($formmail->lines_model as $line) {
		$modelmail_array[$line->id]=$line->label;
	}
	
	$form_question = array ();
	$form_question [] = array (
			'label' => $langs->trans("ChooseMailModel"),
			'type' => 'select',
			'values' => $modelmail_array,
			'name' => 'modelmailselected'
	);
	
		
	$ret = $form->form_confirm($_SERVER['PHP_SELF'].'?socid='.$id.'&amp;mode=init', $langs->trans("ChooseMailModel"), '', "presend", $form_question, '', 1);
	if ($ret == 'html')
		print '<br>';
	else 
		print $res;
	//print $form->formconfirm(,$langs->trans("ChooseMailModel"),$langs->trans("ChooseMailModel"),"confirm_delete",'',0,"action-delete");
}

dol_htmloutput_errors($error,$errors);

if ($mode == 'search')
{
	
	if ($modesearch == 'soc')
	{
		// TODO move to DAO class
		$sql = "SELECT s.rowid";
		if (!$user->rights->societe->client->voir && !$id) $sql .= ", sc.fk_soc, sc.fk_user ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
		if (!$user->rights->societe->client->voir && !$id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	}

	$resql=$db->query($sql);
	if ($resql)
	{
		if ( $db->num_rows($resql) == 1)
		{
			$obj = $db->fetch_object($resql);
			$id = $obj->rowid;
		}
		$db->free($resql);
	}
	
}


if ($id > 0)
{
	// Load data of third party
	$object->fetch($id);
	if ($object->id <= 0)
	{
		dol_print_error($db,$object->error);
	}


	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'tabProspect', $langs->trans("ThirdParty"),0,'company');


	print '<div class="fichecenter"><div class="fichehalfleft">';

	if (!empty($extrafields->attribute_label))
	{
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	}

	print '<table class="border" width="100%">';

	print '<tr><td width="30%">'.$langs->trans("ThirdPartyName").'</td><td width="70%" colspan="3">';
	$object->next_prev_filter="te.client in (1,2,3)";
	print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom','','');
	print '</td></tr>';

	// Prospect/Customer
	print '<tr><td width="30%">'.$langs->trans('ProspectCustomer').'</td><td width="70%" colspan="3">';
	print $object->getLibCustProspStatut();
	print '</td></tr>';

	// Prefix
    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
	   print ($object->prefix_comm?$object->prefix_comm:'&nbsp;');
	   print '</td></tr>';
    }

	// Address
	print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3">';
	dol_print_address($object->address,'gmap','thirdparty',$object->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td class="nowrap">'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td>';
	print '<td colspan="3">'.$object->zip.(($object->zip && $object->town)?' / ':'').$object->town."</td>";
	print '</tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	if (! empty($object->country_code))
	{
		//$img=picto_from_langcode($object->country_code);
		$img='';
		if ($object->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$object->country,$langs->trans("CountryIsInEEC"),1,0);
		else print ($img?$img.' ':'').$object->country;
	}
	print '</td></tr>';
	
	// Sales representative
	include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';
	
	// Level of prospect
	if ($object->client == 2 || $object->client == 3)
	{
		// Status
		print '<tr><td>'.$langs->trans("StatusProsp").'</td><td colspan="2">'.$object->getLibProspCommStatut(4).'</td>';
		print '<td>';
		if ($object->stcomm_id != -1) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=-1&amp;action=cstc">'.img_action(0,-1).'</a>';
		if ($object->stcomm_id !=  0) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=0&amp;action=cstc">'.img_action(0,0).'</a>';
		if ($object->stcomm_id !=  1) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=1&amp;action=cstc">'.img_action(0,1).'</a>';
		if ($object->stcomm_id !=  2) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=2&amp;action=cstc">'.img_action(0,2).'</a>';
		if ($object->stcomm_id !=  3) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=3&amp;action=cstc">'.img_action(0,3).'</a>';
		print '</td></tr>';
	
		// setprospectlevel
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('ProspectLevel');
		print '<td>';
		if ($action != 'editlevel' && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;socid='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editlevel')
			$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->fk_prospectlevel,'prospect_level_id',1);
		else
			print $object->getLibProspLevel();
		print "</td>";
		print '</tr>';
	}
	
	// EMail
	print '<td>'.$langs->trans('EMail').'</td><td colspan="3">'.dol_print_email($object->email,0,$object->id,'AC_EMAIL').'</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web").'</td><td colspan="3">'.dol_print_url($object->url,'_blank').'</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans('Phone').'</td><td style="min-width: 25%;">'.dol_print_phone($object->phone,$object->country_code,0,$object->id,'AC_TEL').'</td>';

	// Fax
	print '<td>'.$langs->trans('Fax').'</td><td style="min-width: 25%;">'.dol_print_phone($object->fax,$object->country_code,0,$object->id,'AC_FAX').'</td></tr>';

	
	if (!empty($extrafields->attribute_label))
	{
	
		// Rubrique
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Rubrique</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_ru'];
		print '</td></tr>';
		
		// Principaux dirigeants
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Principaux dirigeants</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_prd'];
		print '</td></tr>';
		
		// Nom du responsable contacté
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Nom du responsable contacté</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_nrc"])?$_POST["options_nrc"]:$object->array_options["options_nrc"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(nrc,$value).'</td></tr></table>';
		print '</td></tr>';

		// Carte simple
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Carte simple</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_cas'];
		print '</td></tr>';
			
		// Carte premium
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Carte premium</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_cap'];
		print '</td></tr>';
		
		// En savoir +
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">En savoir +</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_esp'];
		print '</td></tr>';
		
		// Lien direct
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Lien direct</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_ld'];
		print '</td></tr>';
		
		// Nombre d'activités
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Nombre d\'activités</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_na'];
		print '</td></tr>';

		// Budget pages jaunes internet minimum
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Budget pages jaunes internet minimum</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_ttmin'];
		print '</td></tr>';
		
		// Budget pages jaunes internet maximum
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Budget pages jaunes internet maximum</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_ttmax'];
		print '</td></tr>';
		
		// Campagne commercial
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Campagne commercial</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_cc'];
		print '</td></tr>';
		
		// Date et heure d'appel
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Date et heure d\'appel</td></tr></table></td><td colspan="3">';
		print date("d-m-Y H:i:s", strtotime($object->array_options ['options_dda']));
		print '</td></tr>';

		// Résultat d'appel
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Résultat d\'appel</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_rda"])?$_POST["options_rda"]:$object->array_options["options_rda"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(rda,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Nombre d'appels
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Nombre d\'appels</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_nba'];
		print '</td></tr>';

		// Date du prochain appel
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Date du prochain appel</td></tr></table></td><td colspan="3">';
		$value = isset($_POST["options_pr"])?dol_mktime($_POST["options_prhour"], $_POST["options_prmin"], 0, $_POST["options_prmonth"], $_POST["options_prday"], $_POST["options_pryear"]):$object->db->jdate($object->array_options['options_pr']);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(pr,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Budget global pages jaunes
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Budget global pages jaunes</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_bgpj"])?$_POST["options_pr"]:$object->array_options["options_bgpj"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(bgpj,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Date de renouvellement pages jaunes
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Date de renouvellement</td></tr></table></td><td colspan="3">';
		$value = isset($_POST["options_ddr"])?dol_mktime($_POST["options_ddrhour"], $_POST["options_ddrmin"], 0, $_POST["options_ddrmonth"], $_POST["options_ddrday"], $_POST["options_ddryear"]):$object->db->jdate($object->array_options['options_ddr']);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(ddr,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Situation du renouvellement Pages jaunes
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Renouvellement Pages jaunes</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_rpj"])?$_POST["options_rpj"]:$object->array_options["options_rpj"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(rpj,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Retombées pages jaunes
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Retombées pages jaunes</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_rpja"])?$_POST["options_rpja"]:$object->array_options["options_rpja"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(rpja,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Budget site internet
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Budget site internet</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_bsi"])?$_POST["options_bsi"]:$object->array_options["options_bsi"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(bsi,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Retombées sites internet
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Retombées sites internet</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_rsi"])?$_POST["options_rsi"]:$object->array_options["options_rsi"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(rsi,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Engagement restant sur site internet
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Engagement restant sur site internet</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_ersi"])?$_POST["options_ersi"]:$object->array_options["options_ersi"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(ersi,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Est il en contact avec des concurrents ?
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Est il en contact avec des concurrents ?</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_cdc"])?$_POST["options_cdc"]:$object->array_options["options_cdc"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(cdc,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Rendez vous pris le
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Rendez vous pris le</td></tr></table></td><td colspan="3">';
		$value = isset($_POST["options_rvp"])?dol_mktime($_POST["options_rvphour"], $_POST["options_rvpmin"], 0, $_POST["options_rvpmonth"], $_POST["options_rvpday"], $_POST["options_rvpyear"]):$object->db->jdate($object->array_options['options_rvp']);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(rvp,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Rendez-vous confirmé
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Rendez-vous confirmé</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_rvc"])?$_POST["options_rvc"]:$object->array_options["options_rvc"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(rvc,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Date de confirmation
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Date de confirmation</td></tr></table></td><td colspan="3">';
		$value = isset($_POST["options_dtc"])?dol_mktime($_POST["options_dtchour"], $_POST["options_dtcmin"], 0, $_POST["options_dtcmonth"], $_POST["options_dtcday"], $_POST["options_dtcyear"]):$object->db->jdate($object->array_options['options_dtc']);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(dtc,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Type SONCAS de prospect
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Type SONCAS de prospect</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_tsp"])?$_POST["options_tsp"]:$object->array_options["options_tsp"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(tsp,$value).'</td></tr></table>';
		print '</td></tr>';
		
		// Commentaire
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Commentaire</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_com"])?$_POST["options_com"]:$object->array_options["options_com"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(com,$value).'</td></tr></table>';
		print '</td></tr>';
	}

	
	print "</table>";
	
	
	print '<br>';
	print '<center>';
	print '<div class="inline-block divButAction"><input type="submit" class="butAction" name="save" value="'.$langs->trans("Save").'"></div>';
	print '</center>';
	print '</form>';
	
	
	print '</div><div class="fichehalfright"><div class="ficheaddleft">';


	// Nbre max d'elements des petites listes
	$MAXLIST=4;
	$tableaushown=1;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$object->id.'">'.$langs->trans("ShowCustomerPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';
	print '<br>';

	$now=dol_now();

	/*
	 * Last proposals
	 */
	if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
	{
		$propal_static = new Propal($db);

		$sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
		$sql.= " p.datep as dp, p.fin_validite as datelimite";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
		$sql.= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " ORDER BY p.datep DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);

            if ($num > 0)
            {
		        print '<table class="noborder" width="100%">';

                print '<tr class="liste_titre">';
    			print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal/list.php?socid='.$object->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table></td>';
    			print '</tr>';
            }

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
				print '<td align="right" style="min-width: 60px">'.price($objp->total_ht).'</td>';
				print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last orders
	 */
	if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
		$commande_static=new Commande($db);

		$sql = "SELECT s.nom, s.rowid,";
		$sql.= " c.rowid as cid, c.total_ht, c.ref, c.fk_statut, c.facture,";
		$sql.= " c.date_commande as dc";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql.= " WHERE c.fk_soc = s.rowid ";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.date_commande DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);

			if ($num > 0)
			{
				// Check if there are orders billable
				$sql2 = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
				$sql2.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as facturee';
				$sql2.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql2.= ', '.MAIN_DB_PREFIX.'commande as c';
				$sql2.= ' WHERE c.fk_soc = s.rowid';
				$sql2.= ' AND s.rowid = '.$object->id;
				// Show orders with status validated, shipping started and delivered (well any order we can bill)
				$sql2.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";

				$resql2=$db->query($sql2);
				$orders2invoice = $db->num_rows($resql2);
				$db->free($resql2);

				print '<table class="noborder" width="100%">';

				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastOrders",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->id.'">'.$langs->trans("AllOrders").' ('.$num.')</a></td>';
				print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				//if($num2 > 0) print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$object->id.'">'.img_picto($langs->trans("CreateInvoiceForThisCustomer"),'object_bill').'</a></td>';
				//else print '<td width="20px" align="right"><a href="#">'.img_picto($langs->trans("NoOrdersToInvoice"),'object_bill').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$objp->cid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$objp->ref."</a>\n";
				print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
				print '<td align="right" style="min-width: 60px">'.price($objp->total_ht).'</td>';
				print '<td align="right" style="min-width: 60px" class="nowrap">'.$commande_static->LibStatut($objp->fk_statut,$objp->facture,5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num >0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last linked contracts
	 */
	if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
	{
		$contratstatic=new Contrat($db);

		$sql = "SELECT s.nom, s.rowid, c.rowid as id, c.ref as ref, c.statut, c.datec as dc";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " WHERE c.fk_soc = s.rowid ";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num >0 )
			{
		        print '<table class="noborder" width="100%">';

			    print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastContracts",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
				print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/list.php?socid='.$object->id.'">'.$langs->trans("AllContracts").' ('.$num.')</a></td></tr></table></td>';
				print '</tr>';
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$contrat=new Contrat($db);

				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap">';
				$contrat->id=$objp->id;
				$contrat->ref=$objp->ref?$objp->ref:$objp->id;
				print $contrat->getNomUrl(1,12);
				print "</td>\n";
				print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
				print '<td width="20">&nbsp;</td>';
				print '<td align="right" class="nowrap">';
				$contrat->fetch_lines();
				print $contrat->getLibStatut(4);
				print "</td>\n";
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last interventions
	 */
	if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire)
	{
		$sql = "SELECT s.nom, s.rowid, f.rowid as id, f.ref, f.fk_statut, f.duree as duration, f.datei as startdate";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " ORDER BY f.tms DESC";

		$fichinter_static=new Fichinter($db);

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
		        print '<table class="noborder" width="100%">';

			    print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastInterventions",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/fichinter/list.php?socid='.$object->id.'">'.$langs->trans("AllInterventions").' ('.$num.')</td></tr></table></td>';
				print '</tr>';
				$var=!$var;
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				$fichinter_static->id=$objp->id;
                $fichinter_static->statut=$objp->fk_statut;

				print "<tr ".$bc[$var].">";
				print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/fichinter/card.php?id='.$objp->id.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a></td>'."\n";
                //print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->startdate)).'</td>'."\n";
				print '<td align="right" width="120">'.convertSecondToTime($objp->duration).'</td>'."\n";
				print '<td align="right" width="100">'.$fichinter_static->getLibStatut(5).'</td>'."\n";
				print '</tr>';
				$var=!$var;
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 *   Last invoices
	 */
	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		$facturestatic = new Facture($db);

		$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount, f.total, f.total_ttc,';
		$sql.= ' f.datef as df, f.datec as dc, f.paye as paye, f.fk_statut as statut,';
		$sql.= ' s.nom, s.rowid as socid,';
		$sql.= ' SUM(pf.amount) as am';
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.amount, f.total, f.total_ttc,';
		$sql.= ' f.datef, f.datec, f.paye, f.fk_statut,';
		$sql.= ' s.nom, s.rowid';
		$sql.= " ORDER BY f.datef DESC, f.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
		        print '<table class="noborder" width="100%">';

			    $tableaushown=1;
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id.'">'.$langs->trans("AllBills").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap">';
				$facturestatic->id=$objp->facid;
				$facturestatic->ref=$objp->facnumber;
				$facturestatic->type=$objp->type;
				print $facturestatic->getNomUrl(1);
				print '</td>';
				if ($objp->df > 0)
				{
					print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->df),'day').'</td>';
				}
				else
				{
					print '<td align="right"><b>!!!</b></td>';
				}
				print '<td align="right" width="120">'.price($objp->total_ttc).'</td>';

				print '<td align="right" class="nowrap" width="100" >'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am)).'</td>';
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	print '</div></div></div>';
	print '<div style="clear:both"></div>';

	dol_fiche_end();


	/*
	 * Barre d'actions
	 */

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been


	print '<div class="tabsAction">';
	
	
	if (! empty($object->email))
	{
		$langs->load("mails");
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;
		
		$model_exists=$formmail->isEMailTemplate($modelmail, $user, $newlang);var_dump($model_exists);
		if ($model_exists<0) 
		{
			setEventMessage($formmail->error,'errors');
		}
		
		if ($model_exists>0) 
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=askmailmodels">'.$langs->trans('SendMail').'</a></div>';
		} 
		elseif (empty($model_exists))
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendMail').'</a></div>';
		}
	}
	else
	{
		$langs->load("mails");
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
	}


	if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->creer)
	{
		$langs->load("fichinter");
		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a></div>';
	}

	// Add action
	if (! empty($conf->agenda->enabled) && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
	{
		if ($user->rights->agenda->myactions->create)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a></div>';
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a></div>';
		}
	}

	print '</div>';

	if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
	{
		// List of contacts
		show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

    if (! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
    {
        print load_fiche_titre($langs->trans("ActionsOnCompany"),'','');

        // List of todo actions
		show_actions_todo($conf,$langs,$db,$object);

        // List of done actions
		show_actions_done($conf,$langs,$db,$object);
	}
	
	if ($action == 'presend')
	{
			/*
			 * Affiche formulaire mail
			*/

			// By default if $action=='presend'
			$titreform='SendMail';
			$topicmail='';
			$action='send';
			

			print '<br>';
			print_titre($langs->trans($titreform));

			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
				$newlang = $_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object->client->default_lang;

			// Cree l'objet formulaire mail
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
			$formmail->fromtype = 'user';
			$formmail->fromid   = $user->id;
			$formmail->fromname = $user->getFullName($langs);
			$formmail->frommail = $user->email;
			$formmail->withfrom=1;
			$formmail->withtopic=1;
			$liste=array();
			foreach ($object->thirdparty_and_contact_email_array(1) as $key=>$value) $liste[$key]=$value;
			$formmail->withto=GETPOST('sendto')?GETPOST('sendto'):$liste;
			$formmail->withtofree=0;
			$formmail->withtocc=$liste;
			$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
			$formmail->withfile=2;
			$formmail->withbody=1;
			$formmail->withdeliveryreceipt=1;
			$formmail->withcancel=1;
			// Tableau des substitutions
			$formmail->substit['__SIGNATURE__']=$user->signature;
			$formmail->substit['__PERSONALIZED__']='';
			$formmail->substit['__CONTACTCIVNAME__']='';

			//Find the good contact adress
			/*
			$custcontact='';
			$contactarr=array();
			$contactarr=$object->liste_contact(-1,'external');

			if (is_array($contactarr) && count($contactarr)>0)
			{
			foreach($contactarr as $contact)
			{
			if ($contact['libelle']==$langs->trans('TypeContact_facture_external_BILLING')) {

			require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

			$contactstatic=new Contact($db);
			$contactstatic->fetch($contact['id']);
			$custcontact=$contactstatic->getFullName($langs,1);
			}
			}

			if (!empty($custcontact)) {
			$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
			}*/


			// Tableau des parametres complementaires du post
			$formmail->param['action']=$action;
			$formmail->param['models']=$modelmail;
			$formmail->param['models_id']=GETPOST('modelmailselected','int');
			$formmail->param['socid']=$object->id;
			$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?socid='.$object->id;

			// Init list of files
			if (GETPOST("mode")=='init')
			{
				$formmail->clear_attached_files();
				$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
			}

			print $formmail->get_form();

			print '<br>';
		}
		
}
else
{
	dol_print_error($db,'Bad value for socid parameter');
}

// End of page
llxFooter();

$db->close();
