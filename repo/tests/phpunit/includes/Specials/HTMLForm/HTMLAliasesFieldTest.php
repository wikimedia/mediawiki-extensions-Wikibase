<?php

namespace Wikibase\Repo\Tests\Specials\HTMLForm;

use Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField;

class HTMLAliasesFieldTest extends \MediaWikiTestCase {

	public function testThrowsExceptionIfFilterCallbackParameterIsSet_WhenCreated() {
		$this->setExpectedException( \Exception::class );

		new HTMLAliasesField(
			[
				'filter-callback' => function () {
				},
			]
		);
	}

	public function testConvertsToArrayAndRemovesExtraSpeces_WhenFilters() {
		$field = new HTMLAliasesField( [] );

		$result = $field->filter( ' a | b ', [] );

		self::assertEquals( [ 'a', 'b' ], $result );
	}

}
