<?php

namespace Wikibase\Repo\Specials;

use RuntimeException;
use Status;
use UserInputException;
use Wikibase\EditEntity;
use Wikibase\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Specials\SpecialWikibasePage;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\EntityContent;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Abstract base class for special pages of the WikibaseRepo extension.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialWikibaseRepoPage extends SpecialWikibasePage {

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right
	 */
	public function __construct( $title, $restriction ) {
		parent::__construct( $title, $restriction );

		// TODO: find a way to inject this
		$this->summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();
	}

	/**
	 * Parses an entity id.
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
				'wikibase-wikibaserepopage-invalid-id',
				array( $rawId ),
				'Entity id is not valid'
			);
		}

		return $id;
	}

	/**
	 * Parses an item id.
	 *
	 * @param string $rawId
	 *
	 * @return ItemId
	 *
	 * @throws UserInputException
	 */
	protected function parseItemId( $rawId ) {
		/** @var EntityId $id */
		$id = $this->parseEntityId( $rawId );
		if ( !( $id instanceof ItemId ) ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-not-itemid',
				array( $rawId ),
				'Entity id does not belong to an item'
			);
		}
		return $id;
	}

	/**
	 * Loads the entity content for this entity id.
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
				'wikibase-wikibaserepopage-invalid-id',
				array( $id->getSerialization() ),
				'Entity id is unknown'
			);
		}

		return $entityContent;
	}

	/**
	 * Saves the entity content using the given summary.
	 *
	 * @param EntityContent $entityContent
	 * @param Summary $summary
	 * @param string $token
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 *
	 * @return Status
	 */
	protected function saveEntity( EntityContent $entityContent, Summary $summary, $token, $flags = EDIT_UPDATE ) {
		//TODO: allow injection/override!
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$entityRevisionLookup = WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( 'uncached' );
		$entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$permissionChecker = WikibaseRepo::getDefaultInstance()->getEntityPermissionChecker();

		$editEntity = new EditEntity(
			$entityTitleLookup,
			$entityRevisionLookup,
			$entityStore,
			$permissionChecker,
			$entityContent->getEntity(), //TODO: refactor special pages to not use EntityContent!
			$this->getUser(),
			false, //XXX: need conflict detection??
			$this->getContext()
		);

		$status = $editEntity->attemptSave(
			$this->summaryFormatter->formatSummary( $summary ),
			$flags,
			$token
		);

		return $status;
	}
}