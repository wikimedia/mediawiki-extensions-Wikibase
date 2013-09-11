<?php

namespace Wikibase\Lib\Specials;

use Html;
use SpecialPage;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;
use Wikibase\Utils;

/**
 * Base for special pages of the Wikibase extension,
 * holding some scaffolding and preventing us from needing to
 * deal with weird SpecialPage insanity (ie $this->mFile inclusion)
 * in every base class.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SpecialWikibasePage extends SpecialPage {

	/**
	 * @var StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 * @param string $restriction
	 * @param bool   $listed
	 */
	public function __construct( $name = '', $restriction = '', $listed = true ) {
		parent::__construct( $name, $restriction, $listed );

		// XXX: Use StringNormalizer as a plain composite - since it
		//      doesn't have any dependencies, local instantiation isn't an issue.
		$this->stringNormalizer = new StringNormalizer();
	}

	/**
	 * The subpage, ie the part after Special:PageName/
	 * Empty string if none is provided.
	 *
	 * @since 0.1
	 * @var string
	 */
	public $subPage;

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @since 0.1
	 */
	public function getDescription() {
		return $this->msg( 'special-' . strtolower( $this->getName() ) )->text();
	}

	/**
	 * @see SpecialPage::setHeaders
	 *
	 * @since 0.1
	 */
	public function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setPageTitle( $this->getDescription() );
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @since 0.1
	 */
	public function execute( $subPage ) {
		$subPage = is_null( $subPage ) ? '' : $subPage;
		$this->subPage = trim( str_replace( '_', ' ', $subPage ) );

		$this->setHeaders();
		$contLang = $this->getContext()->getLanguage();
		$this->outputHeader( $contLang->lc( 'wikibase-' . $this->getName() ) . '-summary' );

		// If the user is authorized, display the page, if not, show an error.
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return false;
		}
		return true;
	}

	/**
	 * Adds the "copyright info message" to the HTML output.
	 *
	 * @since 0.4
	 */
	public function showCopyrightMessage() {
		$this->getOutput()->addHTML(
			Html::rawElement(
				'div',
				array(),
				Utils::getCopyrightMessage()->parse()
			)
		);
	}

}
