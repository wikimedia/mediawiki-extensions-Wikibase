<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use IContextSource;
use InvalidArgumentException;
use Language;
use RequestContext;
use Title;
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
use Wikibase\EntityTitleLookup;
use Wikibase\EntityView;
use Wikibase\Item;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\ParserOutputJsConfigBuilder;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\ReferencedEntitiesFinder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Snak;
use Wikibase\Utils;

/**
 * @covers Wikibase\EntityView
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
abstract class EntityViewTest extends \MediaWikiTestCase {

	protected static $mockRepo;

	protected function newEntityIdParser() {
		// The data provides use P123 and Q123 IDs, so the parser needs to understand these.
		return new BasicEntityIdParser();
	}

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
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param IContextSource $context
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	protected function newEntityView( $entityType, EntityInfoBuilder $entityInfoBuilder = null,
		EntityTitleLookup $entityTitleLookup = null, IContextSource $context = null,
		LanguageFallbackChain $languageFallbackChain = null
	) {
		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string!' );
		}

		$langCode = 'en';

		if ( $context === null ) {
			$context = new RequestContext();
			$context->setLanguage( $langCode );
		}

		if ( $languageFallbackChain === null ) {
			$factory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
			$languageFallbackChain = $factory->newFromLanguage( Language::factory( $langCode ) );
		}

		$mockRepo = $this->getMockRepo();

		if ( !$entityInfoBuilder ) {
			$entityInfoBuilder = $mockRepo;
		}

		if ( !$entityTitleLookup ) {
			$entityTitleLookup = $this->getEntityTitleLookupMock();
		}

		$idParser = $this->newEntityIdParser();

		$formatterOptions = new FormatterOptions();
		$snakFormatter = WikibaseRepo::getDefaultInstance()->getSnakFormatterFactory()
			->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );

		$configBuilder = new ParserOutputJsConfigBuilder(
			$entityInfoBuilder,
			$idParser,
			$entityTitleLookup,
			new ReferencedEntitiesFinder(),
			$langCode
		);

		// @fixme inject language codes
		$options = $this->getSerializationOptions(
			$langCode,
			Utils::getLanguageCodes(),
			$languageFallbackChain
		);

		$class = $this->getEntityViewClass();
		$entityView = new $class(
			$context,
			$snakFormatter,
			$mockRepo,
			$entityInfoBuilder,
			$entityTitleLookup,
			$options,
			$configBuilder
		);

		return $entityView;
	}

    private function getSerializationOptions( $langCode, $langCodes,
		LanguageFallbackChain $fallbackChain
	 ) {
        $langCodes = $langCodes + array( $langCode => $fallbackChain );

        $options = new SerializationOptions();
        $options->setLanguages( $langCodes );

        return $options;
    }

	protected function getMockRepo() {
		if ( !isset( self::$mockRepo ) ) {
			$mockRepo = new MockRepository();

			$mockRepo->putEntity( $this->makeItem( 'Q33' ) );
			$mockRepo->putEntity( $this->makeItem( 'Q22' ) );
			$mockRepo->putEntity( $this->makeItem( 'Q23' ) );
			$mockRepo->putEntity( $this->makeItem( 'Q24' ) );

			$mockRepo->putEntity( $this->makeProperty( 'P11', 'wikibase-item' ) );
			$mockRepo->putEntity( $this->makeProperty( 'P23', 'string' ) );
			$mockRepo->putEntity( $this->makeProperty( 'P42', 'url' ) );
			$mockRepo->putEntity( $this->makeProperty( 'P44', 'wikibase-item' ) );

			self::$mockRepo = $mockRepo;
		}

		return self::$mockRepo;
	}

	/**
	 * @return string
	 */
	protected abstract function getEntityViewClass();

	/**
	 * @param EntityId $id
	 * @param Claim[] $claims
	 *
	 * @return Entity
	 */
	protected abstract function makeEntity( EntityId $id, $claims = array() );

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return EntityId
	 */
	protected abstract function makeEntityId( $n );

	/**
	 * @param Claim[] $claims
	 *
	 * @return EntityRevision
	 */
	protected function newEntityRevisionForClaims( $claims ) {
		static $revId = 1234;
		$revId++;

		$entity = $this->makeEntity( $this->makeEntityId( $revId ), $claims );

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $entity, $revId, $timestamp );

		return $revision;
	}

	/**
	 * @return array
	 */
	public function getHtmlForClaimsProvider() {
		$item = $this->makeEntity( $this->makeEntityId( '33' ), array(
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
	 * @dataProvider parserOutputExtensionDataProvider
	 */
	public function testParserOutputExtensionData( EntityRevision $revision ) {
		$entityView = $this->newEntityView( $revision->getEntity()->getType() );

		$parserOutput = $entityView->getParserOutput( $revision, null, false );
		$configVars = $parserOutput->getJsConfigVars();

		// @todo moar tests
		$this->assertInternalType( 'array', $configVars );
	}

	public function parserOutputExtensionDataProvider() {
		$entity = Item::newEmpty();
		$itemId = ItemId::newFromNumber( 301 );
		$entity->setId( $itemId );
		$entity->setLabel( 'en', 'Cat' );

		$snak = new PropertyValueSnak(
			new PropertyId( 'p1' ),
			new StringValue( 'cats!' )
		);

		$claimGuidGenerator = new ClaimGuidGenerator();

		$claim = new Claim( $snak );
		$claim->setGuid( $claimGuidGenerator->newGuid( $itemId ) );

		$entity->addClaim( $claim );

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $entity, 13044, $timestamp );

		return array(
			array( $revision )
		);
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

		$out = $entityView->getParserOutput( $entityRevision, true, false );
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

	public function testParserOutputLinksForNoClaims() {
		$entityRevision = $this->newEntityRevisionForClaims( array() );
		$entityView = $this->newEntityView( $entityRevision->getEntity()->getType() );

		$out = $entityView->getParserOutput( $entityRevision, true, false );
		$this->assertEquals( array(), $out->getLinks() );
	}

	protected $guidCounter = 0;

	protected function makeItem( $id, $claims = array() ) {
		if ( is_string( $id ) ) {
			$id = new ItemId( $id );
		}

		$item = Item::newEmpty();
		$item->setId( $id );
		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		foreach ( $claims as $claim ) {
			$item->addClaim( $claim );
		}

		return $item;
	}

	protected function makeProperty( $id, $dataTypeId, $claims = array() ) {
		if ( is_string( $id ) ) {
			$id = new PropertyId( $id );
		}

		$property = Property::newFromType( $dataTypeId );
		$property->setId( $id );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		foreach ( $claims as $claim ) {
			$property->addClaim( $claim );
		}

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

		$out = $entityView->getParserOutput( $entityRevision, true, false );
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
	 * @return Entity
	 */
	protected function getTestEntity() {
		$entity = $this->makeEntity( $this->makeEntityId( '22' ) );
		$entity->setLabel( 'de', 'fuh' );
		$entity->setLabel( 'en', 'foo' );

		$entity->setDescription( 'de', 'fuh barr' );
		$entity->setDescription( 'en', 'foo bar' );

		$q33 = new ItemId( 'Q33' );
		$q44 = new ItemId( 'Q44' ); // unknown item
		$p11 = new PropertyId( 'p11' );
		$p77 = new PropertyId( 'p77' ); // unknown property

		$entity->addClaim( $this->makeClaim( new PropertyValueSnak( $p11, new EntityIdValue( $q33 ) ) ) );
		$entity->addClaim( $this->makeClaim( new PropertyValueSnak( $p11, new EntityIdValue( $q44 ) ) ) );
		$entity->addClaim( $this->makeClaim( new PropertyValueSnak( $p77, new EntityIdValue( $q33 ) ) ) );

		return $entity;
	}

}
