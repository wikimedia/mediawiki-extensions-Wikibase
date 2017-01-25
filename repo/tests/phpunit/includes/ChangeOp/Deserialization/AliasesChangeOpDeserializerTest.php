<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class AliasesChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenAliasesFieldNotAnArray_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newAliasesChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'aliases' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newAliasesChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
			},
			'inconsistent-language'
		);
	}

	public function testGivenChangeRequestWithNewAliases_callsNewSetAliasesOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $factory )
			->createEntityChangeOp( [ 'aliases' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveAliasesOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $factory )
			->createEntityChangeOp(
				[ 'aliases' => [
					'en' => [ 'language' => 'en', 'value' => 'foo', 'remove' => '' ] ]
				]
			);
	}

	public function testGivenChangeRequestWithAdd_callsNewRemoveAliasesOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newAddAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $factory )
			->createEntityChangeOp(
				[ 'aliases' => [
					'en' => [ 'language' => 'en', 'value' => 'foo', 'add' => '' ] ]
				]
			);
	}

	private function newAliasesChangeOpDeserializer(
		FingerprintChangeOpFactory $factory
	) {
		return new AliasesChangeOpDeserializer(
			$factory,
			$this->getStringNormalizer(),
			$this->getTermChangeOpValidator()
		);
	}

	private function getStringNormalizer() {
		return new StringNormalizer();
	}

	private function getFingerPrintChangeOpFactory() {
		return $this->getMockBuilder( FingerprintChangeOpFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getTermChangeOpValidator() {
		return new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en', 'de' ] ) );
	}

}
