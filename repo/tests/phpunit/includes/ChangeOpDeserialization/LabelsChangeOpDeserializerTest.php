<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOpDeserialization\LabelsChangeOpDeserializer;
use Wikibase\Repo\ChangeOpDeserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers LabelsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class LabelsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenLabelsFieldNotAnArray_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'not-recognized-array' );
		$this->newLabelsChangeOpDeserializer(
			$this->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'labels' => null ] );
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'inconsistent-language' );
		$this->newLabelsChangeOpDeserializer(
			$this->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithNewLabels_callsNewSetLabelOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetLabelOp' )
			->willReturn( new ChangeOps() );

		$this->newLabelsChangeOpDeserializer( $this->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveLabelOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveLabelOp' )
			->willReturn( new ChangeOps() );

		$this->newLabelsChangeOpDeserializer( $this->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );
	}

	private function newLabelsChangeOpDeserializer(
		ApiErrorReporter $errorReporter,
		FingerprintChangeOpFactory $factory
	) {
		return new LabelsChangeOpDeserializer(
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
			WikibaseRepo::getDefaultInstance()->getTermsLanguages(),
			$errorReporter
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
