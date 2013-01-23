<?php

namespace Wikibase;
use Wikibase\LangLinkHandler;

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
 * @author Nikola Smolenski <smolensk@eunet.rs>
 */
class NoLangLinkHandler {

	protected $out;

	public function __construct( $out ) {
		$this->out = $out;
	}

    /**
     * Actual parser function.
     *
     * @since 0.4
     */
    public function stripExternalLangLinks() {
        $langs = func_get_args();

        // Remove the first member, which is the parser.
        array_shift( $langs );

        $langLinkHandler = new LangLinkHandler(
            Settings::get( 'siteGlobalID' ),
            Settings::get( 'namespaces' ),
            ClientStoreFactory::getStore()->newSiteLinkTable(),
            \Sites::singleton()
        );

        $nel = $langLinkHandler->getNoExternalLangLinks( $this->out );
        $nel += $langs;
        $langLinkHandler->setNoExternalLangLinks( $this->out, $nel );
    }

	/**
	 * Register the magic word.
	 */
	public static function onMagicWordwgVariableIDs( &$aCustomVariableIds ) {
		$aCustomVariableIds[] = 'noexternallanglinks';
		return true;
	}

	/**
	 * Apply the magic word.
	 *
	 * @since 0.1
	 *
	 * @param \Parser $parser
	 *
	 * @return bool
	 */
	public static function onParserGetVariableValueSwitch( &$parser, &$cache, &$magicWordId, &$ret ) {
		if( $magicWordId == 'noexternallanglinks' ) {
			self::execute( $parser, '*' );
		}
		return true;
	}

	public static function execute( &$parser ) {
		var_export( 'no ext langlinks' );
		$out = $parser->getOutput();
		$instance = new self( $out );
		$instance->stripExternalLangLinks();

		return 'nolanglinks';
	}
}
