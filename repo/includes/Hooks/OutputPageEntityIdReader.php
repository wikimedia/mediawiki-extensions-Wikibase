<?php

namespace Wikibase\Repo\Hooks;

use OutputPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\Content\EntityContentFactory;

/**
 * Allows retrieving an EntityId based on a previously propagated OutputPage.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageEntityIdReader {

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityContentFactory $entityContentFactory, EntityIdParser $entityIdParser ) {
		$this->entityContentFactory = $entityContentFactory;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdFromOutputPage( OutputPage $out ) {
		$title = $out->getTitle();
		if ( !$title || !$this->entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			return null;
		}

		$jsConfigVars = $out->getJsConfigVars();

		if ( array_key_exists( 'wbEntityId', $jsConfigVars ) ) {
			$idString = $jsConfigVars['wbEntityId'];

			try {
				return $this->entityIdParser->parse( $idString );
			} catch ( EntityIdParsingException $ex ) {
				wfLogWarning( 'Failed to parse EntityId config var: ' . $idString );
			}
		}

		return null;
	}

}
