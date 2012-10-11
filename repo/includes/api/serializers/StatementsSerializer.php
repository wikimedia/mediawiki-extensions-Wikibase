<?php

namespace Wikibase;
use MWException;

/**
 * API serializer for Statements objects.
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementsSerializer extends ApiSerializerObject {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $statements
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( $statements ) {
		if ( !( $statements instanceof Statements ) ) {
			throw new MWException( 'StatementsSerializer can only serialize Statements objects' );
		}

		$serialization = array();

		// FIXME: "iterator => array => iterator" is stupid
		$statements = new ByPropertyIdArray( iterator_to_array( $statements ) );
		$statements->buildIndex();

		$statementSerializer = new StatementSerializer( $this->getResult(), $this->options );

		$entityFactory = EntityFactory::singleton();

		foreach ( $statements->getPropertyIds() as $propertyId ) {
			$serializedStatements = array();

			foreach ( $statements->getByPropertyId( $propertyId ) as $statement ) {
				$serializedStatements[] = $statementSerializer->getSerialized( $statement );
			}

			$this->getResult()->setIndexedTagName( $serializedStatements, 'statement' );

			$propertyId = $entityFactory->getPrefixedId( Property::ENTITY_TYPE, $propertyId );
			$serialization[$propertyId] = $serializedStatements;
		}

		$this->getResult()->setIndexedTagName( $serialization, 'property' );

		return $serialization;
	}

}

