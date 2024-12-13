<?php

namespace Wikibase\Repo\Tests\Content;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use InvalidArgumentException;
use MediaWiki\Title\Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\NullEntityTermStoreWriter;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Content\EntityHolder;
use Wikibase\Repo\Content\EntityInstanceHolder;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Content\ItemContent
 * @covers \Wikibase\Repo\Content\EntityContent
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseContent
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ItemContentTest extends EntityContentTestCase {

	public static function provideValidConstructorArguments() {
		return [
			'empty' => [ fn () => null, null, null ],
			'empty item' => [ fn () => null, new EntityInstanceHolder( new Item() ), null ],
			'redirect' => [
				fn ( self $self ) => $self->createMock( Title::class ),
				null,
				new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
			],
		];
	}

	/**
	 * @dataProvider provideValidConstructorArguments
	 */
	public function testConstructor(
		callable $titleFactory,
		?EntityHolder $holder,
		?EntityRedirect $redirect
	) {
		$title = $titleFactory( $this );
		$content = new ItemContent( $holder, $redirect, $title );
		$this->assertInstanceOf( ItemContent::class, $content );
	}

	public static function provideInvalidConstructorArguments() {
		$holder = new EntityInstanceHolder( new Item() );
		$redirect = new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
		$titleFactory = fn ( self $self ) => $self->createMock( Title::class );

		$propertyHolder = new EntityInstanceHolder( Property::newFromType( 'string' ) );

		$badTitleFactory = function ( self $self ) {
			$badTitle = $self->createMock( Title::class );
			$badTitle->method( 'getContentModel' )
				->willReturn( 'bad content model' );
			$badTitle->method( 'exists' )
				->willReturn( true );
		};

		return [
			'all' => [ $holder, $redirect, $titleFactory ],
			'holder and redirect' => [ $holder, $redirect, null ],
			'holder and title' => [ $holder, null, $titleFactory ],
			'redirect only' => [ null, $redirect, null ],
			'title only' => [ null, null, $titleFactory ],
			'bad entity type' => [ $propertyHolder, null, null ],
			'bad title' => [ null, $redirect, $badTitleFactory ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testConstructorExceptions(
		?EntityHolder $holder,
		?EntityRedirect $redirect,
		?callable $titleFactory
	) {
		$title = $titleFactory == null ? null : $titleFactory( $this );
		$this->expectException( InvalidArgumentException::class );
		new ItemContent( $holder, $redirect, $title );
	}

	/**
	 * @return ItemId
	 */
	protected static function getDummyId() {
		return new ItemId( 'Q100' );
	}

	/**
	 * @return string
	 */
	protected static function getEntityType() {
		return Item::ENTITY_TYPE;
	}

	/**
	 * @return ItemContent
	 */
	protected static function newEmpty() {
		return new ItemContent();
	}

	/**
	 * @param ItemId|null $itemId
	 *
	 * @throws InvalidArgumentException
	 * @return ItemContent
	 */
	protected static function newBlank( ?EntityId $itemId = null ) {
		return new ItemContent( new EntityInstanceHolder( new Item( $itemId ) ) );
	}

	/**
	 * @param ItemId $itemId
	 * @param ItemId $targetId
	 *
	 * @return ItemContent
	 */
	private function newRedirect( ItemId $itemId, ItemId $targetId ) {
		$nsLookup = WikibaseRepo::getEntityNamespaceLookup();
		$itemNs = $nsLookup->getEntityNamespace( 'item' );

		$title = $this->createMock( Title::class );
		$title->method( 'getFullText' )
			->willReturn( $targetId->getSerialization() );
		$title->method( 'getText' )
			->willReturn( $targetId->getSerialization() );
		$title->method( 'isRedirect' )
			->willReturn( false );
		$title->method( 'getNamespace' )
			->willReturn( $itemNs );
		$title->method( 'equals' )
			->willReturnCallback( static function ( Title $other ) use ( $targetId ) {
				// XXX: Ignores namespaces
				return $other->getText() === $targetId->getSerialization();
			} );
		$title->method( 'getLinkURL' )
			->willReturn( 'http://foo.bar/' . $targetId->getSerialization() );

		return ItemContent::newFromRedirect( new EntityRedirect( $itemId, $targetId ), $title );
	}

	public static function getTextForSearchIndexProvider() {
		$itemContent = self::newBlank();
		$itemContent->getItem()->setLabel( 'en', 'cake' );
		$itemContent->getItem()->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );

		return [
			[ $itemContent, "cake\nBerlin" ],
		];
	}

	/**
	 * @return EntityContent
	 */
	private function getItemContentWithClaim() {
		$itemContent = $this->newBlank();
		$item = $itemContent->getItem();

		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P11' ) ),
			null,
			null,
			'Whatever'
		);

		return $itemContent;
	}

	/**
	 * @return EntityContent
	 */
	private function getItemContentWithIdentifierClaims() {
		$item = new Item( new ItemId( 'Q2' ) );
		$snak = new PropertyValueSnak( new NumericPropertyId( 'P11' ), new StringValue( 'Tehran' ) );
		$guid = $item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P';
		$item->getStatements()->addNewStatement( $snak, null, null, $guid );

		$itemContent = $this->getMockBuilder( ItemContent::class )
			->setConstructorArgs( [ new EntityInstanceHolder( $item ) ] )
			->onlyMethods( [ 'getContentHandler' ] )
			->getMock();

		$handler = $this->getItemHandler();
		$itemContent->method( 'getContentHandler' )
			->willReturn( $handler );

		return $itemContent;
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P11' ), 'external-id' );

		return $dataTypeLookup;
	}

	/**
	 * @return ItemHandler
	 */
	private function getItemHandler() {
		return new ItemHandler(
			new NullEntityTermStoreWriter(),
			WikibaseRepo::getEntityContentDataCodec(),
			WikibaseRepo::getEntityConstraintProvider(),
			WikibaseRepo::getValidatorErrorLocalizer(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getStore()->newSiteLinkStore(),
			WikibaseRepo::getBagOStuffSiteLinkConflictLookup(),
			WikibaseRepo::getEntityIdLookup(),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			$this->createMock( FieldDefinitions::class ),
			$this->getPropertyDataTypeLookup(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
		);
	}

	/**
	 * @return EntityContent
	 */
	private static function getItemContentWithSiteLink() {
		$itemContent = self::newBlank();
		$item = $itemContent->getItem();

		$item->setSiteLinkList( new SiteLinkList( [
			new SiteLink( 'enwiki', 'Foo' ),
		] ) );

		return $itemContent;
	}

	public static function provideGetEntityPageProperties() {
		$cases = parent::provideGetEntityPageProperties();

		// expect wb-sitelinks => 0 for all inherited cases
		foreach ( $cases as &$case ) {
			$case[1]['wb-sitelinks'] = 0;
			$case[1]['wb-identifiers'] = 0;
		}

		$cases['redirect'] = [
			fn ( self $self ) => ItemContent::newFromRedirect(
				new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
				$self->createMock( Title::class )
			),
			[],
		];

		$cases['claims'] = [
			fn ( self $self ) => $self->getItemContentWithClaim(),
			[
				'wb-claims' => 1,
				'wb-identifiers' => 0,
				'wb-sitelinks' => 0,
			],
		];

		$cases['sitelinks'] = [
			fn ( self $self ) => $self->getItemContentWithSiteLink(),
			[
				'wb-claims' => 0,
				'wb-identifiers' => 0,
				'wb-sitelinks' => 1,
			],
		];

		$cases['identifiers'] = [
			fn ( self $self ) => $self->getItemContentWithIdentifierClaims(),
			[
				'wb-claims' => 1,
				'wb-identifiers' => 1,
				'wb-sitelinks' => 0,
			],
		];

		return $cases;
	}

	public static function diffProvider() {
		$cases = parent::diffProvider();

		$q10 = new ItemId( 'Q10' );
		$empty = self::newBlank( $q10 );

		$spam = self::newBlank( $q10 );
		$spam->getItem()->setLabel( 'en', 'Spam' );

		$redirTarget = 'Q17';

		$emptyToRedirDiff = new EntityContentDiff(
			new EntityDiff( [] ),
			new Diff( [
				'redirect' => new DiffOpAdd( $redirTarget ),
			], true ),
			self::getEntityType()
		);

		$spamToRedirDiff = new EntityContentDiff(
			new EntityDiff( [
				'label' => new Diff(
						[ 'en' => new DiffOpRemove( 'Spam' ) ]
					),
			] ),
			new Diff( [
				'redirect' => new DiffOpAdd( $redirTarget ),
			], true ),
			self::getEntityType()
		);

		$redirToSpamDiff = new EntityContentDiff(
			new EntityDiff( [
				'label' => new Diff(
						[ 'en' => new DiffOpAdd( 'Spam' ) ]
					),
			] ),
			new Diff( [
				'redirect' => new DiffOpRemove( $redirTarget ),
			], true ),
			self::getEntityType()
		);

		$cases['same redir'] = [
			function ( self $self ) use ( $q10 ) {
				$redir = $self->newRedirect( $q10, new ItemId( 'Q17' ) );
				return [ $redir, $redir ];
			},
			new EntityContentDiff(
				new EntityDiff(),
				new Diff(),
				self::getEntityType()
			),
		];
		$cases['empty to redir'] = [
			fn ( self $self ) => [ $empty, $self->newRedirect( $q10, new ItemId( 'Q17' ) ) ],
			$emptyToRedirDiff,
		];
		$cases['entity to redir'] = [
			fn ( self $self ) => [ $spam, $self->newRedirect( $q10, new ItemId( 'Q17' ) ) ],
			$spamToRedirDiff,
		];
		$cases['redir to entity'] = [
			fn ( self $self ) => [ $self->newRedirect( $q10, new ItemId( 'Q17' ) ), $spam ],
			$redirToSpamDiff,
		];

		return $cases;
	}

	public static function patchedCopyProvider() {
		$cases = parent::patchedCopyProvider();

		$q10 = new ItemId( 'Q10' );
		$empty = self::newBlank( $q10 );

		$spam = self::newBlank( $q10 );
		$spam->getItem()->setLabel( 'en', 'Spam' );

		$redirTarget = 'Q17';

		$emptyToRedirDiff = new EntityContentDiff(
			new EntityDiff( [] ),
			new Diff( [
				'redirect' => new DiffOpAdd( $redirTarget ),
			], true ),
			self::getEntityType()
		);

		$spamToRedirDiff = new EntityContentDiff(
			new EntityDiff( [
				'label' => new Diff(
						[ 'en' => new DiffOpRemove( 'Spam' ) ]
					),
			] ),
			new Diff( [
				'redirect' => new DiffOpAdd( $redirTarget ),
			], true ),
			self::getEntityType()
		);

		$redirToSpamDiff = new EntityContentDiff(
			new EntityDiff( [
				'label' => new Diff(
						[ 'en' => new DiffOpAdd( 'Spam' ) ]
					),
			] ),
			new Diff( [
				'redirect' => new DiffOpRemove( $redirTarget ),
			], true ),
			self::getEntityType()
		);

		$cases['empty to redir'] = [
			fn ( self $self ) => [ $empty, $emptyToRedirDiff, $self->newRedirect( $q10, new ItemId( $redirTarget ) ) ],
		];
		$cases['entity to redir'] = [
			fn ( self $self ) => [ $spam, $spamToRedirDiff, $self->newRedirect( $q10, new ItemId( $redirTarget ) ) ],
		];
		$cases['redir to entity'] = [
			fn ( self $self ) => [ $self->newRedirect( $q10, new ItemId( $redirTarget ) ), $redirToSpamDiff, $spam ],
		];
		$cases['redir with entity clash'] = [
			fn ( self $self ) => [ $spam, $emptyToRedirDiff, $self->newRedirect( $q10, new ItemId( $redirTarget ) ) ],
		];

		return $cases;
	}

	public static function copyProvider() {
		$cases = parent::copyProvider();

		$cases['redirect'] = [
			fn ( self $self ) => $self->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) ),
		];

		return $cases;
	}

	public static function equalsProvider() {
		$cases = parent::equalsProvider();

		$labels1 = self::newBlank();
		$labels1->getItem()->setLabel( 'en', 'Foo' );

		$cases['same redirect'] = [
			function ( self $self ) {
				$redir = $self->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) );
				return [ $redir, $redir ];
			},
			true,
		];
		$cases['redirect vs labels'] = [
			fn ( self $self ) => [
				$self->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) ),
				$labels1,
			],
			false,
		];
		$cases['labels vs redirect'] = [
			fn ( self $self ) => [
				$labels1,
				$self->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) ),
			],
			false,
		];

		return $cases;
	}

	public static function provideGetEntityId() {
		$q11 = new ItemId( 'Q11' );
		$q12 = new ItemId( 'Q12' );

		yield 'entity id' => [ fn () => [ static::newBlank( $q12 ), $q12 ] ];
		yield 'redirect id' => [
			fn ( self $self ) => [ $self->newRedirect( $q11, $q12 ), $q11 ],
		];
	}

	public static function provideContentObjectsWithoutId() {
		return [
			'no holder' => [ new ItemContent() ],
			'no ID' => [ new ItemContent( new EntityInstanceHolder( new Item() ) ) ],
		];
	}

	public static function entityRedirectProvider() {
		$cases = parent::entityRedirectProvider();

		$cases['redirect'] = [
			fn ( self $self ) => $self->newRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ) ),
			new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ) ),
		];

		return $cases;
	}

	public function testIsEmpty_emptyItem() {
		$content = ItemContent::newFromItem( new Item() );
		$this->assertTrue( $content->isEmpty() );
	}

	public function testIsEmpty_nonEmptyItem() {
		$item = new Item();
		$item->setLabel( 'en', '~=[,,_,,]:3' );
		$content = ItemContent::newFromItem( $item );
		$this->assertFalse( $content->isEmpty() );
	}

	public function testGetTextForFilters() {
		$item = new Item(
			new ItemId( 'Q123' ),
			new Fingerprint(
				new TermList( [ new Term( 'en', 'label1' ), new Term( 'de', 'label2' ) ] ),
				new TermList( [ new Term( 'en', 'descen' ), new Term( 'de', 'descde' ) ] ),
				new AliasGroupList(
					[
						new AliasGroup( 'fr', [ 'alias1', 'alias2' ] ),
						new AliasGroup( 'pt', [ 'alias3' ] ),
					]
				)
			),
			new SiteLinkList(
				[
					new SiteLink(
						'dewiki',
						'page1',
						new ItemIdSet( [ new ItemId( 'Q1' ), new ItemId( 'Q2' ) ] )
					),
					new SiteLink( 'nowiki', 'page2', [ new ItemId( 'Q1' ) ] ),
				]
			),
			new StatementList(
				new Statement(
					new PropertyValueSnak(
						new NumericPropertyId( 'P6654' ), new StringValue( 'stringvalue' )
					),
					new SnakList(
						[
							new PropertyValueSnak(
								new NumericPropertyId( 'P6654' ),
								new GlobeCoordinateValue( new LatLongValue( 1, 2 ), 1 )
							),
							new PropertyValueSnak(
								new NumericPropertyId( 'P6654' ),
								new TimeValue(
									'+2015-11-11T00:00:00Z',
									0,
									0,
									0,
									TimeValue::PRECISION_DAY,
									TimeValue::CALENDAR_GREGORIAN
								)
							),
						]
					),
					new ReferenceList(
						[
							new Reference(
								[
									new PropertySomeValueSnak( new NumericPropertyId( 'P987' ) ),
									new PropertyNoValueSnak( new NumericPropertyId( 'P986' ) ),
								]
							),
						]
					),
					'imaguid'
				)
			)
		);

		$content = new ItemContent( new EntityInstanceHolder( $item ) );
		$output = $content->getTextForFilters();

		$this->assertSame(
			trim( file_get_contents( __DIR__ . '/textForFiltersItem.txt' ) ),
			$output
		);
	}

	private function setLanguageCodeEnglish() {
		$this->setMwGlobals( 'wgLanguageCode', 'en' );
	}

	public function testGetSummaryText() {
		$this->setLanguageCodeEnglish();

		$labels = $this->newBlank();
		$labels->getItem()->setLabel( 'de', 'Will get chosen' );
		$labels->getItem()->setLabel( 'sv', 'Boo' );
		$this->assertEquals( 'Will get chosen', $labels->getTextForSummary() );
	}

	public function testGetSummaryTextGetsCurrentLanguage() {
		$this->setLanguageCodeEnglish();

		$labels = $this->newBlank();
		$labels->getItem()->setLabel( 'de', 'Moo' );
		$labels->getItem()->setLabel( 'en', 'Will get chosen' );
		$this->assertEquals( 'Will get chosen', $labels->getTextForSummary() );
	}

	public function testSummaryGetsCutOffIfTooLong() {
		$this->setLanguageCodeEnglish();

		$labels = $this->newBlank();
		$labels->getItem()->setLabel( 'en', 'Will get chosen' );
		$this->assertEquals( 'Will ge...', $labels->getTextForSummary( 10 ) );
	}

	public function testGetSummaryNoLabelsReturnsEmptyString() {
		$labels = $this->newBlank();
		$this->assertSame( '', $labels->getTextForSummary() );
	}

	public function testGetSummaryRedirect() {
		$itemContent = new ItemContent(
			null,
			new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
			Title::newFromTextThrow( 'Item:Q2' )
		);
		$this->assertSame( '#REDIRECT [[Item:Q2]]', $itemContent->getTextForSummary() );
	}
}
