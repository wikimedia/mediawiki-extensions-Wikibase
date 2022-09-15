<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Serialization;

use Wikibase\DataModel\Serializers\ReferenceListSerializer;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Serializers\SerializerFactory as LegacySerializerFactory;
use Wikibase\DataModel\Serializers\SiteLinkListSerializer;
use Wikibase\DataModel\Serializers\SnakListSerializer;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\StatementSerializer as LegacyStatementSerializer;
use Wikibase\DataModel\Serializers\TypedSnakSerializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

/**
 * @license GPL-2.0-or-later
 */
class SerializerFactory {

	private $legacySerializerFactory;
	private $dataTypeLookup;

	public function __construct( LegacySerializerFactory $legacySerializerFactory, PropertyDataTypeLookup $dataTypeLookup ) {
		$this->legacySerializerFactory = $legacySerializerFactory;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	public function newItemDataSerializer(): ItemDataSerializer {
		return new ItemDataSerializer(
			$this->newStatementListSerializer(),
			new SiteLinkListSerializer( $this->legacySerializerFactory->newSiteLinkSerializer(), true )
		);
	}

	public function newStatementListSerializer(): StatementListSerializer {
		return new StatementListSerializer( $this->newStatementSerializer(), true );
	}

	public function newStatementSerializer(): StatementSerializer {
		$typedSnakSerializer = new SnakSerializer(
			new TypedSnakSerializer( $this->legacySerializerFactory->newSnakSerializer() ),
			$this->dataTypeLookup
		);
		$snakListSerializer = new SnakListSerializer( $typedSnakSerializer, true );

		return new StatementSerializer( new LegacyStatementSerializer(
			$typedSnakSerializer,
			$snakListSerializer,
			new ReferenceListSerializer( new ReferenceSerializer( $snakListSerializer ) )
		) );
	}

	public function newSiteLinkListSerializer(): SiteLinkListSerializer {
		return new SiteLinkListSerializer( $this->legacySerializerFactory->newSiteLinkSerializer(), true );
	}

}
