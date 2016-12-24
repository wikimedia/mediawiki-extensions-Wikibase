<?php

namespace Wikibase\Repo\Tests\Specials\HTMLForm;

use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;

class HTMLContentLanguageFieldTest extends \PHPUnit_Framework_TestCase {

	private static $DEFAULT_REQUIRED_PARAMETERS = [
		'fieldname' => 'some-name',
	];

	public function __construct(
		$name = null,
		array $data = [],
		$dataName = ''
	) {
		parent::__construct( $name, $data, $dataName );

		$this->backupGlobals = false;
		$this->backupStaticAttributes = false;
	}

	/**
	 * @dataProvider provideVariantsToDefineOptionsForTheField
	 */
	public function testDoesNotAllowToSetOptions_WhenCreated( $params ) {

		$this->setExpectedException( \InvalidArgumentException::class );
		$this->createField( $params );
	}

	public function provideVariantsToDefineOptionsForTheField() {
		return [
			'options' => [
				[
					'options' => [],
				],
			],
			'options-messages' => [
				[
					'options-messages' => [],
				],
			],
			'options-message' => [
				[
					'options-message' => [],
				],
			],

		];
	}

	public function testSetsDefaultValueToLanguageFromParentElement_WhenCreatedAndDefaultIsNotDefined() {
		$params = [
			'parent' => $this->createNewContextSourceWithLanguage( 'some-language' ),
		];

		$field = $this->createField( $params );

		self::assertEquals( 'some-language', $field->getDefault() );
	}

	public function testUsesDefaultValue_WhenDefaultIsDefined() {
		$params = [
			'default' => 'default-language',
		];

		$field = $this->createField( $params );

		self::assertEquals( 'default-language', $field->getDefault() );
	}

	private function createNewContextSourceWithLanguage( $langCode ) {
		$mock = $this->getMock( \IContextSource::class );

		$language = new \Language();
		$language->setCode( $langCode );

		$mock->method( 'getLanguage' )->willReturn( $language );

		return $mock;
	}

	/**
	 * @param $params
	 * @return HTMLContentLanguageField
	 */
	protected function createField( $params ) {
		$params = array_merge(
			self::$DEFAULT_REQUIRED_PARAMETERS,
			$params
		);

		return new HTMLContentLanguageField( $params );
	}

}
