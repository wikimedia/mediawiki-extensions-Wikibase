<?php

namespace Wikibase\Test;
use Wikibase\Repo\Hook\LabelPrefetchHookHandlers;

/**
 * @covers Wikibase\Repo\Hook\LabelPrefetchHookHandlers
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class LabelPrefetchHookHandlersTest extends \MediaWikiTestCase {

	private function getLabelPrefetchHookHandlers() {

		return new LabelPrefetchHookHandlers(
		);

	}

	public function testDoOnLinkBegin() {
		$linkBeginHookHandler = $this->getLabelPrefetchHookHandlers();

		$linkBeginHookHandler->doChangesListInitRows( );
	}

}
