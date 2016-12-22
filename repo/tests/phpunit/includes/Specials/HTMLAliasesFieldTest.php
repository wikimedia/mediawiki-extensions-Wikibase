<?php

namespace Wikibase\Repo\Tests\Specials;

use Wikibase\Repo\Specials\HTMLAliasesField;

class HTMLAliasesFieldTest extends \PHPUnit_Framework_TestCase {

	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->backupGlobals = false;
		$this->backupStaticAttributes = false;
	}

	public function testThrowsExceptionIfFilterCallbackParameterIsSet_WhenCreated() {
		$this->setExpectedException( \Exception::class );

		new HTMLAliasesField(
			[
				'filter-callback' => function () { },
			]
		);
	}

	public function testConvertsToArrayAndRemovesExtraSpeces_WhenFilters() {
		$field = new HTMLAliasesField([]);

		$result = $field->filter( ' a | b ' , []);

		self::assertEquals( [ 'a', 'b' ], $result );
	}

}
