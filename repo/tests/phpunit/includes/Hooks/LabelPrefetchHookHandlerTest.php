<?php

namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\RecentChanges\ChangesList;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Hooks\LabelPrefetchHookHandler;
use Wikibase\Repo\Hooks\SummaryParsingPrefetchHelper;

/**
 * @covers \Wikibase\Repo\Hooks\LabelPrefetchHookHandler
 *
 * @group Wikibase
 * @group Database
 *        ^--- who knows what ChangesList may do internally...
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class LabelPrefetchHookHandlerTest extends TestCase {

	/**
	 * @param Title[] $titles
	 *
	 * @return EntityId[]
	 */
	public function titlesToIds( array $titles ) {
		$entityIds = [];
		$idParser = new BasicEntityIdParser();

		foreach ( $titles as $title ) {
			try {
				// Pretend the article ID is the numeric entity ID.
				$entityId = $idParser->parse( $title->getBaseText() );
				$key = $entityId->getNumericId();

				$entityIds[$key] = $entityId;
			} catch ( EntityIdParsingException $ex ) {
				// skip
			}
		}

		return $entityIds;
	}

	/**
	 * @param callable $prefetchTerms
	 * @param string[] $termTypes
	 * @param string[] $languageCodes
	 * @param callable $prefetchSummaryTerms
	 * @return LabelPrefetchHookHandler
	 */
	protected function getLabelPrefetchHookHandlers(
		$prefetchTerms,
		array $termTypes,
		array $languageCodes,
		$prefetchSummaryTerms
	) {
		$termBuffer = $this->createMock( TermBuffer::class );
		$termBuffer->expects( $this->atLeastOnce() )
			->method( 'prefetchTerms' )
			->willReturnCallback( $prefetchTerms );

		$termBufferSummaries = $this->createMock( TermBuffer::class );
		$termBufferSummaries->expects( $this->atLeastOnce() )
			->method( 'prefetchTerms' )
			->willReturnCallback( $prefetchSummaryTerms );

		$idLookup = $this->createMock( EntityIdLookup::class );
		$idLookup->expects( $this->atLeastOnce() )
			->method( 'getEntityIds' )
			->willReturnCallback( [ $this, 'titlesToIds' ] );

		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->expects( $this->atLeastOnce() )
			->method( 'makeTitle' )
			->willReturnCallback( function ( int $ns, string $titleText ) {
				$title = $this->createMock( Title::class );
				$title->expects( $this->once() )
					->method( 'getBaseText' )
					->willReturn( $titleText );
				return $title;
			} );

		$fallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$fallbackChain->method( 'getFetchLanguageCodes' )
			->willReturn( $languageCodes );

		$fallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$fallbackChainFactory->method( 'newFromContext' )
			->willReturn( $fallbackChain );

		return new LabelPrefetchHookHandler(
			$termBuffer,
			$idLookup,
			$titleFactory,
			$termTypes,
			$fallbackChainFactory,
			new SummaryParsingPrefetchHelper( $termBufferSummaries )
		);
	}

	protected function getPrefetchTermsCallback( $expectedIds, $expectedTermTypes, $expectedLanguageCodes ) {
		$prefetchTerms = function (
			array $entityIds,
			array $termTypes,
			array $languageCodes
		) use (
			$expectedIds,
			$expectedTermTypes,
			$expectedLanguageCodes
		) {
			$expectedIdStrings = array_map( function( EntityId $id ) {
				return $id->getSerialization();
			}, $expectedIds );
			$entityIdStrings = array_map( function( EntityId $id ) {
				return $id->getSerialization();
			}, $entityIds );

			sort( $expectedIdStrings );
			sort( $entityIdStrings );

			$this->assertEquals( $expectedIdStrings, $entityIdStrings );
			$this->assertEquals( $expectedTermTypes, $termTypes );
			$this->assertEquals( $expectedLanguageCodes, $languageCodes );
		};
		return $prefetchTerms;
	}

	public function testDoChangesListInitRows() {
		$rows = [
			(object)[
				'rc_namespace' => NS_MAIN,
				'rc_title' => 'Q1',
				'rc_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:P100]]: asdf",
			],
			(object)[
				'rc_namespace' => NS_MAIN,
				'rc_title' => 'P2',
				'rc_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:P200]]: [[Q2013]]",
			],
			(object)[
				'rc_namespace' => NS_MAIN,
				'rc_title' => 'Q3',
				'rc_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:P300]]: asdf",
			],
		];

		$expectedTermTypes = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ];
		$expectedLanguageCodes = [ 'de', 'en', 'it' ];

		$expectedEditedEntityIds = [
			new ItemId( 'Q1' ),
			new NumericPropertyId( 'P2' ),
			new ItemId( 'Q3' ),
		];

		$expectedSummaryEntityIds = [
			new NumericPropertyId( 'P100' ),
			new NumericPropertyId( 'P200' ),
			new ItemId( 'Q2013' ),
			new NumericPropertyId( 'P300' ),
		];

		$linkBeginHookHandler = $this->getLabelPrefetchHookHandlers(
			$this->getPrefetchTermsCallback( $expectedEditedEntityIds, $expectedTermTypes, $expectedLanguageCodes ),
			$expectedTermTypes,
			$expectedLanguageCodes,
			$this->getPrefetchTermsCallback( $expectedSummaryEntityIds, $expectedTermTypes, $expectedLanguageCodes )
		);

		/** @var ChangesList $changesList */
		$changesList = $this->createMock( ChangesList::class );

		$linkBeginHookHandler->onChangesListInitRows(
			$changesList,
			$rows
		);
	}

}
