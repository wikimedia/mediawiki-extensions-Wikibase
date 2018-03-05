<?php

namespace Wikibase\Repo\Tests\Api;

use DataValues\StringValue;
use ApiUsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Reference;
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
 * @covers Wikibase\Repo\Api\RemoveReferences
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
class RemoveReferencesTest extends WikibaseApiTestCase {

	/**
	 * @return Snak[]
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
			$statement->getReferences()->addReference( new Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
		$statements[] = $statement;

		return $statements;
	}

	public function testRequests() {
		foreach ( $this->statementProvider() as $statement ) {
			$item = new Item();

			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

			$guidGenerator = new GuidGenerator();
			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$item->getStatements()->addStatement( $statement );

			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

			$references = $statement->getReferences();

			$hashes = array_map(
				function( Reference $reference ) {
					return $reference->getHash();
				},
				iterator_to_array( $references )
			);

			$this->assertInternalType( 'string', $statement->getGuid() );

			if ( $references->isEmpty() ) {
				$this->makeInvalidRequest(
					$statement->getGuid(),
					[ '~=[,,_,,]:3' ],
					'no-such-reference'
				);
			} else {
				$this->makeValidRequest(
					$statement->getGuid(),
					$hashes
				);
			}
		}
	}

	protected function makeValidRequest( $statementGuid, array $hashes ) {
		$params = [
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => implode( '|', $hashes ),
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );

		$this->makeInvalidRequest( $statementGuid, $hashes, 'no-such-reference' );
	}

	protected function makeInvalidRequest( $statementGuid, array $hashes, $expectedError = null ) {
		$params = [
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => implode( '|', $hashes ),
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
		} catch ( ApiUsageException $e ) {
			if ( $expectedError === null ) {
				$this->assertTrue( true, 'Invalid request raised error' );
			} else {
				$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
				$this->assertEquals( $expectedError, $msg->getApiCode(), 'Invalid request raised correct error' );
			}
		}
	}

	/**
	 * @dataProvider invalidGuidProvider
	 */
	public function testInvalidStatementGuid( $statementGuid, $hash ) {
		$params = [
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => $hash,
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid guid did not throw an error' );
		} catch ( ApiUsageException $ex ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			$this->assertEquals( 'invalid-guid', $msg->getApiCode(), 'Invalid guid raised correct error' );
		}
	}

	public function invalidGuidProvider() {
		$snak = new PropertyValueSnak( 722, new StringValue( 'abc' ) );
		$hash = $snak->getHash();

		return [
			[ 'xyz', $hash ],
			[ 'x$y$z', $hash ]
		];
	}

}
