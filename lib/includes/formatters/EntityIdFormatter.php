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
class EntityIdFormatter extends StringFormatter {

	protected $entityLookup;

	protected $labelFallback;

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options
	 *	 expects 'lang', 'entityLookup', and 'labelFallback' options
	 *   labelFallback can be prefixedId or emptyString (prefixedId is default)
	 *
	 * @throws \MWException
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		$this->entityLookup = $options->getOption( 'entityLookup' );
		$this->labelFallback = $options->hasOption( 'labelFallback' ) ?
			$options->getOption( 'labelFallback' ) : 'prefixedId';
	}

	/**
	 * Format an EntityId data value
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value to format
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string
	 */
	public function format( $value ) {
		if ( ! $value instanceof EntityId ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityId.' );
		}

		$label = $this->lookupItemLabel( $value );

		if ( is_string( $label ) ) {
			return $this->formatString( $label );
		}

		// did not find a label, using fallback
		if ( $this->labelFallback === 'emptyString' ) {
			return '';
		}

		return $value->getPrefixedId();
	}

	/**
	 * Lookup a label for an entity
	 *
	 * @since 0.4
	 *
	 * @param EntityId
	 *
	 * @return string|false
	 */
	protected function lookupItemLabel( EntityId $entityId ) {
		// @todo: use terms table lookup
		$entity = $this->entityLookup->getEntity( $entityId );

		$langCode = $this->getOption( 'lang' );

		return $entity->getLabel( $langCode );
	}

}
