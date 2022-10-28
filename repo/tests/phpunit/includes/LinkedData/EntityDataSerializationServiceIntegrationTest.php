<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\LinkedData;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\Tests\WikibaseTablesUsed;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\LinkedData\EntityDataSerializationService
 *
 * @group Wikibase
 * @group WikibaseRdf
 * @group Database
 *
 * @license GPL-2.0-or-later
 *
 */
class EntityDataSerializationServiceIntegrationTest extends MediaWikiIntegrationTestCase {
	use WikibaseTablesUsed;

	public function testItemRdfWithStub() {
		$this->markTablesUsedForEntityEditing();
		$labelText1 = 'some uniquish string - 2342346345623';
		$item1 = new Item(
			null, $this->getFingerprintWithLabel( $labelText1 ), null, null
		);
		$propertyLabel = 'some uniquish string - 3452054630';
		$property = new Property(
			null, $this->getFingerprintWithLabel( $propertyLabel ), 'wikibase-item'
		);
		$store = WikibaseRepo::getEntityStore();
		$store->saveEntity(
			$item1,
			'add some item',
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);
		$store->saveEntity(
			$property,
			'add some property',
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);

		$labelText2 = 'some uniquish string - 354981328';
		$item2 = new Item(
			null, $this->getFingerprintWithLabel( $labelText2 ), null, null
		);
		$store->assignFreshId( $item2 );
		$item2->setStatements( new StatementList(
			new Statement(
				new PropertyValueSnak(
					$property->getId(), new EntityIdValue( $item1->getId() )
				),
				null,
				null,
				"{$item2->getId()->getSerialization()}\$7b104d3a-95e5-4ed5-9675-5df817343361"
			)
		) );
		$item2Revision = $store->saveEntity(
			$item2,
			'some item',
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);

		$entityDataSerializationService = $this->getEntityDataSerializationService();
		$serialisedData = $entityDataSerializationService->getSerializedData(
			'ttl',
			$item2Revision
		);
		$this->assertStringContainsString( $labelText1,
			$serialisedData[0]
		);
		$this->assertStringContainsString(
			$labelText2,
			$serialisedData[0]
		);
		$this->assertStringContainsString(
			$propertyLabel,
			$serialisedData[0]
		);
	}

	private function getEntityDataSerializationService(): EntityDataSerializationService {
		return WikibaseRepo::getEntityDataSerializationService();
	}

	private function getFingerprintWithLabel( string $labelText ): Fingerprint {
		$fingerprint1 = new Fingerprint();
		$fingerprint1->setLabel( 'en', $labelText );

		return $fingerprint1;
	}

}
