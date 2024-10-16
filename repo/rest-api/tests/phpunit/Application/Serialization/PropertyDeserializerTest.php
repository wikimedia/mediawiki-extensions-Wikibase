<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDeserializerTest extends TestCase {

	private StatementDeserializer $statementDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$this->statementDeserializer = $this->createMock( StatementDeserializer::class );
	}

	public function testDeserializeValidInput(): void {
		$inputData = [
			'labels' => [ 'en' => 'Label' ],
			'descriptions' => [ 'en' => 'Description' ],
			'aliases' => [ 'en' => [ 'Alias1', 'Alias2' ] ],
			'data_type' => 'string',
			'statements' => [
				'P1' => [
					[
						'property' => [ 'id' => 'P1' ],
						'value' => [ 'type' => 'somevalue' ],
					],
				],
			],
		];

		$newStatement = NewStatement::someValueFor( 'P1' )->build();

		$this->statementDeserializer
			->method( 'deserialize' )
			->willReturn( $newStatement );

		$result = $this->newDeserializer()->deserialize( $inputData );

		$this->assertInstanceOf( Property::class, $result );

		$this->assertEquals( 'string', $result->getDataTypeId() );
		$this->assertEquals(
			new Fingerprint(
				new TermList( [ new Term( 'en', 'Label' ) ] ),
				new TermList( [ new Term( 'en', 'Description' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'Alias1', 'Alias2' ] ) ] )
			),
			$result->getFingerprint()
		);
		$this->assertEquals( new StatementList( $newStatement ), $result->getStatements() );
	}

	public function testDeserializeEmptyProperty(): void {
		$this->assertEquals(
			new Property( null, null, 'string' ),
			$this->newDeserializer()->deserialize( [ 'data_type' => 'string' ] )
		);
	}

	private function newDeserializer(): PropertyDeserializer {
		return new PropertyDeserializer(
			new LabelsDeserializer(),
			new DescriptionsDeserializer(),
			new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
			$this->statementDeserializer
		);
	}
}
