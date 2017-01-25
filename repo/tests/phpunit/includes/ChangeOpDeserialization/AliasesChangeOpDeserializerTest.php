<?php

namespace Wikibase\Repo\Tests\ChangeOpDeserialization;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOpDeserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOpDeserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ChangeOpDeserialization\AliasesChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class AliasesChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpDeserializationTestHelper
	 */
	private $testHelper;

	public function setUp() {
		$this->testHelper = new ChangeOpDeserializationTestHelper( $this );
	}

	public function testGivenAliasesFieldNotAnArray_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'not-recognized-array' );
		$this->newAliasesChangeOpDeserializer(
			$this->testHelper->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'aliases' => null ] );
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'inconsistent-language' );
		$this->newAliasesChangeOpDeserializer(
			$this->testHelper->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithNewAliase_callsNewSetAliaseOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $this->testHelper->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'aliases' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveAliasesOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $this->testHelper->getApiErrorReporter( false ), $factory )
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

		$this->newAliasesChangeOpDeserializer( $this->testHelper->getApiErrorReporter( false ), $factory )
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
			$factory,
			$this->getStringNormalizer(),
			$this->getTermChangeOpValidator( $errorReporter ),
			$errorReporter
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

}
