<?php
declare( strict_types=1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Overrides the `href` attribute of entity links via `EntityUrlLookup` if they were previously formatted in
 * `HtmlPageLinkRendererBeginHookHandler`.
 *
 * @license GPL-2.0-or-later
 */
class HtmlPageLinkRendererEndHookHandler {

	private $linkTargetEntityIdLookup;

	private $entityUrlLookup;

	public function __construct( LinkTargetEntityIdLookup $linkTargetEntityIdLookup, EntityUrlLookup $entityUrlLookup ) {
		$this->linkTargetEntityIdLookup = $linkTargetEntityIdLookup;
		$this->entityUrlLookup = $entityUrlLookup;
	}

	private static function newFromGlobalState(): self {
		$wbRepo = WikibaseRepo::getDefaultInstance();

		return new self( $wbRepo->getLinkTargetEntityIdLookup(), $wbRepo->getEntityUrlLookup() );
	}

	public static function onHtmlPageLinkRendererEnd( &...$args ) {
		return self::newFromGlobalState()->doHtmlPageLinkRendererEnd( ...$args );
	}

	/**
	 * See https://www.mediawiki.org/wiki/Manual:Hooks/HtmlPageLinkRendererEnd
	 *
	 * @param LinkRenderer $linkRenderer
	 * @param LinkTarget $target
	 * @param bool $isKnown
	 * @param string|\HtmlArmor $text Contents that the <a> tag should have; either a plain, unescaped string or a HtmlArmor object
	 * @param array $attribs HTML attributes of the <a> tag, after processing, in associative array form
	 * @param string|null $ret Value to return if your hook returns false
	 *
	 * @return bool If true, an <a> element with HTML attributes $attribs and contents $html will be returned. If false, $ret will be returned
	 */
	public function doHtmlPageLinkRendererEnd( LinkRenderer $linkRenderer, LinkTarget $target, $isKnown, &$text, &$attribs, &$ret ): bool {
		if ( !isset( $attribs[HtmlPageLinkRendererBeginHookHandler::FORMATTED_ENTITY_LINK_ATTR] ) ) {
			return true;
		}

		unset( $attribs[HtmlPageLinkRendererBeginHookHandler::FORMATTED_ENTITY_LINK_ATTR] );

		$entityId = $this->linkTargetEntityIdLookup->getEntityId( $target );
		if ( !$entityId ) {
			return true;
		}

		$attribs['href'] = $this->entityUrlLookup->getLinkUrl( $entityId );

		return true;
	}

}
