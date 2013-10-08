<?php

namespace Wikibase\Test;

use Language;
use Title;
use DataValues\StringValue;
use ValueFormatters\ValueFormatterFactory;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Entity;
use Wikibase\EntityRevision;
use Wikibase\EntityRevisionLookup;
use Wikibase\EntityTitleLookup;
use Wikibase\EntityView;
use Wikibase\Item;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;

/**
 * @covers Wikibase\EntityView
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
class EntityViewTest extends \MediaWikiTestCase {

	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

	/**
	 * @return SnakFormatter
	 */
	protected function newSnakFormatterMock() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )->method( 'formatSnak' )
			->will( $this->returnValue( '(value)' ) );

		$snakFormatter->expects( $this->any() )->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML_WIDGET ) );

		$snakFormatter->expects( $this->any() )->method( 'canFormatSnak' )
			->will( $this->returnValue( true ) );

		return $snakFormatter;
	}

	/**
	 * @param string $entityType
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param \IContextSource $context
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @return EntityView
	 */
	protected function newEntityView( $entityType, EntityRevisionLookup $entityRevisionLookup = null,
		EntityTitleLookup $entityTitleLookup = null, \IContextSource $context = null,
		LanguageFallbackChain $languageFallbackChain = null
	) {
		if ( !is_string( $entityType ) ) {
			throw new \InvalidArgumentException( '$entityType must be a string!' );
		}

		if ( !$entityRevisionLookup ) {
			$entityRevisionLookup = new MockRepository();
		}

		if ( !$entityTitleLookup ) {
			$entityTitleLookup = $this->getEntityTitleLookupMock();
		}

		$p11 = new PropertyId( 'p11' );
		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );
		$p44 = new PropertyId( 'p44' );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( $p23, 'string' );
		$dataTypeLookup->setDataTypeForProperty( $p42, 'url' );

		$dataTypeLookup->setDataTypeForProperty( $p11, 'wikibase-item' );
		$dataTypeLookup->setDataTypeForProperty( $p44, 'wikibase-item' );

		$entityView = EntityView::newForEntityType(
			$entityType,
			$this->newSnakFormatterMock(),
			$dataTypeLookup,
			$entityRevisionLookup,
			$entityTitleLookup,
			$context,
			$languageFallbackChain
		);

		return $entityView;
	}

	/**
	 * @param Claim[] $claims
	 *
	 * @return EntityRevision
	 */
	protected function newEntityRevisionForClaims( $claims ) {
		static $revId = 1234;
		$revId++;

		$entity = Item::newEmpty();
		$entity->setId( new ItemId( "Q$revId" ) );

		foreach ( $claims as $claim ) {
			$entity->addClaim( $claim );
		}

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $entity, $revId, $timestamp );

		return $revision;
	}

	/**
	 * @return array
	 */
	public function getHtmlForClaimsProvider() {
		$argLists = array();

		$claim = $this->makeClaim(
			new PropertyNoValueSnak(
				new PropertyId( 'p24' )
			)
		);

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q96' ) );
		$item->addClaim( $claim );

		$argLists[] = array( $item );

		return $argLists;
	}

	/**
	 * @dataProvider getHtmlForClaimsProvider
	 *
	 * @param Entity $entity
	 */
	public function testGetHtmlForClaims( Entity $entity ) {
		$entityView = $this->newEntityView( $entity->getType() );

		$lang = Language::factory( 'en' );

		// Using a DOM document to parse HTML output:
		$doc = new \DOMDocument();

		// Disable default error handling in order to catch warnings caused by malformed markup:
		libxml_use_internal_errors( true );

		// Try loading the HTML:
		$this->assertTrue( $doc->loadHTML( $entityView->getHtmlForClaims( $entity, $lang ) ) );

		// Check if no warnings have been thrown:
		$errorString = '';
		foreach( libxml_get_errors() as $error ) {
			$errorString .= "\r\n" . $error->message;
		}

		$this->assertEmpty( $errorString, 'Malformed markup:' . $errorString );

		// Clear error cache and re-enable default error handling:
		libxml_clear_errors();
		libxml_use_internal_errors();
	}

	/**
	 * @dataProvider getParserOutputLinksProvider
	 *
	 * @param Claim[] $claims
	 * @param EntityId[] $expectedLinks
	 */
	public function testParserOutputLinks( array $claims, $expectedLinks ) {
		$entityRevision = $this->newEntityRevisionForClaims( $claims );
		$entityView = $this->newEntityView( $entityRevision->getEntity()->getType() );

		$out = $entityView->getParserOutput( $entityRevision, null, false );
		$links = $out->getLinks();

		// convert expected links to link structure
		foreach ( $expectedLinks as $entityId ) {
			$title = $this->getTitleForId( $entityId );
			$ns = $title->getNamespace();
			$dbk = $title->getDBkey();

			$this->assertArrayHasKey( $ns, $links, "sub-array for namespace" );
			$this->assertArrayHasKey( $dbk, $links[$ns], "entry for database key" );
		}
	}

	protected $guidCounter = 0;

	protected function makeClaim( Snak $mainSnak, $guid = null ) {
		if ( $guid === null ) {
			$this->guidCounter++;
			$guid = 'EntityViewTest$' . $this->guidCounter;
		}

		$claim = new Claim( $mainSnak );
		$claim->setGuid( $guid );

		return $claim;
	}

	public function getParserOutputLinksProvider() {
		$argLists = array();

		$p11 = new PropertyId( 'p11' );
		$p23 = new PropertyId( 'p42' );
		$p44 = new PropertyId( 'p44' );

		$q23 = new ItemId( 'Q23' );
		$q24 = new ItemId( 'Q24' );

		$argLists["empty"] = array(
			array(),
			array() );

		$argLists["PropertyNoValueSnak"] = array(
			array( $this->makeClaim( new PropertyNoValueSnak( $p44 ) ) ),
			array( $p44 ) );

		$argLists["PropertySomeValueSnak"] = array(
			array( $this->makeClaim( new PropertySomeValueSnak( $p44 ) ) ),
			array( $p44 ) );

		$argLists["PropertyValueSnak with string value"] = array(
			array( $this->makeClaim( new PropertyValueSnak( $p23, new StringValue( 'onoez' ) ) ) ),
			array( $p23 ) );

		$argLists["PropertyValueSnak with EntityId"] = array(
			array( $this->makeClaim( new PropertyValueSnak( $p44, new EntityIdValue( $q23 ) ) ) ),
			array( $p44, $q23 ) );

		$argLists["Mixed Snaks"] = array(
			array(
				$this->makeClaim( new PropertyValueSnak( $p11, new EntityIdValue( $q23 ) ) ),
				$this->makeClaim( new PropertyNoValueSnak( $p44 ) ),
				$this->makeClaim( new PropertySomeValueSnak( $p44 ) ),
				$this->makeClaim( new PropertyValueSnak( $p44, new StringValue( 'onoez' ) ) ),
				$this->makeClaim( new PropertyValueSnak( $p44, new EntityIdValue( $q24 ) ) ),
			),
			array( $p11, $q23, $p44, $q24 ) );

		return $argLists;
	}

	/**
	 * @dataProvider getParserOutputExternalLinksProvider
	 *
	 * @param Claim[] $claims
	 * @param string[] $expectedLinks
	 */
	public function testParserOutputExternalLinks( array $claims, $expectedLinks ) {
		$entityRevision = $this->newEntityRevisionForClaims( $claims );
		$entityView = $this->newEntityView( $entityRevision->getEntity()->getType() );

		$out = $entityView->getParserOutput( $entityRevision, null, false );
		$links = $out->getExternalLinks();

		$expectedLinks = array_values( $expectedLinks );
		sort( $expectedLinks );

		$links = array_keys( $links );
		sort( $links );

		$this->assertEquals( $expectedLinks, $links );
	}

	public function getParserOutputExternalLinksProvider() {
		$argLists = array();

		$p23 = new PropertyId( 'P23' );
		$p42 = new PropertyId( 'P42' );

		$argLists["empty"] = array(
			array(),
			array() );

		$argLists["PropertyNoValueSnak"] = array(
			array( $this->makeClaim( new PropertyNoValueSnak( $p42 ) ) ),
			array());

		$argLists["PropertySomeValueSnak"] = array(
			array( $this->makeClaim( new PropertySomeValueSnak( $p42 ) ) ),
			array() );

		$argLists["PropertyValueSnak with string value"] = array(
			array( $this->makeClaim( new PropertyValueSnak( $p23, new StringValue( 'http://not/a/url' )  ) ) ),
			array() );

		$argLists["PropertyValueSnak with URL"] = array(
			array( $this->makeClaim( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ) ) ),
			array( 'http://acme.com/test' ) );

		return $argLists;
	}

	/**
	 * @dataProvider providerNewForEntityRevision
	 */
	public function testNewForEntityRevision( EntityRevision $entityRevision ) {
		$dataTypeLookup = new InMemoryDataTypeLookup( array() );
		$entityLoader = new MockRepository();
		$entityTitleLookup = $this->getEntityTitleLookupMock();

		// test whether we get the right EntityView from an EntityRevision
		$view = EntityView::newForEntityType(
			$entityRevision->getEntity()->getType(),
			$this->newSnakFormatterMock(), 
			$dataTypeLookup,
			$entityLoader,
			$entityTitleLookup
		);

		// test whether we get the right EntityView from an EntityContent
		$this->assertInstanceOf(
			EntityView::$typeMap[ $entityRevision->getEntity()->getType() ],
			$view
		);
	}

	public static function providerNewForEntityRevision() {
		return array(
			array( new EntityRevision( Item::newEmpty(), 23, '20130102030405' ) ),
			array( new EntityRevision( Property::newEmpty(), 42, '20130102030405' ) )
		);
	}

	/**
	 * @dataProvider provideRegisterJsConfigVars
	 */
	public function testRegisterJsConfigVars( EntityRevision $entityRevision, EntityRevisionLookup $entityRevisionLookup,
		$context, LanguageFallbackChain $languageFallbackChain, $langCode, $editableView, $expected
	) {
		$this->setMwGlobals( 'wgLang', Language::factory( "en" ) );

		$entityView = $this->newEntityView(
			$entityRevision->getEntity()->getType(),
			$entityRevisionLookup,
			$this->getEntityTitleLookupMock(),
			$context,
			$languageFallbackChain
		);

		$out = new \OutputPage( new \RequestContext() );
		$entityView->registerJsConfigVars( $out, $entityRevision, $langCode, $editableView );
		$actual = array_intersect_key( $out->mJsConfigVars, $expected );

		ksort( $expected );
		ksort( $actual );

		$this->assertEquals( $expected, $actual );
	}

	public function provideRegisterJsConfigVars() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		$argLists = array();

		$entity = Item::newEmpty();
		$entity->setLabel( 'de', 'foo' );
		$entity->setId( 27449 );

		$q98 = new ItemId( 'Q27498' );
		$entityQ98 = Item::newEmpty();
		$entityQ98->setLabel( 'de', 'bar' );
		$entityQ98->setId( $q98 );

		$itemTitle = $this->getTitleForId( $q98 );
		$titleText = $itemTitle->getPrefixedText();

		$entityLoader = new MockRepository();
		$entityLoader->putEntity( $entityQ98 );

		$p11 = new PropertyId( 'p11' );

		$entity->addClaim( $this->makeClaim( new PropertyValueSnak( $p11, new EntityIdValue( $q98 ) ) ) );

		$revision = new EntityRevision( $entity, 1234567, '20130505333333' );

		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguageCode(
			'de-formal', LanguageFallbackChainFactory::FALLBACK_ALL
		); // with fallback to German

		$argLists[] = array( $revision, $entityLoader, null, $languageFallbackChain, 'fr', true, array(
			'wbEntityType' => 'item',
			'wbDataLangName' => 'français',
			'wbEntityId' => 'Q27449',
			'wbEntity' => '{"id":"Q27449","type":"item","labels":{"de":{"language":"de","value":"foo"},"fr":{"language":"de","value":"foo"}},"claims":{"P11":[{"id":"EntityViewTest$1","mainsnak":{"snaktype":"value","property":"P11","datatype":"wikibase-item","datavalue":{"value":{"entity-type":"item","numeric-id":27498},"type":"wikibase-entityid"}},"type":"claim"}]}}',
			'wbUsedEntities' => '{"Q27498":{"content":{"id":"Q27498","type":"item","labels":{"fr":{"language":"de","value":"bar"}}},"title":"' . $titleText . '","revision":""}}',
		) );

		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguageCode(
			'de-formal', LanguageFallbackChainFactory::FALLBACK_SELF
		); // with no fallback

		$argLists[] = array( $revision, $entityLoader, null, $languageFallbackChain, 'nl', true, array(
			'wbEntityType' => 'item',
			'wbDataLangName' => 'Nederlands',
			'wbEntityId' => 'Q27449',
			'wbEntity' => '{"id":"Q27449","type":"item","labels":{"de":{"language":"de","value":"foo"}},"claims":{"P11":[{"id":"EntityViewTest$1","mainsnak":{"snaktype":"value","property":"P11","datatype":"wikibase-item","datavalue":{"value":{"entity-type":"item","numeric-id":27498},"type":"wikibase-entityid"}},"type":"claim"}]}}',
			'wbUsedEntities' => '{"Q27498":{"content":{"id":"Q27498","type":"item"},"title":"' . $titleText . '","revision":""}}',
		) );

		// TODO: add more tests for other JS vars

		return $argLists;
	}
}
