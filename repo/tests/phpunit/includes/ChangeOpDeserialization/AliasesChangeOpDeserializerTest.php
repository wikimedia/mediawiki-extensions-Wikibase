<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOpDeserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOpDeserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers AliasesChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class AliasesChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenAliasesFieldNotAnArray_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'not-recognized-array' );
		$this->newAliasesChangeOpDeserializer(
			$this->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'aliases' => null ] );
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'inconsistent-language' );
		$this->newAliasesChangeOpDeserializer(
			$this->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithNewAliase_callsNewSetAliaseOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $this->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'aliases' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveAliasesOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $this->getApiErrorReporter( false ), $factory )
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

		$this->newAliasesChangeOpDeserializer( $this->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp(
				[ 'aliases' => [
					'en' => [ 'language' => 'en', 'value' => 'foo', 'add' => '' ] ]
				]
			);
	}

	private function newAliasesChangeOpDeserializer(
		ApiErrorReporter $errorReporter,
		FingerprintChangeOpFactory $factory
	) {
		return new AliasesChangeOpDeserializer(
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
