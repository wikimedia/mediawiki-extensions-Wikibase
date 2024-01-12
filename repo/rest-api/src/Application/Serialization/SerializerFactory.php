<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

/**
 * @license GPL-2.0-or-later
 */
class SerializerFactory {

	public function newStatementSerializer(): StatementSerializer {
		$propertyValuePairSerializer = new PropertyValuePairSerializer();
		$referenceSerializer = new ReferenceSerializer( $propertyValuePairSerializer );
		return new StatementSerializer( $propertyValuePairSerializer, $referenceSerializer );
	}

	public function newStatementListSerializer(): StatementListSerializer {
		return new StatementListSerializer( $this->newStatementSerializer() );
	}

	public function newItemPartsSerializer(): ItemPartsSerializer {
		return new ItemPartsSerializer(
			new LabelsSerializer(),
			new DescriptionsSerializer(),
			new AliasesSerializer(),
			new StatementListSerializer( $this->newStatementSerializer() ),
			new SiteLinksSerializer( new SiteLinkSerializer() )
		);
	}
}
