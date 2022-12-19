<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use HashSiteStore;
use Site;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	use LabelsChangeOpDeserializationTester;

	use DescriptionsChangeOpDeserializationTester;

	use AliasChangeOpDeserializationTester;

	use ClaimsChangeOpDeserializationTester;

	private const SITE_ID = 'some-wiki';

	private const SITELINK_GROUP = 'testwikis';

	public function testGivenEmptyArray_changesNothingOnEntity() {
		$item = new Item( new ItemId( 'Q123' ) );
		$targetItem = $item->copy();

		$changeOp = $this->newItemChangeOpDeserializer()->createEntityChangeOp( [] );
		$changeOpResult = $changeOp->apply( $item, null );

		$this->assertFalse( $changeOpResult->isEntityChanged() );
		$this->assertEquals( $item, $targetItem );
	}

	public function testGivenAllFieldsInChangeRequest_changeOpChangesAllFields() {
		$item = new Item( new ItemId( 'Q123' ) );

		$newAlias = 'test-alias';
		$newLabel = 'test-label';
		$newDescription = 'test-description';

		$property = new NumericPropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$statementSerializer = WikibaseRepo::getBaseDataModelSerializerFactory()->newStatementSerializer();
		$statementSerialization = $statementSerializer->serialize( $statement );

		$pageTitle = 'Some Title';

		$changeRequest = [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $newAlias ] ],
			'labels' => [ 'en' => [ 'language' => 'en', 'value' => $newLabel ] ],
			'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $newDescription ] ],
			'claims' => [ $statementSerialization ],
			'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => $pageTitle ] ],
		];

		$changeOp = $this->newItemChangeOpDeserializer()->createEntityChangeOp( $changeRequest );

		$changeOp->apply( $item, new Summary() );

		$this->assertSame( [ $newAlias ], $item->getAliasGroups()->getByLanguage( 'en' )->getAliases() );
		$this->assertSame( $newLabel, $item->getLabels()->getByLanguage( 'en' )->getText() );
		$this->assertSame( $newDescription, $item->getDescriptions()->getByLanguage( 'en' )->getText() );

		$this->assertFalse( $item->getStatements()->getByPropertyId( $property )->isEmpty() );
		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, $pageTitle )
			)
		);
	}

	private function newItemChangeOpDeserializer() {
		$changeOpFactoryProvider = WikibaseRepo::getChangeOpFactoryProvider();

		return new ItemChangeOpDeserializer( new ChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( WikibaseRepo::getTermsLanguages() ),
			new SiteLinkBadgeChangeOpSerializationValidator(
				WikibaseRepo::getEntityTitleLookup(),
				[]
			),
			WikibaseRepo::getExternalFormatStatementDeserializer(),
			new SiteLinkPageNormalizer( [] ),
			$this->newSiteLinkTargetProvider(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getEntityLookup(),
			WikibaseRepo::getStringNormalizer(),
			[ self::SITELINK_GROUP ]
		) );
	}

	private function newSiteLinkTargetProvider() {
		$wiki = new Site();
		$wiki->setGlobalId( self::SITE_ID );
		$wiki->setGroup( self::SITELINK_GROUP );

		return new SiteLinkTargetProvider( new HashSiteStore( [ $wiki ] ) );
	}

	public function getChangeOpDeserializer() {
		return $this->newItemChangeOpDeserializer();
	}

	public function getEntity() {
		return new Item( new ItemId( 'Q23' ) );
	}

}
