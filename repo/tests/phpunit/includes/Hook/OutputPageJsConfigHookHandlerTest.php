<?php

namespace Wikibase\Test;

use OutputPage;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Hook\OutputPageJsConfigHookHandler;
use Wikibase\Settings;

/**
 * @covers Wikibase\Hook\OutputPageJsConfigHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigHookHandlerTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( array $expected, array $parserConfig, Settings $settings ) {
		$hookHandler = new OutputPageJsConfigHookHandler(
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock(),
			$settings
		);

		$context = RequestContext::getMain();
		$output = $context->getOutput();

		$output->addJsConfigVars( $parserConfig );

		$hookHandler->handle( $output );

		$configVarsAfter = $output->getJsConfigVars();

		$this->assertEquals( $expected, array_keys( $configVarsAfter ) );
	}

	public function handleProvider() {
		$settings = new Settings();
		$settings->setSetting( 'dataRightsUrl', 'https://creativecommons.org' );
		$settings->setSetting( 'dataRightsText', 'CC-0' );

		$parserConfig = array(
			'wbEntityId' => 'Q4'
		);

		$expected = array(
			'wbEntityId',
			'wbUserIsBlocked',
			'wbUserCanEdit',
			'wbCopyright'
		);

		return array(
			array( $expected, $parserConfig, $settings )
		);
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	public function getTitleForId( EntityId $entityId ) {
		$name = $entityId->getEntityType() . ':' . $entityId->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookupMock() {
		$lookup = $this->getMockBuilder( 'Wikibase\EntityTitleLookup' )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

}
