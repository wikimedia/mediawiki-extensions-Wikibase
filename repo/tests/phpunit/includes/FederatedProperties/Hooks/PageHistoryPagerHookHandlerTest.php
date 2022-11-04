<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Hooks;

use HistoryPager;
use IContextSource;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Hooks\PageHistoryPagerHookHandler;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * @covers \Wikibase\Repo\Hooks\PageHistoryPagerHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class PageHistoryPagerHookHandlerTest extends TestCase {

	/** @var EntityLookup */
	private $entityLookup;

	/** @var PrefetchingTermLookup */
	private $prefetchingLookup;

	/** @var LinkTargetEntityIdLookup */
	private $linkTargetEntityIdLookup;

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	/** @var TermLanguageFallbackChain */
	private $languageFallback;

	/** @var string[] */
	private $languageCodes;

	/** @var HistoryPager|null */
	private $pager;

	/** @var ItemId */
	private $entityId;

	/** @var Item */
	private $entity;

	/** @var IResultWrapper|null */
	private $resultWrapper;

	/** @var Title */
	private $title;

	protected function setUp(): void {
		$this->entityId = new ItemId( 'Q1' );
		$this->entity = new Item( $this->entityId );
		$this->title = Title::newFromTextThrow( $this->entityId->getSerialization() );

		$this->pager = $this->createMock( HistoryPager::class );
		$this->resultWrapper = $this->createMock( IResultWrapper::class );

		$this->prefetchingLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$this->entityLookup = $this->createMock( EntityLookup::class );
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

		$this->pager->method( 'getContext' )
			->willReturn( $this->createMock( IContextSource::class ) );

		$this->languageFallbackChainFactory->method( 'newFromContext' )
			->withAnyParameters()
			->willReturn( $this->languageFallback );
	}

	private function getHookHandler( bool $federatedPropertiesEnabled ) {
		return new PageHistoryPagerHookHandler(
			$federatedPropertiesEnabled,
			$this->prefetchingLookup,
			$this->linkTargetEntityIdLookup,
			$this->languageFallbackChainFactory
		);
	}

	public function setupResultWithSummaries( StatementList $statementList ) {
		$availableProperties = array_map( function( $snak ) {
			return $snak->getPropertyId();
		}, $statementList->getAllSnaks() );

		$summaries = array_map( function ( $prop ) {
			$object = (object)[
				'rev_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:{$prop->getSerialization()}]]: asdfasdasfas",
			];
			return $object;
		}, $availableProperties );

		$valid = [];
		for ( $i = 1; $i < count( $summaries ); $i++ ) {
			$valid[] = true;
		}
		$valid[] = false;

		$this->resultWrapper->method( 'valid' )
			->willReturnOnConsecutiveCalls( ...$valid );

		$this->resultWrapper->method( 'current' )
			->willReturnOnConsecutiveCalls( ...$summaries );
	}

	public function testDoBatchLookups() {
		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 0x29a ) ) );
		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 12345 ) ) );
		$this->entity->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 12345 ) ) );

		$this->setupResultWithSummaries( $this->entity->getStatements() );

		$this->pager->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->with( $this->title )
			->willReturn( $this->entityId );

		$this->prefetchingLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				[ new NumericPropertyId( "P666" ), new NumericPropertyId( "P12345" ) ],
				[ TermTypes::TYPE_LABEL ],
				$this->languageCodes
			);

		$handler = $this->getHookHandler( true );

		$handler->onPageHistoryPager__doBatchLookups( $this->pager, $this->resultWrapper );
	}

	public function testDontPrefetchEmptySummary() {
		$this->setupResultWithSummaries( new StatementList() );

		$this->pager->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->with( $this->title )
			->willReturn( $this->entityId );

		$this->prefetchingLookup->expects( $this->never() )
			->method( 'prefetchTerms' );

		$handler = $this->getHookHandler( true );

		$handler->onPageHistoryPager__doBatchLookups( $this->pager, $this->resultWrapper );
	}

	public function testShouldSkipIfNotEntityId() {
		$this->pager->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->with( $this->title )
			->willReturn( null );

		$this->prefetchingLookup->expects( $this->never() )
			->method( 'prefetchTerms' );

		$handler = $this->getHookHandler( true );

		$handler->onPageHistoryPager__doBatchLookups( $this->pager, $this->resultWrapper );
	}
}
