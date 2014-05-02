<?php

namespace Wikibase\Test;

use Message;
use RuntimeException;
use Wikibase\Badge\BadgeException;
use Wikibase\Repo\Localisation\WikibaseRepoExceptionLocalizer;

/**
 * @covers Wikibase\Repo\Localisation\WikibaseRepoExceptionLocalizer
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseRepoExceptionLocalizerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getExceptionMessageProvider
	 */
	public function testGetExceptionMessage( $expected, $exception ) {
		$exceptionLocalizer = new WikibaseRepoExceptionLocalizer(
			$this->getMessageParameterFormatter()
		);

		$result = $exceptionLocalizer->getExceptionMessage( $exception );

		$this->assertContains( $expected, $result->inLanguage( 'qqx' )->escaped() );
	}

	public function getExceptionMessageProvider() {
		return array(
			array(
				'wikibase-invalid-badge',
				new BadgeException( 'wikibase-invalid-badge', 'P9000', 'invalid badge' )
			),
			array(
				'wikibase-error-unexpected',
				new RuntimeException( 'ohnoeeesz!' )
			)
		);

	}

	private function getMessageParameterFormatter() {
		$messageParamFormatter = $this->getMockBuilder( 'Wikibase\i18n\MessageParameterFormatter' )
			->disableOriginalConstructor()
			->getMock();

		return $messageParamFormatter;
	}

}
