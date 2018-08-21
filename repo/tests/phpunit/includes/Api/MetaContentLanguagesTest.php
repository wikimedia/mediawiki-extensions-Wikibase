<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use FauxRequest;
use Language;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Warning;
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
			$query,
			'wbcontentlanguages'
		);

		$api->execute();
		$apiResult = $api->getResult();
		$results = $apiResult->getResultData()['query']['wbcontentlanguages'];

		$this->assertSame( $expectedResults, $results );
	}

	/**
	 * @expectedException PHPUnit_Framework_Error_Warning
	 */
	public function testExecute_warnsAboutUnknownLanguageNames() {
		$query = $this->getQuery( [ 'wbclprop' => 'name' ] );
		$api = new MetaContentLanguages(
			new WikibaseContentLanguages( [ 'term' => new StaticContentLanguages( [ 'unknown' ] ) ] ),
			true,
			$query,
			'wbcontentlanguages'
		);

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
		$query = new ApiQuery( $main, 'query' );

		return $query;
	}

	private function getContentLanguages() {
		return new WikibaseContentLanguages( [
			'term' => new StaticContentLanguages( [ 'en', 'de', 'es' ] ),
			'test' => new StaticContentLanguages( [ 'en', 'mis', 'und' ] ),
		] );
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

		$haveCldr = Language::fetchLanguageName( 'en', 'de' ) === 'Englisch';
		yield 'test context, with language names' => [
			[ 'wbclcontext' => 'test', 'wbclprop' => 'name' ],
			[
				'en' => [ 'name' => $haveCldr ? 'Englisch' : 'English' ],
				'mis' => [ 'name' => $haveCldr ? 'nicht unterstützte Sprache' : null ],
				'und' => [ 'name' => $haveCldr ? 'Unbekannte Sprache' : null ],
			],
		];
	}

}
