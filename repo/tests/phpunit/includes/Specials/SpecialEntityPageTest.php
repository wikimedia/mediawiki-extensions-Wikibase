<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use FauxResponse;
use HttpError;
use SpecialPageExecutor;
use SpecialPageTestBase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Specials\SpecialEntityPage;

/**
 * @covers \Wikibase\Repo\Specials\SpecialEntityPage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SpecialEntityPageTest extends SpecialPageTestBase {

	private const LOCAL_ENTITY_PAGE_URL = 'https://local.wiki/local-entity-page';
	private const FOREIGN_ENTITY_PAGE_URL = 'https://foreign.wiki/Special:EntityPage/entity-id';

	protected function setUp(): void {
		parent::setUp();

		// Set the language to qqx pseudo-language to have message keys used as UI messages
		$this->setUserLang( 'qqx' );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );

		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback( function ( EntityId $id ) {
				$title = $this->createMock( Title::class );

				$title->method( 'getFullURL' )
					->willReturnCallback(
						function ( $query ) use ( $id ) {
							$base = $id->isForeign()
								? self::FOREIGN_ENTITY_PAGE_URL
								: self::LOCAL_ENTITY_PAGE_URL;
							return wfAppendQuery( $base, $query );
						} );

				return $title;
			} );

		return $titleLookup;
	}

	protected function newSpecialPage() {
		return new SpecialEntityPage(
			new ItemIdParser(),
			$this->getEntityTitleLookup()
		);
	}

	public function provideLocalEntityIdArgumentsToSpecialPage() {
		return [
			'id as a sub page' => [ 'Q100', [] ],
			'id as a request parameter' => [ null, [ 'id' => 'Q100' ] ],
		];
	}

	/**
	 * @dataProvider provideLocalEntityIdArgumentsToSpecialPage
	 */
	public function testGivenLocalEntityId_pageRedirectsToSpecialEntityData(
		$subPage,
		array $requestParams
	) {
		$request = new FauxRequest( $requestParams );

		/** @var FauxResponse $response */
		list( , $response ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertSame( 301, $response->getStatusCode() );
		$this->assertSame( self::LOCAL_ENTITY_PAGE_URL, $response->getHeader( 'Location' ) );
	}

	public function provideQueryArgumentsToSpecialPage() {
		return [
			'no query arguments' => [ [], '' ],
			'supported query argument' => [ [ 'oldid' => 123 ], '?oldid=123' ],
			'unsupported query argument' => [ [ 'foo' => 'bar' ], '' ],
			'mixed' => [ [ 'oldid' => 123, 'foo' => 'bar' ], '?oldid=123' ],
		];
	}

	/**
	 * @dataProvider provideQueryArgumentsToSpecialPage
	 */
	public function testGivenQueryArguments_redirectIncludesArguments(
		$arguments,
		$expectedUrlSuffix
	) {
		$request = new FauxRequest( $arguments );

		/** @var FauxResponse $response */
		list( , $response ) = $this->executeSpecialPage( 'Q100', $request );

		$this->assertSame( 301, $response->getStatusCode() );
		$expectedUrl = self::LOCAL_ENTITY_PAGE_URL . $expectedUrlSuffix;
		$this->assertSame( $expectedUrl, $response->getHeader( 'Location' ) );
	}

	public function provideForeignEntityIdArgumentsToSpecialPage() {
		return [
			'id as a sub page' => [ 'foo:Q100', [] ],
			'id as a request parameter' => [ null, [ 'id' => 'foo:Q100' ] ],
		];
	}

	/**
	 * @dataProvider provideForeignEntityIdArgumentsToSpecialPage
	 */
	public function testGivenForeignEntityId_pageRedirectsToOtherReposSpecialEntityPage(
		$subPage,
		array $requestParams
	) {
		$request = new FauxRequest( $requestParams );

		/** @var FauxResponse $response */
		list( , $response ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertSame( 301, $response->getStatusCode() );
		$this->assertSame( self::FOREIGN_ENTITY_PAGE_URL, $response->getHeader( 'Location' ) );
	}

	public function provideInvalidEntityIdArgumentsToSpecialPage() {
		return [
			'id as a sub page' => [ 'ABCDEF', [], 'ABCDEF' ],
			'id as a request parameter' => [ null, [ 'id' => 'ABCDEF' ], 'ABCDEF' ],
		];
	}

	/**
	 * @dataProvider provideInvalidEntityIdArgumentsToSpecialPage
	 */
	public function testGivenInvalidId_pageShowsBadEntityIdError( $subPage, array $requestParams, $idExpectedInErrorMsg ) {
		$request = new FauxRequest( $requestParams );

		$this->expectException( HttpError::class );
		$this->expectExceptionMessage( "(wikibase-entitypage-bad-id: $idExpectedInErrorMsg)" );

		try {
			$this->executeSpecialPage( $subPage, $request );
		} catch ( HttpError $exception ) {
			$this->assertSame( 400, $exception->getStatusCode() );
			throw $exception;
		}
	}

	public function testGivenIdWithNoRelatedPage_pageShowsAnError() {
		$nullReturningTitleLookup = $this->createMock( EntityTitleLookup::class );
		$nullReturningTitleLookup
			->method( 'getTitleForId' )
			->willReturn( null );

		$specialEntityPage = new SpecialEntityPage(
			new ItemIdParser(),
			$nullReturningTitleLookup
		);

		$this->expectException( HttpError::class );
		$this->expectExceptionMessage( '(wikibase-entitypage-bad-id: Q123)' );

		try {
			( new SpecialPageExecutor() )->executeSpecialPage( $specialEntityPage, 'Q123' );
		} catch ( HttpError $exception ) {
			$this->assertSame( 400, $exception->getStatusCode() );
			throw $exception;
		}
	}

	public function provideNoEntityIdArgumentsToSpecialPage() {
		return [
			'no sub page' => [ '' ],
			'empty id as a request parameter' => [ null, [ 'id' => '' ] ],
		];
	}

	/**
	 * @dataProvider provideNoEntityIdArgumentsToSpecialPage
	 */
	public function testGivenNoEntityId_pageShowsHelpMessage( $subPage, array $requestParams = [] ) {
		$request = new FauxRequest( $requestParams );

		list( $output, ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertStringContainsString( '(wikibase-entitypage-text)', $output );
	}

}
