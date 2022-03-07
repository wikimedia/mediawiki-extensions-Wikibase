<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use ApiUsageException;

/**
 * @covers \Wikibase\Repo\Api\FormatSnakValue
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class FormatSnakValueTest extends FederatedPropertiesApiTestCase {

	public function testApiRequest_shouldReturnApiErrorOnFailedRequest() {

		$this->setSourceWikiUnavailable();

		$params = [
			'action' => 'wbformatvalue',
			'generate' => 'text/plain',
			'datavalue' => '{ "value": "test", "type": "string" }',
			'property' => $this->newFederatedPropertyIdFromPId( 'P123' )->getSerialization(),
			'options' => json_encode( [ 'lang' => 'qqx' ] ),
		];

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'The request failed due to a failed request to the federation source wiki.' );
		$this->doApiRequest( $params );
	}
}
