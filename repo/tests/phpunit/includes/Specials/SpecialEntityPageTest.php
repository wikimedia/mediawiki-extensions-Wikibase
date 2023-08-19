<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use HttpError;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\FauxResponse;
use MediaWiki\Title\Title;
use SpecialPageExecutor;
use SpecialPageTestBase;
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

	protected function setUp(): void {
		parent::setUp();

		// Set the language to qqx pseudo-language to have message keys used as UI messages
		$this->setUserLang( 'qqx' );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$title = $this->createMock( Title::class );
		$title->method( 'getFullURL' )
			->willReturnCallback(
				fn( $query ) => wfAppendQuery( self::LOCAL_ENTITY_PAGE_URL, $query )
			);

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )->willReturn( $title );

		return $titleLookup;
	}

	protected function newSpecialPage() {
		return new SpecialEntityPage(
			new ItemIdParser(),
			$this->getEntityTitleLookup()
		);
	}

	public static function provideLocalEntityIdArgumentsToSpecialPage() {
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

	public static function provideQueryArgumentsToSpecialPage() {
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

	public static function provideInvalidEntityIdArgumentsToSpecialPage() {
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

	public static function provideNoEntityIdArgumentsToSpecialPage() {
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
