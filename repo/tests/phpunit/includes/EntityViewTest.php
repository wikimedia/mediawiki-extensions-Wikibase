<?php

namespace Wikibase\Test;

use IContextSource;
use InvalidArgumentException;
use Language;
use RequestContext;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\EntityRevision;
use Wikibase\EntityView;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\ParserOutputJsConfigBuilder;
use Wikibase\ReferencedEntitiesFinder;
use Wikibase\Repo\WikibaseRepo;
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
abstract class EntityViewTest extends \MediaWikiLangTestCase {

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
		$lookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
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
	 * @param EntityInfoBuilderFactory $entityInfoBuilderFactory
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param IContextSource $context
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	protected function newEntityView(
		$entityType,
		EntityInfoBuilderFactory $entityInfoBuilderFactory = null,
		EntityTitleLookup $entityTitleLookup = null,
		IContextSource $context = null,
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

		if ( !$entityInfoBuilderFactory ) {
			$entityInfoBuilderFactory = $mockRepo;
		}

		if ( !$entityTitleLookup ) {
			$entityTitleLookup = $this->getEntityTitleLookupMock();
		}

		$idParser = $this->newEntityIdParser();

		$formatterOptions = new FormatterOptions();
		$snakFormatter = WikibaseRepo::getDefaultInstance()->getSnakFormatterFactory()
			->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );

		$configBuilder = new ParserOutputJsConfigBuilder(
			$entityInfoBuilderFactory,
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
			$entityInfoBuilderFactory,
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
			$mockRepo->putEntity( $this->makeProperty( 'P43', 'commonsMedia' ) );
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
	 * @param Statement[] $statements
	 *
	 * @return Entity
	 */
	protected abstract function makeEntity( EntityId $id, array $statements = array() );

	/**
	 * Generates a prefixed entity ID based on a numeric ID.
	 *
	 * @param int|string $numericId
	 *
	 * @return EntityId
	 */
	protected abstract function makeEntityId( $numericId );

	/**
	 * @param Statement[] $statements
	 *
	 * @return EntityRevision
	 */
	protected function newEntityRevisionForStatements( array $statements ) {
		static $revId = 1234;
		$revId++;

		$entity = $this->makeEntity( $this->makeEntityId( $revId ), $statements );

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $entity, $revId, $timestamp );

		return $revision;
	}

	protected $guidCounter = 0;

	protected function makeItem( $id, array $statements = array() ) {
		if ( is_string( $id ) ) {
			$id = new ItemId( $id );
		}

		$item = Item::newEmpty();
		$item->setId( $id );
		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		foreach ( $statements as $statement ) {
			$item->addClaim( $statement );
		}

		return $item;
	}

	protected function makeProperty( $id, $dataTypeId, array $statements = array() ) {
		if ( is_string( $id ) ) {
			$id = new PropertyId( $id );
		}

		$property = Property::newFromType( $dataTypeId );
		$property->setId( $id );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		foreach ( $statements as $statement ) {
			$property->addClaim( $statement );
		}

		return $property;
	}

	protected function makeStatement( Snak $mainSnak, $guid = null ) {
		if ( $guid === null ) {
			$this->guidCounter++;
			$guid = 'EntityViewTest$' . $this->guidCounter;
		}

		$statements = new Statement( new Claim( $mainSnak ) );
		$statements->setGuid( $guid );

		return $statements;
	}

	/**
	 * @return Entity
	 */
	protected function getTestEntity() {
		$entity = $this->makeEntity( $this->makeEntityId( 22 ) );
		$entity->setLabel( 'de', 'fuh' );
		$entity->setLabel( 'en', 'foo' );

		$entity->setDescription( 'de', 'fuh barr' );
		$entity->setDescription( 'en', 'foo bar' );

		$q33 = new ItemId( 'Q33' );
		$q44 = new ItemId( 'Q44' ); // unknown item
		$p11 = new PropertyId( 'p11' );
		$p77 = new PropertyId( 'p77' ); // unknown property

		$entity->addClaim( $this->makeStatement( new PropertyValueSnak( $p11, new EntityIdValue( $q33 ) ) ) );
		$entity->addClaim( $this->makeStatement( new PropertyValueSnak( $p11, new EntityIdValue( $q44 ) ) ) );
		$entity->addClaim( $this->makeStatement( new PropertyValueSnak( $p77, new EntityIdValue( $q33 ) ) ) );

		return $entity;
	}

}
