<?php

namespace Wikibase\Test;

use Language;
use Title;
use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Entity;
use Wikibase\EntityInfoBuilder;
use Wikibase\EntityRevision;
use Wikibase\EntityRevisionLookup;
use Wikibase\EntityTitleLookup;
use Wikibase\EntityView;
use Wikibase\Item;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Repo\WikibaseRepo;
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
abstract class EntityViewTest extends \MediaWikiTestCase {

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
	protected function newEntityView( $entityType, EntityInfoBuilder $entityInfoBuilder = null,
		EntityTitleLookup $entityTitleLookup = null, \IContextSource $context = null,
		LanguageFallbackChain $languageFallbackChain = null
	) {
		if ( !is_string( $entityType ) ) {
			throw new \InvalidArgumentException( '$entityType must be a string!' );
		}

		if ( $context === null ) {
			$context = new \RequestContext();
			$context->setLanguage( 'en' );
		}

		if ( $languageFallbackChain === null ) {
			$factory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
			$languageFallbackChain = $factory->newFromLanguage( $context->getLanguage() );
		}

		$mockRepo = new MockRepository();

		$mockRepo->putEntity( $this->makeItem( 'Q33' ) );
		$mockRepo->putEntity( $this->makeItem( 'Q22' ) );
		$mockRepo->putEntity( $this->makeItem( 'Q23' ) );
		$mockRepo->putEntity( $this->makeItem( 'Q24' ) );

		$mockRepo->putEntity( $this->makeProperty( 'P11', 'wikibase-item' ) );
		$mockRepo->putEntity( $this->makeProperty( 'P23', 'string' ) );
		$mockRepo->putEntity( $this->makeProperty( 'P42', 'url' ) );
		$mockRepo->putEntity( $this->makeProperty( 'P44', 'wikibase-item' ) );


		if ( !$entityInfoBuilder ) {
			$entityInfoBuilder = $mockRepo;
		}

		if ( !$entityTitleLookup ) {
			$entityTitleLookup = $this->getEntityTitleLookupMock();
		}

		$idParser = new BasicEntityIdParser();

		$formatterOptions = new FormatterOptions();
		$snakFormatter = WikibaseRepo::getDefaultInstance()->getSnakFormatterFactory()
			->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );

		$class = $this->getEntityViewClass();
		$entityView = new $class(
			$context,
			$snakFormatter,
			$mockRepo,
			$entityInfoBuilder,
			$entityTitleLookup,
			$idParser,
			$languageFallbackChain );

		return $entityView;
	}

	protected abstract function getEntityViewClass();

	/**
	 * @param Claim[] $claims
	 *
	 * @return EntityRevision
	 */
	protected function newEntityRevisionForClaims( $claims ) {
		static $revId = 1234;
		$revId++;

		$entity = $this->makeItem( "Q$revId", $claims );

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $entity, $revId, $timestamp );

		return $revision;
	}

	/**
	 * @dataProvider getHtmlForEditSectionProvider
	 */
	public function testGetHtmlForEditSection( $expected, $url, $tag, $action, $enabled, $langCode ) {
		$entityView = $this->newEntityView( Item::ENTITY_TYPE );

		$context = $entityView->getContext();
		$context->setLanguage( $langCode );
		$entityView->setContext( $context );

		$editSectionHtml = $entityView->getHtmlForEditSection( $url, $tag, $action, $enabled );
		$matcher = array(
			'tag' => $tag,
			'class' => 'wb-editsection'
		);

		$this->assertTag( $matcher, $editSectionHtml, "$action action" );
		$this->assertRegExp( "/$expected/", $editSectionHtml, "$action button label" );
	}

	public function getHtmlForEditSectionProvider() {
		return array(
			array(
				wfMessage( 'wikibase-edit' )->inLanguage( 'es' )->text(),
				'',
				'div',
				'edit',
				true,
				'es'
			),
			array(
				wfMessage( 'wikibase-add' )->inLanguage( 'de' )->text(),
				'',
				'span',
				'add',
				true,
				'de'
			)
		);
	}

	/**
	 * @return array
	 */
	public function getHtmlForClaimsProvider() {
		$item = $this->makeItem( 'Q33', array(
			$this->makeClaim( new PropertyNoValueSnak(
				new PropertyId( 'P11' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P11' ),
				new EntityIdValue( new ItemId( 'Q22' ) )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P23' ),
				new StringValue( 'test' )
			) ),
		) );

		return array(
			array( $item )
		);
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


	protected function makeItem( $id, $claims = array() ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $id ) );
		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		foreach ( $claims as $claim ) {
			$item->addClaim( $claim );
		}

		return $item;
	}

	protected function makeProperty( $id, $dataTypeId ) {
		$property = Property::newEmpty();
		$property->setId( new PropertyId( $id ) );
		$property->setDataTypeId( $dataTypeId );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		return $property;
	}

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

		$p11 = new PropertyId( 'P11' );
		$p23 = new PropertyId( 'P42' );
		$p44 = new PropertyId( 'P44' );

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
	 * @dataProvider provideRegisterJsConfigVars
	 */
	public function testRegisterJsConfigVars( EntityRevision $entityRevision,
		\IContextSource $context, LanguageFallbackChain $languageFallbackChain, $editableView, $expected
	) {
		$this->setMwGlobals( 'wgLang', $context->getLanguage() );

		$entityView = $this->newEntityView(
			$entityRevision->getEntity()->getType(),
			null,
			null,
			$context,
			$languageFallbackChain
		);

		$out = new \OutputPage( $context );
		$entityView->registerJsConfigVars( $out, $entityRevision, $editableView );
		$actual = array_intersect_key( $out->mJsConfigVars, $expected );

		ksort( $expected );
		ksort( $actual );

		$this->assertEquals( array_keys( $expected ), array_keys( $actual ) );

		foreach ( $expected as $field => $expectedJson ) {
			$actualJson = $actual[$field];

			$expectedData = json_decode( $expectedJson, true );
			$actualData = json_decode( $actualJson, true );

			$this->assertEquals( $expectedData, $actualData, $field );
		}
	}

	public function provideRegisterJsConfigVars() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		$argLists = array();

		$entity = Item::newEmpty();
		$entity->setLabel( 'de', 'foo' );
		$entity->setId( new ItemId( 'Q22' ) );

		$q33 = new ItemId( 'Q33' );
		$q44 = new ItemId( 'Q44' ); // unknown item
		$p11 = new PropertyId( 'p11' );
		$p77 = new PropertyId( 'p77' ); // unknown property

		$entity->addClaim( $this->makeClaim( new PropertyValueSnak( $p11, new EntityIdValue( $q33 ) ) ) );
		$entity->addClaim( $this->makeClaim( new PropertyValueSnak( $p11, new EntityIdValue( $q44 ) ) ) );
		$entity->addClaim( $this->makeClaim( new PropertyValueSnak( $p77, new EntityIdValue( $q33 ) ) ) );

		$revision = new EntityRevision( $entity, 1234567, '20130505333333' );

		//FIXME: re-enable once language fallback for referenced entity labels works again. See EntityView::getBasicEntityInfo
		/*
		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguageCode(
			'de-formal', LanguageFallbackChainFactory::FALLBACK_ALL
		); // with fallback to German

		$argLists[] = array( $revision, $entityLoader, null, $languageFallbackChain, 'fr', true, array(
			'wbEntityType' => 'item',
			'wbDataLangName' => 'français',
			'wbEntityId' => 'Q27449',
			'wbEntity' => '{"id":"Q27449","type":"item","labels":{"de":{"language":"de","value":"foo"},"fr":{"language":"de","value":"foo"}},"claims":{"P11":[{"id":"EntityViewTest$1","mainsnak":{"snaktype":"value","property":"P11","datavalue":{"value":{"entity-type":"item","numeric-id":27498},"type":"wikibase-entityid"}},"type":"claim"}]}}',
			'wbUsedEntities' => '{"P11":{"content":{"id":"P11","type":"property"},"title":"property:P11"},"Q27498":{"content":{"id":"Q27498","type":"item","labels":{"fr":{"language":"de","value":"bar"}}},"title":"' . $titleText . '"}}',
		) );
		*/

		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguageCode(
			'de-formal', LanguageFallbackChainFactory::FALLBACK_SELF
		); // with no fallback

		$context = new \RequestContext();
		$context->setLanguage( 'nl' );

		$argLists[] = array( $revision, $context, $languageFallbackChain, true, array(
			'wbEntityType' => 'item',
			'wbDataLangName' => 'Nederlands',
			'wbEntityId' => 'Q22',
			'wbEntity' => json_encode( array(
				'id' => 'Q22',
				'type' => 'item',
				'labels' => array(
					'de' => array(
						'language' => 'de',
						'value' => 'foo',
					),
				),
				'claims' =>
				array(
					'P11' => array(
						array(
							'id' => 'EntityViewTest$1',
							'mainsnak' => array(
								'snaktype' => 'value',
								'property' => 'P11',
								'datavalue' => array(
									'value' => array(
										'entity-type' => 'item',
										'numeric-id' => 33,
									),
									'type' => 'wikibase-entityid',
								),
							),
							'type' => 'claim',
						),
						array(
							'id' => 'EntityViewTest$2',
							'mainsnak' => array(
								'snaktype' => 'value',
								'property' => 'P11',
								'datavalue' => array(
									'value' => array(
										'entity-type' => 'item',
										'numeric-id' => 44,
									),
									'type' => 'wikibase-entityid',
								),
							),
							'type' => 'claim',
						),
					),
					'P77' => array(
						array(
							'id' => 'EntityViewTest$3',
							'mainsnak' => array(
								'snaktype' => 'value',
								'property' => 'P77',
								'datavalue' => array(
									'value' => array(
										'entity-type' => 'item',
										'numeric-id' => 33,
									),
									'type' => 'wikibase-entityid',
								),
							),
							'type' => 'claim',
						),
					),
				),
			) ),
			'wbUsedEntities' => json_encode( array(
				'P11' => array(
					'content' => array(
						'id' => 'P11',
						'type' => 'property',
						'labels' => array(),
						'descriptions' => array(),
						'datatype' => 'wikibase-item',
					),
					'title' => 'property:P11',
				),
				'Q33' => array(
					'content' => array(
						'id' => 'Q33',
						'type' => 'item',
						'labels' => array(),
						'descriptions' => array(),
					),
					'title' => 'item:Q33',
				),
			) )
		) );

		// TODO: add more tests for other JS vars

		return $argLists;
	}
}
