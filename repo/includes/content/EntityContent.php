<?php

namespace Wikibase;

use AbstractContent;
use Content;
use IContextSource;
use ParserOptions;
use ParserOutput;
use RequestContext;
use Status;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\EntitySearchTextGenerator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Validators\EntityValidator;
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
	 * For use in the wb-status page property to indicate that the entity has no special
	 * status, to indicate. STATUS_NONE will not be recorded in the database.
	 *
	 * @see getEntityStatus()
	 */
	const STATUS_NONE = 0;

	/**
	 * For use in the wb-status page property to indicate that the entity is a stub,
	 * i.e. it's empty except for terms (labels, descriptions, and aliases).
	 *
	 * @see getEntityStatus()
	 */
	const STATUS_STUB = 100;

	/**
	 * For use in the wb-status page property to indicate that the entity is empty.
	 * @see getEntityStatus()
	 */
	const STATUS_EMPTY = 200;

	/*
	 * Ugly hack for checking the base revision during the database
	 * transaction that updates the entity.
	 *
	 * @var int|bool
	 */
	protected $baseRevIdForSaving = false;

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
	 * Returns the Title for the item or false if there is none.
	 *
	 * @since 0.1
	 * @deprecated use EntityTitleLookup instead
	 *
	 * @deprecated since 0.5, use EntityTitleLookup:.getTitleForId instead.
	 *
	 * @return Title|bool
	 */
	public function getTitle() {
		$id = $this->getEntity()->getId();

		if ( !$id ) {
			return false;
		}

		$lookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		return $lookup->getTitleForId( $id );
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
	 * @param int|false
	 */
	public function setBaseRevIdForSaving( $baseRevId ) {
		if ( !is_int( $baseRevId ) && ( $baseRevId !== false ) ) {
			throw new \InvalidArgumentException( 'baseRevIdForSaving must be an int or false.' );
		}

		$this->baseRevIdForSaving = $baseRevId;
	}

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
	 * @param int|null $revId
	 * @param ParserOptions|null $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( Title $title, $revId = null, ParserOptions $options = null,
		$generateHtml = true
	) {
		$entityView = $this->getEntityView( null, $options, null );
		$editable = !$options? true : $options->getEditSection();

		if ( $revId === null || $revId === 0 ) {
			$revId = $title->getLatestRevID();
		}

		$revision = new EntityRevision( $this->getEntity(), $revId );

		// generate HTML
		$output = $entityView->getParserOutput( $revision, $editable, $generateHtml );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$output->recordOption( 'userlang' );

		// register page properties
		$this->applyEntityPageProperties( $output );

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
	 * @param IContextSource|null $context
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
	 * @see Content::getNativeData
	 *
	 * @return array
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

		$thisId = $thisEntity->getId();
		$thatId = $thatEntity->getId();

		if ( $thisId !== null && $thatId !== null
			&& !$thisEntity->getId()->equals( $thatId )
		) {
			return false;
		}

		return $thisEntity->equals( $thatEntity );
	}

	/**
	 * Returns true if this content is countable as a "real" wiki page, provided
	 * that it's also in a countable location (e.g. a current revision in the main namespace).
	 *
	 * @param bool $hasLinks: if it is known whether this content contains links, provide this
	 *        information here, to avoid redundant parsing to find out.
	 *
	 * @return bool
	 */
	public function isCountable( $hasLinks = null ) {
		return !$this->getEntity()->isEmpty();
	}

	/**
	 * @since 0.1
	 *
	 * @return bool
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
	 * @see Content::prepareSave
	 *
	 * @param WikiPage $page
	 * @param int      $flags
	 * @param int      $baseRevId
	 * @param User     $user
	 *
	 * @return Status
	 */
	public function prepareSave( WikiPage $page, $flags, $baseRevId, User $user ) {
		wfProfileIn( __METHOD__ );

		// Chain to parent
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );
		if ( !$status->isOK() ) {
			wfProfileOut( __METHOD__ );
			return $status;
		}

		// If $this->baseRevIdForSaving is set, check whether the current revision is still what
		// the caller of save() thought it was.
		// If it isn't, then someone managed to squeeze in an edit after we checked for conflicts.
		// XXX: This should really be done by WikiPage. I wonder why it isn't.
		if ( $this->baseRevIdForSaving !== false && $page->getRevision() !== null ) {
			if ( $page->getRevision()->getId() !== $this->baseRevIdForSaving ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': encountered late edit conflict: '
					. 'current revision changed after regular conflict check.' );
				$status->fatal('edit-conflict');
				return $status;
			}
		}

		$status = $this->applyOnSaveValidators();

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Apply all EntityValidators registered for on-save validation.
	 */
	protected function applyOnSaveValidators() {
		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		$validators = $handler->getOnSaveValidators();

		$entity = $this->getEntity();
		$result = Result::newSuccess();

		/* @var EntityValidator $validator */
		foreach ( $validators as $validator ) {
			$result = $validator->validateEntity( $entity );

			if ( !$result->isValid() ) {
				break;
			}
		}

		$localizer = WikibaseRepo::getDefaultInstance()->getValidatorErrorLocalizer();
		return $localizer->getResultStatus( $result );
	}

	/**
	 * @param $langCode
	 * @param LanguageFallbackChain $fallbackChain
	 *
	 * @return SerializationOptions
	 */
	protected function makeSerializationOptions( $langCode, LanguageFallbackChain $fallbackChain ) {
		$langCodes = Utils::getLanguageCodes() + array( $langCode => $fallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $langCodes );

		return $options;
	}

	/**
	 * Registers any properties returned by getEntityPageProperties()
	 * in $output.
	 *
	 * @param ParserOutput $output
	 */
	private function applyEntityPageProperties( ParserOutput $output ) {
		$properties = $this->getEntityPageProperties();

		foreach ( $properties as $name => $value ) {
			$output->setProperty( $name, $value );
		}
	}

	/**
	 * Returns a map of properties about the entity, to be recorded in
	 * MediaWiki's page_props table. The idea is to allow efficient lookups
	 * of entities based on such properties.
	 *
	 * @see getEntityStatus()
	 *
	 * Keys used:
	 * - wb-status: the entity's status, according to getEntityStatus()
	 * - wb-claims: the number of claims in the entity
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		$entity = $this->getEntity();

		$properties = array(
			'wb-claims' => count( $entity->getClaims() ),
		);

		$status = $this->getEntityStatus();

		if ( $status !== self::STATUS_NONE ) {
			$properties['wb-status'] = $status;
		}

		return $properties;
	}

	/**
	 * Returns an identifier representing the status of the entity,
	 * e.g. STATUS_EMPTY or STATUS_NONE.
	 * Used by getEntityPageProperties().
	 *
	 * @see getEntityPageProperties()
	 * @see STATUS_NONE
	 * @see STATUS_EMPTY
	 * @see STATUS_STUB
	 *
	 * @return int
	 */
	public function getEntityStatus() {
		if ( $this->isEmpty() ) {
			return self::STATUS_EMPTY;
		} elseif ( !$this->getEntity()->hasClaims() ) {
			return self::STATUS_STUB;
		} else {
			return self::STATUS_NONE;
		}
	}
}
