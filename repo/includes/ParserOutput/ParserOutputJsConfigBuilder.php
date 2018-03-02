<?php

namespace Wikibase\Repo\ParserOutput;

use FormatJson;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
class ParserOutputJsConfigBuilder {

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	public function __construct( Serializer $entitySerializer ) {
		$this->entitySerializer = $entitySerializer;
	}

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
			'wbEntity' => FormatJson::encode( $this->getSerializedEntity( $entity ) ),
			'wgEditSubmitButtonLabelPublish' => $wgEditSubmitButtonLabelPublish,
		];

		return $configVars;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string
	 */
	private function getSerializedEntity( EntityDocument $entity ) {
		return $this->entitySerializer->serialize( $entity );
	}

}
