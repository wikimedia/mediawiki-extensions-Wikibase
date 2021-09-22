<?php

namespace Wikibase\DataModel\Entity;

/**
 * Object that can parse the serializations of the EntityIds defined by the DataModel.
 *
 * @since 4.2
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BasicEntityIdParser implements EntityIdParser {

	private $idParser;

	public function __construct() {
		$this->idParser = new DispatchingEntityIdParser( self::getBuilders() );
	}

	/**
	 * @param string $idSerialization
	 *
	 * @return ItemId|PropertyId
	 * @throws EntityIdParsingException
	 */
	public function parse( $idSerialization ) {
		return $this->idParser->parse( $idSerialization );
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
		return [
			ItemId::PATTERN => static function( $serialization ) {
				return new ItemId( $serialization );
			},
			NumericPropertyId::PATTERN => static function( $serialization ) {
				return new NumericPropertyId( $serialization );
			},
		];
	}

}
