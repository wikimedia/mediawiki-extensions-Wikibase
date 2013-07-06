<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use ValueFormatters\ValueFormatterFactory;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\EntityLookup;
use Wikibase\EntityView;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\EntityView
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
class EntityViewTest extends \PHPUnit_Framework_TestCase {

	protected function newEntityView( EntityContent $entityContent, EntityLookup $entityLoader = null,
		\IContextSource $context = null, LanguageFallbackChain $languageFallbackChain = null
	) {
		$valueFormatters = new ValueFormatterFactory( array() );
		if ( !$entityLoader ) {
			$entityLoader = new MockRepository();
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

		$entityView = EntityView::newForEntityContent(
			$entityContent,
			$valueFormatters,
			$dataTypeLookup,
			$entityLoader,
			$context,
			$languageFallbackChain
		);

		return $entityView;
	}

	protected function newEntityContentForClaims( $claims ) {
		$entity = Item::newEmpty();

		foreach ( $claims as $claim ) {
			$entity->addClaim( $claim );
		}

		$content = EntityContentFactory::singleton()->newFromEntity( $entity );
		return $content;
	}

	/**
	 * @return array
	 */
	public function getHtmlForClaimsProvider() {
		$argLists = array();

		$itemContent = ItemContent::newEmpty();
		$itemContent->getEntity()->addClaim(
			new Claim(
				new PropertyNoValueSnak(
					new PropertyId( 'p24' )
				)
			)
		);

		$argLists[] = array( $itemContent );

		return $argLists;
	}

	/**
	 * @dataProvider getHtmlForClaimsProvider
	 *
	 * @param EntityContent $entityContent
	 */
	public function testGetHtmlForClaims( EntityContent $entityContent ) {
		$entityView = $this->newEntityView( $entityContent );

		// Using a DOM document to parse HTML output:
		$doc = new \DOMDocument();

		// Disable default error handling in order to catch warnings caused by malformed markup:
		libxml_use_internal_errors( true );

		// Try loading the HTML:
		$this->assertTrue( $doc->loadHTML( $entityView->getHtmlForClaims( $entityContent ) ) );

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
		$entityContent = $this->newEntityContentForClaims( $claims );
		$entityView = $this->newEntityView( $entityContent );

		$out = $entityView->getParserOutput( $entityContent, null, false );
		$links = $out->getLinks();

		// convert expected links to link structure
		$contentFactory = EntityContentFactory::singleton();

		foreach ( $expectedLinks as $entityId ) {
			$title = $contentFactory->getTitleForId( $entityId );
			$ns = $title->getNamespace();
			$dbk = $title->getDBkey();

			$this->assertArrayHasKey( $ns, $links, "sub-array for namespace" );
			$this->assertArrayHasKey( $dbk, $links[$ns], "entry for database key" );
		}
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
			array( new Claim( new PropertyNoValueSnak( $p44 ) ) ),
			array( $p44 ) );

		$argLists["PropertySomeValueSnak"] = array(
			array( new Claim( new PropertySomeValueSnak( $p44 ) ) ),
			array( $p44 ) );

		$argLists["PropertyValueSnak with string value"] = array(
			array( new Claim( new PropertyValueSnak( $p23, new StringValue( 'onoez' ) ) ) ),
			array( $p23 ) );

		$argLists["PropertyValueSnak with EntityId"] = array(
			array( new Claim( new PropertyValueSnak( $p44, new EntityIdValue( $q23 ) ) ) ),
			array( $p44, $q23 ) );

		$argLists["Mixed Snaks"] = array(
			array(
				new Claim( new PropertyValueSnak( $p11, new EntityIdValue( $q23 ) ) ),
				new Claim( new PropertyNoValueSnak( $p44 ) ),
				new Claim( new PropertySomeValueSnak( $p44 ) ),
				new Claim( new PropertyValueSnak( $p44, new StringValue( 'onoez' ) ) ),
				new Claim( new PropertyValueSnak( $p44, new EntityIdValue( $q24 ) ) ),
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
		$entityContent = $this->newEntityContentForClaims( $claims );
		$entityView = $this->newEntityView( $entityContent );

		$out = $entityView->getParserOutput( $entityContent, null, false );
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
			array( new Claim( new PropertyNoValueSnak( $p42 ) ) ),
			array());

		$argLists["PropertySomeValueSnak"] = array(
			array( new Claim( new PropertySomeValueSnak( $p42 ) ) ),
			array() );

		$argLists["PropertyValueSnak with string value"] = array(
			array( new Claim( new PropertyValueSnak( $p23, new StringValue( 'http://not/a/url' )  ) ) ),
			array() );

		$argLists["PropertyValueSnak with URL"] = array(
			array( new Claim( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ) ) ),
			array( 'http://acme.com/test' ) );

		return $argLists;
	}

	/**
	 * @dataProvider providerNewForEntityContent
	 */
	public function testNewForEntityContent( EntityContent $entityContent ) {
		$valueFormatters = new ValueFormatterFactory( array() );
		$dataTypeLookup = new InMemoryDataTypeLookup( array() );
		$entityLoader = new MockRepository();

		// test whether we get the right EntityView from an EntityContent
		$view = EntityView::newForEntityContent( $entityContent, $valueFormatters, $dataTypeLookup, $entityLoader );
		$this->assertInstanceOf(
			EntityView::$typeMap[ $entityContent->getEntity()->getType() ],
			$view
		);
	}

	public static function providerNewForEntityContent() {
		return array(
			array( ItemContent::newEmpty() ),
			array( PropertyContent::newEmpty() )
		);
	}

	/**
	 * @dataProvider provideRegisterJsConfigVars
	 */
	public function testRegisterJsConfigVars( EntityContent $entityContent, EntityLookup $entityLoader,
		$context, LanguageFallbackChain $languageFallbackChain, $langCode, $editableView, $expected
	) {
		$entityView = $this->newEntityView( $entityContent, $entityLoader, $context, $languageFallbackChain );
		$out = new \OutputPage( new \RequestContext() );
		$entityView->registerJsConfigVars( $out, $entityContent, $langCode, $editableView );
		// Remove HTML stuff to avoid mismatching
		unset( $out->mJsConfigVars['wbCopyright'] );
		$this->assertEquals( $expected, $out->mJsConfigVars );
	}

	public function provideRegisterJsConfigVars() {
		$entityContentFactory = EntityContentFactory::singleton();
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		$argLists = array();

		$entity = Item::newEmpty();
		$entity->setLabel( 'de', 'foo' );
		$entity->setId( 49 );
		$content = $entityContentFactory->newFromEntity( $entity );
		$q98 = new ItemId( 'Q98' );
		$entityQ98 = Item::newEmpty();
		$entityQ98->setLabel( 'de', 'bar' );
		$entityQ98->setId( $q98 );
		$entityLoader = new MockRepository();
		$entityLoader->putEntity( $entityQ98 );
		$p11 = new PropertyId( 'p11' );
		$entity->addClaim( new Claim( new PropertyValueSnak( $p11, new EntityIdValue( $q98 ) ) ) );
		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguageCode(
			'de-formal', LanguageFallbackChainFactory::FALLBACK_ALL
		); // with fallback to German
		$argLists[] = array( $content, $entityLoader, null, $languageFallbackChain, 'fr', true, array(
			'wbUserIsBlocked' => false,
			'wbUserCanEdit' => true,
			'wbIsEditView' => true,
			'wbEntityType' => 'item',
			'wbDataLangName' => 'français',
			'wbEntityId' => 'Q49',
			'wbEntity' => '{"id":"Q49","type":"item","labels":{"de":{"language":"de","value":"foo"},"fr":{"language":"de","value":"foo"}},"claims":{"P11":[{"id":null,"mainsnak":{"snaktype":"value","property":"P11","datavalue":{"value":{"entity-type":"item","numeric-id":98},"type":"wikibase-entityid"}},"type":"claim"}]}}',
			'wbUsedEntities' => '{"Q98":{"content":{"id":"Q98","type":"item","labels":{"fr":{"language":"de","value":"bar"}}},"title":"Item:Q98","revision":""}}',
		) );

		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguageCode(
			'de-formal', LanguageFallbackChainFactory::FALLBACK_SELF
		); // with no fallback
		$argLists[] = array( $content, $entityLoader, null, $languageFallbackChain, 'nl', true, array(
			'wbUserIsBlocked' => false,
			'wbUserCanEdit' => true,
			'wbIsEditView' => true,
			'wbEntityType' => 'item',
			'wbDataLangName' => 'Nederlands',
			'wbEntityId' => 'Q49',
			'wbEntity' => '{"id":"Q49","type":"item","labels":{"de":{"language":"de","value":"foo"}},"claims":{"P11":[{"id":null,"mainsnak":{"snaktype":"value","property":"P11","datavalue":{"value":{"entity-type":"item","numeric-id":98},"type":"wikibase-entityid"}},"type":"claim"}]}}',
			'wbUsedEntities' => '{"Q98":{"content":{"id":"Q98","type":"item"},"title":"Item:Q98","revision":""}}',
		) );

		// TODO: add more tests for other JS vars

		return $argLists;
	}
}
