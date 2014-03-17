<?php

namespace Wikibase;

use AbstractContent;
use Content;
use IContextSource;
use Language;
use MWException;
use ParserOptions;
use ParserOutput;
use RequestContext;
use Status;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\EntitySearchTextGenerator;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Abstract content object for articles representing Wikibase entities.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityContent extends AbstractContent {

	/**
	 * Ugly hack for checking the base revision during the database
	 * transaction that updates the entity.
	 *
	 * @since 0.5
	 *
	 * @var int|bool
	 */
	protected $baseRevisionForSaving = false;

	/**
	 * Checks if this EntityContent is valid for saving.
	 *
	 * Returns false if the entity does not have an ID set.
	 *
	 * @see Content::isValid()
	 */
	public function isValid() {
		if ( is_null( $this->getEntity()->getId() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 0.1
	 * @var WikiPage|bool
	 */
	protected $wikiPage = false;

	/**
	 * Returns the WikiPage for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return WikiPage|bool
	 */
	public function getWikiPage() {
		if ( $this->wikiPage === false ) {
			if ( !$this->isNew() ) {
				$this->wikiPage = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getWikiPageForId(
					$this->getEntity()->getId()
				);
			}
		}

		return $this->wikiPage;
	}

	/**
	 * Returns the Title for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return Title|bool
	 */
	public function getTitle() {
		$wikiPage = $this->getWikiPage();
		return $wikiPage === false ? false : $wikiPage->getTitle();
	}

	/**
	 * Returns if the item has an ID set or not.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isNew() {
		return is_null( $this->getEntity()->getId() );
	}

	/**
	 * Returns the entity contained by this entity content.
	 * Deriving classes typically have a more specific get method as
	 * for greater clarity and type hinting.
	 *
	 * @since 0.1
	 *
	 * @return Entity
	 */
	abstract public function getEntity();

	/**
	 * Returns a ParserOutput object containing the HTML.
	 * The actual work of generating a ParserOutput object is done by calling
	 * EntityView::getParserOutput().
	 *
	 * @note: this calls ParserOutput::recordOption( 'userlang' ) to split the cache
	 * by user language.
	 *
	 * @see Content::getParserOutput
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param null $revId
	 * @param null|ParserOptions $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( Title $title, $revId = null, ParserOptions $options = null,
		$generateHtml = true
	) {
		$entityView = $this->getEntityView( null, $options, null );
		$editable = !$options? true : $options->getEditSection();

		// generate HTML
		$output = $entityView->getParserOutput( $this->getEntityRevision(), $editable, $generateHtml );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$output->recordOption( 'userlang' );

		return $output;
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @note: this uses global state to access the services needed for
	 * displaying the entity.
	 *
	 * @since 0.5
	 *
	 * @param \IContextSource|null $context
	 * @param ParserOptions|null $options
	 * @param LanguageFallbackChain|null $uiLanguageFallbackChain
	 *
	 * @return EntityView
	 */
	public function getEntityView( IContextSource $context = null, ParserOptions $options = null,
		LanguageFallbackChain $uiLanguageFallbackChain = null
	) {
		//TODO: cache last used entity view

		if ( $context === null ) {
			$context = RequestContext::getMain();
		}

		// determine output language ----
		$langCode = $context->getLanguage()->getCode();

		if ( $options !== null ) {
			// NOTE: Parser Options language overrides context language!
			$langCode = $options->getUserLang();
		}

		// make formatter options ----
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $langCode );

		// Force the context's language to be the one specified by the parser options.
		if ( $context && $context->getLanguage()->getCode() !== $langCode ) {
			$context = clone $context;
			$context->setLanguage( $langCode );
		}

		// apply language fallback chain ----
		if ( !$uiLanguageFallbackChain ) {
			$factory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
			$uiLanguageFallbackChain = $factory->newFromContextForPageView( $context );
		}

		$formatterOptions->setOption( 'languages', $uiLanguageFallbackChain );

		// get all the necessary services ----
		$snakFormatter = WikibaseRepo::getDefaultInstance()->getSnakFormatterFactory()
			->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );

		$dataTypeLookup = WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup();
		$entityInfoBuilder = WikibaseRepo::getDefaultInstance()->getStore()->getEntityInfoBuilder();
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$idParser = new BasicEntityIdParser();

		$options = $this->makeSerializationOptions( $langCode, $uiLanguageFallbackChain );

		// construct the instance ----
		$entityView = $this->newEntityView(
			$context,
			$snakFormatter,
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityContentFactory,
			$idParser,
			$options
		);

		return $entityView;
	}

	/**
	 * Instantiates an EntityView.
	 *
	 * @see getEntityView()
	 *
	 * @param IContextSource $context
	 * @param SnakFormatter $snakFormatter
	 * @param Lib\PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $idParser
	 * @param SerializationOptions $options
	 *
	 * @return EntityView
	 */
	protected abstract function newEntityView(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $idParser,
		SerializationOptions $options
	);

	/**
	 * @return String a string representing the content in a way useful for building a full text
	 *         search index.
	 */
	public function getTextForSearchIndex() {
		wfProfileIn( __METHOD__ );

		$entity = $this->getEntity();

		$searchTextGenerator = new EntitySearchTextGenerator();
		$text = $searchTextGenerator->generate( $entity );

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * @return String a string representing the content in a way useful for content filtering as
	 *         performed by extensions like AbuseFilter.
	 */
	public function getTextForFilters() {
		wfProfileIn( __METHOD__ );

		//XXX: $ignore contains knowledge about the Entity's internal representation.
		//     This list should therefore rather be maintained in the Entity class.
		static $ignore = array(
			'language',
			'site',
			'type',
		);

		$data = $this->getEntity()->toArray();

		$values = self::collectValues( $data, $ignore );

		$text = implode( "\n", $values );

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * Recursively collects values from nested arrays.
	 *
	 * @param array $data The array structure to process.
	 * @param array $ignore A list of keys to skip.
	 *
	 * @return array The values found in the array structure.
	 * @todo needs unit test
	 */
	protected static function collectValues( $data, $ignore = array() ) {
		$values = array();

		$erongi = array_flip( $ignore );
		foreach ( $data as $key => $value ) {
			if ( isset( $erongi[$key] ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$values = array_merge( $values, self::collectValues( $value, $ignore ) );
			} else {
				$values[] = $value;
			}
		}

		return $values;
	}

	/**
	 * @return String the wikitext to include when another page includes this  content, or false if
	 *         the content is not includable in a wikitext page.
	 */
	public function getWikitextForTransclusion() {
		return false;
	}

	/**
	 * Returns a textual representation of the content suitable for use in edit summaries and log
	 * messages.
	 *
	 * @param int $maxlength maximum length of the summary text
	 * @return String the summary text
	 */
	public function getTextForSummary( $maxlength = 250 ) {
		return $this->getEntity()->getDescription( $GLOBALS['wgLang']->getCode() );
	}

	/**
	 * Returns native representation of the data. Interpretation depends on the data model used,
	 * as given by getDataModel().
	 *
	 * @return mixed the native representation of the content. Could be a string, a nested array
	 *		 structure, an object, a binary blob... anything, really.
	 */
	public function getNativeData() {
		return $this->getEntity()->toArray();
	}

	/**
	 * returns the content's nominal size in bogo-bytes.
	 *
	 * @return int
	 */
	public function getSize() {
		return strlen( serialize( $this->getNativeData() ) );
	}

	/**
	 * Both contents will be considered equal if they have the same ID and equal Entity data. If
	 * one of the contents is considered "new", then matching IDs is not a criteria for them to be
	 * considered equal.
	 *
	 * @see Content::equals
	 */
	public function equals( Content $that = null ) {
		if ( is_null( $that ) ) {
			return false;
		}

		if ( $that === $this ) {
			return true;
		}

		if ( $that->getModel() !== $this->getModel() ) {
			return false;
		}

		if ( !( $that instanceof EntityContent ) ) {
			return false;
		}

		$thisEntity = $this->getEntity();
		$thatEntity = $that->getEntity();

		if ( !$this->isNew() && !$that->isNew()
			&& !$thisEntity->getId()->equals( $thatEntity->getId() )
		) {
			return false;
		}

		return $thisEntity->equals( $thatEntity );
	}

	/**
	 * Returns true if this content is countable as a "real" wiki page, provided
	 * that it's also in a countable location (e.g. a current revision in the main namespace).
	 *
	 * @param boolean $hasLinks: if it is known whether this content contains links, provide this
	 *        information here, to avoid redundant parsing to find out.
	 * @return boolean
	 */
	public function isCountable( $hasLinks = null ) {
		return !$this->getEntity()->isEmpty();
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->getEntity()->isEmpty();
	}

	/**
	 * @see Content::copy
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function copy() {
		$array = array();

		foreach ( $this->getEntity()->toArray() as $key => $value ) {
			$array[$key] = is_object( $value ) ? clone $value : $value;
		}

		return static::newFromArray( $array );
	}

	/**
	 * Assigns a fresh ID to this entity.
	 *
	 * @throws \MWException if this entity already has an ID assigned, or something goes wrong while
	 *         generating a new ID.
	 * @return int The new ID
	 */
	public function grabFreshId() {
		if ( !$this->isNew() ) {
			throw new MWException( "This entity already has an ID!" );
		}

		wfProfileIn( __METHOD__ );

		$idGenerator = StoreFactory::getStore()->newIdGenerator();

		$id = $idGenerator->getNewId( $this->getContentHandler()->getModelID() );

		$this->getEntity()->setId( $id );

		wfProfileOut( __METHOD__ );
		return $id;
	}

	/**
	 * Saves this item.
	 * If this item does not exist yet, it will be created (ie a new ID will be determined and a new
	 * page in the data NS created).
	 *
	 * @note: if the item does not have an ID yet (i.e. it was not yet created in the database),
	 *        save() will fail with a edit-gone-missing message unless the EDIT_NEW bit is set in
	 *        $flags.
	 *
	 * @note: if the save is triggered by any kind of user interaction, consider using
	 *        EditEntity::attemptSave(), which automatically handles edit conflicts, permission
	 *        checks, etc.
	 *
	 * @note: this method should not be overloaded, and should not be extended to save additional
	 *        information to the database. Such things should be done in a way that will also be
	 *        triggered when the save is performed by calling WikiPage::doEditContent.
	 *
	 * @since 0.1
	 *
	 * @param string     $summary
	 * @param null|User  $user
	 * @param integer    $flags flags as used by WikiPage::doEditContent, use EDIT_XXX constants.
	 *
	 * @param int|bool   $baseRevId
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @todo: move logic into WikiPageEntityStore and make this method a deprecated adapter.
	 *
	 * @return \Status Success indicator, like the one returned by WikiPage::doEditContent().
	 */
	public function save(
		$summary = '',
		User $user = null,
		$flags = 0,
		$baseRevId = false
	) {
		wfProfileIn( __METHOD__ );

		if ( ( $flags & EDIT_NEW ) == EDIT_NEW ) {
			if ( $this->isNew() ) {
				$this->grabFreshId();
			} elseif ( $this->getTitle()->exists() ) {
				wfProfileOut( __METHOD__ );
				return Status::newFatal( 'edit-already-exists' );
			}
		} else {
			if ( $this->isNew() ) {
				wfProfileOut( __METHOD__ );
				return Status::newFatal( 'edit-gone-missing' );
			}
		}

		//XXX: very ugly and brittle hack to pass info to prepareSave so we can check inside a db transaction
		//     whether an edit has occurred after EditEntity checked for conflicts. If we had nested
		//     database transactions, we could simply check here.
		$this->baseRevisionForSaving = $baseRevId;

		// NOTE: make sure we start saving from a clean slate. Calling WikiPage::clearPreparedEdit
		//       may cause the old content to be loaded from the database again. This may be
		//       necessary, because EntityContent is mutable, so the cached object might have changed.
		//
		//       The relevant test case is ItemContentTest::testRepeatedSave
		//
		//       TODO: might be able to further optimize handling of prepared edit in WikiPage.

		$page = $this->getWikiPage();
		$page->clear();
		$page->clearPreparedEdit();

		$status = $page->doEditContent(
			$this,
			$summary,
			$flags | EDIT_AUTOSUMMARY,
			$baseRevId,
			$user
		);

		if ( $status->isOK() && !isset ( $status->value['revision'] ) ) {
			// HACK: No new revision was created (content didn't change). Report the old one.
			// There *might* be a race condition here, but since $page already loaded the
			// latest revision, it should still be cached, and should always be the correct one.
			$status->value['revision'] = $page->getRevision();
		}

		if( $status->isGood() && isset ( $status->value['new'] ) && $status->value['new'] ) {
			StoreFactory::getStore()->newEntityPerPage()->addEntityPage(
				$this->getEntity()->getId(),
				$page->getTitle()->getArticleID() );
		}

		$this->baseRevisionForSaving = false;

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * @see Content::prepareSave
	 *
	 * @param WikiPage $page
	 * @param int      $flags
	 * @param int      $baseRevId
	 * @param User     $user
	 *
	 * @return \Status
	 */
	public function prepareSave( WikiPage $page, $flags, $baseRevId, User $user ) {
		wfProfileIn( __METHOD__ );

		// Chain to parent
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );
		if ( !$status->isOK() ) {
			wfProfileOut( __METHOD__ );
			return $status;
		}

		// If baseRevisionForSaving is set, check whether the current revision is still what
		// the caller of save() thought it was.
		// If it isn't, then someone managed to squeeze in an edit after we checked for conflicts.
		if ( $this->baseRevisionForSaving !== false && $page->getRevision() !== null ) {
			if ( $page->getRevision()->getId() !== $this->baseRevisionForSaving ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': encountered late edit conflict: '
					. 'current revision changed after regular conflict check.' );
				$status->fatal('edit-conflict');
			}
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Adds errors to the status if there are labels that already exist
	 * for another entity of this type in the same language.
	 *
	 * @since 0.1
	 *
	 * @param Status $status
	 */
	final protected function addLabelUniquenessConflicts( Status $status ) {
		$labels = array();

		$entity = $this->getEntity();

		foreach ( $entity->getLabels() as $langCode => $labelText ) {
			$label = new Term( array(
				'termLanguage' => $langCode,
				'termText' => $labelText,
			) );

			$labels[] = $label;
		}

		$foundLabels = StoreFactory::getStore()->getTermIndex()->getMatchingTerms(
			$labels,
			Term::TYPE_LABEL,
			$entity->getType()
		);

		/**
		 * @var Term $foundLabel
		 */
		foreach ( $foundLabels as $foundLabel ) {
			$foundId = $foundLabel->getEntityId();

			if ( !$entity->getId()->equals( $foundId ) ) {
				// Messages: wikibase-error-label-not-unique-wikibase-property,
				// wikibase-error-label-not-unique-wikibase-query
				$status->fatal(
					'wikibase-error-label-not-unique-wikibase-' . $entity->getType(),
					$foundLabel->getText(),
					$foundLabel->getLanguage(),
					$foundId !== null ? $foundId : ''
				);
			}
		}
	}

	/**
	 * Adds errors to the status if there are labels that represent a valid entity id.
	 *
	 * @since 0.5
	 *
	 * @param Status $status
	 * @param string $forbiddenEntityType entity type that should lead to a conflict
	 */
	final protected function addLabelEntityIdConflicts( Status $status, $forbiddenEntityType ) {
		$entity = $this->getEntity();
		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		foreach ( $entity->getLabels() as $labelText ) {
			try {
				$entityId = $entityIdParser->parse( $labelText );
				if ( $entityId->getEntityType() === $forbiddenEntityType ) {
					$status->fatal( 'wikibase-error-label-no-entityid' );
				}
			}
			catch ( EntityIdParsingException $parseException ) {
				// All fine, the parsing did not work, so there is no entity id :)
			}
		}
	}

	/**
	 * @return EntityRevision
	 */
	public function getEntityRevision() {
		$entityPage = $this->getWikiPage();
		$pageRevision = !$entityPage ? null : $entityPage->getRevision();

		$itemRevision = new EntityRevision(
			$this->getEntity(),
			$pageRevision === null ? 0 : $entityPage->getId(),
			$pageRevision === null ? '' : $entityPage->getTimestamp()
		);

		return $itemRevision;
	}

	/**
	 * @return SerializationOptions
	 */
	protected function makeSerializationOptions( $langCode, LanguageFallbackChain $fallbackChain ) {
		$langCodes = Utils::getLanguageCodes() + array( $langCode => $fallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $langCodes );

		return $options;
	}

}
