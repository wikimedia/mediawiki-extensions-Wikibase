<?php

namespace Wikibase;
use Wikibase\LangLinkHandler;
use Wikibase\Client\WikibaseClient;

/**
 * Handles the NOEXTERNALLANGLINKS parser function.
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
 *
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class NoLangLinkHandler {

	/**
	 * Parser function
	 *
	 * @since 0.4
	 *
	 * @param \Parser &$parser
	 *
	 * @return string
	 */
	public static function handle( &$parser ) {
		$langLinkHandler = new LangLinkHandler(
			Settings::get( 'siteGlobalID' ),
			Settings::get( 'namespaces' ),
			Settings::get( 'excludeNamespaces' ),
			WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable(),
			\Sites::singleton(),
			WikibaseClient::getDefaultInstance()->getLangLinkSiteGroup()
		);

		$langs = func_get_args();
		// Remove the first member, which is the parser.
		array_shift( $langs );

		$output = $parser->getOutput();

		$langLinkHandler->excludeRepoLangLinks( $output, $langs );

		return '';
	}

}
