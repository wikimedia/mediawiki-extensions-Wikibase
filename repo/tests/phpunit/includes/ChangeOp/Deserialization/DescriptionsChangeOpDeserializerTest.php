<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DescriptionsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenDescriptionsFieldNotAnArray_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newDescriptionsChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'descriptions' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newDescriptionsChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
			},
			'inconsistent-language'
		);
	}

	public function testGivenChangeRequestWithNewDescription_callsNewSetDescriptionOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetDescriptionOp' )
			->willReturn( new ChangeOps() );

		$this->newDescriptionsChangeOpDeserializer( $factory )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveDescriptionOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveDescriptionOp' )
			->willReturn( new ChangeOps() );

		$this->newDescriptionsChangeOpDeserializer( $factory )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );
	}

	private function newDescriptionsChangeOpDeserializer(
		FingerprintChangeOpFactory $factory
	) {
		return new DescriptionsChangeOpDeserializer(
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
