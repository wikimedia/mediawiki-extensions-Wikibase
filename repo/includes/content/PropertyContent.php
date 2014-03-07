<?php

namespace Wikibase;

use Content;
use DataUpdate;
use IContextSource;
use ParserOutput;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Content object for articles representing Wikibase properties.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContent extends EntityContent {

	/**
	 * @since 0.1
	 * @var Property
	 */
	protected $property;

	/**
	 * Do not use to construct new stuff from outside of this class,
	 * use the static newFoobar methods.
	 *
	 * In other words: treat as protected (which it was, but now
	 * cannot be since we derive from Content).
	 *
	 * @protected
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 */
	public function __construct( Property $property ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY );
		$this->property = $property;
	}

	/**
	 * Create a new propertyContent object for the provided property.
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 *
	 * @return PropertyContent
	 */
	public static function newFromProperty( Property $property ) {
		return new static( $property );
	}

	/**
	 * Create a new PropertyContent object from the provided Property data.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return PropertyContent
	 */
	public static function newFromArray( array $data ) {
		return new static( new Property( $data ) );
	}

	/**
	 * @see Content::prepareSave
	 *
	 * @since 0.1
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
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );

		if ( $status->isOK() ) {
			// first test for label entity id conflicts as this is the faster check
			$this->addLabelEntityIdConflicts( $status, Property::ENTITY_TYPE );
			$this->addLabelUniquenessConflicts( $status );
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Gets the property that makes up this property content.
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * Sets the property that makes up this property content.
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 */
	public function setProperty( Property $property ) {
		$this->property = $property;
	}

	/**
	 * Returns a new empty PropertyContent.
	 *
	 * @since 0.1
	 *
	 * @return PropertyContent
	 */
	public static function newEmpty() {
		return new static( Property::newEmpty() );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	public function getEntity() {
		return $this->property;
	}

	/**
	 * @see Content::getDeletionUpdates
	 *
	 * @param \WikiPage $page
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return DataUpdate[]
	 */
	public function getDeletionUpdates( \WikiPage $page, \ParserOutput $parserOutput = null ) {
		//XXX: access to services should be done via the ContentHandler.
		$infoStore = WikibaseRepo::getDefaultInstance()->getStore()->getPropertyInfoStore();

		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
			array(
				new EntityDeletionUpdate( $this ),
				new PropertyInfoDeletion( $this->getProperty()->getId(), $infoStore ),
			)
		);
	}

	/**
	 * @see ContentHandler::getSecondaryDataUpdates
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param Content|null $old
	 * @param boolean $recursive
	 *
	 * @param null|ParserOutput $parserOutput
	 *
	 * @return DataUpdate[]
	 */
	public function getSecondaryDataUpdates( Title $title, Content $old = null,
		$recursive = false, ParserOutput $parserOutput = null ) {

		//XXX: access to services should be done via the ContentHandler.
		$infoStore = WikibaseRepo::getDefaultInstance()->getStore()->getPropertyInfoStore();

		return array_merge(
			parent::getSecondaryDataUpdates( $title, $old, $recursive, $parserOutput ),
			array(
				new EntityModificationUpdate( $this, $old ),
				new PropertyInfoUpdate( $this->getProperty(), $infoStore ),
			)
		);
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
	 * @param EntityLookup $entityLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $idParser
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @return EntityView
	 */
	protected function newEntityView(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $idParser,
		LanguageFallbackChain $languageFallbackChain
	) {
		return new PropertyView(
			$context,
			$snakFormatter,
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityLookup,
			$entityTitleLookup,
			$idParser,
			$languageFallbackChain
		);
	}
}
