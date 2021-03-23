<?php
declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\FederatedProperties\SummaryParsingPrefetchHelper;
use Wikibase\Repo\Hooks\LabelPrefetchHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\LabelPrefetchHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
abstract class LabelPrefetchHookHandlerTestBase extends TestCase {

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
				$entityId = $idParser->parse( $title->getText() );
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
	 * @param PrefetchingTermLookup|null $prefetchingTermLookup
	 * @param EntityLookup|null $entityLookup
	 * @param bool $federatedPropertiesEnabled
	 * @return LabelPrefetchHookHandler
	 */
	protected function getLabelPrefetchHookHandlers(
		$prefetchTerms,
		array $termTypes,
		array $languageCodes,
		PrefetchingTermLookup $prefetchingTermLookup = null,
		bool $federatedPropertiesEnabled = false
	) {
		if ( $prefetchingTermLookup == null ) {
			$prefetchingTermLookup = $this->createMock( PrefetchingTermLookup::class );
		}

		$termBuffer = $this->createMock( TermBuffer::class );
		$termBuffer->expects( $this->atLeastOnce() )
			->method( 'prefetchTerms' )
			->willReturnCallback( $prefetchTerms );

		$idLookup = $this->createMock( EntityIdLookup::class );
		$idLookup->expects( $this->atLeastOnce() )
			->method( 'getEntityIds' )
			->willReturnCallback( [ $this, 'titlesToIds' ] );

		// TODO add a mock instead
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

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
			$federatedPropertiesEnabled,
			new SummaryParsingPrefetchHelper( $prefetchingTermLookup )
		);
	}

	protected function getPrefetchTermsCallback( $expectedIds, $expectedTermTypes, $expectedLanguageCodes ) {
		$prefetchTerms = function (
			array $entityIds,
			array $termTypes = null,
			array $languageCodes = null
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
}
