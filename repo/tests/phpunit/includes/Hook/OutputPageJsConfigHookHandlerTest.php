<?php

namespace Wikibase\Test;

use RequestContext;
use Title;
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
class OutputPageJsConfigHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( array $expected, Title $title, Settings $settings, $experimental,
		$message
	) {
		$hookHandler = new OutputPageJsConfigHookHandler( $settings );

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();

		$hookHandler->handle( $output, $experimental );

		$configVars = $output->getJsConfigVars();

		$this->assertEquals( $experimental, $configVars['wbExperimentalFeatures'], 'experimental' );
		$this->assertEquals( $expected, array_keys( $configVars ), $message );
	}

	public function handleProvider() {
		$settings = $this->getSettings();

		$expected = array(
			'wbUserIsBlocked',
			'wbUserCanEdit',
			'wbCopyright',
			'wbBadgeItems',
			'wbExperimentalFeatures'
		);

		$entityId = new ItemId( 'Q4' );
		$title = $this->getTitleForId( $entityId );

		return array(
			array( $expected, $title, $settings, true, 'config vars added to OutputPage' )
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
		$name = $entityId->getEntityType() . ':' . $entityId->getSerialization();
		return Title::makeTitle( NS_MAIN, $name );
	}

}
