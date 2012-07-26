<?php

namespace Wikibase;

/**
 * Handles the skin hooks.
 *
 * @since 0.1
 *
 * @file SkinHandler.php
 * @ingroup WikibaseClient
 *
 * @licence	GNU GPL v2+
 * @author	Nikola Smolenski <smolensk@eunet.rs>
 */
class SkinHandler {

	public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {
		// FIXME: we do NOT want to add these resources on every page where the parser is used (ie pretty much all pages)
		$out->addModules( 'ext.wikibaseclient' );
		return true;
	}

	/**
	 * Displays a list of links to pages on the central wiki at the end of the language box.
	 *
	 * @param	$skin - standard Skin object.
	 * @param	$template
	 */
	public static function onSkinTemplateOutputPageBeforeExec( \Skin &$skin, &$template ) {
		global $wgLanguageCode;

		$edit_url = \Wikibase\Settings::get( 'editURL' );
		if( empty( $edit_url ) ) {
			return true;
		}

		$title = $skin->getContext()->getTitle();

		// This must be the same as in LangLinkHandler
		// NOTE: Instead of getFullText(), we need to get a normalized title, and the server should use a locale-aware normalization function yet to be written which has the same output
		$title_text = $title->getFullText();

		$template->data['language_urls'][] = array(
			'href' => wfMsgReplaceArgs( $edit_url, array( urlencode( $title_text ), $wgLanguageCode ) ),
			'text' => wfMsg( 'wbc-editlinks' ),
			'title' => wfMsg( 'wbc-editlinkstitle' ),
			'class' => 'wbc-editpage',
		);

		return true;
	}

}
