<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Hooks;

use CommentStoreComment;
use DifferenceEngine;
use IContextSource;
use MediaWiki\Revision\MutableRevisionRecord;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\FederatedProperties\SummaryParsingPrefetchHelper;
use Wikibase\Repo\Hooks\DifferenceEngineViewHeaderHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\DifferenceEngineViewHeaderHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DifferenceEngineViewHeaderHookHandlerTest extends TestCase {

	private $prefetchingLookup;
	private $entity;

	/**
	 * @var LinkTargetEntityIdLookup
	 */
	private $linkTargetEntityIdLookup;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	protected function setUp(): void {
		$this->prefetchingLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$this->linkTargetEntityIdLookup = $this->createMock( LinkTargetEntityIdLookup::class );

		$stubContentLanguages = $this->createStub( ContentLanguages::class );
		$stubContentLanguages->method( 'hasLanguage' )
			->willReturn( true );

		$this->languageFallback = new TermLanguageFallbackChain( [
			LanguageWithConversion::factory( 'sv' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		], $stubContentLanguages );

		$this->languageCodes = $this->languageFallback->getFetchLanguageCodes();

		$this->languageFallbackChainFactory->method( 'newFromContext' )
			->withAnyParameters()
			->willReturn( $this->languageFallback );
	}

	public function testPrefetchesFederatedProperties() {

		$itemId = new ItemId( "Q1" );
		$this->entity = new Item( $itemId );

		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( new NumericPropertyId( "P32456" ) ) ) );
		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( new NumericPropertyId( "P12345" ) ) ) );

		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->willReturn( $itemId );

		$rows = $this->getRevisionRecords();

		$this->prefetchingLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				[ new NumericPropertyId( "P32456" ), new NumericPropertyId( "P12345" ) ],
				[ TermTypes::TYPE_LABEL ],
				[ 'sv', 'de', 'en' ]
			);

		$diffEngine = $this->getMockedDiffEngine( $rows[0], $rows[1], 'Q1' );

		$hook = $this->getNewHookHandler();

		$hook->onDifferenceEngineViewHeader( $diffEngine );
	}

	public function testPrefetchesFederatedPropertiesEntityIdNotFoundByTitle() {

		$itemId = new ItemId( "Q1" );
		$this->entity = new Item( $itemId );

		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 32456 ) ) );
		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 12345 ) ) );

		$rows = $this->getRevisionRecords();
		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->willReturn( null );

		$this->prefetchingLookup->expects( $this->never() )
			->method( 'prefetchTerms' );

		$diffEngine = $this->getMockedDiffEngine( $rows[0], $rows[1], 'Q1' );

		$hook = $this->getNewHookHandler();

		$hook->onDifferenceEngineViewHeader( $diffEngine );
	}

	public function testPrefetchesFederatedPropertiesOldRevisionNotSet() {

		$itemId = new ItemId( "Q1" );
		$this->entity = new Item( $itemId );

		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( new NumericPropertyId( "P32456" ) ) ) );
		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( new NumericPropertyId( "P12345" ) ) ) );

		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->willReturn( $itemId );

		$rows = $this->getRevisionRecords();

		$this->prefetchingLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				[ new NumericPropertyId( "P12345" ) ],
				[ TermTypes::TYPE_LABEL ],
				[ 'sv', 'de', 'en' ]
			);

		$diffEngine = $this->getMockedDiffEngine( null, $rows[1], 'Q1' );

		$hook = $this->getNewHookHandler();

		$hook->onDifferenceEngineViewHeader( $diffEngine );
	}

	private function getNewHookHandler(
		bool $federatedProperties = true
	) {
		return new DifferenceEngineViewHeaderHookHandler(
			$federatedProperties,
			$this->languageFallbackChainFactory,
			$this->linkTargetEntityIdLookup,
			new SummaryParsingPrefetchHelper( $this->prefetchingLookup )
		);
	}

	private function getRevisionRecords() {
		$availableProperties = array_map( function( $snak ) {
			return $snak->getPropertyId();
		}, $this->entity->getStatements()->getAllSnaks() );

		$rows = array_map( function ( $prop ) {
			$object = new MutableRevisionRecord( Title::newFromTextThrow( $prop->getSerialization() ) );
			$object->setComment( new CommentStoreComment(
				null,
				"[[Property:{$prop->getSerialization()}]]"
			) );
			return $object;
		}, $availableProperties );

		return $rows;
	}

	private function getMockedDiffEngine( $getOldRevision, $getNewRevision, $titleText ) {
		$diffEngine = $this->createMock( DifferenceEngine::class );
		$diffEngine->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( Title::newFromTextThrow( $titleText ) );

		$diffEngine->method( 'getOldRevision' )
			->willReturn( $getOldRevision );

		$diffEngine->method( 'getNewRevision' )
			->willReturn( $getNewRevision );

		$diffEngine->method( 'getContext' )
			->willReturn( $this->createMock( IContextSource::class ) );

		$diffEngine->method( 'loadRevisionData' )
			->willReturn( true );

		return $diffEngine;
	}
}
