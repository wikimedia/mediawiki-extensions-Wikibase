<?php

namespace Wikibase\Client\Tests\Unit\Store;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use PageProps;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityIdLookup;

/**
 * @covers \Wikibase\Client\Store\DescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @license GPL-2.0-or-later
 */
class DescriptionLookupTest extends TestCase {

	/**
	 * @dataProvider provideGetDescriptions
	 */
	public function testGetDescriptions(
		$titles,
		$sources,
		$localDescriptions,
		$centralDescriptions,
		$expectedDescriptions,
		$expectedActualSources
	) {
		$titles = $this->makeTitles( $titles );
		$descriptionLookup = $this->makeDescriptionLookup( $localDescriptions, $centralDescriptions );
		$descriptions = $descriptionLookup->getDescriptions( $titles, $sources, $actualSources );
		$this->assertSame( $expectedDescriptions, $descriptions );
		$this->assertSame( $expectedActualSources, $actualSources );
	}

	public function provideGetDescriptions() {
		$local = DescriptionLookup::SOURCE_LOCAL;
		$central = DescriptionLookup::SOURCE_CENTRAL;
		return [
			'empty' => [
				'titles' => [],
				'sources' => $local,
				'local descriptions' => [],
				'central descriptions' => [],
				'expected descriptions' => [],
				'expected sources' => [],
			],
			'local' => [
				'titles' => [ 1, 2, 3, 4, 5 ],
				'sources' => $local,
				'local descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions' => [ 2 => false, 3 => 'C3', 4 => 'C4' ],
				'expected descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'expected sources' => [ 1 => $local, 2 => $local, 3 => $local ],
			],
			'local (array syntax)' => [
				'titles' => [ 1, 2, 3, 4, 5 ],
				'sources' => [ $local ],
				'local descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions' => [ 2 => false, 3 => 'C3', 4 => 'C4' ],
				'expected descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'expected sources' => [ 1 => $local, 2 => $local, 3 => $local ],
			],
			'central' => [
				'titles' => [ 1, 2, 3, 4, 5 ],
				'sources' => $central,
				'local descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions' => [ 2 => false, 3 => 'C3', 4 => 'C4' ],
				'expected descriptions' => [ 3 => 'C3', 4 => 'C4' ],
				'expected sources' => [ 3 => $central, 4 => $central ],
			],
			'local with central fallback' => [
				'titles' => [ 1, 2, 3, 4, 5 ],
				'sources' => [ $local, $central ],
				'local descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions' => [ 2 => false, 3 => 'C3', 4 => 'C4' ],
				'expected descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3', 4 => 'C4' ],
				'expected sources' => [ 1 => $local, 2 => $local, 3 => $local, 4 => $central ],
			],
			'central with local fallback' => [
				'titles' => [ 1, 2, 3, 4, 5 ],
				'sources' => [ $central, $local ],
				'local descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'L3' ],
				'central descriptions' => [ 2 => false, 3 => 'C3', 4 => 'C4' ],
				'expected descriptions' => [ 1 => 'L1', 2 => 'L2', 3 => 'C3', 4 => 'C4' ],
				'expected sources' => [ 1 => $local, 2 => $local, 3 => $central, 4 => $central ],
			],
		];
	}

	/**
	 * @dataProvider provideGetDescription
	 */
	public function testGetDescription(
		$localDescription,
		$centralDescription,
		$source,
		$expectedDescription,
		$expectedSource
	) {
		$title = $this->makeTitle( 1, 'en' );
		$descriptionLookup = $this->makeDescriptionLookup(
			array_filter( [ 1 => $localDescription ] ),
			array_filter( [ 1 => $centralDescription ] )
		);
		$description = $descriptionLookup->getDescription( $title, $source, $actualSource );
		$this->assertSame( $expectedDescription, $description );
		$this->assertSame( $expectedSource, $actualSource );
	}

	public function provideGetDescription() {
		$local = DescriptionLookup::SOURCE_LOCAL;
		$central = DescriptionLookup::SOURCE_CENTRAL;
		return [
			'none' => [ null, null, $local, null, null ],
			'local only, asking local' => [ 'L', null, $local, 'L', $local ],
			'local only, asking central' => [ 'L', null, $central, null, null ],
			'asking local + central' => [ 'L', 'C', [ $local, $central ], 'L', $local ],
			'asking central + local' => [ 'L', 'C', [ $central, $local ], 'C', $central ],
		];
	}

	public function testGetDescription_error() {
		$title = $this->makeTitle( 1, 'de' );
		$descriptionLookup = $this->makeDescriptionLookup( [], [] );
		$this->expectException( InvalidArgumentException::class );
		$descriptionLookup->getDescription( $title, 'foo' );
	}

	public function testGetDescription_language() {
		$descriptionLookup = $this->makeDescriptionLookup( [], [ 1 => [ 'en' => 'Cen', 'de' => 'Cde' ] ] );

		$title = $this->makeTitle( 1, 'en' );
		$description = $descriptionLookup->getDescription( $title, DescriptionLookup::SOURCE_CENTRAL );
		$this->assertSame( 'Cen', $description );

		$title = $this->makeTitle( 1, 'de' );
		$description = $descriptionLookup->getDescription( $title, DescriptionLookup::SOURCE_CENTRAL );
		$this->assertSame( 'Cde', $description );

		$title = $this->makeTitle( 1, 'fr' );
		$description = $descriptionLookup->getDescription( $title, DescriptionLookup::SOURCE_CENTRAL );
		$this->assertSame( null, $description );
	}

	/**
	 * @param array $localDescriptions Page ID => description / null
	 * @param array $centralDescriptions Page ID => description / null
	 * @return DescriptionLookup
	 */
	private function makeDescriptionLookup( $localDescriptions, $centralDescriptions ) {
		$pageProps = $this->mockPageProps( $localDescriptions );
		$idLookup = $this->getIdLookup( $centralDescriptions );
		$termBuffer = $this->getTermBuffer( $centralDescriptions );
		$descriptionLookup = new DescriptionLookup( $idLookup, $termBuffer, $pageProps );
		return $descriptionLookup;
	}

	/**
	 * @param int[] $pageIds
	 *
	 * @return Title[] page id => Title
	 */
	private function makeTitles( $pageIds ) {
		return array_map( function ( $pageId ) {
			return $this->makeTitle( $pageId, 'en' );
		}, array_combine( $pageIds, $pageIds ) );
	}

	/**
	 * @param int $pageId
	 * @param string $pageLanguageCode
	 *
	 * @return Title
	 */
	private function makeTitle( $pageId, $pageLanguageCode ) {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $pageLanguageCode );
		$title = $this->createMock( Title::class );
		$title->method( 'getArticleID' )
			->willReturn( $pageId );
		$title->method( 'getPageLanguage' )
			->willReturn( $language );
		return $title;
	}

	/**
	 * Mock page property lookup.
	 *
	 * @param array $localDescriptions page id => description
	 *
	 * @return PageProps
	 */
	private function mockPageProps( array $localDescriptions ): PageProps {
		$pageProps = $this->createMock( PageProps::class );
		$pageProps->method( 'getProperties' )
			->with( $this->anything(), DescriptionLookup::LOCAL_PROPERTY_NAME )
			->willReturnCallback( function ( $titlesByPageId ) use ( $localDescriptions ) {
				return array_filter( array_map( function ( Title $title ) use ( $localDescriptions ) {
					if ( !array_key_exists( $title->getArticleID(), $localDescriptions ) ) {
						return null;
					}
					return $localDescriptions[$title->getArticleID()];
				}, $titlesByPageId ), function ( $description ) {
					return $description !== null;
				} );
			} );
		return $pageProps;
	}

	/**
	 * Mock id lookup.
	 *
	 * To keep things simple, we just pretend each title which has a central description
	 * is linked to the entity 'Q<pageid>'.
	 *
	 * @param array $centralDescriptions page id => description
	 *   If $centralDescriptions[<pageid>] is missing, there is no linked entity;
	 *   if it is null, there is no description.
	 *
	 * @return MockObject|EntityIdLookup
	 */
	private function getIdLookup( array $centralDescriptions ) {
		$idLookup = $this->getMockBuilder( EntityIdLookup::class )
			->getMockForAbstractClass();
		$idLookup->method( 'getEntityIds' )
			->willReturnCallback( function ( $titlesByPageId ) use ( $centralDescriptions ) {
				return array_filter( array_map( function ( Title $title ) use ( $centralDescriptions ) {
					if ( !array_key_exists( $title->getArticleID(), $centralDescriptions ) ) {
						return null;
					}
					return new ItemId( ItemId::joinSerialization(
						[ 'central', '', 'Q' . $title->getArticleID() ] ) );
				}, $titlesByPageId ) );
			} );
		return $idLookup;
	}

	/**
	 * Mock term lookup.
	 *
	 * Assumes the setup used by getIdLookup() and must be called with the same $centralDescriptions
	 * array.
	 *
	 * @param array $centralDescriptions page id => description
	 *   If $centralDescriptions[<pageid>] is missing, there is no linked entity;
	 *   if it is null, there is no description.
	 *   Description can be a string or an array of descriptions, indexed by language.
	 *
	 * @return MockObject|TermBuffer
	 */
	private function getTermBuffer( array $centralDescriptions ) {
		$termBuffer = $this->createMock( TermBuffer::class );
		$termBuffer->method( 'getPrefetchedTerm' )
			->willReturnCallback(
				function ( $entityId, $termType, $langCode ) use ( $centralDescriptions ) {
					$pageId = (int)substr( $entityId->getLocalPart(), 1 );
					$description = $centralDescriptions[$pageId];
					if ( is_array( $description ) ) {
						$description = $description[$langCode] ?? false;
					}
					return $description;
				}
			);
		return $termBuffer;
	}

}
