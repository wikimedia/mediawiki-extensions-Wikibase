<?php

namespace Wikibase\Lib\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;

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
		return new DataValueDeserializer( array(
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => function( $value ) use ( $repositoryName ) {
				return isset( $value['id'] )
					? new EntityIdValue( $this->idParserFactory->getIdParser( $repositoryName )->parse( $value['id'] ) )
					: EntityIdValue::newFromArray( $value );
			},
		) );
	}

}
