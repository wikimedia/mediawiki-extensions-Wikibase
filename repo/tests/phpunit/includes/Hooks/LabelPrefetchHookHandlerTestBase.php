<?php
declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use PHPUnit\Framework\TestCase;
use Title;
use TitleFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Hooks\LabelPrefetchHookHandlers;

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
	 *
	 * @return LabelPrefetchHookHandlers
	 */
	protected function getLabelPrefetchHookHandlers(
		$prefetchTerms,
		array $termTypes,
		array $languageCodes
	) {
		$termBuffer = $this->createMock( TermBuffer::class );
		$termBuffer->expects( $this->atLeastOnce() )
			->method( 'prefetchTerms' )
			->will( $this->returnCallback( $prefetchTerms ) );

		$idLookup = $this->createMock( EntityIdLookup::class );
		$idLookup->expects( $this->atLeastOnce() )
			->method( 'getEntityIds' )
			->will( $this->returnCallback( [ $this, 'titlesToIds' ] ) );

		$titleFactory = new TitleFactory();

		return new LabelPrefetchHookHandlers(
			$termBuffer,
			$idLookup,
			$titleFactory,
			$termTypes,
			$languageCodes
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
