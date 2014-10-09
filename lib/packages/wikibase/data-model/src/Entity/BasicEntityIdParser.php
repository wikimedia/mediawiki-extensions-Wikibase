<?php

namespace Wikibase\DataModel\Entity;

/**
 * Object that can parse the serializations of the EntityIds defined by the DataModel.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 */
class BasicEntityIdParser implements EntityIdParser {

	/**
	 * @param string $idSerialization
	 *
	 * @return ItemId|PropertyId
	 * @throws EntityIdParsingException
	 */
	public function parse( $idSerialization ) {
		$idParser = new DispatchingEntityIdParser( self::getBuilders() );
		return $idParser->parse( $idSerialization );
	}

	/**
	 * Returns an id builders array.
	 * Keys are preg_match patterns, values are callables.
	 * (See the DispatchingEntityIdParser constructor for more details.)
	 *
	 * This method returns builders for the ids of all entity types
	 * defined by WikibaseDataModel. It is intended to be used by
	 * applications that allow for registration of additional entity
	 * types, and thus want to extend upon this list. The extended
	 * list can then be used to construct a DispatchingEntityIdParser instance.
	 *
	 * @return callable[]
	 */
	public static function getBuilders() {
		return array(
			ItemId::PATTERN => function( $serialization ) {
				return new ItemId( $serialization );
			},
			PropertyId::PATTERN => function( $serialization ) {
				return new PropertyId( $serialization );
			},
		);
	}

}
