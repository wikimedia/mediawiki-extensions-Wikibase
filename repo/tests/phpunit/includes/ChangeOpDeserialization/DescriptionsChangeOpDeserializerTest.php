<?php

namespace Wikibase\Repo\Tests\ChangeOpDeserialization;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOpDeserialization\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\ChangeOpDeserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ChangeOpDeserialization\DescriptionsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DescriptionsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpDeserializationTestHelper
	 */
	private $testHelper;

	public function setUp() {
		$this->testHelper = new ChangeOpDeserializationTestHelper( $this );
	}

	public function testGivenDescriptionsFieldNotAnArray_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'not-recognized-array' );
		$this->newDescriptionsChangeOpDeserializer(
			$this->testHelper->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'descriptions' => null ] );
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'inconsistent-language' );
		$this->newDescriptionsChangeOpDeserializer(
			$this->testHelper->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithNewDescription_callsNewSetDescriptionOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetDescriptionOp' )
			->willReturn( new ChangeOps() );

		$this->newDescriptionsChangeOpDeserializer( $this->testHelper->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveDescriptionOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveDescriptionOp' )
			->willReturn( new ChangeOps() );

		$this->newDescriptionsChangeOpDeserializer( $this->testHelper->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );
	}

	private function newDescriptionsChangeOpDeserializer(
		ApiErrorReporter $errorReporter,
		FingerprintChangeOpFactory $factory
	) {
		return new DescriptionsChangeOpDeserializer(
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
