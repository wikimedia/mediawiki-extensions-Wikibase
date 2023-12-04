<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use MediaWiki\Request\FauxRequest;
use PHPUnit\Framework\TestCase;
use RequestContext;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Api\MetaContentLanguages;

/**
 * @covers \Wikibase\Repo\Api\MetaContentLanguages
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 */
class MetaContentLanguagesTest extends TestCase {

	private const USER_LANGUAGE = 'de';

	/**
	 * @dataProvider provideParamsAndExpectedResults
	 */
	public function testExecute( array $params, array $expectedResults ) {
		$query = $this->getQuery( $params );
		$api = new MetaContentLanguages(
			$query,
			'wbcontentlanguages',
			$this->getLanguageNameLookupFactory(),
			$this->getContentLanguages()
		);

		$api->execute();
		$apiResult = $api->getResult();
		$results = $apiResult->getResultData()['query']['wbcontentlanguages'];

		$this->assertSame( $expectedResults, $results );
	}

	/**
	 * @param array $params
	 * @return ApiQuery
	 */
	private function getQuery( array $params ): ApiQuery {
		$context = new RequestContext();
		$context->setLanguage( self::USER_LANGUAGE );
		$context->setRequest( new FauxRequest( $params ) );
		$main = new ApiMain( $context );
		$query = $main->getModuleManager()->getModule( 'query' );

		return $query;
	}

	private function getContentLanguages(): WikibaseContentLanguages {
		return new WikibaseContentLanguages( [
			'term' => new StaticContentLanguages( [ 'en', 'de', 'es', 'mul' ] ),
			'test' => new StaticContentLanguages( [ 'en', 'mis', 'mul' ] ),
		] );
	}

	private function getLanguageNameLookupFactory(): LanguageNameLookupFactory {
		$autonymsMap = [
			[ 'en', 'English' ],
			[ 'de', 'Deutsch' ],
			[ 'es', 'español' ],
			[ 'mul', 'mul' ],
			[ 'mis', 'mis' ],
		];
		$autonymLookup = $this->createMock( LanguageNameLookup::class );
		$autonymLookup->method( 'getName' )
			->willReturnMap( $autonymsMap );
		$autonymLookup->method( 'getNameForTerms' )
			->willReturnMap( $autonymsMap );
		$namesMap = [
			[ 'en', 'Englisch' ],
			[ 'de', 'Deutsch' ],
			[ 'es', 'Spanisch' ],
			[ 'mis', 'nicht unterstützte Sprache' ],
		];
		$nameLookup = $this->createMock( LanguageNameLookup::class );
		$nameLookup->method( 'getName' )
			->willReturnMap( [
				...$namesMap,
				[ 'mul', 'Mehrsprachig' ],
			] );
		$nameLookup->method( 'getNameForTerms' )
			->willReturnMap( [
				...$namesMap,
				[ 'mul', 'Standardwerte (mul)' ],
			] );

		$factory = $this->createMock( LanguageNameLookupFactory::class );
		$factory->expects( $this->once() )
			->method( 'getForAutonyms' )
			->willReturn( $autonymLookup );
		$factory->expects( $this->once() )
			->method( 'getForLanguageCode' )
			->with( self::USER_LANGUAGE )
			->willReturn( $nameLookup );
		return $factory;
	}

	public static function provideParamsAndExpectedResults() {
		yield 'default' => [
			[],
			[
				'en' => [ 'code' => 'en' ],
				'de' => [ 'code' => 'de' ],
				'es' => [ 'code' => 'es' ],
				'mul' => [ 'code' => 'mul' ],
			],
		];

		yield 'term context, with autonyms and language names' => [
			[ 'wbclcontext' => 'term', 'wbclprop' => 'code|autonym|name' ],
			[
				'en' => [ 'code' => 'en', 'autonym' => 'English', 'name' => 'Englisch' ],
				'de' => [ 'code' => 'de', 'autonym' => 'Deutsch', 'name' => 'Deutsch' ],
				'es' => [ 'code' => 'es', 'autonym' => 'español', 'name' => 'Spanisch' ],
				'mul' => [ 'code' => 'mul', 'autonym' => 'mul', 'name' => 'Standardwerte (mul)' ],
			],
		];

		yield 'test context, with autonyms' => [
			[ 'wbclcontext' => 'test', 'wbclprop' => 'code|autonym' ],
			[
				'en' => [ 'code' => 'en', 'autonym' => 'English' ],
				'mis' => [ 'code' => 'mis', 'autonym' => 'mis' ],
				'mul' => [ 'code' => 'mul', 'autonym' => 'mul' ],
			],
		];

		yield 'test context, with language names' => [
			[ 'wbclcontext' => 'test', 'wbclprop' => 'name' ],
			[
				'en' => [ 'name' => 'Englisch' ],
				'mis' => [ 'name' => 'nicht unterstützte Sprache' ],
				'mul' => [ 'name' => 'Mehrsprachig' ],
			],
		];
	}

}
