<?php

namespace Wikibase\Repo\Tests\Api;

use DataValues\StringValue;
use ApiUsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers Wikibase\Repo\Api\RemoveQualifiers
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RemoveQualifiersTest extends WikibaseApiTestCase {

	/**
	 * @return Snak
	 */
	protected function snakProvider() {
		$snaks = [];

		$snaks[] = new PropertyNoValueSnak( 42 );
		$snaks[] = new PropertySomeValueSnak( 9001 );
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Statement[]
	 */
	protected function statementProvider() {
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
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		foreach ( $this->statementProvider() as $statement ) {
			$item = new Item();

			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

			$guidGenerator = new GuidGenerator();
			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$item->getStatements()->addStatement( $statement );

			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

			$this->assertInternalType( 'string', $statement->getGuid() );

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

	protected function makeValidRequest( $statementGuid, array $hashes ) {
		$params = [
			'action' => 'wbremovequalifiers',
			'claim' => $statementGuid,
			'qualifiers' => implode( '|', $hashes ),
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );

		$this->makeInvalidRequest( $statementGuid, $hashes, 'no-such-qualifier' );
	}

	protected function makeInvalidRequest( $statementGuid, array $hashes, $expectedError = null ) {
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
	public function testInvalidClaimGuid( $claimGuid, $hash ) {
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
				$msg->getApiCode(),
				'invalid-guid',
				'Invalid claim guid raised correct error'
			);
		}
	}

	public function invalidGuidProvider() {
		$qualifierSnak = new PropertyValueSnak( 722, new StringValue( 'abc' ) );
		$hash = $qualifierSnak->getHash();

		return [
			[ 'xyz', $hash ],
			[ 'x$y$z', $hash ]
		];
	}

}
