<?php

namespace Wikibase\Repo\Specials;

use HttpError;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Special page for addressing entity data pages without knowing the namespace.
 *
 * Given local entity ID as a sub page, this page redirects to entity's page
 * while given the foreign entity ID, it redirects to SpecialEntityPage/{entity id without a prefix}
 * on the foreign repo's wiki, resulting in showing the entity page on the repository it belongs to.
 *
 * This allows wikis to link to an entity page without needing to know namespace names of entity types
 * configured on the target wiki.
 *
 * @license GPL-2.0+
 */
class SpecialEntityPage extends SpecialWikibasePage {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	public function __construct( EntityIdParser $entityIdParser, EntityTitleLookup $entityTitleLookup ) {
		parent::__construct( 'EntityPage' );

		$this->entityIdParser = $entityIdParser;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 *
	 * @throws HttpError
	 */
	public function execute( $subPage ) {
		$id = (string)$subPage;
		$id = $this->getRequest()->getText( 'id', $id );

		if ( $id === '' ) {
			// TODO: Show a form with field to enter entity ID and some general information?
			$this->getOutput()->showErrorPage( 'wikibase-entitypage-title', 'wikibase-entitypage-text' );
			return;
		}

		try {
			$entityId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			throw new HttpError( 400, wfMessage( 'wikibase-entitypage-bad-id', $id ) );
		}

		$title = $this->entityTitleLookup->getTitleForId( $entityId );

		$this->getOutput()->redirect( $title->getFullURL(), 301 );
	}

}
