<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use UsageException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\RemoveReferences
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group RemoveReferencesTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RemoveReferencesTest extends WikibaseApiTestCase {

	/**
	 * @return Snak[]
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
			$item = Item::newEmpty();

			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

			$guidGenerator = new ClaimGuidGenerator();
			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$item->addClaim( $statement );

			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

			$references = $statement->getReferences();

			$hashes = array_map(
				function( Reference $reference ) {
					return $reference->getHash();
				},
				iterator_to_array( $references )
			);

			$this->assertInternalType( 'string', $statement->getGuid() );

			if ( count( $references ) === 0 ) {
				$this->makeInvalidRequest(
					$statement->getGuid(),
					array( '~=[,,_,,]:3' ),
					'no-such-reference'
				);
			}
			else {
				$this->makeValidRequest(
					$statement->getGuid(),
					$hashes
				);
			}
		}
	}

	protected function makeValidRequest( $statementGuid, array $hashes ) {
		$params = array(
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => implode( '|', $hashes ),
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );

		$this->makeInvalidRequest( $statementGuid, $hashes, 'no-such-reference' );
	}

	protected function makeInvalidRequest( $statementGuid, array $hashes, $expectedError = null ) {
		$params = array(
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => implode( '|', $hashes ),
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
		} catch ( UsageException $e ) {
			if ( $expectedError === null ) {
				$this->assertTrue( true, 'Invalid request raised error' );
			} else {
				$this->assertEquals( $expectedError, $e->getCodeString(), 'Invalid request raised correct error' );
			}
		}
	}

	/**
	 * @dataProvider invalidGuidProvider
	 */
	public function testInvalidStatementGuid( $statementGuid, $hash ) {
		$params = array(
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => $hash,
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid claim guid did not throw an error' );
		} catch ( UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(),  'Invalid claim guid raised correct error' );
		}
	}

	public function invalidGuidProvider() {
		$snak = new PropertyValueSnak( 722, new \DataValues\StringValue( 'abc') );
		$hash = $snak->getHash();

		return array(
			array( 'xyz', $hash ),
			array( 'x$y$z', $hash )
		);
	}

}
