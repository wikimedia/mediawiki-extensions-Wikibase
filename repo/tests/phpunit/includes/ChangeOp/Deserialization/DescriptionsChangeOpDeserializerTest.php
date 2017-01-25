<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DescriptionsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenDescriptionsFieldNotAnArray_createEntityChangeOpThrowsError() {
		try {
			$this->newDescriptionsChangeOpDeserializer(
				$this->getFingerPrintChangeOpFactory()
			)->createEntityChangeOp( [ 'descriptions' => null ] );
		} catch ( ChangeOpDeserializationException $exception ) {
			$this->assertSame( 'not-recognized-array', $exception->getErrorCode() );
		}
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		try {
			$this->newDescriptionsChangeOpDeserializer(
				$this->getFingerPrintChangeOpFactory()
			)->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
		} catch ( ChangeOpDeserializationException $exception ) {
			$this->assertSame( 'inconsistent-language', $exception->getErrorCode() );
		}
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
		return WikibaseRepo::getDefaultInstance()->getStringNormalizer();
	}

	private function getFingerPrintChangeOpFactory() {
		return $this->getMockBuilder( FingerprintChangeOpFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getTermChangeOpValidator() {
		return new TermChangeOpSerializationValidator(
			WikibaseRepo::getDefaultInstance()->getTermsLanguages()
		);
	}

}
