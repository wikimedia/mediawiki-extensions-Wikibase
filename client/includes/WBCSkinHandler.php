<?php

/**
 * Handles the skin hooks.
 *
 * @since 0.1
 *
 * @file WBCSkinHandler.php
 * @ingroup WikibaseClient
 *
 * @licence	GNU GPL v2+
 * @author	Nikola Smolenski <smolensk@eunet.rs>
 */
class WBCSkinHandler {
	public static function onBeforePageDisplay( $out, $skin ) {
		$out->addModules( 'wikibaseClient' );
		return true;
	}

	/**
	 * Displays a list of links to pages on the central wiki at the end of the language box.
	 *
	 * @param	$skin - standard Skin object.
	 * @param	$template
	 */
	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$template ) {
		global $wgLanguageCode;

		$edit_url = WBCSettings::get( 'editURL' );
		if( empty( $edit_url ) ) {
			return true;
		}

		$title = $skin->getContext()->getTitle();

		// This must be the same as in WBCLangLinkHandler
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
