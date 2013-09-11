<?php

namespace Wikibase\Test;

use OutputPage;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
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
	public function testHandle( array $expected, EntityId $entityId, array $parserConfig,
		Settings $settings
	) {
		$hookHandler = new OutputPageJsConfigHookHandler(
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock(),
			$settings
		);

		$title = $this->getTitleForId( $entityId );

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();
		$output->addJsConfigVars( $parserConfig );

		$hookHandler->handle( $output );

		$configVarsAfter = $output->getJsConfigVars();

		$this->assertEquals( $expected, array_keys( $configVarsAfter ) );
	}

	public function handleProvider() {
		$settings = $this->getSettings();
		$entityId = new ItemId( 'Q4' );

		$parserConfig = array(
			'wbEntityId' => $entityId->getSerialization()
		);

		$expected = array(
			'wbEntityId',
			'wbUserIsBlocked',
			'wbUserCanEdit',
			'wbCopyright'
		);

		return array(
			array( $expected, $entityId, $parserConfig, $settings )
		);
	}

	/**
	 * @dataProvider handleOnNonEntityTitleProvider
	 */
	public function testHandleOnNonEntityTitle( $expected, $title, $parserConfig, $settings ) {
		$hookHandler = new OutputPageJsConfigHookHandler(
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock(),
			$settings
		);

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();
		$output->addJsConfigVars( $parserConfig );

		$hookHandler->handle( $output );
		$configVarsAfter = $output->getJsConfigVars();

		$this->assertEquals( $expected, $configVarsAfter );
	}

	public function handleOnNonEntityTitleProvider() {
		$title = Title::makeTitle( NS_HELP, 'Contents' );
		$parserConfig = array();
		$settings = $this->getSettings();

		return array(
			array( array(), $title, $parserConfig, $settings )
		);
	}

	/**
	 * @return Settings
	 */
	private function getSettings() {
		$settings = new Settings();
		$settings->setSetting( 'dataRightsUrl', 'https://creativecommons.org' );
		$settings->setSetting( 'dataRightsText', 'CC-0' );

		return $settings;
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
