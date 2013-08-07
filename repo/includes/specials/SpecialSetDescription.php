<?php

use Wikibase\ChangeOpDescription;

/**
 * Special page for setting the description of a Wikibase entity.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
class SpecialSetDescription extends SpecialSetEntity {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetDescription', 'description-update' );
	}

	/**
	 * @see SpecialSetEntity::getPostedValue()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'description' );
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 *
	 * @return string
	 */
	protected function getValue( $entityContent, $language ) {
		return $entityContent === null ? '' : $entityContent->getEntity()->getDescription( $language );
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( $entityContent, $language, $value ) {
		$value = $value === '' ? null : $value;
		$summary = $this->getSummary( 'wbsetdescription' );
		$changeOp = new ChangeOpDescription( $language, $value );
		$changeOp->apply( $entityContent->getEntity(), $summary );

		return $summary;
	}
}