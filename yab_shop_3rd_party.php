<?php
$plugin['name'] = 'yab_shop_3rd_party';
$plugin['version'] = '0.7.1';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'Shopping Cart Plugin (3rd party classes)';
$plugin['type'] = '0';
if (!defined('txpinterface'))
	@include_once('zem_tpl.php');
if (0) {
?>
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
</style>
# --- END PLUGIN CSS ---
# --- BEGIN PLUGIN HELP ---
h1. Third party classes and functions for yab_shop

*{color: #75111B}This is the third party plugin for yab_shop, which required this plugin.*


# --- END PLUGIN HELP ---
<?php
}
# --- BEGIN PLUGIN CODE ---
if (!function_exists('CalcHmacSha1'))
{
/**
 * Copyright (C) 2007 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *			http://www.apache.org/licenses/LICENSE-2.0
 *
 * Calculates the cart's hmac-sha1 signature, this allows google to verify 
 * that the cart hasn't been tampered by a third-party.
 * 
 * @link http://code.google.com/apis/checkout/developer/index.html#create_signature
 * 
 * Modified merchant key validation and access by Joe Wilson <http://www.joecode.com/txp/82/joe_gcart>
 */

	function CalcHmacSha1($data,$merchant_key)
	{
		$key = $merchant_key;
		$blocksize = 64;
		$hashfunc = 'sha1';

		if (strlen($key) > $blocksize)
		{
			$key = pack('H*', $hashfunc($key));
		}

		$key = str_pad($key, $blocksize, chr(0x00));
		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5c), $blocksize);
		$hmac = pack(
			'H*', $hashfunc(
				($key^$opad).pack(
					'H*', $hashfunc(
						($key^$ipad).$data
					)
				)
			)
		);
		return $hmac;
	}
}

if (!class_exists('wfCart'))
{

/**
 * Modified Webforce Cart v.1.5
 *
 * 2008-03-02 Cleaned some code (Tommy Schmucker)
 * 2008-12-18 Modified with promocode-support (Tommy Schmucker)
 * 2009-01-27 Modified with TXP-ID-support (Tommy Schmucker)
 * 
 * Webforce Cart v.1.5
 * A Session based, Object Oriented Shopping Cart Component for PHP.
 *
 * (c) 2004-2005 Webforce Ltd, NZ
 * http://www.webforcecart.com/
 * all rights reserved 
 *
 * Webforce cart is free software. Licence LGPL. (c) 2004-2005 Webforce Ltd, New Zealand.
 * Licence: LGPL - http://www.gnu.org/copyleft/lesser.txt
 *
 */

	class wfCart
	{
		var $total = 0;
		var $itemcount = 0;
		var $items = array();
		var $itemprices = array();
		var $itemnames = array();
		var $itemproperties_1 = array();
		var $itemproperties_2 = array();
		var $itemproperties_3 = array();
		var $itemspecshipping = array();
		var $itemqtys = array();
		var $promocode = 0;
		var $promocodes = array();
		var $itemtxpids = array();

		function set_promocode($value)
		{
			$this->promocode = $value;
		}

		function get_promocode()
		{
			return $this->promocode;
		}

		function edit_promocodes($itemid, $value)
		{
			$this->promocodes[$itemid] = $value;
		}

		function edit_promo_prices($itemid, $value)
		{
			$this->itemprices[$itemid] = $value;
			$this->_update_total();
		}

		function get_contents()
		{
			$items = array();
			foreach ($this->items as $tmp_item)
			{
				$item = false;
				$item['itemid'] = $tmp_item;
				$item['qty'] = $this->itemqtys[$tmp_item];
				$item['price'] = $this->itemprices[$tmp_item];
				$item['name'] = $this->itemnames[$tmp_item];
				$item['property_1'] = $this->itemproperties_1[$tmp_item];
				$item['property_2'] = $this->itemproperties_2[$tmp_item];
				$item['property_3'] = $this->itemproperties_3[$tmp_item];
				$item['spec_shipping'] = $this->itemspecshipping[$tmp_item];
				$item['promocode'] = $this->promocodes[$tmp_item];
				$item['txpid'] = $this->itemtxpids[$tmp_item];
				$item['subtotal'] = $item['qty'] * $item['price'];
				$items[] = $item;
			}
			return $items;
		}

		function add_item($itemid, $txpid, $qty = 1, $price = false, $name = false, $property_1 = false, $property_2 = false, $property_3 = false, $spec_shipping = false)
		{
			if ($qty > 0)
			{
				if (isset($this->itemqtys[$itemid]) and $this->itemqtys[$itemid] > 0)
				{
					$this->itemqtys[$itemid] += $qty;
					$this->_update_total();
				}
				else
				{
					$this->items[]= $itemid;
					$this->itemqtys[$itemid] = $qty;
					$this->itemprices[$itemid] = $price;
					$this->itemnames[$itemid] = $name;
					$this->itemproperties_1[$itemid] = $property_1;
					$this->itemproperties_2[$itemid] = $property_2;
					$this->itemproperties_3[$itemid] = $property_3;
					$this->itemspecshipping[$itemid] = $spec_shipping;
					$this->promocodes[$itemid] = 0;
					$this->itemtxpids[$itemid] = $txpid;
				}
				$this->_update_total();
			}
		}

		function edit_item($itemid, $qty)
		{
			if ($qty < 1)
			{
				$this->del_item($itemid);
			}
			else
			{
				$this->itemqtys[$itemid] = $qty;
			}
			$this->_update_total();
		}

		function del_item($itemid)
		{
			$ti = array();
			$this->itemqtys[$itemid] = 0;
			foreach ($this->items as $item)
			{
				if ($item != $itemid)
				{
					$ti[] = $item;
				}
			}
			$this->items = $ti;
			unset($this->itemprices[$itemid]);
			unset($this->itemnames[$itemid]);
			unset($this->itemproperties_1[$itemid]);
			unset($this->itemproperties_2[$itemid]);
			unset($this->itemproperties_3[$itemid]);
			unset($this->itemspecshipping[$itemid]);
			unset($this->itemqtys[$itemid]);
			unset($this->promocodes[$itemid]);
			unset($this->itemtxpids[$itemid]);
			$this->_update_total();
		}

		function empty_cart()
		{
			$this->total = 0;
			$this->itemcount = 0;
			$this->items = array();
			$this->itemprices = array();
			$this->itemproperties_1 = array();
			$this->itemproperties_2 = array();
			$this->itemproperties_3 = array();
			$this->itemspecshipping = array();
			$this->itemqtys = array();
			$this->promocode = 0;
			$this->promocodes = array();
			$this->itemtxpids = array();
		}

		function _update_total()
		{
			$this->itemcount = 0;
			$this->total = 0;
			if (count($this->items) > 0)
			{
				foreach ($this->items as $item)
				{
					$this->total += ($this->itemprices[$item] * $this->itemqtys[$item]);
					$this->itemcount++;
				}
			}
		}
	}
}

if (!class_exists('PayPalEWP'))
{

/**
 * The PayPal class implements the dynamic encryption of
 * PayPal "buy now" buttons using the PHP openssl functions. 
 *
 * Original Author: Ivor Durham (ivor.durham@ivor.cc)
 * Edited by PayPal_Ahmad	(Nov. 04, 2006)
 * Posted originally on PDNCommunity:
 * http://www.pdncommunity.com/pdn/board/message?board.id=ewp&message.id=87#M87
 *
 * Using the orginal code on PHP 4.4.4 on WinXP Pro
 * I was getting the following error:
 *
 * "The email address for the business is not present in the encrypted blob.
 * Please contact your merchant"
 *
 * I modified and cleaned up a few things to resolve the error - this was
 * tested on PHP 4.4.4 + OpenSSL on WinXP Pro
 *
 * Modified 2008 by tf@1agency.de for PHP5 and PHPDoc
 *
 * 2008-03-26 Modified for usage with PHP4, Textpattern and
 * website payments pro (german: Standard-Zahlungslösung)
 * extended error handling
 * 
 * @copyright Ivor Durham <ivor.durham@ivor.cc>
 * @copyright PayPal_Ahmad	(Nov. 04, 2006)
 * @copyright Unknown Modifier 
 * @copyright Thomas Foerster <tf@1agency.de>
 * @copyright Tommy Schmucker 
 * @package PayPal
 */

	class PayPalEWP
	{
		var $certificate;
		var $certificateFile;
		var $privateKey;
		var $privateKeyFile;
		var $paypalCertificate;
		var $paypalCertificateFile;
		var $certificateID;
		var $tempFileDirectory;
		var $error;

		/**
		 * Constructor
		 *
		 */
		function __PayPalEWP()
		{
				$this->error = 0;
		}

		function setTempDir($tempdir)
		{
			$this->tempFileDirectory = $tempdir;
		}

		/**
		 * Sets the ID assigned by PayPal to the client certificate
		 *
		 * @param string $id The certificate ID assigned when the certificate
		 * was uploaded to PayPal
		 */
		function setCertificateID($id)
		{
			if ($id != '')
			{
				$this->certificateID = $id;
			}
			else
			{
				$this->error = 1;
			}
		}

		/**
		 * Set the client certificate and private key pair.
		 *
		 * @param string $certificateFilename The path to the client
		 * (public) certificate
		 * @param string $privateKeyFilename The path to the private key
		 * corresponding to the certificate
		 * @return bool TRUE if the private key matches the certificate,
		 * FALSE otherwise
		 */
		function setCertificate($certificateFilename, $privateKeyFilename)
		{
		 if (is_readable($certificateFilename) and is_readable($privateKeyFilename))
		 {
				$handle = fopen($certificateFilename, "r");
				if ($handle === false)
				{
					return false;
				}

				$size = filesize($certificateFilename);
				$certificate = fread($handle, $size);
				@fclose($handle);

				unset($handle);

				$handle = fopen($privateKeyFilename, "r");
				if ($handle === false)
				{
					return false;
				}

				$size = filesize($privateKeyFilename);
				$privateKey = fread($handle, $size);
				@fclose($handle);

				if (($certificate !== false) and ($privateKey !== false) and openssl_x509_check_private_key($certificate, $privateKey))
				{
					$this->certificate		 = $certificate;
					$this->certificateFile = $certificateFilename;
					$this->privateKey			= $privateKey;
					$this->privateKeyFile	= $privateKeyFilename;
					return true;
				}
			}
			else
			{
				$this->error = 2;
				return false;
			}
		}

		/**
		 * Sets the PayPal certificate
		 *
		 * @param string $fileName The path to the PayPal certificate
		 * @return bool TRUE if the certificate is read successfully,
		 * FALSE otherwise.
		 */
		function setPayPalCertificate($fileName)
		{
			if (is_readable($fileName))
			{
				$handle = fopen($fileName, "r");
				if ($handle === false)
				{
					return false;
				}

				$size = filesize($fileName);
				$certificate = fread($handle, $size);
				if ($certificate === false)
				{
					return false;
				}

				fclose($handle);

				$this->paypalCertificate		 = $certificate;
				$this->paypalCertificateFile = $fileName;

				return true;
			}
			else
			{
				$this->error = 3;
				return false;
			}
		}

		/**
		 * Using the previously set certificates and the tempFileDirectory to
		 * encrypt the button information
		 *
		 * @param array $parameters Array with parameter names as keys
		 * @return mixed The encrypted string OR false
		 */
		function encryptButton($parameters)
		{
			if (($this->certificateID == '') or !isset($this->certificate) or !isset($this->paypalCertificate))
			{
				return false;
			}

			$clearText = '';
			$encryptedText = '';

			$data = "cert_id=" . $this->certificateID . "\n";

			foreach($parameters as $k => $v)
			{
				$d[] = "$k=$v";
			}

			$data .= join("\n", $d);

			$dataFile = tempnam($this->tempFileDirectory, 'data');

			$out = fopen("{$dataFile}_data.txt", 'wb');
			fwrite($out, $data);
			fclose($out);

			$out = fopen("{$dataFile}_signed.txt", "w+"); 

			if (!openssl_pkcs7_sign("{$dataFile}_data.txt", "{$dataFile}_signed.txt", $this->certificate, $this->privateKey, array(), PKCS7_BINARY))
			{
				$this->error = 4;
				return false;
			}

			fclose($out);

			$signedData = explode("\n\n", file_get_contents("{$dataFile}_signed.txt"));

			$out = fopen("{$dataFile}_signed.txt", 'wb');
			fwrite($out, base64_decode($signedData[1]));
			fclose($out);

			if (!openssl_pkcs7_encrypt("{$dataFile}_signed.txt", "{$dataFile}_encrypted.txt", $this->paypalCertificate, array(), PKCS7_BINARY))
			{
				$this->error = 4;
				return false;
			}

			$encryptedData = explode("\n\n", file_get_contents("{$dataFile}_encrypted.txt"));

			$encryptedText = $encryptedData[1];

			@unlink($dataFile);	
			@unlink("{$dataFile}_data.txt");
			@unlink("{$dataFile}_signed.txt");
			@unlink("{$dataFile}_encrypted.txt");

			return "-----BEGIN PKCS7-----\n".$encryptedText."\n-----END PKCS7-----";
		}
	}
}
# --- END PLUGIN CODE ---
?>
