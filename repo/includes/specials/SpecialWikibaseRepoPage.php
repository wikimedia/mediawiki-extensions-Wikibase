<?php

namespace Wikibase\Repo\Specials;

use RuntimeException;
use UserInputException;
use Wikibase\EntityId;
use Wikibase\Lib\Specials\SpecialWikibasePage;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\EntityContent;

/**
 * Abstract base class for special pages of the WikibaseRepo extension.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialWikibaseRepoPage extends SpecialWikibasePage {

	/**
	 * Parses an entity id.
	 *
	 * @since 0.5
	 *
	 * @param string $rawId
	 *
	 * @return EntityId
	 *
	 * @throws UserInputException
	 */
	protected function parseEntityId( $rawId ) {
		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		try {
			$id = $idParser->parse( $rawId );
		} catch ( RuntimeException $ex ) {
			throw new UserInputException(
				'wikibase-setentity-invalid-id',
				array( $rawId ),
				'Entity id is not valid'
			);
		}

		return $id;
	}

	/**
	 * Loads the entity content for this entity id.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $id
	 *
	 * @return EntityContent
	 *
	 * @throws UserInputException
	 */
	protected function loadEntityContent( EntityId $id ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$entityContent = $entityContentFactory->getFromId( $id );

		if ( $entityContent === null ) {
			throw new UserInputException(
				'wikibase-setentity-invalid-id',
				array( $id->getSerialization() ),
				'Entity id is unknown'
			);
		}

		return $entityContent;
	}
}