<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

/**
 * @license GPL-2.0-or-later
 */
class SerializerFactory {

	private PropertyDataTypeLookup $dataTypeLookup;

	public function __construct( PropertyDataTypeLookup $dataTypeLookup ) {
		$this->dataTypeLookup = $dataTypeLookup;
	}

	public function newStatementSerializer(): StatementSerializer {
		$propertyValuePairSerializer = new PropertyValuePairSerializer( $this->dataTypeLookup );
		$referenceSerializer = new ReferenceSerializer( $propertyValuePairSerializer );
		return new StatementSerializer( $propertyValuePairSerializer, $referenceSerializer );
	}

	public function newReadModelStatementSerializer(): ReadModelStatementSerializer {
		$propertyValuePairSerializer = new PropertyValuePairSerializer( $this->dataTypeLookup );
		$referenceSerializer = new ReferenceSerializer( $propertyValuePairSerializer );
		return new ReadModelStatementSerializer( $propertyValuePairSerializer, $referenceSerializer );
	}

	public function newStatementListSerializer(): StatementListSerializer {
		return new StatementListSerializer( $this->newReadModelStatementSerializer() );
	}

	public function newItemDataSerializer(): ItemDataSerializer {
		return new ItemDataSerializer(
			new StatementListSerializer( $this->newReadModelStatementSerializer() ),
			new SiteLinkListSerializer()
		);
	}
}
