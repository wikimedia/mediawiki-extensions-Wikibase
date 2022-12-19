<?php

namespace Wikibase\Repo\Tests\Validators;

use Article;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Repo\Validators\WikiLinkExistsValidator;
use WikitextContent;

/**
 * @covers \Wikibase\Repo\Validators\WikiLinkExistsValidator
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikiLinkExistsValidatorTest extends MediaWikiIntegrationTestCase {
	private const EXISTING_PAGE = "Foo";
	private const NONEXISTENT_PAGE = "Foo.NOT-FOUND";

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $expected, $value ) {
		$title = Title::makeTitle( NS_PROJECT, self::EXISTING_PAGE );

		$article = new Article( $title );
		$page = $article->getPage();
		$page->doUserEditContent(
			new WikitextContent( 'Some [[link]]' ),
			$this->getTestUser()->getUser(),
			'summary'
		);

		$validator = new WikiLinkExistsValidator( NS_PROJECT );

		$this->assertSame(
			$expected,
			$validator->validate( $value )->isValid()
		);
	}

	public function provideValidate() {
		return [
			"Valid, plain string" => [
				true, self::EXISTING_PAGE,
			],
			"Valid, StringValue" => [
				true, new StringValue( self::EXISTING_PAGE ),
			],
			"Invalid, StringValue" => [
				false, new StringValue( self::NONEXISTENT_PAGE ),
			],
		];
	}

	public function testValidate_noString() {
		$validator = new WikiLinkExistsValidator( 1 );

		$this->expectException( InvalidArgumentException::class );
		$validator->validate( 5 );
	}

}
