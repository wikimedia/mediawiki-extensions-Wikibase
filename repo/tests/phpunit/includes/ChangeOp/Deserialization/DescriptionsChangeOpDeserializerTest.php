<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DescriptionsChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	use DescriptionsChangeOpDeserializationTester;

	public function testGivenDescriptionsFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deseralizer = $this->newDescriptionsChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deseralizer->createEntityChangeOp( [ 'descriptions' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidChangeRequest_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$validator = $this->createMock( TermChangeOpSerializationValidator::class );

				$validator->method( $this->anything() )
					->willThrowException(
						new ChangeOpDeserializationException( 'invalid serialization', 'test-error' )
					);

				$deserializer = $this->newDescriptionsChangeOpDeserializer( $validator );
				$deserializer->createEntityChangeOp( [
					'descriptions' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ],
				] );
			},
			'test-error'
		);
	}

	private function newDescriptionsChangeOpDeserializer( TermChangeOpSerializationValidator $validator ) {
		return new DescriptionsChangeOpDeserializer(
			$this->getFingerPrintChangeOpFactory(),
			$this->getStringNormalizer(),
			$validator
		);
	}

	private function getStringNormalizer() {
		return new StringNormalizer();
	}

	private function getFingerPrintChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new FingerprintChangeOpFactory( $mockProvider->getMockTermValidatorFactory() );
	}

	private function getTermChangeOpValidator() {
		return new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en' ] ) );
	}

	public function getChangeOpDeserializer() {
		return $this->newDescriptionsChangeOpDeserializer( $this->getTermChangeOpValidator() );
	}

	public function getEntity() {
		return new Item();
	}

}
