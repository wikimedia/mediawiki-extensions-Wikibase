<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
class ParserOutputJsConfigBuilder {

	/**
	 * @param EntityDocument $entity
	 *
	 * @return array
	 */
	public function build( EntityDocument $entity ) {
		global $wgEditSubmitButtonLabelPublish;

		$entityId = $entity->getId();

		if ( !$entityId ) {
			$entityId = ''; //XXX: should probably throw an exception
		} else {
			$entityId = $entityId->getSerialization();
		}

		$configVars = [
			'wbEntityId' => $entityId,
			'wgEditSubmitButtonLabelPublish' => $wgEditSubmitButtonLabelPublish,
		];

		return $configVars;
	}

}
