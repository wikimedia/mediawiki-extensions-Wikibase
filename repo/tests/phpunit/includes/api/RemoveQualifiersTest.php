<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use UsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\RemoveQualifiers
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group RemoveQualifiersTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RemoveQualifiersTest extends WikibaseApiTestCase {

	/**
	 * @return Snak
	 */
	protected function snakProvider() {
		$snaks = array();

		$snaks[] = new PropertyNoValueSnak( 42 );
		$snaks[] = new PropertySomeValueSnak( 9001 );
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Statement[]
	 */
	protected function statementProvider() {
		$statements = array();

		$mainSnak = new PropertyNoValueSnak( 42 );
		$statement = new Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new SnakList( array( $snak ) );
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
			$item = Item::newEmpty();

			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

			$guidGenerator = new ClaimGuidGenerator();
			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$item->addClaim( $statement );

			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

			$this->assertInternalType( 'string', $statement->getGuid() );

			$qualifiers = $statement->getQualifiers();

			if ( count( $qualifiers ) === 0 ) {
				$this->makeInvalidRequest(
					$statement->getGuid(),
					array( '~=[,,_,,]:3' ),
					'no-such-qualifier'
				);
			}
			else {
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
		$params = array(
			'action' => 'wbremovequalifiers',
			'claim' => $statementGuid,
			'qualifiers' => implode( '|', $hashes ),
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );

		$this->makeInvalidRequest( $statementGuid, $hashes, 'no-such-qualifier' );
	}

	protected function makeInvalidRequest( $statementGuid, array $hashes, $expectedError = null ) {
		$params = array(
			'action' => 'wbremovequalifiers',
			'claim' => $statementGuid,
			'qualifiers' => implode( '|', $hashes ),
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
		} catch ( UsageException $e ) {
			if ( $expectedError === null ) {
				$this->assertTrue( true, 'Invalid request raised error' );
			} else {
				$this->assertEquals(
					$expectedError,
					$e->getCodeString(),
					'Invalid request raised correct error'
				);
			}
		}
	}

	/**
	 * @dataProvider invalidGuidProvider
	 */
	public function testInvalidClaimGuid( $claimGuid, $hash ) {
		$params = array(
			'action' => 'wbremovequalifiers',
			'claim' => $claimGuid,
			'qualifiers' => $hash,
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid claim guid did not throw an error' );
		} catch ( UsageException $e ) {
			$this->assertEquals(
				$e->getCodeString(),
				'invalid-guid',
				'Invalid claim guid raised correct error'
			);
		}
	}

	public function invalidGuidProvider() {
		$qualifierSnak = new PropertyValueSnak( 722, new StringValue( 'abc') );
		$hash = $qualifierSnak->getHash();

		return array(
			array( 'xyz', $hash ),
			array( 'x$y$z', $hash )
		);
	}

}
