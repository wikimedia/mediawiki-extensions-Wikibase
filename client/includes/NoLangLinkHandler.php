<?php

namespace Wikibase;
use \Wikibase\LangLinkHandler as LangLinkHandler;

/**
 * Handles the NOEXTERNALINTERLANG parser function.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 */
class NoLangLinkHandler {

	/**
	 * Register the parser function.
	 * @param $parser \Parser
	 * @return bool
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'noexternalinterlang', '\Wikibase\NoLangLinkHandler::noExternalInterlang', SFH_NO_HASH );
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
	 * @param $parser \Parser
	 * @return string
	 */
	public static function noExternalInterlang( &$parser ) {
		$langs = func_get_args();
		// Remove the first member, which is the parser.
		array_shift( $langs );
		$langs = array_flip( $langs );

		$out = $parser->getOutput();
		$nei = LangLinkHandler::getNoExternalInterlang( $out );
		$nei += $langs;
		$out->setProperty( 'no_external_interlang', serialize( $nei ) );

		return "";
	}

}
