<?php

namespace Wikibase;
use ValueFormatters\FormatterOptions;

/**
 * Formatter for Wikibase Item values
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ItemFormatter extends StringFormatter {

	protected $entityLookup;

	public function __construct( FormatterOptions $options, EntityLookup $entityLookup ) {
		parent::__construct( $options );
		$this->entityLookup = $entityLookup;
	}

	public function format( $value ) {
		if ( !$value instanceof EntityId ) {
			// @todo: error!
			return '';
		}

		$label = $this->lookupItemLabel( $value );

		if ( is_string( $label ) ) {
			return $this->formatString( $label );
		}

		// did not find label
		return '';
	}

	protected function lookupItemLabel( $entityId ) {
		$entity = $this->entityLookup->getEntity( $entityId );

		$langCode = $this->getOption( 'lang' );

		return $entity->getLabel( $langCode );
	}

}
