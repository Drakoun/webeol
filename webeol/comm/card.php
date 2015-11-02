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
 *       \file       htdocs/custom/webeol/webeol/card.php
 *       \ingroup    téléprospecteur fiche
 *       \brief      Page to show teleprospecteur card of a third party
 */

$res = @include "../../../main.inc.php"; // From htdocs directory
if (! $res) {
	$res = @include "../../../../main.inc.php"; // From "custom" directory
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

// email
$modelmail='thirdparty';

// Ajouter les references personnalisees des boutons precedent et suivant
if ($liste = $_SESSION[liste])
{
	$key = array_search($id, $liste);
	$object->ref_previous = $liste[$key - 1];
	$object->ref_next = $liste[$key + 1];
}

// Verification si l'utilisateur est un télépro et si ce prospect lui est associé
if (!$user->admin && $user->rights->webeol->telepro)
{
	$object->fetch($id);
	if ($user->id != $object->array_options ["options_telepro"])
	{
		accessforbidden();
	}
}

/*
 * Actions
 */

$parameters = array('socid' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Update third party à partir du formulaire de modification du prospect
if ($action == 'update')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	$ret=$object->fetch($id);
	$object->oldcopy=dol_clone($object);
	
	if (GETPOST('name')) $object->name = GETPOST('name');
	if (GETPOST('address')) $object->address = GETPOST('address');
	if (GETPOST('zip')) $object->zip = GETPOST('zip');
	if (GETPOST('town')) $object->town = GETPOST('town');
	if (GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL) != null) $object->email = GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
	if (GETPOST('url', 'custom', 0, FILTER_SANITIZE_EMAIL) != null) $object->url = GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
	if (GETPOST('phone')) $object->phone = GETPOST('phone');
}

// Update third party à partir du formulaire de l'appel
if ($action == 'appel')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	$ret=$object->fetch($id);
	$object->oldcopy=dol_clone($object);
	
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
	$object->array_options ["options_tsp"] = GETPOST("options_tsp");
	$object->array_options ["options_com"] = GETPOST("options_com");
	$object->array_options ["options_sc"] = GETPOST("options_sc");
	$object->array_options ["options_asi"] = GETPOST("options_asi");
	$object->array_options ["options_oac"] = GETPOST("options_oac");
	$object->array_options ["options_smart"] = GETPOST("options_smart");
	$object->array_options ["options_ata"] = GETPOST("options_ata");
	$object->array_options ["options_apra"] = GETPOST("options_apra");
	$object->array_options ["options_iform"] = GETPOST("options_iform");

	// Date et heure d'appel, Nombre d'appels en automatique si le dernier appel s'est fait au moins il y a 4 heures
	if (strtotime($object->array_options['options_dda']) + (4 * 60 * 60) < dol_now())
	{
		$object->array_options['options_dda'] = dol_now();
		$object->array_options['options_nba'] += 1;
		
		// ajout dans la table call_history pour l'historique des appels
		$object->db->begin();
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cust_call_history_extrafields (fk_object,dda,rda) ";
		$sql .= "VALUES (".$id.",'".$object->db->idate($object->array_options['options_dda'])."','".$object->array_options['options_rda']."')";
		dol_syslog(get_class($object)."::insertCustCallHistoryExtraFields insert", LOG_DEBUG);
		$resql = $object->db->query($sql);
		if (! $resql)
		{
			$object->error=$object->db->lasterror();
			$object->db->rollback();
			var_dump($error);
		}
		else
		{
			$object->db->commit();
		}
	}
	// Sinon il faut changer le type de la date de string à time
	else
	{
		$object->array_options['options_dda'] = strtotime($object->array_options['options_dda']);
	}
	
	// Modification du status de prospection en automatique
	if ($object->array_options ["options_rda"] == 'Grands groupes') $object->stcomm_id = -1;
	if ($object->array_options ["options_rda"] == 'Barrage secrétaire' || $object->array_options ["options_rda"] == 'Entretien téléphonique' ||
			 $object->array_options ["options_rda"] == 'Envoi de mail de présentation' || $object->array_options ["options_rda"] == 'A renouvelé') 
		$object->stcomm_id = 1;
	if ($object->array_options ["options_rda"] == 'Rendez-vous pris') $object->stcomm_id = 2;
	$object->set_commnucation_level($user);
}

// Mise à jour dans la base
if ($action == 'update' || $action == 'appel')
{
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

	print '<table class="border" width="100%">';

	// Formulaire de modification du prospect
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding">';
	print '<tr><td width="30%">'.$langs->trans("ThirdPartyName").'</td>';
	if ($action != 'editname') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editname&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
	print '</tr></table>';
	print '<td width="70%" colspan="3">';
	
	
	// Redéfinition des boutons precedent et suivant en mettant le corps de la fonction showrefnav (print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom','','');) et en enlevant une fonction appelee et en remplacant les parametres
	
	if (!$liste) $object->next_prev_filter="te.client in (1,2,3)";
	
	$ret='';
	$moreparam = '';
	
	if (!$liste) $object->load_previous_next_ref((isset($object->next_prev_filter)?$object->next_prev_filter:''),'rowid',0);
	
	$previous_ref = $object->ref_previous?'<a data-role="button" data-icon="arrow-l" data-iconpos="left" href="'.$_SERVER["PHP_SELF"].'?'.socid.'='.urlencode($object->ref_previous).$moreparam.'">'.(empty($conf->dol_use_jmobile)?img_picto($langs->trans("Previous"),'previous.png'):'&nbsp;').'</a>':'';
	$next_ref     = $object->ref_next?'<a data-role="button" data-icon="arrow-r" data-iconpos="right" href="'.$_SERVER["PHP_SELF"].'?'.socid.'='.urlencode($object->ref_next).$moreparam.'">'.(empty($conf->dol_use_jmobile)?img_picto($langs->trans("Next"),'next.png'):'&nbsp;').'</a>':'';
	
	//print "xx".$previous_ref."x".$next_ref;
	if ($previous_ref || $next_ref) {
		$ret.='<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
	}
	
	if ($action == 'editname')
		$ret .= '<input type="text" name="name" id="name" value="'.$object->name.'"><input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
	else			$ret.=dol_htmlentities($object->nom);
	
	if (($user->societe_id?0:1) && ($previous_ref || $next_ref) && $action != 'editname')
	{
		$ret.='</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td>';
		$ret.='<td class="nobordernopadding" align="center" width="20">'.$next_ref;
	}
	if ($previous_ref || $next_ref)
	{
		$ret.='</td></tr></table>';
	}
	
	print $ret;
	
	
	print '</td></tr>';

	// Prospect/Customer
	print '<tr><td width="30%">'.$langs->trans('ProspectCustomer').'</td><td width="70%" colspan="3">';
	print $object->getLibCustProspStatut();
	print '</td></tr>';
	
	// Address
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('Address');
	print '</td>';
	if ($action != 'editaddress') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editaddress&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editaddress')
		print '<input type="text" name="address" id="address" value="'.$object->address.'"><input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
	else
		dol_print_address($object->address,'gmap','thirdparty',$object->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('Zip').' / '.$langs->trans('Town');
	print '</td>';
	if ($action != 'editville') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editville&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editville')
		print '<input type="text" name="zip" id="zip" value="'.$object->zip.'"> / <input type="text" name="town" id="town" value="'.$object->town.'"><input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
	else
		print $object->zip.(($object->zip && $object->town)?' / ':'').$object->town;
	print '</td></tr>';

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
	
	// Status
	print '<tr><td>'.$langs->trans("StatusProsp").'</td><td colspan="2">'.$object->getLibProspCommStatut(4).'</td>';
	print '<td>';
	if ($object->stcomm_id != -1) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=-1&amp;action=cstc">'.img_action(0,-1).'</a>';
	if ($object->stcomm_id !=  0) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=0&amp;action=cstc">'.img_action(0,0).'</a>';
	if ($object->stcomm_id !=  1) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=1&amp;action=cstc">'.img_action(0,1).'</a>';
	if ($object->stcomm_id !=  2) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=2&amp;action=cstc">'.img_action(0,2).'</a>';
	if ($object->stcomm_id !=  3) print '<a href="card.php?socid='.$object->id.'&amp;stcomm=3&amp;action=cstc">'.img_action(0,3).'</a>';
	print '</td></tr>';
	

	// Telepro
	print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Téléprospecteur</td></tr></table></td><td colspan="3">';
	print $extrafields->showOutputField(telepro,$object->array_options ['options_telepro']);
	print '</td></tr>';
	
	// setprospectlevel
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('ProspectLevel');
	print '<td>';
	if ($action != 'editlevel') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;socid='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editlevel')
		$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->fk_prospectlevel,'prospect_level_id',1);
	else
		print $object->getLibProspLevel();
	print '</td>';
	print '</tr>';
	
	// EMail
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('EMail');
	print '</td>';
	if ($action != 'editemail') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editemail&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editemail')
		print '<input type="text" name="email" id="email" size="32" value="'.$object->email.'"><input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
	else
		print dol_print_email($object->email,0,$object->id,'AC_EMAIL');
	print '</td></tr>';

	// Web
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans("Web");
	print '</td>';
	if ($action != 'editurl') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editurl&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editurl')
		print '<input type="text" name="url" id="url" size="32" value="'.$object->url.'"><input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
	else
		print dol_print_url($object->url,'_blank');
	print '</td></tr>';

	// Phone
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('Phone').'</td>';
	if ($action != 'editphone') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editphone&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
	print '</tr></table>';
	print '<td colspan="3">';
	if ($action == 'editphone')
		print '<input type="text" name="phone" id="phone" value="'.$object->phone.'"><input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
	else
		print dol_print_phone($object->phone,$object->country_code,0,$object->id,'AC_TEL');
	print '</td></tr>';
	
	// Fin du Formulaire de modification du prospect
	print '</form>';
	
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
		
		// Formulaire de l'appel
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		print '<input type="hidden" name="action" value="appel">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		
		// Nom du responsable contacté
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Nom du responsable contacté</td></tr></table></td><td colspan="3">';
		$value=(isset($_POST["options_nrc"])?$_POST["options_nrc"]:$object->array_options["options_nrc"]);
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $extrafields->showInputField(nrc,$value).'</td></tr></table>';
		print '</td></tr>';
		
		if ($object->array_options ['options_tp'] == 1 || $object->array_options ['options_tp'] == 3)
		{
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
		}
		
		// Campagne commercial
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Campagne commercial</td></tr></table></td><td colspan="3">';
		print $object->array_options ['options_cc'];
		print '</td></tr>';
		
		// Date et heure d'appel
		print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Date et heure d\'appel</td></tr></table></td><td colspan="3">';
		print dol_print_date($db->jdate($object->array_options["options_dda"]),'dayhourtextshort');
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
		
		if ($object->array_options ['options_tp'] == 1 || $object->array_options ['options_tp'] == 3)
		{
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
		}
		
		if ($object->array_options ['options_tp'] == 2 || $object->array_options ['options_tp'] == 3)
		{
			// Les supports de communication
			print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Les supports de communication</td></tr></table></td><td colspan="3">';
			$value=(isset($_POST["options_sc"])?$_POST["options_sc"]:$object->array_options["options_sc"]);
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $extrafields->showInputField(sc,$value).'</td></tr></table>';
			print '</td></tr>';	
			
			// A un site internet
			print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">A un site internet</td></tr></table></td><td colspan="3">';
			$value=(isset($_POST["options_asi"])?$_POST["options_asi"]:$object->array_options["options_asi"]);
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $extrafields->showInputField(asi,$value).'</td></tr></table>';
			print '</td></tr>';
			
			// Offre des avantages à ses clients
			print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Offre des avantages à ses clients</td></tr></table></td><td colspan="3">';
			$value=(isset($_POST["options_oac"])?$_POST["options_oac"]:$object->array_options["options_oac"]);
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $extrafields->showInputField(oac,$value).'</td></tr></table>';
			print '</td></tr>';
			
			// A un smartphone
			print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">A un smartphone</td></tr></table></td><td colspan="3">';
			$value=(isset($_POST["options_smart"])?$_POST["options_smart"]:$object->array_options["options_smart"]);
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $extrafields->showInputField(smart,$value).'</td></tr></table>';
			print '</td></tr>';
			
			// A télécharger des applications
			print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">A télécharger des applications</td></tr></table></td><td colspan="3">';
			$value=(isset($_POST["options_ata"])?$_POST["options_ata"]:$object->array_options["options_ata"]);
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $extrafields->showInputField(ata,$value).'</td></tr></table>';
			print '</td></tr>';
			
			// A pensé à réaliser une application
			print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">A pensé à réaliser une application</td></tr></table></td><td colspan="3">';
			$value=(isset($_POST["options_apra"])?$_POST["options_apra"]:$object->array_options["options_apra"]);
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $extrafields->showInputField(apra,$value).'</td></tr></table>';
			print '</td></tr>';
			
			// Intéressé par une formation
			print '<tr><td class="nowrap"><table width="100%" class="nobordernopadding"><tr><td class="nowrap">Intéressé par une formation</td></tr></table></td><td colspan="3">';
			$value=(isset($_POST["options_iform"])?$_POST["options_iform"]:$object->array_options["options_iform"]);
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $extrafields->showInputField(iform,$value).'</td></tr></table>';
			print '</td></tr>';
		}
		
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
	
	if (! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
	{
		print load_fiche_titre($langs->trans("ActionsOnCompany"),'','');
	
		// List of todo actions
		show_actions_todo($conf,$langs,$db,$object);
	
		// List of done actions
		show_actions_done($conf,$langs,$db,$object);
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
		
		$model_exists=$formmail->isEMailTemplate($modelmail, $user, $newlang);
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
	
	if ($object->array_options ['options_rda'] == 'Rendez-vous pris' || $object->array_options ['options_rda'] == 'Envoi de mail de présentation' || $object->array_options ['options_rda'] =='Entretien téléphonique')
	{
		print '<h2>Texte à copier-coller dans la description, lors de la création d\'un événement, pour que ces informations apparaissent dans l\'agenda</h2>';
		print '<div class="pair nodrag nodrop">';
		print $langs->trans("ThirdPartyName").' : '.$object->nom.'<br>';
		print $langs->trans('Address'). ' : '.$object->address.'<br>';
		print $langs->trans('Zip').' / '.$langs->trans('Town').' : '.$object->zip.' / '.$object->town.'<br>';
		print $langs->trans('ProspectLevel').' : '.$object->getLibProspLevel().'<br>';
		print $langs->trans('EMail').' : '.$object->email.'<br>';
		print $langs->trans("Web").' : '.$object->url.'<br>';
		print $langs->trans('Phone').' : '.$object->phone.'<br>';
		print 'Rubrique : '.$object->array_options ['options_ru'].'<br>';
		print 'Nom du responsable contacté : '.$object->array_options["options_nrc"].'<br>';
		print 'Campagne commercial : '.$object->array_options ['options_cc'].'<br>';
		if ($object->array_options ['options_tp'] == 1 || $object->array_options ['options_tp'] == 3)
		{
		print 'Budget global pages jaunes : '.$object->array_options ['options_bgpj'].'<br>';
		print 'Retombées pages jaunes : '.$object->array_options ['options_rpja'].'<br>';
		}
		if ($object->array_options ['options_tp'] == 2 || $object->array_options ['options_tp'] == 3)
		{
			print 'Les supports de communication : '.$object->array_options ['options_sc'].'<br>';
			print 'A un site internet : '.$object->array_options ['options_asi'].'<br>';
			print 'Offre des avantages à ses clients : '.$object->array_options ['options_oac'].'<br>';
			print 'A un smartphone : '.$object->array_options ['options_smart'].'<br>';
			print 'A télécharger des applications : '.$object->array_options ['options_ata'].'<br>';
			print 'A pensé à réaliser une application : '.$object->array_options ['options_apra'].'<br>';
			print 'Intéressé par une formation : '.$object->array_options ['options_iform'].'<br>';
		}
		print 'Budget site internet : '.$object->array_options ['options_bsi'].'<br>';
		print 'Retombées sites internet : '.$object->array_options ['options_rsi'].'<br>';
		print 'Engagement restant sur site internet : '.$object->array_options ['options_ersi'].'<br>';
		print 'Type SONCAS de prospect : '.$object->array_options ['options_tsp'].'<br>';
		print 'Commentaire : '.$object->array_options ['options_com'].'<br>';
		print '</div><br>';
	}
		
	if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
	{
		// List of contacts
		show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
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
