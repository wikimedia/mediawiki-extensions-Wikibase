<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use Html;
use Linker;
use MWException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataTypeSelector;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page to list properties by data type
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialListProperties extends SpecialWikibasePage {

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @since 0.5
	 */
	public function __construct() {
		parent::__construct( 'ListProperties' );

		$this->dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
		$this->propertyInfoStore = WikibaseRepo::getDefaultInstance()->getStore()->getPropertyInfoStore();
		$this->entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.5
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$propertyLister = new PropertyLister();

		$output = $this->getOutput();
		$request = $this->getRequest();
		$title = $this->getPageTitle();
		$languageCode = $this->getLanguage()->getCode();

		$propertyLister->doExecute( $output, $request, $title, $languageCode, $subPage );
	}

}
