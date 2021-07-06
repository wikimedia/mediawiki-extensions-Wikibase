<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use Exception;
use FauxRequest;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use RequestContext;
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

	/**
	 * @dataProvider provideParamsAndExpectedResults
	 */
	public function testExecute( array $params, array $expectedResults ) {
		$query = $this->getQuery( $params );
		$api = new MetaContentLanguages(
			$this->getContentLanguages(),
			false,
			$this->getLanguageNameUtils(),
			$query,
			'wbcontentlanguages'
		);

		$api->execute();
		$apiResult = $api->getResult();
		$results = $apiResult->getResultData()['query']['wbcontentlanguages'];

		$this->assertSame( $expectedResults, $results );
	}

	public function testExecute_warnsAboutUnknownLanguageNames() {
		$query = $this->getQuery( [ 'wbclprop' => 'name' ] );
		$api = new MetaContentLanguages(
			new WikibaseContentLanguages( [ WikibaseContentLanguages::CONTEXT_TERM => new StaticContentLanguages( [ 'unknown' ] ) ] ),
			true,
			$this->getLanguageNameUtils(),
			$query,
			'wbcontentlanguages'
		);

		$this->expectWarning();
		$api->execute();
	}

	/**
	 * @param array $params
	 * @return ApiQuery
	 */
	private function getQuery( array $params ): ApiQuery {
		$context = new RequestContext();
		$context->setLanguage( 'de' );
		$context->setRequest( new FauxRequest( $params ) );
		$main = new ApiMain( $context );
		$query = $main->getModuleManager()->getModule( 'query' );

		return $query;
	}

	private function getContentLanguages(): WikibaseContentLanguages {
		return new WikibaseContentLanguages( [
			'term' => new StaticContentLanguages( [ 'en', 'de', 'es' ] ),
			'test' => new StaticContentLanguages( [ 'en', 'mis', 'und' ] ),
		] );
	}

	private function getLanguageNameUtils(): LanguageNameUtils {
		$languageNameUtils = $this->createMock( LanguageNameUtils::class );
		$languageNameUtils->method( 'getLanguageName' )
			->willReturnCallback( function ( $code, $inLanguage = null ) {
				if ( $inLanguage === null ) {
					return $code === 'en' ? 'English' : '';
				}
				$this->assertSame( 'de', $inLanguage );
				switch ( $code ) {
					case 'en':
						return 'Englisch';
					case 'mis':
						return 'nicht unterstützte Sprache';
					case 'und':
						return 'Unbekannte Sprache';
					case 'unknown':
						return '';
				}
				throw new Exception( "unexpected call: getLanguage( $code, $inLanguage )" );
			} );
		return $languageNameUtils;
	}

	public function provideParamsAndExpectedResults() {
		yield 'default' => [
			[],
			[
				'en' => [ 'code' => 'en' ],
				'de' => [ 'code' => 'de' ],
				'es' => [ 'code' => 'es' ],
			],
		];

		yield 'test context, with autonyms' => [
			[ 'wbclcontext' => 'test', 'wbclprop' => 'code|autonym' ],
			[
				'en' => [ 'code' => 'en', 'autonym' => 'English' ],
				'mis' => [ 'code' => 'mis', 'autonym' => null ],
				'und' => [ 'code' => 'und', 'autonym' => null ],
			],
		];

		yield 'test context, with language names' => [
			[ 'wbclcontext' => 'test', 'wbclprop' => 'name' ],
			[
				'en' => [ 'name' => 'Englisch' ],
				'mis' => [ 'name' => 'nicht unterstützte Sprache' ],
				'und' => [ 'name' => 'Unbekannte Sprache' ],
			],
		];
	}

}
