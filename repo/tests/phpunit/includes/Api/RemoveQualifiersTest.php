<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\RemoveQualifiers
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RemoveQualifiersTest extends WikibaseApiTestCase {

	/**
	 * @return Snak[]
	 */
	protected function snakProvider(): iterable {
		$snaks = [];

		$snaks[] = new PropertyNoValueSnak( 42 );
		$snaks[] = new PropertySomeValueSnak( 9001 );
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Statement[]
	 */
	protected function statementProvider(): iterable {
		$statements = [];

		$mainSnak = new PropertyNoValueSnak( 42 );
		$statement = new Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new SnakList( [ $snak ] );
			$statement->setQualifiers( $snaks );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new SnakList( $this->snakProvider() );
		$statement->setQualifiers( $snaks );
		$statements[] = $statement;

		return $statements;
	}

	public function testRequests() {
		$store = $this->getEntityStore();
		$guidGenerator = new GuidGenerator();

		foreach ( $this->statementProvider() as $statement ) {
			$item = new Item();

			$store->saveEntity( $item, '', $this->user, EDIT_NEW );

			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$item->getStatements()->addStatement( $statement );

			$store->saveEntity( $item, '', $this->user, EDIT_UPDATE );

			$this->assertIsString( $statement->getGuid() );

			$qualifiers = $statement->getQualifiers();

			if ( count( $qualifiers ) === 0 ) {
				$this->makeInvalidRequest(
					$statement->getGuid(),
					[ '~=[,,_,,]:3' ],
					'no-such-qualifier'
				);
			} else {
				$hashes = array_map(
					function( Snak $qualifier ) {
						return $qualifier->getHash();
					},
					iterator_to_array( $qualifiers )
				);

				$this->makeValidRequest(
					$statement->getGuid(),
					$hashes
				);
			}
		}
	}

	public function testRemoveQualifiersWithTag() {
		$store = $this->getEntityStore();
		$item = new Item();

		$store->saveEntity( $item, '', $this->user, EDIT_NEW );

		$statement = $this->statementProvider()[2];
		$guidGenerator = new GuidGenerator();
		$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
		$item->getStatements()->addStatement( $statement );

		$store->saveEntity( $item, '', $this->user, EDIT_UPDATE );

		$this->assertIsString( $statement->getGuid() );

		$qualifiers = $statement->getQualifiers();
		$hashes = array_map(
			function( Snak $qualifier ) {
				return $qualifier->getHash();
			},
			iterator_to_array( $qualifiers )
		);

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbremovequalifiers',
			'claim' => $statement->getGuid(),
			'qualifiers' => implode( '|', $hashes ),
		] );
	}

	protected function makeValidRequest( string $statementGuid, array $hashes ): void {
		$params = [
			'action' => 'wbremovequalifiers',
			'claim' => $statementGuid,
			'qualifiers' => implode( '|', $hashes ),
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertIsArray( $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );

		$this->makeInvalidRequest( $statementGuid, $hashes, 'no-such-qualifier' );
	}

	protected function makeInvalidRequest( string $statementGuid, array $hashes, ?string $expectedError ) {
		$params = [
			'action' => 'wbremovequalifiers',
			'claim' => $statementGuid,
			'qualifiers' => implode( '|', $hashes ),
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
		} catch ( ApiUsageException $e ) {
			if ( $expectedError === null ) {
				$this->assertTrue( true, 'Invalid request raised error' );
			} else {
				$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
				$this->assertEquals(
					$expectedError,
					$msg->getApiCode(),
					'Invalid request raised correct error'
				);
			}
		}
	}

	/**
	 * @dataProvider invalidGuidProvider
	 */
	public function testInvalidClaimGuid( string $claimGuid, string $hash ) {
		$params = [
			'action' => 'wbremovequalifiers',
			'claim' => $claimGuid,
			'qualifiers' => $hash,
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid claim guid did not throw an error' );
		} catch ( ApiUsageException $e ) {
			$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
			$this->assertEquals(
				'invalid-guid',
				$msg->getApiCode(),
				'Invalid claim guid raised correct error'
			);
		}
	}

	public function invalidGuidProvider() {
		$qualifierSnak = new PropertyValueSnak( 722, new StringValue( 'abc' ) );
		$hash = $qualifierSnak->getHash();

		return [
			[ 'xyz', $hash ],
			[ 'x$y$z', $hash ],
		];
	}

}
