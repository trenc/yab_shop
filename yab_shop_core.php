<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'yab_shop_core';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.8.2';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'Shopping Cart Plugin (Core)';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '';

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * This plugin is released under the GNU General Public License Version 2 and above
 * Version 2: http://www.gnu.org/licenses/gpl-2.0.html
 * Version 3: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (class_exists('\Textpattern\Tag\Registry'))
{
	Txp::get('\Textpattern\Tag\Registry')
		->register('yab_shop_cart')
		->register('yab_shop_cart_items')
		->register('yab_shop_cart_subtotal')
		->register('yab_shop_cart_quantity')
		->register('yab_shop_cart_message')
		->register('yab_shop_cart_link')
		->register('yab_shop_checkout')
		->register('yab_shop_add')
		->register('yab_shop_price')
		->register('yab_shop_show_config')
		->register('yab_shop_custom_field');
}

function yab_shop_cart($atts, $thing = null)
{
	extract(
		lAtts(
			array(
				'output'	=> 'cart'
			),$atts
		)
	);

	global $thisarticle, $prefs;
	$articleid = $thisarticle['thisid'];
	$section = $thisarticle['section'];

	yab_shop_start_session();

	$cart =& $_SESSION['wfcart'];
	if (!is_object($cart))
	{
		$cart = new wfCart();
	}

	$custom_field_price = yab_shop_get_custom_field_names(yab_shop_config('custom_field_price_name'));
	$custom_field_price = ucfirst($custom_field_price);

	yab_promocode();

	if ($section)
	{
		$products = array();
		$products[$articleid] = safe_row("ID as id, Title as name, $custom_field_price as price", "textpattern", "ID = $articleid");
	}

	if (ps('yab-shop-id'))
	{
		$articleid = preg_replace("/[^0-9]/", '', ps('yab-shop-id'));
		$products = array();
		$products[$articleid] = safe_row("ID as id, Title as name, $custom_field_price as price", "textpattern", "ID = $articleid");
	}

	if (ps('add') != '')
	{
		$pqty = preg_replace("/[^0-9]/", '', ps('qty'));
		$product = $products[$articleid];
		$product_ps_1 = ps(yab_shop_ascii(strtolower(yab_shop_config('custom_field_property_1_name'))));
		$product_ps_1 = explode(': ',$product_ps_1);
		$product_property_1 = $product_ps_1[0];
		$product_ps_2 = ps(yab_shop_ascii(strtolower(yab_shop_config('custom_field_property_2_name'))));
		$product_ps_2 = explode(': ',$product_ps_2);
		$product_property_2 = $product_ps_2[0];
		$product_ps_3 = ps(yab_shop_ascii(strtolower(yab_shop_config('custom_field_property_3_name'))));
		$product_ps_3 = explode(': ',$product_ps_3);
		$product_property_3 = $product_ps_3[0];
		$product_id = $product['id'].'-'.yab_shop_check_item($cart, $product['id'], $product_property_1, $product_property_2, $product_property_3);

		$product_price = yab_shop_replace_commas($product['price']);
		$product_db_id = $product['id'];

		if (yab_shop_check_property_prices($product_db_id) != false)
		{
			$property_db_1 = '';
			$property_db_2 = '';
			$property_db_3 = '';
			if (yab_shop_config('custom_field_property_1_name') != '')
			{
				if (ps('yab-shop-id'))
				{
					$field_name_1 = yab_shop_get_custom_field_names(yab_shop_config('custom_field_property_1_name'));
					$property_db_1 = safe_field("`$field_name_1`", 'textpattern', "ID = $product_db_id");
				}
				else
				{
					$property_db_1 = $thisarticle[strtolower(yab_shop_config('custom_field_property_1_name'))];
				}
			}
			if (yab_shop_config('custom_field_property_2_name') != '')
			{
				if (ps('yab-shop-id'))
				{
					$field_name_2 = yab_shop_get_custom_field_names(yab_shop_config('custom_field_property_2_name'));
					$property_db_2 = safe_field("`$field_name_2`", 'textpattern', "ID = $product_db_id");
				}
				else
				{
					$property_db_2 = $thisarticle[strtolower(yab_shop_config('custom_field_property_2_name'))];
				}
			}
			if (yab_shop_config('custom_field_property_3_name') != '')
			{
				if (ps('yab-shop-id'))
				{
					$field_name_3 = yab_shop_get_custom_field_names(yab_shop_config('custom_field_property_3_name'));
					$property_db_3 = safe_field("`$field_name_3`", 'textpattern', "ID = $product_db_id");
				}
				else
				{
					$property_db_3 = $thisarticle[strtolower(yab_shop_config('custom_field_property_3_name'))];
				}
			}

			if (!empty($product_ps_1[1]))
			{
				$product_db_1_array = explode(';', $property_db_1);
				foreach ($product_db_1_array as $product_db_1_part)
				{
					$product_db_1_part_array = explode('--', $product_db_1_part);
					if (trim($product_db_1_part_array[0]) == trim($product_ps_1[0]))
					{
						$product_price = yab_shop_replace_commas(preg_replace('/[^{\,,\.}0-9]/', '', $product_db_1_part_array[1]));
						break;
					}
				}
			}
			if (!empty($product_ps_2[1]))
			{
				$product_db_2_array = explode(';', $property_db_2);
				foreach ($product_db_2_array as $product_db_2_part)
				{
					$product_db_2_part_array = explode('--', $product_db_2_part);
					if (trim($product_db_2_part_array[0]) == trim($product_ps_2[0]))
					{
						$product_price = yab_shop_replace_commas(preg_replace('/[^{\,,\.}0-9]/', '', $product_db_2_part_array[1]));
						break;
					}
				}
			}
			if (!empty($product_ps_3[1]))
			{
				$product_db_3_array = explode(';', $property_db_3);
				foreach ($product_db_3_array as $product_db_3_part)
				{
					$product_db_3_part_array = explode('--', $product_db_3_part);
					if (trim($product_db_3_part_array[0]) == trim($product_ps_3[0]))
					{
						$product_price = yab_shop_replace_commas(preg_replace('/[^{\,,\.}0-9]/', '', $product_db_3_part_array[1]));
						break;
					}
				}
			}
		}

		$product_spec_shipping = '';
		if (yab_shop_config('custom_field_shipping_name') != '')
		{
			if (ps('yab-shop-id'))
			{
				$shipping_field_name = yab_shop_get_custom_field_names(yab_shop_config('custom_field_shipping_name'));
				$product_spec_shipping = safe_field("`$shipping_field_name`", 'textpattern', "ID = $product_id");
			}
			else
			{
				$product_spec_shipping = $thisarticle[strtolower(yab_shop_config('custom_field_shipping_name'))];
			}

		}
		$cart->add_item($product_id, $articleid, $pqty, $product_price, $product['name'], $product_property_1, $product_property_2, $product_property_3, $product_spec_shipping);
	}

	if (ps('edit') != '')
	{
		$qty = preg_replace("/[^0-9]/", '', ps('editqty'));
		$id = preg_replace("/[^{\\-}0-9]/", '', ps('editid'));

		if ($qty != '' and $id != '')
		{
			$cart->edit_item($id, $qty);
		}
		else
		{
			$cart->del_item($id);
		}
	}

	if (ps('del') != '')
	{
		$id = preg_replace("/[^{\\-}0-9]/", '', ps('editid'));
		$cart->del_item($id);
	}

	if ($output == 'cart')
	{
		if ($thing === null)
		{
			$out = yab_shop_cart_items();
		}
		else
		{
			$out = parse($thing);
		}
	}
	else
	{
		$out = '';
	}

	return $out;
}

function yab_shop_cart_items()
{
	$cart =& $_SESSION['wfcart'];
	if (!is_object($cart))
	{
		$cart = new wfCart();
	}
	return yab_shop_build_cart($cart);
}

function yab_shop_cart_subtotal($atts)
{
	extract(
		lAtts(
			array(
				'showalways'	=> '1',
				'break'				=> br,
				'label'				=> 'Subtotal: ',
				'wraptag'			=> '',
				'class'				=> ''
			),$atts
		)
	);

	$cart =& $_SESSION['wfcart'];
	if (!is_object($cart))
	{
		$cart = new wfCart();
	}

	if ($label)
	{
		$label = htmlspecialchars($label);
	}

	$out = '';
	if ($showalways == '1')
	{
		$out .= $label;
		$out .= yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total);
		$out = doTag($out, $wraptag, $class).$break;
	}
	else
	{
		if ($cart->itemcount > 0)
		{
			$out .= $label;
			$out .= yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total);
			$out = doTag($out, $wraptag, $class).$break;
		}
	}
	return $out;
}

function yab_shop_cart_quantity($atts)
{
	extract(
		lAtts(
			array(
				'output'	=> 'single',
				'showalways'	=> '1',
				'break'				=> br,
				'label'				=> 'Quantity: ',
				'wraptag'			=> '',
				'class'				=> ''
			),$atts
		)
	);

	if ($label)
	{
		$label = htmlspecialchars($label);
	}

	$cart =& $_SESSION['wfcart'];
	if (!is_object($cart))
	{
		$cart = new wfCart();
	}

	$qty = 0;
	$out = '';

	if ($output == 'single')
	{
		$qty += $cart->itemcount;
	}
	else
	{
		if ($cart->itemcount > 0)
		{
			foreach ($cart->get_contents() as $item)
			{
				$qty += $item['qty'];
			}
		}
	}

	if ($showalways == '1')
	{
		$out .= $label.$qty;
		$out = doTag($out, $wraptag, $class).$break;
	}
	else
	{
		if ($cart->itemcount > 0)
		{
			$out .= $label.$qty;
			$out = doTag($out, $wraptag, $class).$break;
		}
	}
	return $out;
}

function yab_shop_cart_message($atts)
{
	extract(
		lAtts(
			array(
				'add'		=> 'Product has been added',
				'edit'	=> 'Cart has been updated',
				'del'		=> 'Product has been deleted',
				'break'				=> br,
				'wraptag'			=> '',
				'class'				=> ''
			),$atts
		)
	);

	if (ps('add'))
	{
		$message = htmlspecialchars($add);
		$out = doTag($message, $wraptag, $class).$break;
	}
	elseif (ps('edit'))
	{
		$message = htmlspecialchars($edit);
		$out = doTag($message, $wraptag, $class).$break;
	}
	elseif (ps('del'))
	{
		$message = htmlspecialchars($del);
		$out = doTag($message, $wraptag, $class).$break;
	}
	else
	{
		$out = '';
	}
	return $out;
}

function yab_shop_cart_link($atts)
{
	extract(
		lAtts(
			array(
				'label'				=> yab_shop_lang('to_checkout'),
				'break'				=> br,
				'showalways'	=> '1',
				'wraptag'			=> '',
				'class'				=> ''
			),$atts
		)
	);

	$cart =& $_SESSION['wfcart'];
	if (!is_object($cart))
	{
		$cart = new wfCart();
	}

	$url = pagelinkurl(array('s' => yab_shop_config('checkout_section_name')));
	$label = htmlspecialchars($label);
	$out = '';

	if ($class and !$wraptag)
	{
		$link = href($label, $url, ' title="'.$label.'" class="'.$class.'"');
	}
	else
	{
		$link = href($label, $url, ' title="'.$label.'"');
	}

	if ($showalways == '1')
	{
		$out = doTag($link, $wraptag, $class).$break;
	}
	else
	{
		if ($cart->itemcount > 0)
		{
		$out = doTag($link, $wraptag, $class).$break;
		}
	}

	return $out;
}

function yab_shop_checkout($atts)
{
	extract(
		lAtts(
			array(
				'summary'	=> 'This table shows the cart with selected products and the total sum of the products.'
			),$atts
		)
	);

	yab_shop_start_session();

	$cart =& $_SESSION['wfcart'];
	if (!is_object($cart))
	{
		$cart = new wfCart();
	}
	yab_promocode();

	if ($cart->itemcount > 0)
	{
		$affirmation = yab_shop_config('order_affirmation_mail');
		$to_shop = graf(tag(yab_shop_lang('checkout_history_back'), 'a', ' href="'.yab_shop_config('back_to_shop_link').'"'), ' class="history-back"');
		$checkout_display = yab_shop_build_checkout_table($cart, $summary);
		$checkout_display .= yab_build_promo_input($cart);
		$checkout_message = graf(yab_shop_lang('checkout_required_field_notice'), ' class="yab-shop-notice"');
		$checkout_form = yab_shop_build_checkout_form();

		if (ps('order') != '')
		{
			$ps_order = array();
			$ps_order = yab_shop_clean_input($_POST);
			$checkout_message = graf(yab_shop_lang('checkout_mail_field_error'), ' class="yab-shop-required-notice" id="yab-shop-checkout-anchor"');

			$notice = yab_shop_check_required_fields($ps_order, $affirmation);

			if ($notice != '')
			{
				$checkout_message .= tag($notice, 'ul', ' class="yab-shop-notice"');
				$checkout_form = yab_shop_build_checkout_form();
			}
			else
			{
				$checkout_display = '';
				$checkout_form = '';

				if ($affirmation != '1')
				{
					yab_remember(ps('remember'), ps('forget'), ps('checkbox_type'));
					switch (ps('payment'))
					{
						case yab_shop_lang('checkout_payment_paypal'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							if (yab_shop_config('use_encrypted_paypal_button') != '1')
							{
								$checkout_form = yab_shop_build_paypal_form($cart);
							}
							else
							{
								$checkout_form = yab_shop_build_paypal_encrypted_form($cart);
							}
							$cart->empty_cart();
							break;
						case yab_shop_lang('checkout_payment_google'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							$checkout_form = yab_shop_build_google_form($cart);
							$cart->empty_cart();
							break;
						default:
							$checkout_message = graf(yab_shop_lang('checkout_mail_error'), ' class="yab-shop-message"');
							if (yab_shop_shop_mail(yab_shop_config('admin_mail'), yab_shop_lang('admin_mail_subject'), yab_shop_build_mail_body($cart, $ps_order)))
							{
								$cart->empty_cart();
								yab_shop_redirect(yab_shop_config('checkout_thanks_site'));
								$checkout_message = graf(yab_shop_lang('checkout_mail_success'), ' class="yab-shop-message"').$to_shop;
							}
							break;
					}
				}
				else
				{
					yab_remember(ps('remember'), ps('forget'), ps('checkbox_type'));
					switch (ps('payment'))
					{
						case yab_shop_lang('checkout_payment_paypal'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							if (yab_shop_config('use_encrypted_paypal_button') != '1')
							{
								$checkout_form = yab_shop_build_paypal_form($cart);
							}
							else
							{
								$checkout_form = yab_shop_build_paypal_encrypted_form($cart);
							}
							$cart->empty_cart();
							break;
						case yab_shop_lang('checkout_payment_google'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							$checkout_form = yab_shop_build_google_form($cart);
							$cart->empty_cart();
							break;
						default:
							$checkout_message = graf(yab_shop_lang('checkout_mail_error'), ' class="yab-shop-message"');
							if (yab_shop_shop_mail(yab_shop_config('admin_mail'), yab_shop_lang('admin_mail_subject'), yab_shop_build_mail_body($cart, $ps_order)))
							{
								$checkout_message = graf(yab_shop_lang('checkout_mail_affirmation_error'), ' class="yab-shop-message"');
								if (yab_shop_shop_mail($ps_order['email'], yab_shop_lang('affirmation_mail_subject'), yab_shop_build_mail_body($cart, $ps_order, '1')))
								{
									yab_shop_redirect(yab_shop_config('checkout_thanks_site'));
									$checkout_message = graf(yab_shop_lang('checkout_mail_affirmation_success'), ' class="yab-shop-message"').$to_shop;
								}
								$cart->empty_cart();
							}
							break;
					}
				}
			}
		}
		return $checkout_display.$checkout_message.$checkout_form;
	}
	else
	{
		$checkout_display = graf(yab_shop_lang('empty_cart'), ' class="yab-empty"');

		if (gps('merchant_return_link') != '')
		{
			yab_shop_redirect(yab_shop_config('checkout_thanks_site'));
			$checkout_display = graf(yab_shop_lang('paypal_return_message'), ' class="yab-shop-message"').graf(tag(yab_shop_lang('checkout_history_back'), 'a', ' href="'.yab_shop_config('back_to_shop_link').'"'), ' class="history-back"');
		}

		return $checkout_display;
	}
}

function yab_shop_add()
{
	global $thisarticle, $is_article_list;

	$id = $thisarticle['thisid'];
	$property_1_name = yab_shop_config('custom_field_property_1_name');
	$property_2_name = yab_shop_config('custom_field_property_2_name');
	$property_3_name = yab_shop_config('custom_field_property_3_name');
	$hinput = '';
	$purl = permlinkurl_id($id);
	$script = '';

	if ($is_article_list == true)
	{
		$hinput = hInput('yab-shop-id', $id);
		if (serverSet('REQUEST_URI') and serverSet('HTTP_HOST'))
		{
			$purl = PROTOCOL.serverSet('HTTP_HOST').serverSet('REQUEST_URI');
		}
	}

	if (yab_shop_config('use_property_prices') == '1')
	{
		$script .= yab_shop_property_prices($id).n;
	}

	$add_form = tag(
		$hinput.
		yab_shop_build_custom_select_tag($property_1_name, yab_shop_lang('custom_field_property_1')).
		yab_shop_build_custom_select_tag($property_2_name, yab_shop_lang('custom_field_property_2')).
		yab_shop_build_custom_select_tag($property_3_name, yab_shop_lang('custom_field_property_3')).
		graf(
			fInput('text','qty','1','','','','1').
			fInput('submit','add',yab_shop_lang('add_to_cart'),'submit'),
			' class="yab-add"'
		),
	'form', ' method="post" action="'.$purl.'#yab-shop-form-'.$id.'" id="yab-shop-form-'.$id.'"'
	);

	return $script.$add_form;
}

function yab_shop_price($atts)
{
	extract(
		lAtts(
			array(
				'wraptag'	=> 'span',
				'class'		=> 'yab-shop-price'
			),$atts
		)
	);

	global $thisarticle;
	$id = $thisarticle['thisid'];

	$custom_field = yab_shop_config('custom_field_price_name');
	$out = yab_shop_custom_field(array('name' => $custom_field));
	$out = tag($out, $wraptag, ' id="yab-shop-price-'.$id.'" class="'.$class.'"');

	return $out;
}

function yab_shop_show_config($atts)
{
	extract(
		lAtts(
			array(
				'name'	=> ''
			),$atts
		)
	);

	$config_value = yab_shop_config($name);

	if ($config_value)
	{
		return $config_value;
	}
	else
	{
		return 'No config value with this name available.';
	}
}

function yab_shop_custom_field($atts)
{
	global $thisarticle, $prefs;
	assert_article();

	extract(
		lAtts(
			array(
				'name'			=> @$prefs['custom_1_set'],
				'default'	 => '',
			),$atts
		)
	);

	$currency = yab_shop_config('currency');
	$name = strtolower($name);
	$custom_field_price_name = strtolower(yab_shop_config('custom_field_price_name'));

	if (!empty($thisarticle[$name]))
	{

		if ($name == $custom_field_price_name)
		{
			$out = $thisarticle[$name];
			$out = yab_shop_replace_commas($out);
			$out = yab_shop_currency_out($currency, 'cur').yab_shop_currency_out($currency, 'toform', $out);
		}
		else
		{
			$out = $thisarticle[$name];
			$out = explode(';', $out);
			$out = str_replace('--', ': '.yab_shop_currency_out($currency, 'cur'), $out);
			$out = yab_shop_type_select_custom($out, $name);
		}
	}
	else
	{
		$out = $default;
	}
	return $out;
}

function yab_shop_property_prices($id = '')
{
	$out = '';

	if (!empty($id))
	{
		$base_price = yab_shop_custom_field(array('name' => yab_shop_config('custom_field_price_name')));
		$cust_field = yab_shop_check_property_prices($id);

		if ($cust_field != false)
		{
			$cust_field = yab_shop_ascii($cust_field);
			$out .= '<script type="text/javascript">'.n;
			$out .= '/* <![CDATA[ */'.n;
			$out .= '	$(document).ready(function() {'.n;
			$out .= '		$("#select-'.$cust_field.'-'.$id.'").change(function () {'.n;
			$out .= '			$("#select-'.$cust_field.'-'.$id.' option:selected").each(function() {'.n;
			$out .= '				var str = $(this).text().match(/: /) ? $(this).text().replace(/.*: /, "") : "'.$base_price.'";'.n;
			$out .= '			$("#yab-shop-price-'.$id.'").text(str);'.n;
			$out .= '			})'.n;
			$out .= '		})'.n;
			$out .= '	});'.n;
			$out .= '/* ]]> */'.n;
			$out .= '</script>';
		}
	}
	return $out;
}

function yab_shop_field_names($in)
{
	global $prefs;

	foreach ($prefs as $val => $key)
	{
		if ($key == $in)
		{
			return $val;
			break;
		}
	}
}

function yab_shop_get_custom_field_names($val)
{
	return str_replace('_set', '', yab_shop_field_names($val));
}

function yab_shop_check_property_prices($id = '')
{
	global $thisarticle, $is_article_list;

	if (yab_shop_config('use_property_prices') == '1')
	{
		$regex = '/--[0-9]*(,|.|)[0-9]{2}/';
		$thisproperty_1 = '';
		$thisproperty_2 = '';
		$thisproperty_3 = '';

		if ($is_article_list == true)
		{
			$prop_1 = yab_shop_config('custom_field_property_1_name');
			$prop_2 = yab_shop_config('custom_field_property_2_name');
			$prop_3 = yab_shop_config('custom_field_property_3_name');
			$list_prop_1 = yab_shop_get_custom_field_names($prop_1);
			$list_prop_2 = yab_shop_get_custom_field_names($prop_2);
			$list_prop_3 = yab_shop_get_custom_field_names($prop_3);
			$article_properties = array();
			$article_properties = safe_row("$list_prop_1 as property_1, $list_prop_2 as property_2, $list_prop_3 as property_3", 'textpattern', "ID = $id");
			$article_property_1 = $article_properties['property_1'];
			$article_property_2 = $article_properties['property_2'];
			$article_property_3 = $article_properties['property_3'];
		}
		else
		{
			$prop_1 = strtolower(yab_shop_config('custom_field_property_1_name'));
			$prop_2 = strtolower(yab_shop_config('custom_field_property_2_name'));
			$prop_3 = strtolower(yab_shop_config('custom_field_property_3_name'));
			$article_property_1 = $thisarticle[$prop_1];
			$article_property_2 = $thisarticle[$prop_2];
			$article_property_3 = $thisarticle[$prop_3];
		}

		if (isset($article_property_1))
		{
			$thisproperty_1 = $article_property_1;
		}
		if (isset($article_property_2))
		{
			$thisproperty_2 = $article_property_2;
		}
		if (isset($article_property_3))
		{
			$thisproperty_3 = $article_property_3;
		}

		if (preg_match($regex, $thisproperty_1))
		{
			return $prop_1;
		}
		elseif (preg_match($regex, $thisproperty_2))
		{
			return $prop_2;
		}
		elseif (preg_match($regex, $thisproperty_3))
		{
			return $prop_3;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}

function yab_shop_check_required_fields($ps_order, $affirmation)
{
	$notice = '';

	if (yab_shop_config('using_tou_checkbox') == '1')
	{
		if (!isset($_POST['tou']))
		{
			$ps_order['tou|r'] = '';
		}
	}

	foreach ($ps_order as $key => $ps)
	{
		if (preg_match('/\|r$/', $key) and $ps == '')
		{
			$notice .= tag(yab_shop_lang('checkout_'.preg_replace('/\|r$/', '', $key).''), 'li');
		}
	}

	if ($affirmation == '1' and !is_valid_email($ps_order['email']))
	{
		$notice .= tag(yab_shop_lang('checkout_mail_email_error'), 'li');
	}
	elseif ($affirmation == '0' and $ps_order['email'] != '')
	{
		if (!is_valid_email($ps_order['email']))
		{
			$notice .= tag(yab_shop_lang('checkout_mail_email_error'), 'li');
		}
	}
	return $notice;
}

function yab_shop_check_item($cart, $productid, $product_property_1, $product_property_2, $product_property_3)
{
	$i = 0;
	foreach ($cart->get_contents() as $item)
	{
		if (preg_match('/'.$productid.'-/', $item['itemid']))
		{
			$i++;
			if ($item['property_1'] == $product_property_1 and $item['property_2'] == $product_property_2 and $item['property_3'] == $product_property_3)
			{
				$i = str_replace($productid.'-', '', $item['itemid']);
				break;
			}
		}
	}
	return $i;
}

function yab_shop_return_input($input)
{
	$output = '';
	if (ps('order') != '')
	{
		$output = yab_shop_clean_input($_POST[$input]);
	}
	elseif (cs('yab_shop_remember') == 1)
	{
		$output = cs('yab_shop_'.$input);
	}

	return $output;
}

function yab_shop_build_paypal_form($cart)
{
	$subdomain = '';
	if (yab_shop_config('paypal_live_or_sandbox') == 'sandbox')
	{
		$subdomain = '.sandbox';
	}

	$tax = '0.00';
	if (yab_shop_config('tax_inclusive') == '0')
	{
		$tax = number_format(yab_shop_calculate_sum('tax'),2);
	}

	$email = '';
	if (ps('email'))
	{
		$email = hInput('email', yab_shop_return_input('email')).n;
	}
	$state = '';
	if (ps('state|r'))
	{
		$state = hInput('state', yab_shop_return_input('state|r')).n;
	}

	$action = 'https://www'.$subdomain.'.paypal.com/cgi-bin/webscr';
	$message = yab_shop_lang('checkout_paypal_no_forward');
	$message2 = yab_shop_lang('checkout_paypal_forward');
	$business_email = yab_shop_config('paypal_business_mail');
	$country = yab_shop_config('paypal_prefilled_country');
	$lc = yab_shop_config('paypal_interface_language');
	$section = pagelinkurl(array('s' => yab_shop_config('checkout_section_name')));
	$currency = yab_shop_config('currency');
	$shipping = yab_shop_shipping_costs();

	$i = 0;
	$products = '';
	foreach ($cart->get_contents() as $item)
	{
		$i++;
		$products .=	hInput('item_name_'.$i, $item['name']).n.
									hInput('amount_'.$i, $item['price']).n.
									hInput('quantity_'.$i, $item['qty']).n;

		$properties = '';
		if (!empty($item['property_1']))
		{
			$properties .=	hInput('on0_'.$i, yab_shop_lang('custom_field_property_1')).n.
											hInput('os0_'.$i, $item['property_1']).n;
		}
		if (!empty($item['property_2']))
		{
			if (!empty($item['property_3']))
			{
				$properties .=	hInput('on1_'.$i, yab_shop_lang('custom_field_property_2').'/'.yab_shop_lang('custom_field_property_3')).n.
												hInput('os1_'.$i, $item['property_2'].'/'.$item['property_3']).n;
			}
			else
			{
				$properties .=	hInput('on1_'.$i, yab_shop_lang('custom_field_property_2')).n.
												hInput('os1_'.$i, $item['property_2']).n;
			}
		}
		else
		{
			if (!empty($item['property_3']))
			{
				$properties .=	hInput('on1_'.$i, yab_shop_lang('custom_field_property_3')).n.
												hInput('os1_'.$i, $item['property_3']).n;
			}
		}
		$products .= $properties;
	}

	$form = '';
	$form = '<script type="text/javascript">function doPaypal(){var New="'.$message2.'";document.getElementById("yabshoppaypalforward").innerHTML=New;document.getElementById("yab-paypal-form").submit();document.getElementById("yabpaypalsubmit").style.display="none"}window.onload=doPaypal;</script>';
	$form .= graf($message, ' class="yab-shop-message" id="yabshoppaypalforward"');
	$form .= tag(
		hInput('cmd', '_ext-enter').n.
		hInput('redirect_cmd', '_cart').n.
		hInput('upload', '1').n.
		hInput('business', $business_email).n.
		hInput('return', $section).n.
		hInput('country', $country).n.
		hInput('lc', $lc).n.
		hInput('currency_code', $currency).n.
		hInput('tax_cart', $tax).n.
		hInput('shipping_1', $shipping).n.
		hInput('first_name', yab_shop_return_input('firstname|r')).n.
		hInput('last_name', yab_shop_return_input('surname|r')).n.
		$email.
		hInput('address1', yab_shop_return_input('street|r')).n.
		hInput('city', yab_shop_return_input('city|r')).n.
		hInput('zip', yab_shop_return_input('postal|r')).n.
		$state.
		$products.
		fInput('submit','paypal', yab_shop_lang('checkout_paypal_button'), 'submit', '', '', '', '', 'yabpaypalsubmit').n,'form', ' method="post" action="'.$action.'" id="yab-paypal-form"'
	);
	return $form;
}

function yab_shop_build_paypal_encrypted_form($cart)
{
	global $tempdir;

	$subdomain = '';
	if (yab_shop_config('paypal_live_or_sandbox') == 'sandbox')
	{
		$subdomain = '.sandbox';
	}

	$email = '';
	if (ps('email'))
	{
		$email = yab_shop_return_input('email');
	}
	$state = '';
	if (ps('state|r'))
	{
		$state = yab_shop_return_input('state|r');
	}

	$tax = '0.00';
	if (yab_shop_config('tax_inclusive') == '0')
	{
		$tax = number_format(yab_shop_calculate_sum('tax'),2);
	}

	$action = 'https://www'.$subdomain.'.paypal.com/cgi-bin/webscr';
	$message = yab_shop_lang('checkout_paypal_no_forward');
	$message2 = yab_shop_lang('checkout_paypal_forward');
	$business_email = yab_shop_config('paypal_business_mail');
	$country = yab_shop_config('paypal_prefilled_country');
	$lc = yab_shop_config('paypal_interface_language');
	$section = pagelinkurl(array('s' => yab_shop_config('checkout_section_name')));
	$currency = yab_shop_config('currency');
	$shipping = yab_shop_shipping_costs();

	$myPublicCertificate = yab_shop_config('paypal_certificates_path').'/'.yab_shop_config('paypal_my_public_certificate_name');
	$myPrivateKey = yab_shop_config('paypal_certificates_path').'/'.yab_shop_config('paypal_my_private_key_name');
	$CertificateID = yab_shop_config('paypal_certificate_id');
	$PayPalPublicCertificate = yab_shop_config('paypal_certificates_path').'/'.yab_shop_config('paypal_public_certificate_name');

	$paypal = new PayPalEWP();
	$paypal->setTempDir($tempdir);
	$paypal->setCertificate($myPublicCertificate, $myPrivateKey);
	$paypal->setCertificateID($CertificateID);
	$paypal->setPayPalCertificate($PayPalPublicCertificate);

	$parameters = array(
		'cmd'					 => '_ext-enter',
		'redirect_cmd'	=> '_cart',
		'upload'				=> '1',
		'business'			=> $business_email,
		'cert_id'			 => $CertificateID,
		'return'				=> $section,
		'country'			 => $country,
		'lc'						=> $lc,
		'currency_code' => $currency,
		'tax_cart'			=> $tax,
		'shipping_1'		=> $shipping,
		'first_name'		=> yab_shop_return_input('firstname|r'),
		'last_name'		 => yab_shop_return_input('surname|r'),
		'email'				 => $email,
		'address1'			=> yab_shop_return_input('street|r'),
		'city'					=> yab_shop_return_input('city|r'),
		'zip'					 => yab_shop_return_input('postal|r'),
		'state'				 => $state
	);

	$i = 0;
	foreach ($cart->get_contents() as $item)
	{
		$i++;
		$parameters['item_name_'.$i] = $item['name'];
		$parameters['amount_'.$i]		 = number_format($item['price'], 2);
		$parameters['quantity_'.$i]	 = $item['qty'];

		if (!empty($item['property_1']))
		{
			$parameters['on0_'.$i] = yab_shop_lang('custom_field_property_1');
			$parameters['os0_'.$i] = $item['property_1'];
		}
		if (!empty($item['property_2']))
		{
			if (!empty($item['property_3']))
			{
				$parameters['on1_'.$i] = yab_shop_lang('custom_field_property_2').'/'.yab_shop_lang('custom_field_property_3');
				$parameters['os1_'.$i] = $item['property_2'].'/'.$item['property_3'];
			}
			else
			{
				$parameters['on1_'.$i] = yab_shop_lang('custom_field_property_2');
				$parameters['os1_'.$i] = $item['property_2'];
			}
		}
		else
		{
			if (!empty($item['property_3']))
			{
				$parameters['on1_'.$i] = yab_shop_lang('custom_field_property_3');
				$parameters['os1_'.$i] = $item['property_3'];
			}
		}
	}

	if (ps('email'))
	{
		$parameters['email'] = yab_shop_return_input('email');
	}

	$encryptedButton = $paypal->encryptButton($parameters);

	$form = '<script type="text/javascript">function doPaypal(){var New="'.$message2.'";document.getElementById("yabshoppaypalforward").innerHTML=New;document.getElementById("yab-paypal-form").submit();document.getElementById("yabpaypalsubmit").style.display="none"}window.onload=doPaypal;</script>';
	$form .= graf($message, ' class="yab-shop-message" id="yabshoppaypalforward"');
	$form .= tag(
		hInput('cmd', '_s-xclick').n.
		hInput('encrypted', $encryptedButton).n.
		fInput('submit','paypal', yab_shop_lang('checkout_paypal_button'), 'submit', '', '', '', '', 'yabpaypalsubmit').n,'form', ' method="post" action="'.$action.'" id="yab-paypal-form"'
	);

	switch ($paypal->error)
	{
		case 0:
			$out = $form;
			break;
		case 1:
			$out = 'Paypal certificate id is not set!';
			break;
		case 2:
			$out = 'Your public and/or private certificate is not readable! Please check permissions, names and paths!';
			break;
		case 3:
			$out = 'Paypal public certificate is not readable! Please check permissions, names and paths!';
			break;
		case 4:
			$out = 'Seems to be openssl is not supported!';
			break;
		default:
			$out =	'Unkown error occured!';
	}
	return $out;
}

function yab_shop_build_google_form($cart)
{
	$merchant_id = yab_shop_config('google_merchant_id');
	$merchant_key = yab_shop_config('google_merchant_key');
	$message = yab_shop_lang('checkout_google_no_forward');
	$message2 = yab_shop_lang('checkout_google_forward');
	$currency = yab_shop_config('currency');
	$shipping = yab_shop_shipping_costs();

	$domain = 'https://checkout.google.com/api/checkout/v2/checkout/Merchant/'.$merchant_id;
	if (yab_shop_config('google_live_or_sandbox') == 'sandbox')
	{
		$domain = 'https://sandbox.google.com/checkout/api/checkout/v2/checkout/Merchant/'.$merchant_id;
	}

	$gitems = '';
	$gitem_property_1 = '';
	$gitem_property_2 = '';
	$gitem_property_3 = '';
	$gitem_properties = '';

	foreach ($cart->get_contents() as $item)
	{

		$gi = 0;
		if (!empty($item['property_1']))
		{
			$gitem_property_1 = yab_shop_lang('custom_field_property_1').': '.$item['property_1'];
			$gi++;
		}
		if (!empty($item['property_2']))
		{
			$gitem_property_2 = ': '.yab_shop_lang('custom_field_property_2').': '.$item['property_2'];
			$gi++;
		}
		if (!empty($item['property_3']))
		{
			$gitem_property_3 = ': '.yab_shop_lang('custom_field_property_3').': '.$item['property_3'];
			$gi++;
		}

		if ($gi != 0)
		{
			$gitem_properties = tag($gitem_property_1.$gitem_property_2.$gitem_property_3,'item-description');
		}
		else
		{
			$gitem_properties = tag(' ','item-description');
		}

		$gitems .= tag(
			tag($item['name'], 'item-name').
			tag($item['price'], 'unit-price' , ' currency="'.$currency.'"').
			tag($item['qty'], 'quantity').
			$gitem_properties
		, 'item');
	}

	$gcart_xml = '<?xml version="1.0" encoding="UTF-8"?>'.n;
	$gcart_xml .= tag(
		tag(
			tag($gitems, 'items')
		, 'shopping-cart').
		tag(
			tag(
				tag(
					tag(
						tag($shipping, 'price', ' currency="'.$currency.'"')
					, 'flat-rate-shipping', ' name="'.yab_shop_config('shipping_via').'"')
				, 'shipping-methods')
			, 'merchant-checkout-flow-support')
		, 'checkout-flow-support')
	, 'checkout-shopping-cart', ' xmlns="http://checkout.google.com/schema/2"');

	$gsig = CalcHmacSha1($gcart_xml, $merchant_key);
	$base64_gcart = base64_encode($gcart_xml);
	$base64_gsig = base64_encode($gsig);

	$form = graf($message, ' class="yab-shop-message" id="yabshopgoogleforward"');
	$form .= tag(
		hInput('cart', $base64_gcart).n.
		hInput('signature', $base64_gsig).n.
		'<input type="image" name="Google Checkout" alt="Fast checkout through Google" src="http://checkout.google.com/buttons/checkout.gif?merchant_id='.$merchant_id.'&w=160&h=43&style=white&variant=text&loc=en_US" id="yabgooglesubmit" />'
	,'form', ' method="post" action="'.$domain.'" id="yab-google-form"');

	return $form;
}

function yab_shop_build_checkout_form()
{
	$req1 = '';
	$state = '';
	$tou = '';
	if (yab_shop_config('order_affirmation_mail') == '1')
	{
		$req1 = ' yab-shop-required';
	}
	if (yab_shop_config('using_checkout_state') == '1')
	{
		$state = graf(
			tag(yab_shop_lang('checkout_state'), 'label', ' for="state"').
			fInput('text', 'state|r', yab_shop_return_input('state|r'), '', '', '', '', '', 'state'), ' class="yab-shop-required yab-shop-state"'
		);
	}
	if (yab_shop_config('using_tou_checkbox') == '1')
	{
		$tou = graf(
			checkbox('tou', '1', '0', '', 'yab-tou').
			tag(yab_shop_lang('checkout_terms_of_use'), 'label', ' for="yab-tou"'),
			' class="yab-shop-required tou"'
			);
	}

	$form = tag(
		fieldset(
			graf(
				tag(yab_shop_lang('checkout_firstname'), 'label', ' for="firstname"').
				fInput('text', 'firstname|r', yab_shop_return_input('firstname|r'), '', '', '', '', '', 'firstname'), ' class="yab-shop-required yab-shop-firstname"'
			).
			graf(
				tag(yab_shop_lang('checkout_surname'), 'label', ' for="surname"').
				fInput('text', 'surname|r', yab_shop_return_input('surname|r'), '', '', '', '', '', 'surname'), ' class="yab-shop-required yab-shop-surname"'
			).
			graf(
				tag(yab_shop_lang('checkout_street'), 'label', ' for="street"').
				fInput('text', 'street|r', yab_shop_return_input('street|r'), '', '', '', '', '', 'street'), ' class="yab-shop-required yab-shop-street"'
			).
			graf(
				tag(yab_shop_lang('checkout_city'), 'label', ' for="city" class="city"').
				fInput('text', 'city|r', yab_shop_return_input('city|r'), '', '', '', '', '', 'city'),
			' class="yab-shop-required yab-shop-city"'
			).
			graf(
				tag(yab_shop_lang('checkout_postal'), 'label', ' for="postal"').
				fInput('text', 'postal|r', yab_shop_return_input('postal|r'), '', '', '', '', '', 'postal'),
			' class="yab-shop-required yab-shop-zip"'
			).
			$state.
			graf(
				tag(yab_shop_lang('checkout_phone'), 'label', ' for="phone"').
				fInput('text', 'phone', yab_shop_return_input('phone'), '', '', '', '', '', 'phone'),
			' class="yab-shop-phone"'
			).
			graf(
				tag(yab_shop_lang('checkout_email'), 'label', ' for="email"').
				fInput('text', 'email', yab_shop_return_input('email'), '', '', '', '', '', 'email'),
			' class="yab-shop-email'.$req1.'"'
			).
			yab_shop_checkout_payment_methods().
			graf(
				tag(yab_shop_lang('checkout_message'), 'label', ' for="message"').
				'<textarea cols="40" rows="5" name="message" id="message">'.yab_shop_return_input('message').'</textarea>',
			' class="yab-shop-text"'
			).
			$tou.
			graf(yab_remember_checkbox(), ' class="tou remember"').
			graf(
			fInput('submit', 'order', yab_shop_lang('checkout_order'), 'submit'),
			' class="submit"'
			)
		),'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'#yab-shop-checkout-anchor" id="yab-checkout-form"'
	);

	return $form;
}

function yab_shop_build_checkout_table($cart, $summary, $no_change = false)
{
	$tax_inclusive = yab_shop_config('tax_inclusive');

	$checkout_display = tr(
		tag(yab_shop_lang('table_caption_content'), 'th').
		tag(yab_shop_lang('table_caption_change'), 'th', ' class="yab-checkout-change"').
		tag(yab_shop_lang('table_caption_price'), 'th', ' class="yab-checkout-price"')
	).n;

	$class = '';
	if ($no_change != false)
	{
		$class = ' class="yab-shop-nochange"';
	}

	foreach ($cart->get_contents() as $item)
	{
		$item_price = yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price']);
		$item_price_sum = yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price'] * $item['qty']);

		$out_qty = yab_shop_checkout_qty_edit($item['itemid'], $item['qty']);
		if ($no_change != false)
		{
			$out_qty = $item['qty'];
		}

		$checkout_display .= tr(
			td(
				yab_shop_checkout_image($item['txpid']).href($item['name'], permlinkurl_id($item['txpid'])).
				tag(
				yab_shop_build_checkout_customs($item['property_1'], yab_shop_lang('custom_field_property_1'), yab_shop_config('custom_field_property_1_name')).
				yab_shop_build_checkout_customs($item['property_2'], yab_shop_lang('custom_field_property_2'), yab_shop_config('custom_field_property_2_name')).
				yab_shop_build_checkout_customs($item['property_3'], yab_shop_lang('custom_field_property_3'), yab_shop_config('custom_field_property_3_name')).
				yab_shop_build_checkout_customs($item_price, yab_shop_lang('price'), yab_shop_config('custom_field_price_name'))
				, 'ul')
			).
			td($out_qty, '', 'yab-checkout-change').
			td($item_price_sum, '', 'yab-checkout-price')
		).n;
	}

	if ($tax_inclusive == '0')
	{
		$checkout_display .= tr(
			tda(yab_shop_lang('sub_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), ' class="yab-checkout-sum"'),
			' class="yab-checkout-subtotal"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('checkout_tax_exclusive'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax')), ' class="yab-checkout-sum"'),
			' class="yab-checkout-tax"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('shipping_costs'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_shipping_costs()), ' class="yab-checkout-sum"'),
			' class="yab-checkout-shipping"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('grand_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('brutto') + yab_shop_shipping_costs()), ' class="yab-checkout-sum"'),
			' class="yab-checkout-total"'
		);
	}
	else
	{
		$checkout_display .= tr(
			tda(yab_shop_lang('sub_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), ' class="yab-checkout-sum"'),
			' class="yab-checkout-subtotal"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('shipping_costs'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_shipping_costs()), ' class="yab-checkout-sum"'), ' class="yab-checkout-shipping"').n;
		$checkout_display .= tr(
			tda(yab_shop_lang('grand_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total + yab_shop_shipping_costs()), ' class="yab-checkout-sum"'),
			' class="yab-checkout-total"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('checkout_tax_inclusive'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax')), ' class="yab-checkout-sum"'),
			' class="yab-checkout-tax"'
		);
	}
	$checkout_display = tag($checkout_display, 'table', ' id="yab-checkout-table" summary="'.$summary.'"'.$class);
	return $checkout_display;
}

function yab_shop_build_cart($cart)
{
	$cart_display = '';

	if ($cart->itemcount > 0)
	{
		foreach ($cart->get_contents() as $item)
		{
			$cart_display .= tag(
				href($item['name'], permlinkurl_id($item['txpid'])).
				tag(
					tag(yab_shop_lang('price').':&nbsp;'.yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price']), 'li', ' class="yab-price"').
						tag(yab_shop_lang('quantity').':&nbsp;'.$item['qty'], 'li', ' class="yab-qty"'),
				'ul'),
			'li', ' class="yab-item"');
		}
		$cart_display = tag($cart_display, 'ul', ' class="yab-cart"');
		$cart_display .= tag(yab_shop_lang('sub_total').':&nbsp;'.yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), 'span', ' class="yab-subtotal"');
		$cart_display .= tag(yab_shop_lang('to_checkout'), 'a', ' href="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'" title="'.yab_shop_lang('to_checkout').'" class="yab-to-checkout"');
	}
	else
	{
		$cart_display = tag(yab_shop_lang('empty_cart'), 'span', ' class="yab-empty"');
	}
	return $cart_display;
}

function yab_shop_build_mail_body($cart, $ps_order, $affirmation = '0')
{
	$line_1 = '----------------------------------------------------------------------';
	$line_2 = '======================================================================';
	$line_3 = '______________________________________________________________________';

	$eol = "\r\n";
	if (!is_windows())
	{
		$eol = "\n";
	}

	$promo_admin	= '';
	$promo_client = '';
	if ($cart->get_promocode() == 1)
	{
		$promo_admin	.= yab_shop_lang('admin_mail_promocode').$eol;
		$promo_client .= yab_shop_lang('affirmation_mail_promocode').$eol;
	}

	if ($affirmation != '1')
	{
		$body = yab_shop_lang('admin_mail_pre_products').$eol;
	}
	else
	{
		$body = yab_shop_lang('affirmation_mail_pre_products').$eol;
	}

	$state = '';
	if (yab_shop_config('using_checkout_state') == '1')
	{
		$state = $eol.yab_shop_lang('checkout_state').': '.$ps_order['state|r'];
	}

	$body .=
		$eol.yab_shop_lang('checkout_firstname').': '.$ps_order['firstname|r'].
		$eol.yab_shop_lang('checkout_surname').': '.$ps_order['surname|r'].
		$eol.yab_shop_lang('checkout_street').': '.$ps_order['street|r'].
		$eol.yab_shop_lang('checkout_city').': '.$ps_order['city|r'].
		$eol.yab_shop_lang('checkout_postal').': '.$ps_order['postal|r'].
		$state.
		$eol.yab_shop_lang('checkout_phone').': '.$ps_order['phone'].
		$eol.yab_shop_lang('checkout_email').': '.$ps_order['email'].
		$eol.yab_shop_lang('checkout_payment').': '.$ps_order['payment'].
		$eol.yab_shop_lang('checkout_message').': '.$ps_order['message'].$eol;
	$body .= $eol.$line_1.$eol;

	foreach ($cart->get_contents() as $item)
	{
		$item_price = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price']);
		$item_price_sum = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price'] * $item['qty']);

		$body .= $eol.$item['name'].
			$eol.$item['qty'].' x '.$item_price.' = '.$item_price_sum.$eol;
		$body .= yab_shop_build_mail_customs($item['property_1'], yab_shop_lang('custom_field_property_1'), $eol);
		$body .= yab_shop_build_mail_customs($item['property_2'], yab_shop_lang('custom_field_property_2'), $eol);
			$body .= yab_shop_build_mail_customs($item['property_3'], yab_shop_lang('custom_field_property_3'), $eol);
	}

	if (yab_shop_config('tax_inclusive') == '0')
	{
		$body .= $eol.$line_1.
			$eol.$eol.yab_shop_lang('sub_total').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total).
			$eol.yab_shop_lang('checkout_tax_exclusive').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax')).
			$eol.yab_shop_lang('shipping_costs').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_shipping_costs()).
			$eol.$eol.$line_2.
			$eol.$eol.yab_shop_lang('grand_total').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('brutto') + yab_shop_shipping_costs()).
			$eol.$line_3.$eol.$line_2;
	}
	else
	{
		$body .= $eol.$line_1.
			$eol.$eol.yab_shop_lang('sub_total').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total).
			$eol.yab_shop_lang('shipping_costs').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_shipping_costs()).
			$eol.$eol.$line_2.
			$eol.$eol.yab_shop_lang('grand_total').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total + yab_shop_shipping_costs()).
			$eol.$line_3.$eol.$line_2.
			$eol.$eol.yab_shop_lang('checkout_tax_inclusive').': '.yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax')).$eol;
	}

	if ($affirmation != '1')
	{
		$body .= $promo_admin.$eol.yab_shop_lang('admin_mail_after_products');
	}
	else
	{
		$body .= $promo_client.$eol.yab_shop_lang('affirmation_mail_after_products');
	}

	return $body;
}

function yab_shop_build_mail_customs($item, $lang, $eol)
{
	$out = '';
	if (!empty($item))
	{
		$out = $lang.': '.$item.$eol;
	}
	return $out;
}

function yab_shop_shop_mail($to, $subject, $body)
{
	global $prefs;

	if ($prefs['override_emailcharset'] and is_callable('utf8_decode'))
	{
		$charset = 'ISO-8859-1';
		$subject = utf8_decode($subject);
		$body = utf8_decode($body);
	}
	else
	{
	$charset = 'UTF-8';
	}

	if (!is_callable('mail'))
	{
		return false;
	}
	else
	{
		$eol = "\r\n";
		if (!is_windows())
		{
			$eol = "\n";
		}
		$sitename = yab_shop_mailheader($prefs['sitename'], 'text');
		$subject = yab_shop_mailheader($subject, 'text');

		return mail($to, $subject, $body,
			'From: '.$sitename.' <'.yab_shop_config('admin_mail').'>'.''.
			$eol.'Reply-To: '.$sitename.' <'.yab_shop_config('admin_mail').'>'.''.
			$eol.'X-Mailer: Textpattern (yab_shop)'.
			$eol.'Content-Transfer-Encoding: 8-bit'.
			$eol.'Content-Type: text/plain; charset="'.$charset.'"'.$eol
		);
	}
}

function yab_shop_mailheader($string, $type)
{
	global $prefs;

	if (!strstr($string,'=?') and !preg_match('/[\x00-\x1F\x7F-\xFF]/', $string))
	{
		if ("phrase" == $type)
		{
			if (preg_match('/[][()<>@,;:".\x5C]/', $string))
			{
				$string = '"'.strtr($string, array("\\" => "\\\\", '"' => '\"')).'"';
			}
		}
		elseif ("text" != $type)
		{
			trigger_error('Unknown encode_mailheader type', E_USER_WARNING);
		}
		return $string;
	}

	if ($prefs['override_emailcharset'])
	{
		$start = '=?ISO-8859-1?B?';
		$pcre	= '/.{1,42}/s';
	}
	else
	{
		$start = '=?UTF-8?B?';
		$pcre	= '/.{1,45}(?=[\x00-\x7F\xC0-\xFF]|$)/s';
	}

	$end = '?=';
	$sep = "\r\n";

	if (!is_windows())
	{
		$sep = "\n";
	}

	preg_match_all($pcre, $string, $matches);

	return $start.join($end.$sep.' '.$start, array_map('base64_encode',$matches[0])).$end;
}

function yab_shop_clean_input($input, $modus = 'output')
{
	if (empty($input))
	{
		$cleaned = $input;
	}

	if (is_array($input))
	{
		foreach ($input as $key => $val)
		{
			$cleaned[$key] = yab_shop_clean_input($val);
		}
	}
	else
	{
		$cleaned = str_replace(array('=', '&', '"', '\'', '<', '>', ';', '\\'), '', $input);
		if ($modus != 'output')
		{
			$cleaned = doSlash($cleaned);
		}
		else
		{
			$cleaned = doStrip($cleaned);
		}
	}
	return $cleaned;
}

function yab_shop_checkout_payment_methods()
{
	$option = '';
	$attr = '';
	$select = '';
	$content = '';
	$hidden_value = '';
	$label = tag(yab_shop_lang('checkout_payment'), 'label', ' for="payment"');
	$b = 0;

	if (yab_shop_config('payment_method_acc') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_acc');
		$option .= tag(yab_shop_lang('checkout_payment_acc'), 'option', ' value="'.yab_shop_lang('checkout_payment_acc').'"');
	}
	if (yab_shop_config('payment_method_pod') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_pod');
		$option .= tag(yab_shop_lang('checkout_payment_pod'), 'option', ' value="'.yab_shop_lang('checkout_payment_pod').'"');
	}
	if (yab_shop_config('payment_method_pre') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_pre');
		$option .= tag(yab_shop_lang('checkout_payment_pre'), 'option', ' value="'.yab_shop_lang('checkout_payment_pre').'"');
	}
	if (yab_shop_config('payment_method_paypal') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_paypal');
		$option .= tag(yab_shop_lang('checkout_payment_paypal'), 'option', ' value="'.yab_shop_lang('checkout_payment_paypal').'"');
	}
	if (yab_shop_config('payment_method_google') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_google');
		$option .= tag(yab_shop_lang('checkout_payment_google'), 'option', ' value="'.yab_shop_lang('checkout_payment_google').'"');
	}

	if ($b == 0)
	{
		$content .= 'No payment method available!';
		$attr .= ' style="font-weight: bold; color: #9E0000"';
	}
	elseif ($b == 1)
	{
		$select .=	fInput('hidden', 'payment', $hidden_value, '', '', '', '', '', 'payment').
								tag($hidden_value, 'span', ' id="yab-shop-one-payment"');
		$content .= $label.$select;
	}
	else
	{
		$select .= tag($option, 'select', ' name="payment" id="payment"');
		$content .= $label.$select;
	}

	$payment = graf($content, ' class="yab-shop-payments"'.$attr);
	return $payment;
}

function yab_shop_checkout_qty_edit($itemid, $qty)
{
	$edit_form = tag(
			tag(
				hInput('editid', $itemid).
				fInput('text','editqty',$qty,'','','','1').
				fInput('submit','edit',yab_shop_lang('checkout_edit'), 'submit-edit').
				fInput('submit','del',yab_shop_lang('checkout_delete'), 'submit-del'),
			'div'),
	'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'"'
	);
	return $edit_form;
}

function yab_shop_build_checkout_customs($item, $lang, $conf)
{
	$conf = yab_shop_ascii($conf);
	$out = '';
	if (!empty($item))
	{
		$item = htmlspecialchars($item);
		$out = tag($lang.': '.$item, 'li', ' class="yab-checkout-item-'.$conf.'"');
	}
	return $out;
}

function yab_shop_build_custom_select_tag($custom_field, $label_name)
{
	global $thisarticle;
	$custom_field_low = strtolower($custom_field);
	$out = '';
	$id = $thisarticle['thisid'];

	if (!empty($thisarticle[$custom_field_low]))
	{
		$custom_field_ascii = yab_shop_ascii($custom_field);
		$out =	graf(
			tag($label_name.': ', 'label', ' for="select-'.$custom_field_ascii.'-'.$id.'"').
			yab_shop_custom_field(array('name' => $custom_field)),' class="yab-add-select-'.$custom_field_ascii.'"'
		);
	}
	return $out;
}

function yab_shop_shipping_costs()
{
	$cart           =& $_SESSION['wfcart'];
	$sub_total      = $cart->total;
	$shipping_costs = yab_shop_replace_commas(yab_shop_config('shipping_costs'));
	$free_shipping  = yab_shop_replace_commas(yab_shop_config('free_shipping'));

	if (yab_shop_config('custom_field_shipping_name') != '')
	{
		$special_cost = 0;
		foreach ($cart->get_contents() as $item)
		{
			$special_cost += floatval(yab_shop_replace_commas($item['spec_shipping']));
		}
		$shipping_costs += $special_cost;
	}

	if ($sub_total >= $free_shipping)
	{
		$shipping_costs = 0;
	}

	return $shipping_costs;
}

function yab_shop_calculate_sum($what)
{
	$cart =& $_SESSION['wfcart'];
	$tax_rate = yab_shop_replace_commas(yab_shop_config('tax_rate'));
	$tax_inclusive = yab_shop_config('tax_inclusive');
	$sub_total = $cart->total;
	$calculated = array();

	if ($tax_inclusive == '0')
	{
		$calculated['netto'] = $sub_total;
		$calculated['brutto'] = yab_shop_rounding($sub_total * ($tax_rate / 100 + 1));
		$calculated['tax'] = yab_shop_rounding($sub_total * ($tax_rate / 100));

	}
	else
	{
		$calculated['brutto'] = $sub_total;
		$calculated['netto'] = yab_shop_rounding($sub_total / ($tax_rate / 100 + 1), 'down');
		$calculated['tax'] = yab_shop_rounding($calculated['netto'] * ($tax_rate / 100));
	}
	return $calculated[$what];
}

function yab_shop_rounding($value, $modus = 'up')
{
	$decimal = 2;

	$rounded = ceil($value * pow(10, $decimal)) / pow(10, $decimal);
	if ($modus != 'up')
	{
		$rounded = floor($value * pow(10, $decimal)) / pow(10, $decimal);
	}
	return $rounded;
}

function yab_shop_replace_commas($input)
{
	$replaced = str_replace(',', '.', $input);
	return $replaced;
}

function yab_shop_currency_out($currency, $what, $toform = '')
{
	$toform = floatval($toform);

	switch ($currency)
	{
		case 'USD':
			$out = array(
				'cur'			 => '$',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'GBP':
			$out = array(
				'cur'			 => '£',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'CAD':
			$out = array(
				'cur'			 => '$',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'JPY':
			$out = array(
				'cur'			 => '￥',
				'toform'	=> number_format($toform)
			);
			break;
		case 'AUD':
			$out = array(
				'cur'			 => '$',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'NZD':
			$out = array(
				'cur'			 => '$',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'CHF':
			$out = array(
				'cur'			 => 'SFr.',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'HKD':
			$out = array(
				'cur'			 => '$',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'SGD':
			$out = array(
				'cur'			 => '$',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'SEK':
			$out = array(
				'cur'			 => 'kr',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'DKK':
			$out = array(
				'cur'			 => 'kr',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'PLN':
			$out = array(
				'cur'			 => 'zł',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'NOK':
			$out = array(
				'cur'			 => 'kr',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'HUF':
			$out = array(
				'cur'			 => 'Ft',
				'toform'	=> number_format($toform)
			);
			break;
		case 'CZK':
			$out = array(
				'cur'			 => 'Kč',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'EEK':
			$out = array(
				'cur'			 => 'kr',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'RSD':
			$out = array(
				'cur'			 => 'din ',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'BRL':
			$out = array(
				'cur'			 => 'R$',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		case 'ZAR':
			$out = array(
				'cur'			 => 'R',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'PHP':
			$out = array(
				'cur'			 => 'PhP ',
				'toform'	=> number_format($toform, 2)
			);
			break;
		case 'RON':
			$out = array(
				'cur'			 => 'lei ',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
		default:
			$out = array(
				'cur'			 => '€',
				'toform'	=> number_format($toform, 2, ',', '.')
			);
			break;
	}
	return $out[$what];
}

function yab_shop_type_select_custom($array, $name = 'type')
{
	global $thisarticle;
	$id = $thisarticle['thisid'];
	$name_ascii = yab_shop_ascii($name);

	$out = '<select name="'.$name_ascii.'" id="select-'.$name_ascii.'-'.$id.'">'.n;
	foreach ($array as $option)
	{
		$option = htmlspecialchars(trim($option));
		$out .= t.'<option value="'.$option.'">'.$option.'</option>'.n;
	}
	$out .= '</select>'.n;
	return $out;
}

function yab_shop_start_session()
{
	if (headers_sent())
	{
		if (!isset($_SESSION))
		{
			$_SESSION = array();
		}
		return false;
	}
	elseif (!isset($_SESSION))
	{
		session_cache_limiter("must-revalidate");
		session_start();
		return true;
	}
	else
	{
		return true;
	}
}

function yab_shop_ascii($input)
{
	if (!preg_match('/![a-zA-Z0-9]/', $input))
	{
		$out = htmlentities($input, ENT_NOQUOTES, 'UTF-8');
		$out = preg_replace('/[^{$a-zA-Z0-9}]/', '', $out);
		$out = strtolower($out);
	}
	else
	{
		$out = strtolower($input);
	}
	return $out;
}

function yab_promocode()
{
	$cart =& $_SESSION['wfcart'];
	$pcode = ps('yab-promo');

	if (yab_shop_config('promocode') != '')
	{
		if ($pcode != '')
		{
			if ($pcode == yab_shop_config('promocode'))
			{
				$cart->set_promocode(1);
				foreach ($cart->get_contents() as $item)
				{
					if ($item['promocode'] == 0)
					{
						$cart->edit_promocodes($item['itemid'], 1);
						$cart->edit_promo_prices($item['itemid'], yab_calc_promo_prices($item['price']));
					}
				}
			}
		}
		else
		{
			if ($cart->get_promocode() == 1)
			{
				foreach ($cart->get_contents() as $item)
				{
					if ($item['promocode'] == 0)
					{
						$cart->edit_promocodes($item['itemid'], 1);
						$cart->edit_promo_prices($item['itemid'], yab_calc_promo_prices($item['price']));
					}
				}
			}
		}
	}
	return true;
}

function yab_build_promo_input($cart)
{
	$pcode = ps('yab-promo');
	$out = '';

	if (yab_shop_config('promocode') != '')
	{
		if ($pcode != '')
		{
			if ($pcode == yab_shop_config('promocode'))
			{
				$out .= graf(yab_shop_lang('promocode_success'), ' class="yab-shop-notice yab-promo-success"');
			}
			else
			{
				$out .= graf(yab_shop_lang('promocode_error'), ' class="yab-shop-notice yab-promo-error"').
								tag(
									graf(
										tag(yab_shop_lang('promocode_label'), 'label', ' for="yab-promo"').
											fInput('text','yab-promo','','','','','','','yab-promo').
											fInput('submit','',yab_shop_lang('promocode_button'), 'yab-promo-submit'),
										' class="yab-promocode"'),
									'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'"'
								);
			}
		}
		else
		{
			if ($cart->get_promocode() == 1)
			{
				$out .= graf(yab_shop_lang('promocode_success'), ' class="yab-shop-notice yab-promo-success"');
			}
			else
			{
				$out .= tag(
					graf(
						tag(yab_shop_lang('promocode_label'), 'label', ' for="yab-promo"').
						fInput('text','yab-promo','','','','','','','yab-promo').
						fInput('submit','',yab_shop_lang('promocode_button'), 'yab-promo-submit'),
					' class="yab-promocode"'),
				'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'"'
				);
			}
		}
	}
	return $out;
}

function yab_calc_promo_prices($price = false)
{
	$price_tmp	= $price * yab_shop_config('promo_discount_percent') / 100;
	$price			= $price - $price_tmp;
	return $price;
}

function yab_remember_checkbox()
{
	$rememberCookie = cs('yab_shop_remember');
	$remember = ps('remember');
	$forget = ps('forget');
	if ($rememberCookie === '')
	{
		$checkbox_type = 'remember';
		$remember = 1;
	}
	elseif ($rememberCookie == 1)
	{
		$checkbox_type = 'forget';
	}
	else
	{
		$checkbox_type = 'remember';
	}

	if ($checkbox_type == 'forget')
	{
		if ($forget == 1)
		{
			yab_shop_destroyCookies();
		}
			$checkbox = checkbox('forget', 1, $forget, '', 'yab-remember').tag(yab_shop_lang('forget_me'), 'label', ' for="yab-remember"');
	}
	else
	{
		if ($remember != 1)
		{
			yab_shop_destroyCookies();
		}
			$checkbox = checkbox('remember', 1, $remember, '', 'yab-remember').tag(yab_shop_lang('remember_me'), 'label', ' for="yab-remember"');
	}

	$checkbox .= ' '.hInput('checkbox_type', $checkbox_type);

	return $checkbox;
}

function yab_shop_setCookies($first_name, $last_name, $street, $zip, $city, $state, $phone, $email)
{
	$cookietime = time() + (365*24*3600);
	ob_start();
	setcookie("yab_shop_firstname|r",	$first_name,	$cookietime, "/");
	setcookie("yab_shop_surname|r",	$last_name,	$cookietime, "/");
	setcookie("yab_shop_street|r",	$street,	$cookietime, "/");
	setcookie("yab_shop_postal|r",	$zip,	$cookietime, "/");
	setcookie("yab_shop_city|r",	$city,	$cookietime, "/");
	setcookie("yab_shop_state|r",	$state,	$cookietime, "/");
	setcookie("yab_shop_phone",	$phone,	$cookietime, "/");
	setcookie("yab_shop_email", $email, $cookietime, "/");
	setcookie("yab_shop_last",	date("H:i d/m/Y"),$cookietime,"/");
	setcookie("yab_shop_remember", '1', $cookietime, "/");
}

function yab_shop_destroyCookies()
{
	$cookietime = time()-3600;
	ob_start();
	setcookie("yab_shop_firstname|r",	'', $cookietime, "/");
	setcookie("yab_shop_surname|r", '', $cookietime, "/");
	setcookie("yab_shop_street|r",	 '', $cookietime, "/");
	setcookie("yab_shop_postal|r",	 '', $cookietime, "/");
	setcookie("yab_shop_city|r",	 '', $cookietime, "/");
	setcookie("yab_shop_state|r",	 '', $cookietime, "/");
	setcookie("yab_shop_phone",	 '', $cookietime, "/");
	setcookie("yab_shop_email",	 '', $cookietime, "/");
	setcookie("yab_shop_last",	'', $cookietime, "/");
	setcookie("yab_shop_remember", '0', $cookietime + (365*25*3600), "/");
}

function yab_remember($remember, $forget, $checkbox_type)
{
	if ($remember == 1 || $checkbox_type == 'forget' && $forget != 1)
	{
		yab_shop_setCookies(ps('firstname|r'), ps('surname|r'), ps('street|r'), ps('postal|r'), ps('city|r'), ps('state|r'), ps('phone'), ps('email'));
	}
	else
	{
		yab_shop_destroyCookies();
	}
}

function yab_shop_checkout_image($articleid)
{
	global $img_dir;
	$out = '';

	if (yab_shop_config('use_checkout_images') == '1')
	{
		$rsi = safe_row('*', 'textpattern', "ID = $articleid");
		if ($rsi)
		{
			if (is_numeric($rsi['Image']))
			{
				$rs = safe_row('*', 'txp_image', 'id = '.intval($rsi['Image']));
				if ($rs)
				{
					if ($rs['thumbnail'])
					{
						extract($rs);
						$alt = htmlspecialchars($alt);
						$out .= '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'" />';
					}
				}
			}
		}
	}
	return $out;
}

function yab_shop_redirect($uri)
{
	if ($uri != '')
	{
		txp_status_header('303 See Other');
		header('Location: '.$uri);
		header('Connection: close');
		header('Content-Length: 0');
	}
	else
	{
		return false;
	}
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
	h1, h2, h3,
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
	h4 {
		margin-bottom: 0;
		font-size: 1em
	}
</style>
# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
h1. yab_shop help

*{color: #75111B}This plugin requires the admin ui plugin (yab_shop_admin) and the 3rd-party plugin (yab_shop_3rd_party)*

* "Installation":#install
* "Update":#update
* "Setup":#setup
* "Tags for output":#tags
* "Important notes on setup and maintaining":#notes
* "Uninstallation":#uninstall
* "Other stuff":#stuff

h2(#install). Installation

If you see this plugin help, so you have the yab_shop_core plugin. Grab also the yab_shop_admin and the yab_shop_3rd_party plugin.

# Install and activate these plugins.
# Go "»Extensions->Yab Shop Preferences«":?event=yab_shop_prefs and install the needed database tables.
# (optional): Install a prepared and prefilled language/localisation plugin (yab_shop_add_language_xx-xx)
# Set your preferences and language/localisation

h2(#update). Update

Mostly you can seemlessly update the plugin. With version 0.8.0 config and language strings will saved in additional database tables.

h3. Updating from a version before v0.8.0

# Make a copy of your settings and language/localisation strings.
# Remove or disable the yab_shop_config plugin
# Install the ones (yab_shop_core, yab_shop_admin, yab_shop_3rd_party)
# Go "»Extensions->Yab Shop Preferences«":?event=yab_shop_prefs and install the needed database tables.
# (optional): Install a prepared and prefilled language/localisation plugin (yab_shop_add_language_xx-xx)
# Set your preferences and language/localisation

h3. Updating from a version before v0.7.0

For an easy usage to newcomers and by the reasons of new features some tags has been removed or renamed.

* @<txp:yab_shop_cart output="message" />@
Attribute value @output="message"@ doesn't exists any more. See below the for changes.
And now you have to place it in checkout section to (f.i. with @<txp:yab_shop_cart output="none" />@)!
* @<txp:yab_shop_add_message message="your message here" output="message" />@
Removed.
Now use @<txp:yab_shop_cart_message />@ instead.
* @<txp:yab_shop_custom_field name="price custom field" />@
Renamed to @<txp:yab_shop_price />@ with the attributes wraptag and class.
* @<txp:yab_shop_property_prices />@
Removed. Now load the jquery.js manually please!

h2(#setup). Setup

Note: I've created a page with a simple plugin HowTo (beginner) and a FAQ: "See here":http://www.yablo.de/article/404/howto-an-faq-about-the-textpattern-shopping-cart-plugin-yab_shop

You have to create one additional section. This section will be used for the checkout (table and form).

Further you have to create at least one additional custom field, where you can store the price for the products. So create one and name it.
Place the used name in Yab Shop Preferences. Now you can create up to three addtional custom fields if you want multiple product properties.

Next you have to configure your shop. So go "Yab Shop Preferences":?event=yab_shop_prefs which contains the configuration and go "Yab Shop L10n":?event=yab_shop_language which contains the phrases where you can change on your own. See the "yab_shop_admin plugin help":?event=plugin&step=plugin_help&name=yab_shop_admin for further information.

For paypal and google checkout setup see "plugin help for yab_shop_config":?event=plugin&step=plugin_help&name=yab_shop_admin or a "thread in the forum":http://forum.textpattern.com/viewtopic.php?pid=205495#p205495

h2(#tags). Tags for output

h3. @<txp:yab_shop_add />@

This tag outputs the add-to-cart form for the specific product. You have to place it into the individual product/article form (maybe @"default"@). Since yab_shop_v0.7.0 you can place it in article listings too.

h3. @<txp:yab_shop_cart />@

This tag is used for adding, editing and deleting products and it's outputs the little cart. It *must* be placed somewhere in the shop sections *and* in the your checkout section. Since yab_shop_v0.7.0 it *can* be used as a container tag. You can change the output by the following attribute:

* @output="cart"@ - default, outputs the little cart
* @output="none"@ - no output, so you can use it checkout section without any output

h4. Usage as container tag

bc. <txp:yab_shop_cart>
  <txp:yab_shop_cart_items />
  <txp:yab_shop_cart_quantity />
  <txp:yab_shop_cart_subtotal />
  <txp:yab_shop_cart_link />
  <txp:yab_shop_cart_message />
</txp:yab_shop_cart>

h3. @<txp:yab_shop_cart_items />@

Outputs the items in the cart al a list. Can only be used inside the container tag @<txp:yab_shop_cart>@. No attributes.

h3. @<txp:yab_shop_cart_quantity />@

Shows the quantity of the items in the cart. Can be used standalone or inside the container tag @<txp:yab_shop_cart>@. The following attributes are available:

* *output="single"*
Choose your itemcount. 'single' for different products. 'all' for all product items (default 'single').
* *showalways="1"*
Displaying it even if cart is empty (default '1').
* *break="br"*
Break after output (default 'br').
* *label="Quantity: "*
Label or name before itemcount output (default 'Quantity: ').
* *wraptag="span"*
Wraptag around the output (default blank).
* *class="someclass"*
Class for wraptag (default blank).

h3. @<txp:yab_shop_cart_subtotal />@

Shows the cart subtotal. Can be used standalone or inside the container tag @<txp:yab_shop_cart>@. The following attributes are available:

* *showalways="1"*
Displaying it even if cart is empty (default '1').
* *break="br"*
Break after output (default 'br').
* *label="Subtotal: "*
Label or name before itemcount output (default 'Subtotal: ').
* *wraptag="span"*
Wraptag around the output (default blank).
* *class="someclass"*
Class for wraptag (default blank).

h3. @<txp:yab_shop_cart_link />@

Shows a link to your checkout page. Can be used standalone or inside the container tag @<txp:yab_shop_cart>@. The following attributes are available:

* *showalways="1"*
Displaying it even if cart is empty (default '1').
* *break="br"*
Break after output (default 'br').
* *label="proceed to checkout"*
Label or name before itemcount output (default 'to_checkout' from yab_shop_config).
* *wraptag="span"*
Wraptag around the output (default blank).
* *class="someclass"*
Class for wraptag or link, if no wraptag is set (default blank).

h3. @<txp:yab_shop_cart_message />@

Shows a message depending on a done action. Can be used standalone or inside the container tag @<txp:yab_shop_cart>@. The following attributes are available:

* *add="Product has been added"*
Shows a message when a products has been added to cart (default 'Product has been added').
* *edit="Cart has been updated"*
Shows a message when a product count has been changed in checkout page (default 'Cart has been updated').
* *del="Product has been deleted"*
Shows a message when a product has been deleted from cart in checkout page (default 'Product has been deleted').
* *break="br"*
Break after output (default 'br').
* *wraptag="span"*
Wraptag around the output (default blank).
* *class="someclass"*
Class for wraptag (default blank).


h3. @<txp:yab_shop_price />@

It outputs the price. It can be placed in all article/product forms (individual and listings).
The following attributes are available:

* *wraptag="span"*
Wraptag surrounded the Price (default 'span').
* *class="yab-shop-price"*
Class for the wraptag (default 'yab-shop-price').

h3. @<txp:yab_shop_checkout />@

This tag outputs the checkout table, where you can edit product quantities. And it outputs the checkout form, where you can finally submit your order.
The following attributes are available:

* *summary="your summary here"*
Summary attribute of the HTML table element.

h3. @<txp:yab_shop_show_config />@

Outputs a value of the current yab_shop_config, so it can be used for weird things (@<txp:if ... />@, @<txp:variable ... />@ etc. pp.).
The following attributes are available:

* *name="config value here"*
The value of the config.

h2(#notes). Important notes on setup and maintaining

All numbers for prices in custom field or shipping costs in config can be written with comma or dot as decimal delimter. But beware: Do not use any thousand delimiter!
The output format in HTML or mail depends on the selected currency in the config.

h3. How do I input properties?

If you use one, two or all three custom fields for different product properties you have to fill the input fields with values separated by a semicolon, followed by a whitespace (you can leave the whitespace out, it will work both ways).
E.g. for custom field &raquo;Size&laquo;: @0.2mm; 3m; 5km; 100pc@

h3. And how do I input prices for a property?

Note: You can only assign *one* property with a price.

First go in "»Yab Shop Preferences«":?event=yab_shop_prefs and change the use_property_prices to @'1'@.

Then, if not yet done, load the jquery.js in your shop sections. Add the following line in your form or site template between the @<head>@ and @</head>@:

@<script type="text/javascript" src="<txp:site_url />textpattern/jquery.js"></script>@

*Input the Prices:*
If you want use a property price you must give an price in your price field (custom field) even so. You can use it as an base price.
Now you can give prices to the properties in *one* property field (custom field). Use double minus as delimter between property and price. E.g for the property field color:

@red; white--2,50; black--12,00; green--0,55@

The properties with no price declaration will use the base price of the price field (custom field). The first property price should be the same as the base price. That's all!

h3. How do I use promo-codes, coupons etc.?

Go in  "Yab Shop Preferences":?event=yab_shop_prefs and set the @Promocode key@ with a key, which a customer have to insert on the checkout page to get the promotional discount (E.g. @'XFHTE'@ or another value). With @Given promo discount (%)@ you can set the promotional discount in percent (E.g. @'5'@). Absolute discounts like 5€ on all products are not supported due the lack of support by paypal and google checkout.

h2(#uninstall). Uninstallation

# For an uninstallation "klick here":?event=yab_shop_prefs&step=yab_shop_uninstall (*All Yab Shop setting will be removed immediately*)
# Disable or delete the yab_shop_xxx plugins (Deletion of yab_shop_admin will remove the Yab Shop setting too)

h2(#stuff). Other stuff

You can see a live demo on "demoshop.yablo.de":http://demoshop.yablo.de/
For help use the "forum":http://forum.textpattern.com/viewtopic.php?id=26300 please!
# --- END PLUGIN HELP ---
-->
<?php
}
?>
