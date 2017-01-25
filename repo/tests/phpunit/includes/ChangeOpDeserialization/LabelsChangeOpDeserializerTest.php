<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use RuntimeException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOpDeserialization\LabelsChangeOpDeserializer;
use Wikibase\Repo\ChangeOpDeserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Tests\ChangeOpDeserialization\ChangeOpDeserializationTestHelper;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ChangeOpDeserialization\LabelsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class LabelsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpDeserializationTestHelper
	 */
	private $testHelper;

	public function setUp() {
		$this->testHelper = new ChangeOpDeserializationTestHelper( $this );
	}

	public function testGivenLabelsFieldNotAnArray_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'not-recognized-array' );
		$this->newLabelsChangeOpDeserializer(
			$this->testHelper->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'labels' => null ] );
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		$this->setExpectedException( RuntimeException::class, 'inconsistent-language' );
		$this->newLabelsChangeOpDeserializer(
			$this->testHelper->getApiErrorReporter( true ),
			$this->getFingerPrintChangeOpFactory()
		)->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithNewLabels_callsNewSetLabelOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetLabelOp' )
			->willReturn( new ChangeOps() );

		$this->newLabelsChangeOpDeserializer( $this->testHelper->getApiErrorReporter( false ), $factory )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveLabelOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveLabelOp' )
			->willReturn( new ChangeOps() );

		$this->newLabelsChangeOpDeserializer( $this->testHelper->getApiErrorReporter( false ), $factory )
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

}
