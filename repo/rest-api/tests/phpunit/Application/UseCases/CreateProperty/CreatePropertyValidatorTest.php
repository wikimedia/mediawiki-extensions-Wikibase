<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\CreateProperty;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CreatePropertyValidatorTest extends TestCase {

	private PropertyDeserializer $propertyDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyDeserializer = $this->createStub( PropertyDeserializer::class );
	}

	public function testGivenValidRequest_returnsDeserializedRequest(): void {
		$propertySerialization = [ 'data_type' => 'string' ];
		$request = new CreatePropertyRequest( $propertySerialization, [], false, null, null );

		$expectedProperty = $this->createStub( Property::class );
		$this->propertyDeserializer = $this->createMock( PropertyDeserializer::class );
		$this->propertyDeserializer->expects( $this->once() )
			->method( 'deserialize' )
			->with( $propertySerialization )
			->willReturn( $expectedProperty );

		$this->assertSame( $expectedProperty, $this->newValidator()->validateAndDeserialize( $request )->getProperty() );
	}

	private function newValidator(): CreatePropertyValidator {
		return new CreatePropertyValidator( $this->propertyDeserializer );
	}
}
