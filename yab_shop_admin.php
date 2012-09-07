<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'yab_shop_admin';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.8.1';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'Shopping Cart Plugin (Admin UI & Prefs)';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '4';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '1';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '3';

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
if (@txpinterface == 'admin')
{
	global $yab_shop_admin_lang;
	$yab_shop_admin_lang = array();
	if (yab_shop_table_exist('yab_shop_lang'))
	{
		$yab_shop_admin_lang = yab_shop_get_lang('lang_prefs_admin');
	}

	add_privs('yab_shop_prefs','1');
	register_tab('extensions', 'yab_shop_prefs', yab_shop_admin_lang('shop_prefs'));
	register_callback('yab_shop_prefs', 'yab_shop_prefs');

	add_privs('yab_shop_language','1');
	register_tab('extensions', 'yab_shop_language', yab_shop_admin_lang('shop_lang'));
	register_callback('yab_shop_language', 'yab_shop_language');

	add_privs('plugin_prefs.yab_shop_admin','1');
	register_callback('yab_shop_prefs', 'plugin_prefs.yab_shop_admin');
	register_callback('yab_shop_uninstall','plugin_lifecycle.yab_shop_admin', 'deleted');
}

// define some prefs and language as globals
global $yab_shop_prefs, $yab_shop_public_lang;
if (yab_shop_table_exist('yab_shop_prefs'))
{
	$yab_shop_prefs = yab_shop_get_prefs();
}
if (yab_shop_table_exist('yab_shop_lang'))
{
	$yab_shop_public_lang = yab_shop_get_lang('lang_public');
}

/**
 * On plugin update, compare previously installed version
 * with current plugin version for database update
 *
 * @return boolean
 */
function yab_shop_check_update()
{
 global $yab_shop_prefs, $plugins_ver;

	$old = $yab_shop_prefs['yab_shop_version'];
	$new = $plugins_ver['yab_shop_admin'];

	if (version_compare($old, $new, '<')) {
		return true;
	}

	return false;
}

/**
 * Draw initialise and draw shop prefs
 *
 * @param string $event as $_GET or $_POST
 * @param string $step as $_GET or $_POST
 * @return string echo the admin ui
 */
function yab_shop_prefs($event, $step)
{
	$message = '';
	$content = '';

	$update = yab_shop_check_update();

	if (!$step)
	{
		// check for prefs db-table
		if (yab_shop_table_exist('yab_shop_prefs'))
		{
			if ($update)
			{
				$content = yab_shop_draw_instup('update');
			}
			else
			{
				$content = yab_shop_display_prefs();
			}
		}
		else
		{
			$content = yab_shop_draw_instup();
		}
	}
	else
	{
		$message = $step();
		if (yab_shop_table_exist('yab_shop_prefs'))
		{
			$content = yab_shop_display_prefs();
		}
	}
	echo pagetop(yab_shop_admin_lang('shop_prefs'), $message).$content;
}

/**
 * Draw initialise and draw shop language
 *
 * @param string $event as $_GET or $_POST
 * @param string $step as $_GET or $_POST
 * @return string echo the admin ui
 */
function yab_shop_language($event, $step)
{
	$message = '';
	$content = '';

	$update = yab_shop_check_update();

	if (!$step)
	{
		// check for language db-table
		if (yab_shop_table_exist('yab_shop_lang'))
		{
			if ($update)
			{
				$content = yab_shop_draw_instup('update');
			}
			else
			{
				$content = yab_shop_display_prefs('yab_shop_lang');
			}
		}
		else
		{
			$content = yab_shop_draw_instup();
		}
	}
	else
	{
		$message = $step();
		$content = yab_shop_display_prefs('yab_shop_lang');
	}
	echo pagetop(yab_shop_admin_lang('shop_lang'), $message).$content;
}

/**
 * Draw installation/update form
 *
 * @param string $modus Choose between 'install' or 'update'
 * @return string Draw from
 */
function yab_shop_draw_instup($modus = 'install')
{
	global $plugins_ver;

	if ($modus == 'install') // install
	{
		$heading1 = hed('Yab Shop - '.gTxt('install'), 1, ' class="txp-heading"');
		$heading2 = hed('Yab Shop '.$plugins_ver['yab_shop_admin'].' - '.gTxt('install'), 2);
		$sInput = sInput('yab_shop_first_install');
		$button = gTxt('install');
		$text = 'If you have a version before 0.8.x installed, please remove them.<br />Hit the button to install Yab Shop. ';
	}
	else // update
	{
		$heading1 = hed('Yab Shop - '.gTxt('update'), 1, ' class="txp-heading"');
		$heading2 = hed('Yab Shop '.$plugins_ver['yab_shop_admin'].' - '.gTxt('update'), 2);
		$sInput = sInput('yab_shop_update');
		$button = gTxt('update');
		$text = yab_shop_admin_lang('klick_to_update');
	}

	$form = form(
		fInput('submit', 'submit', $button, 'publish').
		$sInput.
		eInput('yab_shop_prefs')
	);

	$out = $heading1.tag($heading2.graf($text).$form, 'div', ' class="txp-edit"');

	return $out;
}

/**
 * Call installation routine and return message
 *
 * @return string Message
 */
function yab_shop_first_install()
{
	if (yab_shop_install('yab_shop_lang'))
	{
		$message = 'Language table installed';
	}
	else
	{
		$message = 'Could not install language table';
	}
	if (yab_shop_install('yab_shop_prefs'))
	{
		$message .= ', Prefs table installed';
	}
	else
	{
		$message .= ', Could not install prefs table';
	}
	return $message;
}

/**
 * Update the plugin and return a message
 *
 * @return string Message
 */
function yab_shop_update()
{
	global $yab_shop_prefs, $plugins_ver;
	$old = $yab_shop_prefs['yab_shop_version'];
	$new = $plugins_ver['yab_shop_admin'];


	// update from 0.8.0
	if ($old == '' or  $old == '0.8.0' )
	{
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Yab Shop Preferences' where name='shop_prefs' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Yab Shop language saved.' where name='lang_updated' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Hit the button to update Yab Shop.' where name='klick_to_update' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Yab Shop database tables deleted.' where name='tables_delete_success' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Yab Shop preferences saved.' where name='prefs_updated' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Yab Shop public language and localisation' where name='lang_public' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Yab Shop L10n' where name='shop_lang' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_lang')." set val='Yab Shop common preferences' where name='shop_common_prefs' AND lang='en-gb'";
		$rs[] = "update ".safe_pfx('yab_shop_prefs')." set val='0.8.1' where name='yab_shop_version'";
	}

	foreach ($rs as $query)
	{
		$result = safe_query($query);
		if (!$result)
		{
			return 'Could not update plugin from version '.$old.' to version '.$new;
		}
	}
	return 'Plugin successfully updated to version '.$new;
}

/**
 * Look for a given table in DB
 *
 * @param string Table name without prefix
 * @return boolean
 */
function yab_shop_table_exist($tbl)
{
	$tbl = PFX.$tbl;
	$r = mysql_num_rows(safe_query("SHOW TABLES LIKE '".$tbl."'"));
	if ($r)
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Get Yab Shop prefs or language and display it
 *
 * @param string Table without prefix
 * @return string
 */
function yab_shop_display_prefs($table = 'yab_shop_prefs')
{
	// choose step and event
	if ($table == 'yab_shop_lang')
	{
		$heading = hed(yab_shop_admin_lang('shop_lang'), 1, ' class="txp-heading"');
		$submit = sInput('yab_shop_lang_save').eInput('yab_shop_language');
	}
	else
	{
		$heading = hed(yab_shop_admin_lang('shop_prefs'), 1, ' class="txp-heading"');
		$submit = sInput('yab_shop_prefs_save').eInput('yab_shop_prefs').hInput('prefs_id', '1');
	}

  $out = '<div id="page-prefs" class="txp-container">'.
		'<form method="post" action="index.php" class="prefs-form basic">'.
		startTable('', '', 'txp-list').'<tbody>';

	// does exists the choosen language?
	$lang_count = safe_count('yab_shop_lang', "lang='".doSlash(LANG)."'");
	if ($lang_count)
	{
		$lang_code = LANG;
	}
	else
	{
		// fallback language
		$lang_code = 'en-gb';
	}

	// do table dependend query
	if ($table == 'yab_shop_lang')
	{
		$rs = safe_rows_start('*', 'yab_shop_lang', "lang = '".doSlash($lang_code)."' AND event = 'lang_public' ORDER BY event DESC, position ");
	}
	else
	{
		$rs = safe_rows_start('*', 'yab_shop_prefs', "type = 1 AND prefs_id = 1 ORDER BY event DESC, position ");
	}

	// now make a html table from the database table
	$cur_evt = '';
	while ($a = nextRow($rs))
	{
		if ($a['event']!= $cur_evt)
		{
			$cur_evt = $a['event'];
			$out .= n.tr(
				n.tdcs(
					hed(yab_shop_admin_lang($a['event']), 3, ' class="'.$a['event'].'-prefs"')
				, 2)
			, ' class="pref-heading"');
		}

		if ($a['html'] != 'yesnoradio')
		{
			$label = '<label for="'.$a['name'].'">'.yab_shop_admin_lang($a['name']).'</label>';
		}
		else
		{
			$label = yab_shop_admin_lang($a['name']);
		}

		if ($a['html'] == 'text_input')
		{
			// choose different text_input sizes for these fields
			$look_for = array(
				'checkout_section_name',
				'checkout_thanks_site',
				'back_to_shop_link',
				'custom_field_price_name',
				'custom_field_property_1_name',
				'custom_field_property_2_name',
				'custom_field_property_3_name',
				'custom_field_shipping_name',
				'admin_mail',
				'paypal_business_mail',
				'paypal_live_or_sandbox',
				'paypal_certificate_id',
				'paypal_certificates_path',
				'paypal_public_certificate_name',
				'paypal_my_public_certificate_name',
				'paypal_my_private_key_name',
				'google_live_or_sandbox',
				'google_merchant_id',
				'google_merchant_key'
			);
			if (in_array($a['name'], $look_for))
			{
				$size = 25;
			}
			elseif ($table == 'yab_shop_lang')
			{
				$size = 40;
			}
			else
			{
				$size = 4;
			}
			$out_tr = td(
				yab_shop_pref_func('yab_shop_text_input', $a['name'], $a['val'], $size),
				'',
				'pref-value'
			);
		}
		elseif ($a['html'] == 'text_area')
		{
			if ($table == 'yab_shop_lang')
			{
				$size = 37;
			}
			else
			{
				$size = 17;
			}
			$out_tr = td(
				yab_shop_pref_func('yab_shop_text_area', $a['name'], $a['val'], $size),
				'',
				'pref-value'
			);
		}
		else
		{
			if (is_callable($a['html']))
			{
				$out_tr = td(
					yab_shop_pref_func($a['html'], $a['name'], $a['val']),
					'',
					'pref-value'
				);
			}
			else
			{
				$out.= n.td($a['val']);
			}
		}
		$out .= n.tr(
			n.tda($label, ' class="pref-label"').
			n.$out_tr
		);
	}

	$out .= n.'</tbody>'.n.endTable().
		graf(fInput('submit', 'Submit', gTxt('save_button'), 'publish').$submit).
		n.n.'</form></div>';

	return $heading.$out;
}

/**
 * Return config values
 *
 * @param string $what
 * @return string
 */
function yab_shop_config($what)
{
	global $yab_shop_prefs;
	return $yab_shop_prefs[$what];
}

/**
 * Return public language strings
 *
 * @param string $what
 * @return string Return language if exists, otherwise @param
 */
function yab_shop_lang($what)
{
	global $yab_shop_public_lang;
	if (isset($yab_shop_public_lang[$what]))
	{
		return $yab_shop_public_lang[$what];
	}
	else
	{
		return $what;
	}
}

/**
 * Return admin language strings
 *
 * @param string $what
 * @return string Return language if exists, otherwise @param
 */
function yab_shop_admin_lang($what)
{
	global $yab_shop_admin_lang;

	// admin language strings are not stored in db (translating strings from a table with strings from the same table would be weird)
	$lang_arr = array(
		'price'															=> 'Price',
		'quantity'													=> 'Quantity',
		'sub_total'													=> 'Subtotal',
		'to_checkout'												=> '"To checkout" link',
		'empty_cart'												=> 'Empty Cart',
		'add_to_cart'												=> 'Add to Cart',
		'table_caption_content'							=> 'Checkout table caption "Content"',
		'table_caption_change'							=> 'Checkout table caption "Quantity"',
		'table_caption_price'								=> 'Checkout table caption "Price"',
		'custom_field_property_1'						=> 'Item Property 1 (custom field)',
		'custom_field_property_2'						=> 'Item Property 2 (custom field)',
		'custom_field_property_3'						=> 'Item Property 3 (custom field)',
		'checkout_tax_exclusive'						=> 'Checkout for tax exclusive',
		'checkout_tax_inclusive'						=> 'Checkout for tax inclusive',
		'shipping_costs'										=> 'Shipping Costs',
		'grand_total'												=> 'Grand total',
		'checkout_edit'											=> 'Checkout "edit"',
		'checkout_delete'										=> 'Checkout "delete"',
		'promocode_label'										=> 'Promocode label',
		'promocode_button'									=> 'Promocode button',
		'promocode_error'										=> 'Promocode error',
		'promocode_success'									=> 'Promocode success',
		'checkout_required_field_notice'		=> 'Checkout required field notice',
		'checkout_firstname'								=> 'Checkout label first name',
		'checkout_surname'									=> 'Checkout label surname',
		'checkout_street'										=> 'Checkout label street',
		'checkout_postal'										=> 'Checkout label ZIP/postal',
		'checkout_city'											=> 'Checkout label city',
		'checkout_state'										=> 'Checkout label state',
		'checkout_phone'										=> 'Checkout label phone',
		'checkout_email'										=> 'Checkout label email',
		'checkout_message'									=> 'Checkout label message textarea',
		'checkout_tou'											=> 'Checkout Terms of use',
		'checkout_terms_of_use'							=> 'Checkout label for TOU',
		'remember_me'												=> 'Checkout label remember data',
		'forget_me'													=> 'Checkout label forget data',
		'checkout_order'										=> 'Checkout order',
		'checkout_legend'										=> 'Checkout legend',
		'checkout_payment_acc'							=> 'Checkout payment "Purchase on Account"',
		'checkout_payment_pod'							=> 'Checkout payment "Purchase on Delivery"',
		'checkout_payment_pre'							=> 'Checkout payment "Purchase against Prepayment"',
		'checkout_payment_paypal'						=> 'Checkout payment "Purchase via Paypal"',
		'checkout_payment_google'						=> 'Checkout payment "Purchase via Google Checkout"',
		'checkout_payment'									=> 'Checkout payment method',
		'checkout_paypal_forward'						=> 'Checkout forward to Paypal',
		'checkout_paypal_button'						=> 'Checkout Paypal button',
		'checkout_paypal_no_forward'				=> 'Checkout no forward to Paypal',
		'paypal_return_message'							=> 'Paypal return message',
		'checkout_google_forward'						=> 'Checkout forward to Google',
		'checkout_google_no_forward'				=> 'Checkout no forward to Google',
		'checkout_history_back'							=> 'Checkout link text "back"',
		'checkout_mail_error'								=> 'Checkout mail error',
		'checkout_mail_success'							=> 'Checkout mail success',
		'checkout_mail_email_error'					=> 'Invalid mail',
		'checkout_mail_affirmation_error'		=> 'Checkout mail affirmation error',
		'checkout_mail_affirmation_success'	=> 'Checkout mail affirmation success',
		'checkout_mail_field_error'					=> 'Checkout mail field error',
		'admin_mail_subject'								=> 'Admin mail subject',
		'admin_mail_pre_products'						=> 'Admin mail text before products',
		'admin_mail_after_products'					=> 'Admin mail text after products',
		'admin_mail_promocode'							=> 'Admin mail promocode',
		'affirmation_mail_subject'					=> 'Affirmation mail subject',
		'affirmation_mail_pre_products'			=> 'Affirmation mail text before products',
		'affirmation_mail_after_products'		=> 'Affirmation mail text after products',
		'affirmation_mail_promocode'				=> 'Affirmation mail promocode'
	);

	if (gps('event') == 'yab_shop_language')
	{
		if (isset($lang_arr[$what]))
		{
			$lang_array = $lang_arr;
		}
		else
		{
			$lang_array = $yab_shop_admin_lang;
		}
	}
	else
	{
		$lang_array = $yab_shop_admin_lang;
	}

	if (isset($lang_array[$what]))
	{
		return $lang_array[$what];
	}
	else
	{
		return $what;
	}
}

/**
 * Return prefs array from database
 *
 * @return array Prefs
 */
function yab_shop_get_prefs()
{
	$r = safe_rows_start('name, val', 'yab_shop_prefs', 'prefs_id=1');

	if ($r)
	{
		while ($a = nextRow($r))
		{
			$out[$a['name']] = $a['val'];
		}
		return $out;
	}
	return array();
}

/**
 * Return language array from database, depending of a
 * given event (for public or admin ui) and the choosen language
 *
 * @param string $lang_event
 * @return array
 */
function yab_shop_get_lang($lang_event)
{
	// does choosen language exists in yab_shop_lang
	$lang_count = safe_count('yab_shop_lang', "lang='".doSlash(LANG)."'");
	if ($lang_count)
	{
		$lang_code = LANG;
	}
	else
	{
		// fallback language
		$lang_code = 'en-gb';
	}

	$r = safe_rows_start('name, val', 'yab_shop_lang',"lang='".doSlash($lang_code)."' AND event='".doSlash($lang_event)."'");

	if ($r)
	{
		while ($a = nextRow($r))
		{
			$out[$a['name']] = $a['val'];
		}
		return $out;
	}
	return array();
}

/**
 * Return call_user_func()
 *
 * @param string $func
 * @param string $name
 * @param string $val
 * @param integer $size
 * @return mixed
 */
function yab_shop_pref_func($func, $name, $val, $size = '')
{
	if (is_callable('pref_'.$func))
	{
		$func = 'pref_'.$func;
	}
	else
	{
		$func = $func;
	}
	return call_user_func($func, $name, $val, $size);
}

/**
 * Create text input field
 *
 * @param string $name
 * @param string $val
 * @param integer $size
 * @return string HTML text input
 */
function yab_shop_text_input($name, $val, $size = '')
{
	return fInput('text', $name, $val, 'edit', '', '', $size, '', $name);
}

/**
 * Create textarea field
 *
 * @param string $name
 * @param string $val
 * @param integer $size
 * @return string HTML textarea
 */
function yab_shop_text_area($name, $val, $size = '')
{
	return tag($val, 'textarea', ' name="'.$name.'" cols="'.$size.'" rows="4"');
}

/**
 * Save language setting in admin ui
 *
 * @return string Message for pagetop()
 */
function yab_shop_lang_save()
{
	$post = doSlash(stripPost());
	$lang_code = LANG;
	$prefnames = safe_column("name", "yab_shop_lang", "lang = '".doSlash(LANG)."' AND event = 'lang_public'");
	if (!$prefnames)
	{
		$prefnames = safe_column("name", "yab_shop_lang", "lang = 'en-gb' AND event = 'lang_public'");
		$lang_code = 'en-gb';
	}

	foreach($prefnames as $prefname)
	{
		if (isset($post[$prefname]))
		{
			safe_update(
				"yab_shop_lang",
				"val = '".$post[$prefname]."'",
				"name = '".doSlash($prefname)."' AND lang = '".doSlash($lang_code)."'"
			);
		}
  }
	return yab_shop_admin_lang('lang_updated');
}

/**
 * Save prefs setting in admin ui
 *
 * @return string Message for pagetop()
 */
function yab_shop_prefs_save()
{
	$post = doSlash(stripPost());
	$prefnames = safe_column("name", "yab_shop_prefs", "prefs_id = 1 AND type = 1");

	foreach($prefnames as $prefname)
	{
		if (isset($post[$prefname]))
		{
			safe_update(
				"yab_shop_prefs",
				"val = '".$post[$prefname]."'",
				"name = '".doSlash($prefname)."' and prefs_id = 1"
			);
		}
  }
	return yab_shop_admin_lang('prefs_updated');
}

/**
 * Uninstall Yab Shop database tables
 *
 * @return string Message for pagetop()
 */
function yab_shop_uninstall()
{
	$queries = array();
	if (yab_shop_table_exist('yab_shop_lang'))
	{
		$queries[] = 'DROP TABLE `'.PFX.'yab_shop_lang`';
	}
	if (yab_shop_table_exist('yab_shop_prefs'))
	{
		$queries[] = 'DROP TABLE `'.PFX.'yab_shop_prefs`';
	}

	foreach ($queries as $query)
	{
		$result = safe_query($query);
		if (!$result)
		{
			return yab_shop_admin_lang('tables_delete_error');
		}
	}
	return yab_shop_admin_lang('tables_delete_success');
}

/**
 * Installation routine
 *
 * @return boolean
 */
function yab_shop_install($table)
{
	global $txpcfg, $plugins_ver;
	$yab_shop_version = $plugins_ver['yab_shop_admin'];

	$version = mysql_get_server_info();
	$dbcharset = $txpcfg['dbcharset'];

	if (intval($version[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$version))
	{
		$tabletype = " ENGINE=MyISAM ";
	}
	else
	{
	$tabletype = " TYPE=MyISAM ";
	}

	if (isset($dbcharset) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)))
	{
		$tabletype .= " CHARACTER SET = $dbcharset ";
		if (isset($dbcollate))
		{
			$tabletype .= " COLLATE $dbcollate ";
		}
		mysql_query("SET NAMES ".$dbcharset);
	}

	$create_sql = array();

	switch ($table)
	{
		case 'yab_shop_prefs':
			$create_sql[] = "CREATE TABLE `".PFX."yab_shop_prefs` (
				`prefs_id` int(11) NOT NULL,
				`name` varchar(255) NOT NULL,
				`val` varchar(255) NOT NULL default '',
				`type` smallint(5) unsigned NOT NULL default '1',
				`event` varchar(18) NOT NULL default 'shop_prefs',
				`html` varchar(64) NOT NULL default 'text_input',
				`position` smallint(5) unsigned NOT NULL default '0',
				UNIQUE KEY `prefs_idx` (`prefs_id`,`name`),
				KEY `name` (`name`)
			) $tabletype ";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'tax_rate', '19', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'shipping_costs', '7.50', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'shipping_via', 'UPS', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'free_shipping', '20.00', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'currency', 'EUR', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'promocode', '', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'promo_discount_percent', '10', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'tax_inclusive', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'payment_method_acc', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'payment_method_pod', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'payment_method_pre', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'payment_method_paypal', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'payment_method_google', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'using_checkout_state', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'using_tou_checkbox', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'checkout_section_name', 'checkout', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'checkout_thanks_site', 'http://domain/shop/thank-you', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'back_to_shop_link', 'http://domain/shop/', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'custom_field_price_name', 'Price', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'custom_field_property_1_name', 'Size', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'custom_field_property_2_name', 'Color', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'custom_field_property_3_name', 'Variant', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'custom_field_shipping_name', '', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'admin_mail', 'admin@domain.tld', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'order_affirmation_mail', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'use_property_prices', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'use_checkout_images', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'use_encrypted_paypal_button', '1', 1, 'paypal_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_prefilled_country', 'en', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_interface_language', 'en', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_business_mail', 'admin@domain.tld', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_live_or_sandbox', 'sandbox', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_certificate_id', 'CERTIFICATEID', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_certificates_path', '/path/to/your/certificates', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_public_certificate_name', 'paypal_cert.pem', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_my_public_certificate_name', 'my-public-certificate.pem', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'paypal_my_private_key_name', 'my-private-key.pem', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'google_live_or_sandbox', 'sandbox', 1, 'google_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'google_merchant_id', 'your-merchant-id', 1, 'google_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'google_merchant_key', 'your-merchant-id', 1, 'google_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, 'yab_shop_version', '".doSlash($yab_shop_version)."', 2, 'version', '', 50)";
			break;
		case 'yab_shop_lang':
			$create_sql[] = "CREATE TABLE `".PFX."yab_shop_lang` (
				`id` int(9) NOT NULL auto_increment,
				`lang` varchar(16) NOT NULL,
				`name` varchar(64) NOT NULL,
				`val` tinytext,
				`event` varchar(64) NOT NULL,
				`html` varchar(64) NOT NULL default 'text_input',
				`position` smallint(5) unsigned NOT NULL default '50',
				PRIMARY KEY  (`id`),
				UNIQUE KEY `lang` (`lang`,`event`,`name`),
				KEY `lang_2` (`lang`,`event`)
			) $tabletype AUTO_INCREMENT=1 ";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (1, 'en-gb', 'price', 'Price', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (2, 'en-gb', 'quantity', 'Quantity', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (3, 'en-gb', 'sub_total', 'Subtotal', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (4, 'en-gb', 'to_checkout', 'Proceed to Checkout', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (5, 'en-gb', 'empty_cart', 'No Items in Cart', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (6, 'en-gb', 'add_to_cart', 'Add to Cart', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (7, 'en-gb', 'table_caption_content', 'Content', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (8, 'en-gb', 'table_caption_change', 'Quantity', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (9, 'en-gb', 'table_caption_price', 'Price Sum', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (10, 'en-gb', 'custom_field_property_1', 'Size', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (11, 'en-gb', 'custom_field_property_2', 'Color', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (12, 'en-gb', 'custom_field_property_3', 'Variant', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (13, 'en-gb', 'checkout_tax_exclusive', '19% Tax exclusive', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (14, 'en-gb', 'checkout_tax_inclusive', '19% Tax inclusive', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (15, 'en-gb', 'shipping_costs', 'Shipping Costs', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (16, 'en-gb', 'grand_total', 'Total', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (17, 'en-gb', 'checkout_edit', 'Change Qty', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (18, 'en-gb', 'checkout_delete', 'x', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (19, 'en-gb', 'promocode_label', 'Enter promo-code to get a 10% discount on every product!', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (20, 'en-gb', 'promocode_button', 'update', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (21, 'en-gb', 'promocode_error', 'Sorry wrong promo code', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (22, 'en-gb', 'promocode_success', 'Promo code correct! You have a 10% discount on every product now!', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (23, 'en-gb', 'checkout_required_field_notice', 'Fields marked with red labels are required!', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (24, 'en-gb', 'checkout_firstname', 'First Name', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (25, 'en-gb', 'checkout_surname', 'Last Name', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (26, 'en-gb', 'checkout_street', 'Street', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (27, 'en-gb', 'checkout_postal', 'ZIP Code', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (28, 'en-gb', 'checkout_city', 'City', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (29, 'en-gb', 'checkout_state', 'State', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (30, 'en-gb', 'checkout_phone', 'Phone', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (31, 'en-gb', 'checkout_email', 'Email', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (32, 'en-gb', 'checkout_message', 'Message', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (33, 'en-gb', 'checkout_tou', 'Terms Of Use', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (34, 'en-gb', 'checkout_terms_of_use', 'I have read the <a href=\"http:\/\/demoshop.yablo.de\/index.php?s=tou\" title=\"Terms of Use\">Terms of Use</a>!', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (35, 'en-gb', 'remember_me', 'Remember my data for next visit (cookie)', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (36, 'en-gb', 'forget_me', 'Forget my data', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (37, 'en-gb', 'checkout_order', 'Purchase\/Order', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (38, 'en-gb', 'checkout_legend', 'Purchase Form', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (39, 'en-gb', 'checkout_payment_acc', 'Purchase on Account', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (40, 'en-gb', 'checkout_payment_pod', 'Purchase on Delivery', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (41, 'en-gb', 'checkout_payment_pre', 'Purchase against Prepayment', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (42, 'en-gb', 'checkout_payment_paypal', 'Purchase via Paypal', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (43, 'en-gb', 'checkout_payment_google', 'Purchase via Google Checkout', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (44, 'en-gb', 'checkout_payment', 'Payment Method', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (45, 'en-gb', 'checkout_paypal_forward', 'You will be forwarded to Paypal! Please wait&hellip;', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (46, 'en-gb', 'checkout_paypal_button', 'Go to paypal', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (47, 'en-gb', 'checkout_paypal_no_forward', 'Please click the button to proceed!', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (48, 'en-gb', 'paypal_return_message', 'Thank you for purchasing!', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (49, 'en-gb', 'checkout_google_forward', 'You will be forwarded to Google Checkout! Please wait&hellip;', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (50, 'en-gb', 'checkout_google_no_forward', 'Please click the button to proceed!', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (51, 'en-gb', 'checkout_history_back', 'Back to Shop', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (52, 'en-gb', 'checkout_mail_error', 'Your order could not be sent', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (53, 'en-gb', 'checkout_mail_success', 'Your order was successfully sent', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (54, 'en-gb', 'checkout_mail_email_error', 'Email is invalid', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (55, 'en-gb', 'checkout_mail_affirmation_error', 'Your order was successfully sent but could not sent affirmation mail', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (56, 'en-gb', 'checkout_mail_affirmation_success', 'Your order and affirmation were successfully sent.', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (57, 'en-gb', 'checkout_mail_field_error', 'Please fill out the following required fields:.', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (58, 'en-gb', 'admin_mail_subject', 'Shop Order', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (59, 'en-gb', 'admin_mail_pre_products', 'The following was ordered:', 'lang_public', 'text_area', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (60, 'en-gb', 'admin_mail_after_products', 'This text will be on the end of the admin mail', 'lang_public', 'text_area', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (61, 'en-gb', 'admin_mail_promocode', 'The order is already calculated with promo discount', 'lang_public', 'text_area', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (62, 'en-gb', 'affirmation_mail_subject', 'Your Shop Order', 'lang_public', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (63, 'en-gb', 'affirmation_mail_pre_products', 'You have ordered the following:', 'lang_public', 'text_area', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (64, 'en-gb', 'affirmation_mail_after_products', 'This text will on the of the affirmation mail to the buyer (this could be your address).', 'lang_public', 'text_area', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (65, 'en-gb', 'affirmation_mail_promocode', 'Your order is already calculated with promo discount', 'lang_public', 'text_area', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (66, 'en-gb', 'shop_common_prefs', 'Yab Shop common preferences', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (67, 'en-gb', 'tax_rate', 'Tax rate (%)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (68, 'en-gb', 'shipping_costs', 'Shipping costs', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (69, 'en-gb', 'shipping_via', 'Shipping via (Used by Google Checkout)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (70, 'en-gb', 'free_shipping', 'Free shipping at', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (71, 'en-gb', 'currency', 'Currency (ISO 4717)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (72, 'en-gb', 'promocode', 'Promocode key', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (73, 'en-gb', 'promo_discount_percent', 'Given promo discount (%)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (74, 'en-gb', 'tax_inclusive', 'Tax inclusive (otherwise exclusive)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (75, 'en-gb', 'payment_method_acc', 'Use payment method: Purchase on account', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (76, 'en-gb', 'payment_method_pod', 'Use payment method: Purchase on delivery', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (77, 'en-gb', 'payment_method_pre', 'Use payment method: Purchase against prepayment', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (78, 'en-gb', 'payment_method_paypal', 'Use payment method: Paypal checkout', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (79, 'en-gb', 'payment_method_google', 'Use payment method: Google checkout', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (80, 'en-gb', 'using_checkout_state', 'Use state field in checkout form', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (81, 'en-gb', 'using_tou_checkbox', 'Use TOU checkbox in checkout form', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (82, 'en-gb', 'checkout_section_name', 'Name of the checkout section', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (83, 'en-gb', 'checkout_thanks_site', 'Checkout thank-you-site (Full URI)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (84, 'en-gb', 'back_to_shop_link', 'Back-to-shop-link (Full URI)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (85, 'en-gb', 'custom_field_price_name', 'Name of the custom field price', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (86, 'en-gb', 'custom_field_property_1_name', 'Name of the custom field property 1', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (87, 'en-gb', 'custom_field_property_2_name', 'Name of the custom field property 2', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (88, 'en-gb', 'custom_field_property_3_name', 'Name of the custom field property 3', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (89, 'en-gb', 'custom_field_shipping_name', 'Name of the custom field special shipping costs', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (90, 'en-gb', 'admin_mail', 'Admin Mail (Receives the orders)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (91, 'en-gb', 'order_affirmation_mail', 'Send affirmation mail to buyers', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (92, 'en-gb', 'use_property_prices', 'Use property prices', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (93, 'en-gb', 'use_checkout_images', 'Use images in checkout form', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (94, 'en-gb', 'paypal_prefs', 'Preferences for Paypal checkout', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (95, 'en-gb', 'use_encrypted_paypal_button', 'Use an encrypted Paypal button', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (96, 'en-gb', 'paypal_prefilled_country', 'Prefilled country in Paypal interface', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (97, 'en-gb', 'paypal_interface_language', 'Paypal interface language', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (98, 'en-gb', 'paypal_business_mail', 'Email of the Paypal business account', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (99, 'en-gb', 'paypal_live_or_sandbox', 'Live or sandbox', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (100, 'en-gb', 'paypal_certificate_id', 'Paypal certificate ID', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (101, 'en-gb', 'paypal_certificates_path', 'Path to Paypal certificate (absolute)', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (102, 'en-gb', 'paypal_public_certificate_name', 'Name of the public Paypal certificate', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (103, 'en-gb', 'paypal_my_public_certificate_name', 'Name of your public certificate', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (104, 'en-gb', 'paypal_my_private_key_name', 'Name of your private key', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (105, 'en-gb', 'google_prefs', 'Preferences for Google checkout', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (106, 'en-gb', 'google_live_or_sandbox', 'Live or sandbox', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (107, 'en-gb', 'google_merchant_id', 'Google merchant ID', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (108, 'en-gb', 'google_merchant_key', 'Google merchant key', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (109, 'en-gb', 'shop_prefs', 'Yab Shop Preferences', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (110, 'en-gb', 'shop_lang', 'Yab Shop L10n', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (111, 'en-gb', 'lang_public', 'Yab Shop public language and localisation', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (112, 'en-gb', 'prefs_updated', 'Yab Shop preferences saved.', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (113, 'en-gb', 'tables_delete_error', 'Could not delete Yab Shop database tables.', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (114, 'en-gb', 'tables_delete_success', 'Yab Shop database tables deleted.', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (115, 'en-gb', 'klick_to_update', 'Hit the button to update Yab Shop:', 'lang_prefs_admin', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_lang` VALUES (116, 'en-gb', 'lang_updated', 'Yab Shop language saved.', 'lang_prefs_admin', 'text_input', 50)";
			break;
		default:
			break;
	}

	foreach ($create_sql as $query)
	{
		$result = safe_query($query);
		if (!$result)
		{
			return false;
		}
	}
	return true;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
  h1, h2, h3
  h1 code, h2 code, h3 code {
    margin-bottom: 0.6em;
    font-weight: bold
  }
  h1 {
    font-size: 1.4em
  }
  h2 {
    font-size: 1.25em
  }
  h3 {
    margin-bottom: 0;
    font-size: 1.1em
  }
  table {
    margin-bottom: 1em
  }
	td table td {
		padding: 3px 0;
		border-bottom: 1px solid #000000
	}
</style>
# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
*{color: #75111B}This is admin ui plugin for yab_shop, which required this plugin.*

h1. Some help for configuration

# "Yab Shop common preferences":#a1
# "Preferences for Paypal checkout":#a2
# "Preferences for Google checkout":#a3
# "Yab Shop public language and localisation":#a4

h2(#a1). 1. Yab Shop common preferences

This stores the core config for the yab_shop plugin.

|Tax rate (%)|your tax rate in percent|
|Shipping costs|write with dot or comma as decimal delimiter|
|Shipping via (Used by Google Checkout)|shipping method; this time only relevant for google checkout|
|Free shipping at|free shipping limit|
|Currency (ISO 4717)|Paypal supported ISO 4217 currency codes (see "here":https://www.paypal.com/us/cgi-bin/webscr?cmd=_batch-payment-format-outside) and additional support for EEK (Ask for more!)|
|Promocode key|If you want a promo-code support ad a promo-key here (E.g: @'XFHDB'@) otherwise leave it blank|
|Given promo discount (%)|Discount for accepted promo-codes in percent (absolute discounts are not supported!)|
|Tax inclusive (otherwise exclusive)|if @'Yes'@ sums and output calculated with tax inclusive, otherwise exclusive|
|Use payment method: Purchase on account| @'Yes'@ for "purchase on account"|
|Use payment method: Purchase on delivery| @'Yes'@ for "purchase on delivery"|
|Use payment method: Purchase against prepayment| @'Yes'@ for "purchase against prepaiment"|
|Use payment method: Paypal checkout| @'Yes'@ for "paypal as payment method"|
|Use payment method: Google checkout| @'Yes'@ for "google checkout as payment method"|
|Use state field in checkout form| @'Yes' @ displays an additional form for state (useful for US and Canada)|
|Use TOU checkbox in checkout form| @'Yes'@, if you want an required Terms-of-use-checkbox in Checkout|
|Name of the checkout section|name for the created checkout section|
|Checkout thank-you-site (Full URI)|redirect to a special thanks site after a successful order so you can use site and/or conversion tracking (leave it blank if you don't use it)|
|Back-to-shop-link (Full URI)|link for the "back top shoppping" after an order|
|Name of the custom field price|name for the created @custom_field@ for the product price (must be the same)|
|Name of the custom field property 1|name for first product property|
|Name of the custom field property 2|name for second product property|
|Name of the custom field property 3|name for third product property|
|Name of the custom field special shipping costs|name for an extra special shipping custom_field. By using this you can set an product specific shipping cost, which will add to the base shipping cost at checkout (leave it blank if you don't use it).|
|Admin Mail (Receives the orders)|shop mail address, which will receive the orders|
|Send affirmation mail to buyers|if @'Yes'@ an order affirmation mail will be sent to customer and the form email field will be marked as required|
|Use property prices| @'Yes'@ for usage of extra prices for one product property|
|Use images in checkout form|use of article images (existing thumbnails) in checkout table|

h2(#a2). 2. Preferences for Paypal checkout

|Use an encrypted Paypal button|If you are using Paypal it's strongly recommended using an encrypted button. See "here":http://forum.textpattern.com/viewtopic.php?pid=210899#p210899 an "here":https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_encryptedwebpayments#id08A3I0P017Q |
|Prefilled country in Paypal interface|the country, which should prefilled in the paypal form|
|Paypal interface language|en, fr, es or de (maybe more, see paypal site)|
|Email of the Paypal business account|it's your paypal business account  mail|
|Live or sandbox|is this shop in testing use @'sandbox'@ otherwise @'live'@|

*If you are using Paypal it's strongly recommended using an encrypted button*

The button encryption will only working with a php openssl support. In doubt ask your hoster or simple test it. It should output an error if php openssl functions don't exists. For setup a certificate for your paypal account follow the instructions at "paypal":https://www.paypal.com/IntegrationCenter/ic_button-encryption.html#Encryptbuttonsdynamically or have look in this "forum thread":http://forum.textpattern.com/viewtopic.php?pid=210899#p210899 and block all non-encrypted website payments (info on same site). After setting up your account with the certificates, you will have two certificates files, one private key file and a paypal certificate id.These three files (a public paypal certificate, your public certificate and your private key) you have to copy on your server.

But you *MUST copy these in a directory which is outside of DocumentRoot*, nobody should get access on your own private key.

|Paypal certificate ID|generated ID from paypal for your uploaded public certificate|
|ath to Paypal certificate (absolute)|absolute path to your certificate files
(f.i. @/home/user/certificates@)|
|Name of the public Paypal certificate|name of paypal public certificate|
|Name of your public certificate|name of your public certificate|
|Name of your private key|name of your private key|

h2(#a3). 3. Preferences for Google checkout

If you are choose google checkout as payment method and your location is in US I prefer the following setup, 'cause of different tax rates and tax calculation methods:
Set @Tax Rate (%)@ to @0@ and @10  Tax inclusive (otherwise exclusive)@ to @No@. In your google checkout merchant account you can configure the right tax rates for the states. The tax calculation will be done by google. In @Checkout for tax exclusive@ in the "Yab Shop L10n":?event=yab_shop_language you can give a notice to your customers that the tax will be calculated later (by google checkout).

For google checkout you have to set @Shipping via@ with your appropriate shipping method.

|Live or sandbox|is this shop in testing use @'sandbox'@ otherwise @'live'@|
|Google merchant ID|this is your google checkout merchant id|
|Google merchant key|this is your google checkout merchant key|

h2(#a4). 4. Yab Shop public language and localisation

Localized phrases for the html and mail output. If you want prefilled language and localisations, look for an yab_shop_add_language_xx-xx plugin.
# --- END PLUGIN HELP ---
-->
<?php
}
?>