<?php

/*
 * File: w2lHelper.php
 *
 * Purpose:
 * Provides all functions, which integrate into Mediawiki
 *
 * License:
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

if ( !defined('MEDIAWIKI') ) {
	$msg  = 'To install Wiki2LaTeX, put the following line in LocalSettings.php:<br/>';
	$msg .= '<tt>require_once( $IP."/extensions/path_to_Wiki2LaTeX_files/wiki2latex.php" );</tt>';
	echo $msg;
	exit( 1 );
}

class Wiki2LaTeXHelper {
		
	function __construct() {
		global $w2lConfig;

		$this->version     = W2L_VERSION;
		$this->required_mw = '1.11';
		
		$this->messagesLoaded = false;
		$this->config  =& $w2lConfig;
		$this->actions = array('w2llatexform', 'w2ltexfiles', 'w2lpdf', 'w2ltextarea', 'w2lcleartempfolder');
		
		return true;       
	}

	function Setup() {
		global $wgExtensionCredits, $w2lConfig;

		// A current MW-Version is required so check for it...
		wfUseMW($this->required_mw);
		
		
		// Check if messages are loaded. If not do so.
		if ($this->messagesLoaded == false ) {

			wfLoadExtensionMessages( 'wiki2latex' );
			$this->messagesLoaded = true;
		}

		$wgExtensionCredits['other'][] = array(
			'name'        => wfMsg('wiki2latex'),
			'author'      => 'Hans-Georg Kluge',
			'description' => wfMsg('w2l_description'),
			'url'         => 'http://www.mediawiki.org/wiki/Extension:Wiki2LaTeX',
			'version'     => $this->version
		);

		if ( $w2lConfig['debug'] == true ) {
			error_reporting(E_ALL);
		}
		return true;

	}

	function onBeforePageDisplay(&$out) {
		$script = <<<EOF
<style type="text/css">/*<![CDATA[*/
li#ca-latex {margin-left:1.6em;}
/*]]>*/</style>
EOF;
		$out->addScript($script."\n");

		return true;

	}

	public function onSkinTemplateContentActions(&$content_actions) {
		// Here comes the small Wiki2LaTeX-Tab
		global $wgUser;
		global $wgTitle;

		if ($this->messagesLoaded == false ) {
			$this->onLoadAllMessages();
		}
		$values = new webRequest();
		$action = $values->getVal('action');

		$current_ns       = $wgTitle->getNamespace();
		$disallow_actions = array('edit', 'submit'); // disallowed actions

		if ( ($wgUser->getID() == 0) AND ($this->config['allow_anonymous'] == false) ) {
			return true;
		}

		if ( ( in_array($current_ns, $this->config['allowed_ns']) ) and !in_array($action, $disallow_actions)) {
			$content_actions['latex'] = array(
				'class' => ( in_array($action, $this->actions )  ) ? 'selected' : false,
				'text' => wfMsg('w2l_tab'),
				'href' => $wgTitle->getLocalUrl( 'action=w2llatexform' )
			);
		}

		return true;
	}

	public function onUnknownAction($action, $article) {
		global $wgUser;
		// Check the requested action
		// return if not for w2l
		//return true;

		$action = strtolower($action);
		if ( !in_array($action, $this->actions) ) {
			// Not our action, so return!
			return true;
		}

		// Check, if anonymous usage is allowed...
		if ( ($wgUser->getID() == 0) AND ($this->config['allow_anonymous'] == false) ) {
			return true;
		}
		
		if ( !$this->messagesLoaded ) {
			$this->onLoadAllMessages();
		}

		// we are on our own now!
		
		$w2l = new Wiki2LaTeXCore;
		$w2l->onUnknownAction($action, $article);
		return false;

	}

}

