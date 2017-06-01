<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use HashSiteStore;
use Site;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ItemChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	use LabelsChangeOpDeserializationTester;

	use DescriptionsChangeOpDeserializationTester;

	use AliasChangeOpDeserializationTester;

	use ClaimsChangeOpDeserializationTester;

	const SITE_ID = 'some-wiki';

	const SITELINK_GROUP = 'testwikis';

	public function testGivenEmptyArray_returnsEmptyChangeOps() {
		$this->assertEmpty(
			( new ItemChangeOpDeserializer( $this->getChangeOpDeserializerFactoryMock() ) )
				->createEntityChangeOp( [] )
				->getChangeOps()
		);
	}

	public function testGivenAllFieldsInChangeRequest_changeOpChangesAllFields() {
		$item = new Item( new ItemId( 'Q123' ) );

		$newAlias = 'test-alias';
		$newLabel = 'test-label';
		$newDescription = 'test-description';

		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$statementSerialization = WikibaseRepo::getDefaultInstance()->getStatementSerializer()->serialize( $statement );

		$pageTitle = 'Some Title';

		$changeRequest = [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $newAlias ] ],
			'labels' => [ 'en' => [ 'language' => 'en', 'value' => $newLabel ] ],
			'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $newDescription ] ],
			'claims' => [ $statementSerialization ],
			'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => $pageTitle ] ]
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
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		return new ItemChangeOpDeserializer( new ChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( $wikibaseRepo->getTermsLanguages() ),
			new SiteLinkBadgeChangeOpSerializationValidator(
				$wikibaseRepo->getEntityTitleLookup(),
				[]
			),
			$wikibaseRepo->getExternalFormatStatementDeserializer(),
			$this->newSiteLinkTargetProvider(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStringNormalizer(),
			[ self::SITELINK_GROUP ]
		) );
	}

	private function getChangeOpDeserializerFactoryMock() {
		return $this->getMockBuilder( ChangeOpDeserializerFactory::class )
			->disableOriginalConstructor()
			->getMock();
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
