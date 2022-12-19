<?php

namespace Wikibase\Client\Tests\Integration\Api;

use ApiTestCase;
use ApiUsageException;
use FormatJson;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Lib\WikibaseSettings;

/**
 * Integration tests for wbformatreference,
 * making full requests against the API with real services.
 * See also {@link ApiFormatReferenceUnitTest}.
 *
 * @covers \Wikibase\Client\Api\ApiFormatReference
 *
 * @group Database
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class ApiFormatReferenceTest extends ApiTestCase {

	/**
	 * @dataProvider provideValidFormatReferenceParameters
	 */
	public function testGivenValidParamsYieldsRenderedReference( $params, $expectedHtml ) {
		if ( !$this->isApiTestable() ) {
			$this->markTestSkipped( 'wbformatreference is not enabled or Database not setup.' );
		}

		$result = $this->doApiRequest( $this->decorateParamsAsModernApiRequest( $params ) );
		$this->assertEquals( $expectedHtml, $result[0]['wbformatreference']['html'] );
	}

	/**
	 * @dataProvider provideBadFormatReferenceParameters
	 */
	public function testGivenFaultyParamsYieldsAnError( $params, $expectedError, $expectedParams ) {
		if ( !$this->isApiTestable() ) {
			$this->markTestSkipped( 'wbformatreference is not enabled or Database not setup.' );
		}

		try {
			$this->doApiRequest( $this->decorateParamsAsModernApiRequest( $params ) );
			$this->fail( 'Exception expected but not thrown' );
		} catch ( ApiUsageException $e ) {
			$message = $e->getMessageObject();
			$this->assertEquals( $expectedError, $message->getKey() );
			$this->assertEquals( $expectedParams, $message->getParams() );
		}
	}

	public function provideValidFormatReferenceParameters() {
		yield [
			[
				'action' => 'wbformatreference',
				'reference' => FormatJson::encode( [
					'snaks' => [
					],
				], true ),
				'outputformat' => 'html',
				'style' => 'internal-data-bridge',
			],
			'<div class="mw-parser-output"></div>',
		];
		$idOfUnavailableProperty = 'P' . Int32EntityId::MAX; // hopefully not present on the wiki under test
		yield [
			[
				'action' => 'wbformatreference',
				'reference' => FormatJson::encode( [
					'snaks' => [
						$idOfUnavailableProperty => [ [
							'property' => $idOfUnavailableProperty,
							'snaktype' => 'value',
							'datavalue' => [ 'type' => 'string', 'value' => 'a distinctive string' ],
							'datatype' => 'string',
						] ],
					],
				], true ),
				'outputformat' => 'html',
				'style' => 'internal-data-bridge',
			],
			"<div class=\"mw-parser-output\"><p><span>a distinctive string <span class=\"error wb-format-error\">" .
				"(wikibase-snakformatter-property-not-found: $idOfUnavailableProperty)" .
				"</span></span>(wikibase-reference-formatter-snak-terminator)\n</p></div>",
		];
	}

	public function provideBadFormatReferenceParameters() {
		yield [
			[ 'action' => 'wbformatreference' ],
			'paramvalidator-missingparam',
			[ [ 'plaintext' => 'reference' ] ],
		];
		yield [
			[ 'action' => 'wbformatreference', 'reference' => '{}' ],
			'paramvalidator-missingparam',
			[ [ 'plaintext' => 'style' ] ],
		];
		yield [
			[ 'action' => 'wbformatreference', 'reference' => '{}', 'style' => 'foo' ],
			'paramvalidator-badvalue-enumnotmulti',
			[
				[ 'plaintext' => 'style' ],
				[ 'plaintext' => 'foo' ],
				[ 'list' => [ [ 'plaintext' => 'internal-data-bridge' ] ], 'type' => 'text' ],
				[ 'num' => 1 ],
			],
		];
		yield [
			[ 'action' => 'wbformatreference', 'reference' => '{}', 'style' => 'internal-data-bridge', 'outputformat' => 'json' ],
			'paramvalidator-badvalue-enumnotmulti',
			[
				[ 'plaintext' => 'outputformat' ],
				[ 'plaintext' => 'json' ],
				[ 'list' => [ [ 'plaintext' => 'html' ] ], 'type' => 'text' ],
				[ 'num' => 1 ],
			],
		];
		yield [
			[ 'action' => 'wbformatreference', 'reference' => '{', 'outputformat' => 'html', 'style' => 'internal-data-bridge' ],
			'json-error-syntax',
			[],
		];
		yield [
			[ 'action' => 'wbformatreference', 'reference' => '{}', 'outputformat' => 'html', 'style' => 'internal-data-bridge' ],
			'wikibase-error-deserialize-error',
			[],
		];
	}

	private function isApiTestable(): bool {
		$apiEnabled = WikibaseClient::getSettings()->getSetting( 'dataBridgeEnabled' ) ?? false;
		$repoEnabled = WikibaseSettings::isRepoEnabled(); // to ensure DB is set up
		return $apiEnabled && $repoEnabled;
	}

	private function decorateParamsAsModernApiRequest( array $params ) {
		return array_merge(
			[
				'uselang' => 'qqx',
				'errorformat' => 'raw',
				'formatversion' => 2,
			],
			$params
		);
	}
}
