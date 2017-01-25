<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOpDeserializers\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\Validators\TermChangeOpSerializationValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers DescriptionsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DescriptionsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenDescriptionsFieldNotAnArray_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'not-recognized-array' );
		$this->newDescriptionsChangeOpDeserializer(
			$this->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'descriptions' => null ] );
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'inconsistent-language' );
		$this->newDescriptionsChangeOpDeserializer(
			$this->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithNewDescription_callsNewSetDescriptionOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetDescriptionOp' )
			->willReturn( new ChangeOps() );

		$this->newDescriptionsChangeOpDeserializer( $this->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveDescriptionOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveDescriptionOp' )
			->willReturn( new ChangeOps() );

		$this->newDescriptionsChangeOpDeserializer( $this->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'remove' => true ] ] ] );
	}

	private function newDescriptionsChangeOpDeserializer(
		ApiErrorReporter $errorReporter,
		FingerprintChangeOpFactory $factory
	) {
		return new DescriptionsChangeOpDeserializer(
			$errorReporter,
			$factory,
			$this->getStringNormalizer(),
			$this->getTermChangeOpValidator( $errorReporter )
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

	private function getTermChangeOpValidator( ApiErrorReporter $errorReporter ) {
		return new TermChangeOpSerializationValidator(
			$errorReporter,
			WikibaseRepo::getDefaultInstance()->getTermsLanguages()
		);
	}

	/**
	 * TODO: Refactor into mock class or test helper
	 *
	 * @param bool $expectsError
	 *
	 * @return ApiErrorReporter
	 */
	private function getApiErrorReporter( $expectsError = false ) {
		$errorReporter = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		if ( !$expectsError ) {
			$errorReporter->expects( $this->never() )
				->method( 'dieError' );
		} else {
			$errorReporter->expects( $this->once() )
				->method( 'dieError' )
				->willReturnCallback( function( $description, $errorCode ) {
					throw new RuntimeException( $errorCode );
				} );
		}

		return $errorReporter;
	}

}
