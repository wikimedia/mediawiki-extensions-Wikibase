<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Context\IContextSource;
use MediaWiki\Pager\HistoryPager;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
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

	/** @var PrefetchingTermLookup */
	private $prefetchingLookup;

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	/** @var TermLanguageFallbackChain */
	private $languageFallback;

	/** @var string[] */
	private $languageCodes;

	/** @var HistoryPager|null */
	private $pager;

	/** @var Item */
	private $entity;

	/** @var IResultWrapper|null */
	private $resultWrapper;

	protected function setUp(): void {
		$this->entity = new Item( new ItemId( 'Q1' ) );

		$this->pager = $this->createMock( HistoryPager::class );
		$this->resultWrapper = $this->createMock( IResultWrapper::class );

		$this->prefetchingLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );

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

	private function getHookHandler() {
		return new PageHistoryPagerHookHandler(
			$this->prefetchingLookup,
			$this->languageFallbackChainFactory
		);
	}

	private function setupResultWithSummaries( StatementList $statementList ) {
		$availableProperties = array_map( function( $snak ) {
			return $snak->getPropertyId();
		}, $statementList->getAllSnaks() );

		$i = 0;
		$summaries = array_map( function ( $prop ) use ( &$i ) {
			$i++;
			$object = (object)[
				'rev_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:{$prop->getSerialization()}]]: foo [[Q$i]] bar.",
			];
			return $object;
		}, $availableProperties );

		$valid = [];
		for ( $i = 0; $i < count( $summaries ); $i++ ) {
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

		$this->prefetchingLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				[
					new NumericPropertyId( "P666" ),
					new ItemId( 'Q1' ),
					new NumericPropertyId( "P12345" ),
					new ItemId( 'Q2' ),
					new NumericPropertyId( "P12345" ),
					new ItemId( 'Q3' ),
				],
				[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ],
				$this->languageCodes
			);

		$handler = $this->getHookHandler();

		$handler->onPageHistoryPager__doBatchLookups( $this->pager, $this->resultWrapper );
	}

	public function testDontPrefetchEmptySummary() {
		$this->setupResultWithSummaries( new StatementList() );

		$this->prefetchingLookup->expects( $this->never() )
			->method( 'prefetchTerms' );

		$handler = $this->getHookHandler();

		$handler->onPageHistoryPager__doBatchLookups( $this->pager, $this->resultWrapper );
	}
}
