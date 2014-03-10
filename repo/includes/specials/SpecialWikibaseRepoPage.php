<?php

namespace Wikibase\Repo\Specials;

use MWException;
use RuntimeException;
use Status;
use Title;
use UserInputException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EditEntity;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\EntityRevisionLookup;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\Specials\SpecialWikibasePage;
use Wikibase\Repo\WikibaseRepo;
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
	 * @var EntityRevisionLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

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

		//TODO: allow overriding services for testing
		$this->summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();
		$this->entityLookup = WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup();
		$this->titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
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
	 * @since 0.5
	 *
	 * @param EntityId $id
	 *
	 * @return EntityRevision
	 *
	 * @throws UserInputException
	 */
	protected function loadEntity( EntityId $id ) {
		$entity = $this->entityLookup->getEntityRevision( $id );

		if ( $entity === null ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-invalid-id',
				array( $id->getSerialization() ),
				'Entity id is unknown'
			);
		}

		return $entity;
	}

	/**
	 * @since 0.5
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @return null|Title
	 */
	protected function getEntityTitle( EntityId $id ) {
		return $this->titleLookup->getTitleForId( $id );
	}

	/**
	 * Saves the entity content using the given summary.
	 *
	 * @param Entity $entity
	 * @param Summary $summary
	 * @param string $token
	 * @param int $baseRev the base revision, for conflict detection
	 *
	 * @return Status
	 */
	protected function saveEntity( Entity $entity, Summary $summary, $token, $baseRev = 0 ) {
		//TODO: allow injection/override!
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$entityRevisionLookup = WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( 'uncached' );
		$entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$editEntity = new EditEntity(
			$entityTitleLookup,
			$entityRevisionLookup,
			$entityStore,
			$entity,
			$this->getUser(),
			$baseRev,
			$this->getContext()
		);

		$status = $editEntity->attemptSave(
			$this->summaryFormatter->formatSummary( $summary ),
			EDIT_UPDATE,
			$token
		);

		return $status;
	}
}