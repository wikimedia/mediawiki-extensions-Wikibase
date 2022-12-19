<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use Wikibase\Repo\Tests\WikibaseTablesUsed;

/**
 * @covers \Wikibase\Repo\Api\EditEntity
 * @covers \Wikibase\Repo\Api\ModifyEntity
 *
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 */
class EditEntityFingerprintUniquenessIntegrationTest extends WikibaseApiTestCase {

	use WikibaseTablesUsed;

	protected function setUp(): void {
		parent::setUp();
		$this->markAnyTermsStorageUsed();
	}

	public function testNewPropertyLabelConflict() {
		$expectedFailureCode = 'failed-save';
		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'datatype' => 'string',
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
			] ),
			'new' => 'property',
		];
		$this->doApiRequestWithToken( $params );

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => $expectedFailureCode,
		];
		// Repeating the same request with the same label should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function testExistingPropertyLabelConflict() {
		$expectedFailureCode = 'failed-save';

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'datatype' => 'string',
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
			] ),
			'new' => 'property',
		];
		$this->doApiRequestWithToken( $params );

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'datatype' => 'string',
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'no conflict label' ] ],
			] ),
			'new' => 'property',
		];
		$existingPropertyId = $this->doApiRequestWithToken( $params )[0]['entity']['id'];

		$params = [
			'action' => 'wbeditentity',
			'id' => $existingPropertyId,
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
			] ),
		];

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => $expectedFailureCode,
		];
		// Repeating the same request with the same label should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function testItemLabelWithoutDescriptionNotConflicting() {
		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'no conflict label' ] ],
			] ),
			'new' => 'item',
		];
		$this->doApiRequestWithToken( $params );

		// Repeating the same request with the same label should not fail.
		list( $result, , ) = $this->doApiRequestWithToken( $params );
		$this->assertArrayHasKey( 'success', $result );
	}

	public function testNewItemLabelDescriptionConflict() {
		$expectedFailureCode = 'modification-failed';

		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict description' ] ],
			] ),
		];
		$this->doApiRequestWithToken( $params );

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => $expectedFailureCode,
		];
		// Repeating the same request with the same label and description should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function testExistingItemLabelDescriptionConflict() {
		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict description' ] ],
			] ),
		];
		$this->doApiRequestWithToken( $params );

		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => json_encode( [
				'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict label' ] ],
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'no conflict description' ] ],
			] ),
		];
		$existingItemId = $this->doApiRequestWithToken( $params )[0]['entity']['id'];

		$params = [
			'action' => 'wbeditentity',
			'id' => $existingItemId,
			'data' => json_encode( [
				'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'conflict description' ] ],
			] ),
		];

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => 'modification-failed',
		];
		// Repeating the same request with the same label and description should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

}
