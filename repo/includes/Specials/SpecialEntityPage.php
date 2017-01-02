<?php

namespace Wikibase\Repo\Specials;

use HttpError;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * Special page for addressing entity data pages without knowing the namespace.
 *
 * Given local entity ID as a sub page, this page redirects to Special:EntityData/{entity id},
 * while given the foreign entity ID, it redirects to pecialEntityPage/{entity id without a prefix}
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

	public function __construct( EntityIdParser $entityIdParser ) {
		parent::__construct( 'EntityPage' );

		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param null|string $subPage
	 *
	 * @throws HttpError
	 */
	public function execute( $subPage ) {
		$id = $subPage;
		$id = $this->getRequest()->getText( 'id', $id );

		if ( $id === '' || $id === null ) {
			// TODO: Show a form with field to enter entity ID and some general information?
			$this->getOutput()->showErrorPage( 'wikibase-entitypage-title', 'wikibase-entitypage-text' );
			return;
		}

		try {
			$entityId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			throw new HttpError( 400, wfMessage( 'wikibase-entitypage-bad-id', $id ) );
		}

		if ( !$entityId->isForeign() ) {
			// TODO: use TitleFactory
			$title = Title::makeTitle( NS_SPECIAL, 'EntityData/' . $entityId->getSerialization() );
		} else {
			// TODO: use TitleFactory
			$title = Title::makeTitle( NS_SPECIAL, 'EntityPage/' . $entityId->getLocalPart(), '', $entityId->getRepositoryName() );
		}

		$this->getOutput()->redirect( $title->getFullURL(), 303 );
	}

}
