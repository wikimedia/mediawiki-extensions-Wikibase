<?php

namespace Wikibase\Repo\Tests\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\Repo\Validators\InterWikiLinkExistsValidator;
use MediaWiki\Site\MediaWikiPageNameNormalizer;

/**
 * @covers Wikibase\Repo\Validators\InterWikiLinkExistsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jonas Kress
 */
class InterWikiLinkExistsValidatorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	const EXISTING_PAGE = "Foo.map";
	const NONEXISTENT_PAGE = "Foo.NOT-FOUND.map";

	/**
	 * @return MediaWikiPageNameNormalizer
	 */
	private function getMediaWikiPageNameNormalizer() {
		$pageNormalizer = $this->getMockBuilder( MediaWikiPageNameNormalizer::class )
			->disableOriginalConstructor()
			->getMock();

		$pageNormalizer->method( 'normalizePageName' )
			->with( $this->isType( 'string' ), $this->anything() )
			->will( $this->returnCallback( function( $pageName ) {
				return $pageName === self::EXISTING_PAGE ? $pageName : false;
			} ) );

		return $pageNormalizer;
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $expected, $value ) {
		$validator = new InterWikiLinkExistsValidator(
			$this->getMediaWikiPageNameNormalizer(),
			'http://does-not.matter'
		);

		$this->assertSame(
			$expected,
			$validator->validate( $value )->isValid()
		);
	}

	public function provideValidate() {
		return [
			"Valid, plain string" => [
				true, self::EXISTING_PAGE
			],
			"Valid, StringValue" => [
				true, new StringValue( self::EXISTING_PAGE )
			],
			"Invalid, StringValue" => [
				false, new StringValue( self::NONEXISTENT_PAGE )
			]
		];
	}

	public function testValidate_noString() {
		$validator = new InterWikiLinkExistsValidator(
			$this->getMediaWikiPageNameNormalizer(),
			'http://does-not.matter/'
		);

		$this->setExpectedException( InvalidArgumentException::class );
		$validator->validate( 5 );
	}

}
