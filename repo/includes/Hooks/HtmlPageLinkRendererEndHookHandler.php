<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use HtmlArmor;
use MediaWiki\Context\RequestContext;
use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\Linker\Hook\HtmlPageLinkRendererEndHook;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesException;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;

/**
 * Handler for the HtmlPageLinkRendererEnd hook, used to change the default link text of links to
 * wikibase Entity pages to the respective entity's label. This is used mainly for listings on
 * special pages or for edit summaries, where it is useful to see pages listed by label rather than
 * their entity ID.
 *
 * Label lookups are relatively expensive if done repeatedly for individual labels. If possible,
 * labels will be pre-loaded via the LabelPrefetchHookHandler and buffered for later use here.
 *
 * @see LabelPrefetchHookHandler
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class HtmlPageLinkRendererEndHookHandler implements HtmlPageLinkRendererEndHook {

	private EntityExistenceChecker $entityExistenceChecker;
	private EntityIdParser $entityIdParser;
	private TermLookup $termLookup;
	private EntityNamespaceLookup $localEntityNamespaceLookup;
	private InterwikiLookup $interwikiLookup;
	private EntityLinkFormatterFactory $linkFormatterFactory;
	private SpecialPageFactory $specialPageFactory;
	private LanguageFallbackChainFactory $languageFallbackChainFactory;
	private ?LabelDescriptionLookup $labelDescriptionLookup = null;
	private EntityUrlLookup $entityUrlLookup;
	private LinkTargetEntityIdLookup $linkTargetEntityIdLookup;
	private ?string $federatedPropertiesSourceScriptUrl;
	private bool $federatedPropertiesEnabled;
	private bool $isMobileSite;

	public static function factory(
		InterwikiLookup $interwikiLookup,
		SpecialPageFactory $specialPageFactory,
		EntityExistenceChecker $entityExistenceChecker,
		EntityIdParser $entityIdParser,
		EntityLinkFormatterFactory $entityLinkFormatterFactory,
		EntityUrlLookup $entityUrlLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		EntityNamespaceLookup $localEntityNamespaceLookup,
		bool $isMobileSite,
		SettingsArray $repoSettings,
		TermLookup $termLookup
	): self {
		return new self(
			$entityExistenceChecker,
			$entityIdParser,
			$termLookup,
			$localEntityNamespaceLookup,
			$interwikiLookup,
			$entityLinkFormatterFactory,
			$specialPageFactory,
			$languageFallbackChainFactory,
			$entityUrlLookup,
			$linkTargetEntityIdLookup,
			$repoSettings->getSetting( 'federatedPropertiesSourceScriptUrl' ),
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$isMobileSite
		);
	}

	/**
	 * Special page handling where we want to display meaningful link labels instead of just the items ID.
	 * This is only handling special pages right now and gets disabled in normal pages.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/HtmlPageLinkRendererEnd
	 *
	 * @param LinkRenderer $linkRenderer
	 * @param LinkTarget $target
	 * @param bool $isKnown
	 * @param HtmlArmor|string|null &$text
	 * @param array &$extraAttribs
	 * @param string|null &$ret
	 *
	 * @return bool true to continue processing the link, false to use $ret directly as the HTML for the link
	 */
	public function onHtmlPageLinkRendererEnd(
		$linkRenderer,
		$target,
		$isKnown,
		&$text,
		&$extraAttribs,
		&$ret
	): bool {
		$context = RequestContext::getMain();
		if ( !$context->hasTitle() ) {
			// Short-circuit this hook if no title is
			// set in the main context (T131176)
			return true;
		}

		return $this->doHtmlPageLinkRendererEnd(
			$linkRenderer,
			Title::newFromLinkTarget( $target ),
			$text,
			$extraAttribs,
			$context,
			$ret
		);
	}

	public function __construct(
		EntityExistenceChecker $entityExistenceChecker,
		EntityIdParser $entityIdParser,
		TermLookup $termLookup,
		EntityNamespaceLookup $localEntityNamespaceLookup,
		InterwikiLookup $interwikiLookup,
		EntityLinkFormatterFactory $linkFormatterFactory,
		SpecialPageFactory $specialPageFactory,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		EntityUrlLookup $entityUrlLookup,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		?string $federatedPropertiesSourceScriptUrl,
		bool $federatedPropertiesEnabled,
		bool $isMobileSite
	) {
		$this->entityExistenceChecker = $entityExistenceChecker;
		$this->entityIdParser = $entityIdParser;
		$this->termLookup = $termLookup;
		$this->localEntityNamespaceLookup = $localEntityNamespaceLookup;
		$this->interwikiLookup = $interwikiLookup;
		$this->linkFormatterFactory = $linkFormatterFactory;
		$this->specialPageFactory = $specialPageFactory;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->entityUrlLookup = $entityUrlLookup;
		$this->linkTargetEntityIdLookup = $linkTargetEntityIdLookup;
		$this->federatedPropertiesSourceScriptUrl = $federatedPropertiesSourceScriptUrl;
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->isMobileSite = $isMobileSite;
	}

	/**
	 * @param LinkRenderer $linkRenderer
	 * @param Title $target
	 * @param HtmlArmor|string|null &$text
	 * @param array &$customAttribs
	 * @param RequestContext $context
	 * @param string|null &$html
	 *
	 * @return bool true to continue processing the link, false to use $html directly for the link
	 */
	public function doHtmlPageLinkRendererEnd(
		LinkRenderer $linkRenderer,
		Title $target,
		&$text,
		array &$customAttribs,
		RequestContext $context,
		?string &$html = null
	): bool {
		$outTitle = $context->getOutput()->getTitle();

		// For good measure: Don't do anything in case the OutputPage has no Title set.
		if ( !$outTitle ) {
			return true;
		}

		// if custom link text is given, there is no point in overwriting it
		// but not if it is similar to the plain title
		if ( $text !== null
				&& $target->getFullText() !== HtmlArmor::getHtml( $text )
				&& $target->getText() !== HtmlArmor::getHtml( $text ) ) {
			return true;
		}

		if ( !$this->shouldConvertNoBadTitle( $outTitle, $linkRenderer ) ) {
			return true;
		}

		try {
			return $this->internalDoHtmlPageLinkRendererEnd( $linkRenderer, $target, $text, $customAttribs, $context, $html );
		} catch ( FederatedPropertiesException ) {
			$this->federatedPropsDegradedDoHtmlPageLinkRendererEnd( $target, $text, $customAttribs );

			return true;
		}
	}

	/**
	 * Hook handling logic for the HtmlPageLinkRendererEnd hook in case federated properties are
	 * enabled, but access to the source wiki failed.
	 *
	 * @param Title $linkTarget
	 * @param HtmlArmor|string|null &$text
	 * @param array &$customAttribs
	 */
	private function federatedPropsDegradedDoHtmlPageLinkRendererEnd(
		LinkTarget $linkTarget,
		&$text,
		array &$customAttribs
	): void {
		$entityId = $this->linkTargetEntityIdLookup->getEntityId( $linkTarget );
		$text = $entityId->getSerialization();

		// This is a hack and could probably use the TitleIsAlwaysKnown hook instead.
		// Filter out the "new" class to avoid red links for existing entities.
		$customAttribs['class'] = $this->removeNewClass( $customAttribs['class'] ?? '' );
		// Use the entity id as title, as we can't lookup the label
		$customAttribs['title'] = $entityId->getSerialization();

		$customAttribs['href'] = $this->federatedPropertiesSourceScriptUrl .
			'index.php?title=Special:EntityData/' . urlencode( $entityId->getSerialization() );
	}

	/**
	 * Parts of the hook handling logic for the HtmlPageLinkRendererEnd hook that potentially
	 * interact with entity storage.
	 *
	 * @param LinkRenderer $linkRenderer
	 * @param Title $target
	 * @param HtmlArmor|string|null &$text
	 * @param array &$customAttribs
	 * @param RequestContext $context
	 * @param string|null &$html
	 *
	 * @return bool true to continue processing the link, false to use $html directly for the link
	 */
	private function internalDoHtmlPageLinkRendererEnd(
		LinkRenderer $linkRenderer,
		Title $target,
		&$text,
		array &$customAttribs,
		RequestContext $context,
		?string &$html = null
	): bool {
		$foreignEntityId = $this->parseForeignEntityId( $target );
		$isLocalEntityNamespace = $target->getInterwiki() === ''
			&& $this->localEntityNamespaceLookup->isEntityNamespace( $target->getNamespace() );
		if ( !$foreignEntityId && !$isLocalEntityNamespace ) {
			return true;
		}

		$entityId = $this->linkTargetEntityIdLookup->getEntityId( $target );
		if ( !$entityId ) {
			// Handle "fake" titles for new entities as generated by
			// MediaWikiEditFilterHookRunner::getContextForEditFilter().
			// For instance, a link to Property:NewProperty would be replaced by
			// a link to Special:NewProperty. This is useful in logs, to indicate
			// that the logged action occurred while creating an entity.
			if ( $foreignEntityId !== null ) {
				// For some reason, the lookup didn't find it.
				return true;
			}
			$targetText = $target->getText();
			[ $name ] = $this->specialPageFactory->resolveAlias( $targetText );
			if ( $name === null ) {
				return true;
			}
			$entityType = $this->localEntityNamespaceLookup->getEntityType( $target->getNamespace() );
			if ( ( 'New' . ucfirst( $entityType ) ) !== $name ) {
				return true;
			}
			$target = new TitleValue( NS_SPECIAL, $targetText );
			$html = $linkRenderer->makeKnownLink( $target );
			return false;
		}

		$linkUrl = $this->entityUrlLookup->getLinkUrl( $entityId );
		if ( $target->isRedirect() && $linkUrl !== null ) {
			$linkUrl = wfAppendQuery( $linkUrl, [ 'redirect' => 'no' ] );
		}
		$customAttribs['href'] = $linkUrl;

		if ( !$this->entityExistenceChecker->exists( $entityId ) ) {
			// The link points to a non-existing entity.
			return true;
		}

		// This is a hack and could probably use the TitleIsAlwaysKnown hook instead.
		// Filter out the "new" class to avoid red links for existing entities.
		$customAttribs['class'] = $this->removeNewClass( $customAttribs['class'] ?? '' );

		$labelDescriptionLookup = $this->getLabelDescriptionLookup( $context );
		try {
			$label = $labelDescriptionLookup->getLabel( $entityId );
			$description = $labelDescriptionLookup->getDescription( $entityId );
		} catch ( LabelDescriptionLookupException ) {
			return true;
		}

		$labelData = $this->termFallbackToTermData( $label );
		$descriptionData = $this->termFallbackToTermData( $description );

		$linkFormatter = $this->linkFormatterFactory->getLinkFormatter(
			$entityId->getEntityType(),
			$context->getLanguage()
		);

		$text = new HtmlArmor( $linkFormatter->getHtml( $entityId, $labelData ) );

		$customAttribs['title'] = $linkFormatter->getTitleAttribute(
			$entityId,
			$labelData,
			$descriptionData
		);

		$fragment = $linkFormatter->getFragment( $entityId, $target->getFragment() );
		$target->setFragment( '#' . $fragment );

		// add wikibase styles in all cases, so we can format the link properly:
		$out = $context->getOutput();
		$out->addModuleStyles( [ 'wikibase.alltargets' ] );
		if ( !$this->isMobileSite && $this->federatedPropertiesEnabled && $entityId instanceof PropertyId ) {
			$customAttribs[ 'class' ] = $customAttribs[ 'class' ] == '' ? 'fedprop' : $customAttribs[ 'class' ] . ' fedprop';
			$out->addModules( 'wikibase.federatedPropertiesLeavingSiteNotice' );
		}
		return true;
	}

	/**
	 * Remove the new class from a space separated list of classes.
	 */
	private function removeNewClass( string $classes ): string {
		return implode( ' ', array_filter(
			preg_split( '/\s+/', $classes ),
			function ( $class ) {
				return $class !== 'new';
			}
		) );
	}

	/**
	 * @param TermFallback|null $term
	 * @return string[]|null
	 */
	private function termFallbackToTermData( ?TermFallback $term ): ?array {
		if ( $term ) {
			return [
				'value' => $term->getText(),
				'language' => $term->getActualLanguageCode(),
			];
		}

		return null;
	}

	private function parseForeignEntityId( LinkTarget $target ): ?EntityId {
		$interwiki = $target->getInterwiki();

		if ( $interwiki === '' || !$this->interwikiLookup->isValidInterwiki( $interwiki ) ) {
			return null;
		}

		$foreignId = $this->extractForeignIdString( $target );

		if ( $foreignId !== null ) {
			try {
				return $this->entityIdParser->parse( $foreignId );
			} catch ( EntityIdParsingException ) {
			}
		}

		return null;
	}

	/**
	 * Should be given an already confirmed valid interwiki link that uses Special:EntityPage
	 * to link to an entity on a remote Wikibase
	 */
	private function extractForeignIdString( LinkTarget $linkTarget ): ?string {
		return $this->extractForeignIdStringMainNs( $linkTarget ) ?: $this->extractForeignIdStringSpecialNs( $linkTarget );
	}

	private function extractForeignIdStringMainNs( LinkTarget $linkTarget ): ?string {
		if ( $linkTarget->getNamespace() !== NS_MAIN ) {
			return null;
		}

		$linkTargetChangedNamespace = Title::newFromText( $linkTarget->getText() );

		if ( $linkTargetChangedNamespace === null ) {
			return null;
		}

		return $this->extractForeignIdStringSpecialNs( $linkTargetChangedNamespace );
	}

	private function extractForeignIdStringSpecialNs( LinkTarget $linkTarget ): ?string {
		// FIXME: This encodes knowledge from EntityContentFactory::getTitleForId
		$prefix = 'EntityPage/';
		$prefixLength = strlen( $prefix );
		$pageName = $linkTarget->getText();

		if ( $linkTarget->getNamespace() === NS_SPECIAL && strncmp( $pageName, $prefix, $prefixLength ) === 0 ) {
			return substr( $pageName, $prefixLength );
		}

		return null;
	}

	private function shouldConvertNoBadTitle( Title $currentTitle, LinkRenderer $linkRenderer ): bool {
		return $linkRenderer->isForComment() ||
			// Note: this may not work right with special page transclusion. If $out->getTitle()
			// doesn't return the transcluded special page's title, the transcluded text will
			// not have entity IDs resolved to labels.
			// Also Note: Badtitle is excluded because it is used in rendering actual page content
			// that is added to the ParserCache. See T327062#8796532 and https://www.mediawiki.org/wiki/API:Stashedit
			( $currentTitle->isSpecialPage() && !$currentTitle->isSpecial( 'Badtitle' ) );
	}

	private function getLabelDescriptionLookup( RequestContext $context ): LabelDescriptionLookup {
		if ( $this->labelDescriptionLookup === null ) {
			$this->labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
				$this->termLookup,
				$this->languageFallbackChainFactory->newFromContext( $context )
			);
		}

		return $this->labelDescriptionLookup;
	}

}
