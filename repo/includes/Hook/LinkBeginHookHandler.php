<?php

namespace Wikibase\Repo\Hook;

use DummyLinker;
use Html;
use IContextSource;
use Language;
use MWContentSerializationException;
use MWException;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityContent;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 */
class LinkBeginHookHandler {

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @return LinkHookHandler
	 */
	public static function newFromGlobalState() {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$languageFallbackChainFactory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
		$context = RequestContext::getMain();

		return new self( $entityContentFactory, $languageFallbackChainFactory, $context );
	}

	/**
	 * Special page handling where we want to display meaningful link labels instead of just the items ID.
	 * This is only handling special pages right now and gets disabled in normal pages.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinkBegin
	 *
	 * @param DummyLinker $skin
	 * @param Title $target
	 * @param string $html
	 * @param array $customAttribs
	 * @param string $query
	 * @param array $options
	 * @param mixed $ret
	 * @return bool true
	 */
	public static function onLinkBegin( $skin, $target, &$html, array &$customAttribs, &$query,
		&$options, &$ret
	) {
		$handler = self::newFromGlobalState();
		return $handler->doOnLinkBegin( $target, $html, $customAttribs );
	}

	/**
	 * @param EntityContentFactory $entityContentFactory
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param IContextSource $context
	 */
	public function __construct(
		EntityContentFactory $entityContentFactory,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		IContextSource $context
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->context = $context;
	}

	/**
	 * @param Title $target
	 * @param string &$html
	 * @param array &$customAttribs
	 *
	 * @return boolean
	 */
	public function doOnLinkBegin( Title $target, &$html, array &$customAttribs ) {
		wfProfileIn( __METHOD__ );

		if ( !$this->isOnSpecialPage() || !$this->isEntityContent( $target ) ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		// if custom link text is given, there is no point in overwriting it
		// but not if it is similar to the plain title
		if ( $html !== null && $target->getFullText() !== $html ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$entity = $this->getEntityForTitle( $target );

		if ( !$entity ) {
			return true;
		}

		// Try to find the most preferred available language to display data in current context.
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromContext( $this->context );

		$labelData = $languageFallbackChain->extractPreferredValueOrAny( $entity->getLabels() );

		if ( $labelData ) {
			$labelText = $labelData['value'];
			$labelLang = Language::factory( $labelData['language'] );
		} else {
			$labelText = '';
			$labelLang = $this->context->getLanguage();
		}

		$descriptionData = $languageFallbackChain->extractPreferredValueOrAny(
			$entity->getDescriptions()
		);

		// Go on and construct the link
		$html = $this->formatLabelHtml( $labelLang, $labelText, $target );

		$customAttribs['title'] = $this->getTitleAttribute(
			$target,
			$labelLang,
			$labelText,
			$descriptionData
		);

		// add wikibase styles in all cases, so we can format the link properly:
		$this->context->getOutput()->addModuleStyles( array( 'wikibase.common' ) );

		wfProfileOut( __METHOD__ );
		return true;
	}

	private function isOnSpecialPage() {
		// Title is temporarily set to special pages Title in case of special page inclusion!
		// Therefore we can just check whether the page is a special page and
		// if not, disable the behavior.
		$currentTitle = $this->context->getTitle();

		return $currentTitle !== null && $currentTitle->isSpecialPage();
	}

	private function isEntityContent( Title $title ) {
		//NOTE: the model returned by Title::getContentModel() is not reliable, see bug 37209
		return $this->entityContentFactory->isEntityContentModel( $title->getContentModel() );
	}

	private function getEntityForTitle( Title $title ) {
		$page = new WikiPage( $title );

		try {
			$content = $page->getContent();

			// Failed, can't continue. This could happen because the content is empty
			// (page doesn't exist), e.g. after item was deleted.

			// Due to bug 37209, we may also get non-entity content here, despite checking
			// Title::getContentModel up front.

			// TODO: resolve redirect, show redirect info in link
			if ( $content instanceof EntityContent && !$content->isRedirect() ) {
				return $content->getEntity();
			}
		} catch ( MWContentSerializationException $ex ) {
			// if this fails, it's not horrible.
			wfWarn( 'Failed to get entity object for [[' . $title->getFullText() . ']]'
					. ': ' . $ex->getMessage() );
		}
	}

	private function formatLabelHtml( $labelLang, $labelText, Title $title ) {
		$idHtml = Html::openElement( 'span', array( 'class' => 'wb-itemlink-id' ) )
			. wfMessage(
				'wikibase-itemlink-id-wrapper',
				$title->getText()
			)->inContentLanguage()->escaped()
			. Html::closeElement( 'span' );

		$labelHtml = Html::openElement( 'span', array(
				'class' => 'wb-itemlink-label',
				'lang' => $labelLang->getHtmlCode(),
				'dir' => $labelLang->getDir()
			) )
			. htmlspecialchars( $labelText )
			. Html::closeElement( 'span' );

		return Html::openElement( 'span', array( 'class' => 'wb-itemlink' ) )
			. wfMessage( 'wikibase-itemlink' )->rawParams(
				$labelHtml,
				$idHtml
			)->inContentLanguage()->escaped()
			. Html::closeElement( 'span' );
	}

	private function getTitleAttribute( Title $title, $labelLang, $labelText, $descriptionData ) {
		if ( $descriptionData ) {
			$descriptionText = $descriptionData['value'];
			$descriptionLang = Language::factory( $descriptionData['language'] );
		} else {
			$descriptionText = '';
			$descriptionLang = $this->context->getLanguage();
		}

		// Set title attribute for constructed link, and make tricks with the directionality to get it right
		$titleText = ( $labelText !== '' )
			? $labelLang->getDirMark() . $labelText . $this->context->getLanguage()->getDirMark()
			: $title->getPrefixedText();

		return ( $descriptionText !== '' ) ?
			wfMessage(
				'wikibase-itemlink-title',
				$titleText,
				$descriptionLang->getDirMark() . $descriptionText
					. $this->context->getLanguage()->getDirMark()
			)->inContentLanguage()->text() :
			$titleText; // no description, just display the title then
	}

}
