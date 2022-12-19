<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AliasesChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	use AliasChangeOpDeserializationTester;

	public function testGivenAliasesFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deserializer = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deserializer->createEntityChangeOp( [ 'aliases' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function provideInvalidAliasesArray() {
		return [
			[ null ],
			[ false ],
			[ 1 ],
			[ 'alias1|alias2' ],
		];
	}

	/**
	 * @dataProvider provideInvalidAliasesArray
	 */
	public function testGivenAliasesForLanguageNotAnArray_createEntityChangeOpThrowsError( $aliases ) {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function () use ( $aliases ) {
				$deserializer = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deserializer->createEntityChangeOp( [ 'aliases' => [ 'en' => $aliases ] ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$validator = $this->createMock( TermChangeOpSerializationValidator::class );

				$validator->method( $this->anything() )
					->willThrowException(
						new ChangeOpDeserializationException( 'invalid serialization', 'test-error' )
					);

				$deserializer = $this->newAliasesChangeOpDeserializer( $validator );
				$deserializer->createEntityChangeOp( [
					'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ],
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

	public function getEntity() {
		return new Item();
	}

}
