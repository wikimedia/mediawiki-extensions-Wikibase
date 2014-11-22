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
class LinkHooksHandler {

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
	private function doOnLinkBegin( Title $target, &$html, array &$customAttribs ) {
		wfProfileIn( __METHOD__ );

		// Title is temporarily set to special pages Title in case of special page inclusion! Therefore we can
		// just check whether the page is a special page and if not, disable the behavior.
		$currentTitle = $this->context->getTitle();

		if ( $currentTitle === null || !$currentTitle->isSpecialPage() ) {
			// no special page, we don't handle this for now
			// NOTE: If we want to handle this, messages would have to be generated in sites language instead of
			//	   users language so they are cache independent.
			wfProfileOut( __METHOD__ );
			return true;
		}

		//NOTE: the model returned by Title::getContentModel() is not reliable, see bug 37209
		$contentModel = $target->getContentModel();

		// we only want to handle links to Wikibase entities differently here
		if ( !$this->entityContentFactory->isEntityContentModel( $contentModel ) ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		// if custom link text is given, there is no point in overwriting it
		// but not if it is similar to the plain title
		if ( $html !== null && $target->getFullText() !== $html ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		// The following three vars should all exist, unless there is a failurre
		// somewhere, and then it will fail hard. Better test it now!
		$page = new WikiPage( $target );
		$content = null;

		try {
			$content = $page->getContent();
		} catch ( MWContentSerializationException $ex ) {
			// if this fails, it's not horrible.
			wfWarn( 'Failed to get entity object for [[' . $page->getTitle()->getFullText() . ']]'
					. ': ' . $ex->getMessage() );
		}

		if ( !( $content instanceof EntityContent ) ) {
			// Failed, can't continue. This could happen because the content is empty (page doesn't exist),
			// e.g. after item was deleted.

			// Due to bug 37209, we may also get non-entity content here, despite checking
			// Title::getContentModel up front.
			wfProfileOut( __METHOD__ );
			return true;
		}

		if ( $content->isRedirect() ) {
			// TODO: resolve redirect, show redirect info in link
			wfProfileOut( __METHOD__ );
			return true;
		}

		// Try to find the most preferred available language to display data in current context.
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromContext( $this->context );

		/** @var EntityContent $content */
		$entity = $content->getEntity();
		$labelData = $languageFallbackChain->extractPreferredValueOrAny( $entity->getLabels() );
		$descriptionData = $languageFallbackChain->extractPreferredValueOrAny( $entity->getDescriptions() );

		if ( $labelData ) {
			$labelText = $labelData['value'];
			$labelLang = Language::factory( $labelData['language'] );
		} else {
			$labelText = '';
			$labelLang = $this->context->getLanguage();
		}

		if ( $descriptionData ) {
			$descriptionText = $descriptionData['value'];
			$descriptionLang = Language::factory( $descriptionData['language'] );
		} else {
			$descriptionText = '';
			$descriptionLang = $this->context->getLanguage();
		}

		// Go on and construct the link
		$idHtml = Html::openElement( 'span', array( 'class' => 'wb-itemlink-id' ) )
			. wfMessage( 'wikibase-itemlink-id-wrapper', $target->getText() )->inContentLanguage()->escaped()
			. Html::closeElement( 'span' );

		$labelHtml = Html::openElement( 'span', array( 'class' => 'wb-itemlink-label', 'lang' => $labelLang->getHtmlCode(), 'dir' => $labelLang->getDir() ) )
			. htmlspecialchars( $labelText )
			. Html::closeElement( 'span' );

		$html = Html::openElement( 'span', array( 'class' => 'wb-itemlink' ) )
			. wfMessage( 'wikibase-itemlink' )->rawParams( $labelHtml, $idHtml )->inContentLanguage()->escaped()
			. Html::closeElement( 'span' );

		// Set title attribute for constructed link, and make tricks with the directionality to get it right
		$titleText = ( $labelText !== '' )
			? $labelLang->getDirMark() . $labelText . $this->context->getLanguage()->getDirMark()
			: $target->getPrefixedText();

		$customAttribs[ 'title' ] = ( $descriptionText !== '' ) ?
			wfMessage(
				'wikibase-itemlink-title',
				$titleText,
				$descriptionLang->getDirMark() . $descriptionText . $this->context->getLanguage()->getDirMark()
			)->inContentLanguage()->text() :
			$titleText; // no description, just display the title then

		// add wikibase styles in all cases, so we can format the link properly:
		$this->context->getOutput()->addModuleStyles( array( 'wikibase.common' ) );

		wfProfileOut( __METHOD__ );
		return true;
	}

}
