<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	use LabelsChangeOpDeserializationTester;

	public function testGivenLabelsFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deserializer = $this->newLabelsChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deserializer->createEntityChangeOp( [ 'labels' => null ] );
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

				$deserializer = $this->newLabelsChangeOpDeserializer( $validator );
				$deserializer->createEntityChangeOp( [
					'labels' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ],
				] );
			},
			'test-error'
		);
	}

	private function newLabelsChangeOpDeserializer( TermChangeOpSerializationValidator $validator ) {
		return new LabelsChangeOpDeserializer(
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
		return $this->newLabelsChangeOpDeserializer( $this->getTermChangeOpValidator() );
	}

	public function getEntity() {
		return new Item();
	}

}
