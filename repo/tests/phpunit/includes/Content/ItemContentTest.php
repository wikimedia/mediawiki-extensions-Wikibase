<?php

namespace Wikibase\Repo\Tests\Content;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use InvalidArgumentException;
use Title;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\ItemContent
 * @covers Wikibase\EntityContent
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

	public function provideValidConstructorArguments() {
		return [
			'empty' => [ null, null, null ],
			'empty item' => [ new EntityInstanceHolder( new Item() ), null, null ],
			'redirect' => [
				null,
				new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
				$this->getMock( Title::class )
			],
		];
	}

	/**
	 * @dataProvider provideValidConstructorArguments
	 */
	public function testConstructor(
		EntityHolder $holder = null,
		EntityRedirect $redirect = null,
		Title $title = null
	) {
		$content = new ItemContent( $holder, $redirect, $title );
		$this->assertInstanceOf( ItemContent::class, $content );
	}

	public function provideInvalidConstructorArguments() {
		$holder = new EntityInstanceHolder( new Item() );
		$redirect = new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
		$title = $this->getMock( Title::class );

		$propertyHolder = new EntityInstanceHolder( Property::newFromType( 'string' ) );

		$badTitle = $this->getMock( Title::class );
		$badTitle->method( 'getContentModel' )
			->will( $this->returnValue( 'bad content model' ) );
		$badTitle->method( 'exists' )
			->will( $this->returnValue( true ) );

		return [
			'all' => [ $holder, $redirect, $title ],
			'holder and redirect' => [ $holder, $redirect, null ],
			'holder and title' => [ $holder, null, $title ],
			'redirect only' => [ null, $redirect, null ],
			'title only' => [ null, null, $title ],
			'bad entity type' => [ $propertyHolder, null, null ],
			'bad title' => [ null, $redirect, $badTitle ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testConstructorExceptions(
		EntityHolder $holder = null,
		EntityRedirect $redirect = null,
		Title $title = null
	) {
		$this->setExpectedException( InvalidArgumentException::class );
		new ItemContent( $holder, $redirect, $title );
	}

	/**
	 * @return ItemId
	 */
	protected function getDummyId() {
		return new ItemId( 'Q100' );
	}

	/**
	 * @return string
	 */
	protected function getEntityType() {
		return Item::ENTITY_TYPE;
	}

	/**
	 * @return ItemContent
	 */
	protected function newEmpty() {
		return new ItemContent();
	}

	/**
	 * @param ItemId|null $itemId
	 *
	 * @throws InvalidArgumentException
	 * @return ItemContent
	 */
	protected function newBlank( EntityId $itemId = null ) {
		return new ItemContent( new EntityInstanceHolder( new Item( $itemId ) ) );
	}

	/**
	 * @param ItemId $itemId
	 * @param ItemId $targetId
	 *
	 * @return ItemContent
	 */
	private function newRedirect( ItemId $itemId, ItemId $targetId ) {
		$nsLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$itemNs = $nsLookup->getEntityNamespace( 'item' );

		$title = $this->getMock( Title::class );
		$title->expects( $this->any() )
			->method( 'getFullText' )
			->will( $this->returnValue( $targetId->getSerialization() ) );
		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $targetId->getSerialization() ) );
		$title->expects( $this->any() )
			->method( 'isRedirect' )
			->will( $this->returnValue( false ) );
		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $itemNs ) );
		$title->expects( $this->any() )
			->method( 'equals' )
			->will( $this->returnCallback( function( Title $other ) use ( $targetId ) {
				// XXX: Ignores namespaces
				return $other->getText() === $targetId->getSerialization();
			} ) );
		$title->expects( $this->any() )
			->method( 'getLinkURL' )
			->will( $this->returnValue( 'http://foo.bar/' . $targetId->getSerialization() ) );

		return ItemContent::newFromRedirect( new EntityRedirect( $itemId, $targetId ), $title );
	}

	public function getTextForSearchIndexProvider() {
		$itemContent = $this->newBlank();
		$itemContent->getItem()->setLabel( 'en', 'cake' );
		$itemContent->getItem()->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );

		return [
			[ $itemContent, "cake\nBerlin" ],
		];
	}

	public function providePageProperties() {
		$cases = parent::providePageProperties();

		$contentLinkStub = $this->newBlank( $this->getDummyId() );
		$contentLinkStub->getItem()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$cases['sitelinks'] = [
			$contentLinkStub,
			[ 'wb-claims' => 0, 'wb-sitelinks' => 1 ]
		];

		// @todo this is needed in PropertyContentTest as well
		//       once we have statements in properties
		$contentWithClaim = $this->newBlank( $this->getDummyId() );
		$snak = new PropertyNoValueSnak( 83 );
		$guid = '$testing$';
		$contentWithClaim->getItem()->getStatements()->addNewStatement( $snak, null, null, $guid );

		$cases['claims'] = [
			$contentWithClaim,
			[ 'wb-claims' => 1 ]
		];

		return $cases;
	}

	/**
	 * @return EntityContent
	 */
	private function getItemContentWithClaim() {
		$itemContent = $this->newBlank();
		$item = $itemContent->getItem();

		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P11' ) ),
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
		$snak = new PropertyValueSnak( new PropertyId( 'P11' ), new StringValue( 'Tehran' ) );
		$guid = $item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P';
		$item->getStatements()->addNewStatement( $snak, null, null, $guid );

		$itemContent = $this->getMockBuilder( ItemContent::class )
			->setConstructorArgs( [ new EntityInstanceHolder( $item ) ] )
			->setMethods( [ 'getContentHandler' ] )
			->getMock();

		$handler = $this->getItemHandler();
		$itemContent->expects( $this->any() )
			->method( 'getContentHandler' )
			->will( $this->returnValue( $handler ) );

		return $itemContent;
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P11' ), 'external-id' );

		return $dataTypeLookup;
	}

	/**
	 * @return ItemHandler
	 */
	private function getItemHandler() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new ItemHandler(
			$wikibaseRepo->getStore()->getTermIndex(),
			$wikibaseRepo->getEntityContentDataCodec(),
			$wikibaseRepo->getEntityConstraintProvider(),
			$wikibaseRepo->getValidatorErrorLocalizer(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStore()->newSiteLinkStore(),
			$wikibaseRepo->getEntityIdLookup(),
			$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
			$this->getMock( FieldDefinitions::class ),
			$this->getPropertyDataTypeLookup()
		);
	}

	/**
	 * @return EntityContent
	 */
	private function getItemContentWithSiteLink() {
		$itemContent = $this->newBlank();
		$item = $itemContent->getItem();

		$item->setSiteLinkList( new SiteLinkList( [
			new SiteLink( 'enwiki', 'Foo' )
		] ) );

		return $itemContent;
	}

	public function provideGetEntityPageProperties() {
		$cases = parent::provideGetEntityPageProperties();

		// expect wb-sitelinks => 0 for all inherited cases
		foreach ( $cases as &$case ) {
			$case[1]['wb-sitelinks'] = 0;
			$case[1]['wb-identifiers'] = 0;
		}

		$cases['redirect'] = [
			ItemContent::newFromRedirect(
				new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
				$this->getMock( Title::class )
			),
			[]
		];

		$cases['claims'] = [
			$this->getItemContentWithClaim(),
			[
				'wb-claims' => 1,
				'wb-identifiers' => 0,
				'wb-sitelinks' => 0,
			]
		];

		$cases['sitelinks'] = [
			$this->getItemContentWithSiteLink(),
			[
				'wb-claims' => 0,
				'wb-identifiers' => 0,
				'wb-sitelinks' => 1,
			]
		];

		$cases['identifiers'] = [
			$this->getItemContentWithIdentifierClaims(),
			[
				'wb-claims' => 1,
				'wb-identifiers' => 1,
				'wb-sitelinks' => 0,
			]
		];

		return $cases;
	}

	public function diffProvider() {
		$cases = parent::diffProvider();

		$q10 = new ItemId( 'Q10' );
		$empty = $this->newBlank( $q10 );

		$spam = $this->newBlank( $q10 );
		$spam->getItem()->setLabel( 'en', 'Spam' );

		$redir = $this->newRedirect( $q10, new ItemId( 'Q17' ) );
		$redirTarget = 'Q17';

		$emptyToRedirDiff = new EntityContentDiff(
			new EntityDiff( [] ),
			new Diff( [
				'redirect' => new DiffOpAdd( $redirTarget ),
			], true ),
			$this->getEntityType()
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
			$this->getEntityType()
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
			$this->getEntityType()
		);

		$cases['same redir'] = [ $redir, $redir, new EntityContentDiff(
			new EntityDiff(),
			new Diff(),
			$this->getEntityType()
		) ];
		$cases['empty to redir'] = [ $empty, $redir, $emptyToRedirDiff ];
		$cases['entity to redir'] = [ $spam, $redir, $spamToRedirDiff ];
		$cases['redir to entity'] = [ $redir, $spam, $redirToSpamDiff ];

		return $cases;
	}

	public function patchedCopyProvider() {
		$cases = parent::patchedCopyProvider();

		$q10 = new ItemId( 'Q10' );
		$empty = $this->newBlank( $q10 );

		$spam = $this->newBlank( $q10 );
		$spam->getItem()->setLabel( 'en', 'Spam' );

		$redirTarget = 'Q17';
		$redir = $this->newRedirect( $q10, new ItemId( $redirTarget ) );

		$emptyToRedirDiff = new EntityContentDiff(
			new EntityDiff( [] ),
			new Diff( [
				'redirect' => new DiffOpAdd( $redirTarget ),
			], true ),
			$this->getEntityType()
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
			$this->getEntityType()
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
			$this->getEntityType()
		);

		$cases['empty to redir'] = [ $empty, $emptyToRedirDiff, $redir ];
		$cases['entity to redir'] = [ $spam, $spamToRedirDiff, $redir ];
		$cases['redir to entity'] = [ $redir, $redirToSpamDiff, $spam ];
		$cases['redir with entity clash'] = [ $spam, $emptyToRedirDiff, null ];

		return $cases;
	}

	public function copyProvider() {
		$cases = parent::copyProvider();

		$redir = $this->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) );

		$cases['redirect'] = [ $redir ];

		return $cases;
	}

	public function equalsProvider() {
		$cases = parent::equalsProvider();

		$redir = $this->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) );

		$labels1 = $this->newBlank();
		$labels1->getItem()->setLabel( 'en', 'Foo' );

		$cases['same redirect'] = [ $redir, $redir, true ];
		$cases['redirect vs labels'] = [ $redir, $labels1, false ];
		$cases['labels vs redirect'] = [ $labels1, $redir, false ];

		return $cases;
	}

	public function testGetParserOutput_redirect() {
		$content = $this->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q123' ) );

		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title );

		$html = $parserOutput->getText();

		$this->assertContains( '<div class="redirectMsg">', $html, 'redirect message' );
		$this->assertContains( '<a href="', $html, 'redirect target link' );
		$this->assertContains( 'Q123</a>', $html, 'redirect target label' );
	}

	public function provideGetEntityId() {
		$q11 = new ItemId( 'Q11' );
		$q12 = new ItemId( 'Q12' );

		$cases = [];
		$cases['entity id'] = [ $this->newBlank( $q11 ), $q11 ];
		$cases['redirect id'] = [ $this->newRedirect( $q11, $q12 ), $q11 ];

		return $cases;
	}

	public function provideContentObjectsWithoutId() {
		return [
			'no holder' => [ new ItemContent() ],
			'no ID' => [ new ItemContent( new EntityInstanceHolder( new Item() ) ) ],
		];
	}

	public function entityRedirectProvider() {
		$cases = parent::entityRedirectProvider();

		$cases['redirect'] = [
			$this->newRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ) ),
			new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ) )
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

}
