<?php

/**
 * Page for listing available datatypes.
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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
class SpecialListDatatypes extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'ListDatatypes' );

	}
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$this->getOutput()->addHTML( $this->msg( 'wikibase-listdatatypes-intro' ) );
		$this->getOutput()->addHTML( Html::openElement( 'dl' ));
		foreach (\Wikibase\Settings::get( 'dataTypes' ) as $dataTypeId ) {
			$baseKey = 'wikibase-listdatatypes-' . mb_strtolower( $dataTypeId );
			$out = Html::rawElement( 'dt', array(), $this->msg( $baseKey . '-head' )->parse() )
			. Html::rawElement( 'dd', array(), $this->msg( $baseKey . '-body' )->parse() );
			$this->getOutput()->addHTML( $out );
		}
		$this->getOutput()->addHTML( Html::closeElement( 'dl' ));
	}
}
