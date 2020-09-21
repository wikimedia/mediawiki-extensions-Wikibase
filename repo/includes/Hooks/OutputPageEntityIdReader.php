<?php

namespace Wikibase\Repo\Hooks;

use OutputPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;

/**
 * Allows retrieving an EntityId based on a previously propagated OutputPage.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageEntityIdReader {

	/**
	 * @var OutputPageEntityViewChecker
	 */
	private $entityViewChecker;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( OutputPageEntityViewChecker $entityViewChecker, EntityIdParser $entityIdParser ) {
		$this->entityViewChecker = $entityViewChecker;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdFromOutputPage( OutputPage $out ) {
		if ( !$this->entityViewChecker->hasEntityView( $out ) ) {
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
