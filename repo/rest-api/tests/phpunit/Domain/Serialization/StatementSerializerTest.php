<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serialization;

use DataValues\Serializers\DataValueSerializer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Serialization\StatementSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Serialization\StatementSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementSerializerTest extends TestCase {

	public function testMinimalStatementContainsRequiredFields(): void {
		$statement = NewStatement::noValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();

		$serialization = $this->newSerializer()->serialize( $statement );

		$requiredFieldsWithExpectedValues = [
			'id' => $statement->getGuid(),
			'mainsnak' => [
				'snaktype' => 'novalue',
				'property' => $statement->getMainSnak()->getPropertyId(),
				'hash' => $statement->getMainSnak()->getHash(),
			],
			'rank' => 'normal',
			'qualifiers' => (object)[],
			'qualifiers-order' => [],
			'references' => [],
		];

		foreach ( $requiredFieldsWithExpectedValues as $field => $value ) {
			$this->assertArrayHasKey( $field, $serialization );
			$this->assertEquals( $value, $serialization[$field] );
		}
	}

	public function testEmptyFieldDefaultsGetOverridden(): void {
		$qualifierPropertyId = 'P321';
		$statement = NewStatement::noValueFor( 'P123' )
			->withQualifier( $qualifierPropertyId, 'Q666' )
			->build();
		$statement->setReferences( new ReferenceList( [
			new Reference( [ new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) ] )
		] ) );

		$serialization = $this->newSerializer()->serialize( $statement );

		$this->assertObjectHasAttribute( $qualifierPropertyId, $serialization['qualifiers'] );
		$this->assertNotEmpty( $serialization['qualifiers-order'] );
		$this->assertNotEmpty( $serialization['references'] );
	}

	private function newSerializer(): StatementSerializer {
		return new StatementSerializer( ( new SerializerFactory(
			new DataValueSerializer(), SerializerFactory::OPTION_OBJECTS_FOR_MAPS )
		)->newStatementSerializer() );
	}

}
