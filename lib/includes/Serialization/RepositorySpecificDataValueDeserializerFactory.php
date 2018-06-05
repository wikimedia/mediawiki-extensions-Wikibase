<?php

namespace Wikibase\Lib\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\IllegalValueException;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;

/**
 * Factory creating DataValueDeserializer instances configured for the given repository, ie.
 * using the EntityIdParser configured for the repository when deserializing EntityIdValue.
 *
 * @license GPL-2.0-or-later
 */
class RepositorySpecificDataValueDeserializerFactory {

	private $idParserFactory;

	/**
	 * @var DataValueDeserializer[]
	 */
	private $deserializers = [];

	public function __construct( PrefixMappingEntityIdParserFactory $idParserFactory ) {
		$this->idParserFactory = $idParserFactory;
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return DataValueDeserializer
	 */
	public function getDeserializer( $repositoryName ) {
		if ( !isset( $this->deserializers[$repositoryName] ) ) {
			$this->deserializers[$repositoryName] = $this->newDeserializerForRepository( $repositoryName );
		}

		return $this->deserializers[$repositoryName];
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return DataValueDeserializer
	 */
	private function newDeserializerForRepository( $repositoryName ) {
		$parser = $this->idParserFactory->getIdParser( $repositoryName );

		return new DataValueDeserializer( [
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => function( $value ) use ( $parser, $repositoryName ) {
				if ( $repositoryName !== '' && !isset( $value['id'] ) ) {
					throw new IllegalValueException(
						'Not able to parse entity id values from the foreign repository not containing the "id" string'
					);
				}
				try {
					$result = isset( $value['id'] )
						? new EntityIdValue( $parser->parse( $value['id'] ) )
						: EntityIdValue::newFromArray( $value );
				} catch ( EntityIdParsingException $e ) {
					$result = new UnmappedEntityIdValue( $value['id'] );
				}
				return $result;
			},
		] );
	}

}
