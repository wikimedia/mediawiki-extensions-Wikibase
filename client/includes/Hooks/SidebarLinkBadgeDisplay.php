<?php

namespace Wikibase\Client\Hooks;

use Language;
use Sanitizer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Basic display logic to output badges in the sidebar
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 */
class SidebarLinkBadgeDisplay {

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var array
	 */
	protected $badgeClassNames;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param EntityLookup $entityLookup
	 * @param array $badgeClassNames
	 * @param Language $language
	 */
	public function __construct( EntityLookup $entityLookup, array $badgeClassNames, Language $language ) {
		$this->entityLookup = $entityLookup;
		$this->badgeClassNames = $badgeClassNames;
		$this->language = $language;
	}

	/**
	 * Applies the badges described in the wikibase_badges property of $output to
	 * the language link to $languageLinkTitle. The badge info for this linked is
	 * looked up in the wikibase_badges data using the key returned by
	 * $languageLinkTitle->getInterwiki().
	 *
	 * This is generally called in the context of generating skin output.
	 *
	 * @since 0.5
	 *
	 * @param array $sidebarLink
	 * @param array $badgeInfo An associative array with the keys 'class' and 'itemtitle' with assigned
	 * string values. These fields are the one outputted by the getBadgeInfo() function.
	 */
	public function applyBadgeToLink( array &$sidebarLink, array $badgeInfo ) {
		if ( isset( $sidebarLink['class'] ) ) {
			$sidebarLink['class'] .= ' ' . $badgeInfo['class'];
		} else {
			$sidebarLink['class'] = $badgeInfo['class'];
		}

		$sidebarLink['itemtitle'] = $badgeInfo['label'];
	}

	/**
	 * Builds badge information for the given badges.
	 * CSS classes are derived from the given list of badges, and any extra badge class
	 * names specified in the badgeClassNames setting are added.
	 * For badges that have a such an extra class name assigned, this also
	 * adds a title according to the items' labels. Other badges do not have labels
	 * added to the link's title attribute, so the can be effectively ignored
	 * on this client wiki.
	 *
	 * @param ItemId[] $badges
	 *
	 * @return array An associative array with the keys 'class' and 'itemtitle' with assigned
	 * string values. These fields correspond to the fields in the description array for language
	 * links used by the SkinTemplateGetLanguageLink hook and expected by the applyBadgeToLink()
	 * function.
	 */
	public function getBadgeInfo( array $badges ) {
		$classes = array();
		$labels = array();

		foreach ( $badges as $badge ) {
			$badgeSerialization = $badge->getSerialization();
			$classes[] = 'badge-' . Sanitizer::escapeClass( $badgeSerialization );

			// nicer classes for well known badges
			if ( isset( $this->badgeClassNames[$badgeSerialization] ) ) {
				// add class name
				$classes[] = Sanitizer::escapeClass( $this->badgeClassNames[$badgeSerialization] );

				// add label (but only if this badge is well known on this wiki)
				$label = $this->getLabel( $badge );

				if ( $label !== null ) {
					$labels[] = $label;
				}
			}
		}

		$info = array(
			'class' => implode( ' ', $classes ),
			'label' => $this->language->commaList( $labels ),
		);

		return $info;
	}

	/**
	 * Returns the label for the given badge.
	 *
	 * @param ItemId $badge
	 *
	 * @return string|null
	 */
	private function getLabel( ItemId $badge ) {
		$entity = $this->entityLookup->getEntity( $badge );
		if ( !$entity ) {
			return null;
		}

		$title = $entity->getLabel( $this->language->getCode() );
		if ( !$title ) {
			return null;
		}
		return $title;
	}

}
