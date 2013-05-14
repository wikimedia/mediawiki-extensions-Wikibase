<?php

namespace Wikibase\Test\Api;
use Wikibase\Reference;

/**
 * Unit tests for the Wikibase\ApiSetReference class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.3
 *
 * @ingroup WikibaseRepoTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetReferenceTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SetReferenceTest extends \ApiTestCase {

	// TODO: clean this up so more of the input space can easily be tested
	// semi-blocked by cleanup of GUID handling in claims
	// can perhaps tseal from RemoveReferencesTest
	public function testRequests() {
		$item = \Wikibase\Item::newEmpty();
		$content = new \Wikibase\ItemContent( $item );
		$content->save( '', null, EDIT_NEW );

		/**
		 * @var \Wikibase\Statement $statement
		 */
		$statement = $item->newClaim( new \Wikibase\PropertyNoValueSnak( 42 ) );

		$reference = new \Wikibase\Reference( new \Wikibase\SnakList(
			array( new \Wikibase\PropertySomeValueSnak( 1 ) )
		) );

		$statement->getReferences()->addReference( $reference );

		$item->addClaim( $statement );

		$content->save( '' );

		$referenceHash = $reference->getHash();

		$reference = new \Wikibase\Reference( new \Wikibase\SnakList(
			array( new \Wikibase\PropertyNoValueSnak( 42 ) )
		) );

		$this->makeValidRequest(
			$statement->getGuid(),
			$referenceHash,
			$reference
		);

		// Since the reference got modified, the hash should no longer match
		$this->makeInvalidRequest(
			$statement->getGuid(),
			$referenceHash,
			$reference
		);
	}

	protected function makeValidRequest( $statementGuid, $referenceHash, Reference $reference ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $reference );
		$serializedReference = $serializer->getSerialized( $reference );

		$params = array(
			'action' => 'wbsetreference',
			'statement' => $statementGuid,
			'reference' => $referenceHash,
			'snaks' => \FormatJson::encode( $serializedReference['snaks'] ),
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'reference', $resultArray, 'top level element has a reference key' );

		$serializedReference = $resultArray['reference'];

		unset( $serializedReference['lastrevid'] );
		$this->assertArrayEquals( $serializer->getSerialized( $reference ), $serializedReference );
	}

	protected function makeInvalidRequest( $statementGuid, $referenceHash, Reference $reference ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $reference );
		$serializedReference = $serializer->getSerialized( $reference );

		$params = array(
			'action' => 'wbsetreference',
			'statement' => $statementGuid,
			'reference' => $referenceHash,
			'snaks' => \FormatJson::encode( $serializedReference['snaks'] ),
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		try {
			$this->doApiRequest( $params );
			$this->assertFalse( true, 'Invalid request should raise an exception' );
		}
		catch ( \UsageException $e ) {
			$this->assertEquals( 'setreference-no-such-reference', $e->getCodeString(), 'Invalid request raised correct error' );
		}
	}

    /**
     * @dataProvider invalidClaimProvider
     */
    public function testInvalidClaimGuid( $claimGuid, $snakHash, $refHash ) {
        $caughtException = false;

        $params = array(
            'action' => 'wbsetreference',
            'statement' => $claimGuid,
			'snaks' => $snakHash,
			'reference' => $refHash,
            'token' => $GLOBALS['wgUser']->getEditToken()
        );

        try {
            $this->doApiRequest( $params );
        } catch ( \UsageException $e ) {
            $this->assertEquals( $e->getCodeString(), 'setreference-invalid-guid',  'Invalid claim guid raised correct error' );
            $caughtException = true;
        }

        $this->assertTrue( $caughtException, 'Exception was caught' );
    }

    public function invalidClaimProvider() {
        $snak = new \Wikibase\PropertyValueSnak( 722, new \DataValues\StringValue( 'abc') );
		$snakHash = $snak->getHash();

		$reference = new \Wikibase\PropertyValueSnak( 723, new \DataValues\StringValue( 'def' ) );
		$refHash = $reference->getHash();

		return array(
            array( 'xyz', $snakHash, $refHash ),
            array( 'x$y$z', $snakHash, $refHash )
        );
    }

}
