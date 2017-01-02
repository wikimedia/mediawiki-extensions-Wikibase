<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use FauxResponse;
use HttpError;
use SpecialPageExecutor;
use SpecialPageTestBase;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Specials\SpecialEntityPage;

/**
 * @covers Wikibase\Repo\Specials\SpecialEntityPage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *
 * @license GPL-2.0+
 */
class SpecialEntityPageTest extends SpecialPageTestBase {

	const LOCAL_ENTITY_PAGE_URL = 'https://local.wiki/local-entity-page';
	const FOREIGN_ENTITY_PAGE_URL = 'https://foreign.wiki/Special:EntityPage/entity-id';

	protected function setUp() {
		parent::setUp();

		// Set the language to qqx pseudo-language to have message keys used as UI messages
		$this->setUserLang( 'qqx' );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$localTitle = $this->getMock( Title::class );
		$localTitle->expects( $this->any() )
			->method( 'getFullURL' )
			->will(
				$this->returnValue( self::LOCAL_ENTITY_PAGE_URL )
			);
		$foreignTitle = $this->getMock( Title::class );
		$foreignTitle->expects( $this->any() )
			->method( 'getFullURL' )
			->will(
				$this->returnValue( self::FOREIGN_ENTITY_PAGE_URL )
			);

		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback(
				function ( $id ) use ( $localTitle, $foreignTitle ) {
					return strpos( $id, ':' ) === false ? $localTitle : $foreignTitle;
				}
			) );

		return $titleLookup;
	}

	protected function newSpecialPage() {
		$page = new SpecialEntityPage(
			new BasicEntityIdParser(),
			$this->getEntityTitleLookup()
		);

		return $page;
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

		/* @var FauxResponse $response */
		list( , $response ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertSame( 301, $response->getStatusCode() );
		$this->assertSame( self::LOCAL_ENTITY_PAGE_URL, $response->getHeader( 'Location' ) );
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

		/* @var FauxResponse $response */
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
		$this->setExpectedException( HttpError::class );

		try {
			$this->executeSpecialPage( $subPage, $request );
		} catch ( HttpError $exception ) {
			$this->assertSame( 400, $exception->getStatusCode() );
			$this->assertEquals( "(wikibase-entitypage-bad-id: $idExpectedInErrorMsg)", $exception->getMessage() );
			throw $exception;
		}
	}

	public function testGivenIdWithNoRelatedPage_pageShowsAnError() {
		$nullReturningTitleLookup = $this->getMock( EntityTitleLookup::class );
		$nullReturningTitleLookup
			->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( null ) );

		$specialEntityPage = new SpecialEntityPage(
			new BasicEntityIdParser(),
			$nullReturningTitleLookup
		);

		$this->setExpectedException( HttpError::class );

		try {
			( new SpecialPageExecutor() )->executeSpecialPage( $specialEntityPage, 'Q123' );
		} catch ( HttpError $exception ) {
			$this->assertSame( 400, $exception->getStatusCode() );
			$this->assertEquals( '(wikibase-entitypage-bad-id: Q123)', $exception->getMessage() );
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

		/* @var FauxResponse $response */
		list( $output, ) = $this->executeSpecialPage( $subPage, $request );

		$this->assertContains( '(wikibase-entitypage-text)', $output );
	}

}
