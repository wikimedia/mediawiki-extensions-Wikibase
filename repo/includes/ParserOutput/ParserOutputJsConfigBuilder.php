<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
class ParserOutputJsConfigBuilder {

	public function build( EntityDocument $entity, ParserOutput $parserOutput ): void {
		global $wgEditSubmitButtonLabelPublish;

		$entityId = $entity->getId();

		if ( !$entityId ) {
			$entityId = ''; //XXX: should probably throw an exception
		} else {
			$entityId = $entityId->getSerialization();
		}

		$parserOutput->setJsConfigVar( 'wbEntityId', $entityId );
		$parserOutput->setJsConfigVar( 'wgEditSubmitButtonLabelPublish', $wgEditSubmitButtonLabelPublish );
	}

}
