<?php

namespace Wikibase\Repo\Specials;

use MWException;
use RuntimeException;
use Status;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Abstract base class for special pages of the WikibaseRepo extension.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialWikibaseRepoPage extends SpecialWikibasePage {

	/**
	 * @var SpecialPageCopyrightView
	 */
	protected $copyrightView;

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EditEntityFactory $editEntityFactory
	 */
	public function __construct(
		$title,
		$restriction,
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct( $title, $restriction );
		$this->copyrightView = $copyrightView;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->editEntityFactory = $editEntityFactory;
	}

	/**
	 * Parses an entity id.
	 *
	 * @param string $rawId
	 *
	 * @return EntityId
	 * @throws UserInputException
	 */
	protected function parseEntityId( $rawId ) {
		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		try {
			$id = $idParser->parse( $rawId );
		} catch ( RuntimeException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-invalid-id',
				[ $rawId ],
				"Entity ID \"$rawId\" is not valid"
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
	 * @throws UserInputException
	 */
	protected function parseItemId( $rawId ) {
		$id = $this->parseEntityId( $rawId );

		if ( !( $id instanceof ItemId ) ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-not-itemid',
				[ $rawId ],
				"Entity ID \"$rawId\" does not refer to an Item"
			);
		}

		return $id;
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @return null|Title
	 */
	protected function getEntityTitle( EntityId $id ) {
		return $this->entityTitleLookup->getTitleForId( $id );
	}

	/**
	 * Saves the entity using the given summary.
	 *
	 * @param EntityDocument $entity
	 * @param Summary $summary
	 * @param string $token
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 * @param bool|int $baseRev the base revision, for conflict detection
	 *
	 * @return Status
	 */
	protected function saveEntity(
		EntityDocument $entity,
		Summary $summary,
		$token,
		$flags = EDIT_UPDATE,
		$baseRev = false
	) {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			$entity,
			$baseRev
		);

		$status = $editEntity->attemptSave(
			$this->summaryFormatter->formatSummary( $summary ),
			$flags,
			$token
		);

		return $status;
	}

	/**
	 * @return string HTML
	 */
	protected function getCopyrightHTML() {
		$submitKey = 'wikibase-' . strtolower( $this->getName() ) . '-submit';
		return $this->copyrightView->getHtml( $this->getLanguage(), $submitKey );
	}

}
