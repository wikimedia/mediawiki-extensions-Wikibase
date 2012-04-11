<?php

/**
 * Handles the NOEXTERNALINTERLANG parser function.
 *
 * @since 0.1
 *
 * @file WBCNoLangLinkHandler.php
 * @ingroup WikibaseClient
 *
 * @licence	GNU GPL v2+
 * @author	Nikola Smolenski <smolensk@eunet.rs>
 */
class WBCNoLangLinkHandler {

	/**
	 * Register the parser function.
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'noexternalinterlang', 'WBCNoLangLinkHandler::noExternalInterlang', SFH_NO_HASH );
		return true;
	}

	/**
	 * Register the magic word.
	 */
	public static function onMagicWordwgVariableIDs( &$aCustomVariableIds ) {
		$aCustomVariableIds[] = 'noexternalinterlang';
		return true;
	}

	/**
	 * Apply the magic word.
	 */
	public static function onParserGetVariableValueSwitch( &$parser, &$cache, &$magicWordId, &$ret ) {
		if( $magicWordId == 'noexternalinterlang' ) {
			self::noExternalInterlang( $parser, '*' );
		}

		return true;
	}

	/**
	 * Actual parser function.
	 */
	public static function noExternalInterlang( &$parser ) {
		$langs = func_get_args();
		// Remove the first member, which is the parser.
		array_shift( $langs );
		$langs = array_flip( $langs );

		$out = $parser->getOutput();
		$nei = WBCLangLinkHandler::getNEI( $out );
		$nei += $langs;
		$out->setProperty( 'no_external_interlang', serialize( $nei ) );

		return "";
	}

}
