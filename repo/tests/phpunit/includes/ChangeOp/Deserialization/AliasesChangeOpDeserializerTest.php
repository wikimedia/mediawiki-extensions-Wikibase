<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class AliasesChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase implements ChangeOpDeserializerTest {

	use AliasChangeOpDeserializationTest;

	public function testGivenAliasesFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deserializer = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deserializer->createEntityChangeOp( [ 'aliases' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$validator = $this->getMockBuilder( TermChangeOpSerializationValidator::class )
					->disableOriginalConstructor()
					->getMock();

				$validator->method( $this->anything() )
					->will(
						$this->throwException(
							new ChangeOpDeserializationException( 'invalid serialization', 'test-error' )
						)
					);

				$deserializer = $this->newAliasesChangeOpDeserializer( $validator );
				$deserializer->createEntityChangeOp( [
					'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ]
				] );
			},
			'test-error'
		);
	}

	private function newAliasesChangeOpDeserializer( TermChangeOpSerializationValidator $validator ) {
		return new AliasesChangeOpDeserializer(
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
		return $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() );
	}
}
