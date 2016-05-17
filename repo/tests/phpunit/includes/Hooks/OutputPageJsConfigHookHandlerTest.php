<?php

namespace Wikibase\Repo\Tests\Hooks;

use MediaWikiTestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler;

/**
 * @covers Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch
 */
class OutputPageJsConfigHookHandlerTest extends MediaWikiTestCase {

	/**
	 * @dataProvider doOutputPageBeforeHtmlRegisterConfigProvider
	 */
	public function testDoOutputPageBeforeHtmlRegisterConfig( array $expected, Title $title, $message ) {
		$entityNamespaceLookup = new EntityNamespaceLookup( array( $title->getNamespace() ) );

		$hookHandler = new OutputPageJsConfigHookHandler( $entityNamespaceLookup, 'https://creativecommons.org', 'CC-0', array() );

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();

		$hookHandler->doOutputPageBeforeHtmlRegisterConfig( $output );

		$configVars = $output->getJsConfigVars();

		$this->assertEquals( $expected, array_keys( $configVars ), $message );
	}

	public function doOutputPageBeforeHtmlRegisterConfigProvider() {
		$expected = array(
			'wbUserIsBlocked',
			'wbUserCanEdit',
			'wbCopyright',
			'wbBadgeItems'
		);

		$entityId = new ItemId( 'Q4' );
		$title = $this->getTitleForId( $entityId );

		return array(
			array( $expected, $title, true, 'config vars added to OutputPage' )
		);
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
