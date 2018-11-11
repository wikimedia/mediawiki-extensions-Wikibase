<?php

namespace Wikibase\Client\Hooks;

use Language;
use Sanitizer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;

/**
 * Basic display logic to output badges in the sidebar
 *
 * @license GPL-2.0-or-later
 */
class SidebarLinkBadgeDisplay {

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var string[]
	 */
	private $badgeClassNames;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param string[] $badgeClassNames
	 * @param Language $language
	 */
	public function __construct(
		LabelDescriptionLookup $labelDescriptionLookup,
		array $badgeClassNames,
		Language $language
	) {
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->badgeClassNames = $badgeClassNames;
		$this->language = $language;
	}

	/**
	 * Applies the badges described in the wikibase_badges property of $output to
	 * the language link.
	 *
	 * This is generally called in the context of generating skin output.
	 *
	 * @param string[] &$sidebarLink
	 * @param string[] $badgeInfo An associative array with the keys 'class' and 'label' with assigned
	 * string values. These fields are the one outputted by the getBadgeInfo() function.
	 */
	public function applyBadgeToLink( array &$sidebarLink, array $badgeInfo ) {
		$badgeClass = $badgeInfo['class'];
		if ( !$badgeClass ) {
			return;
		}

		if ( isset( $sidebarLink['class'] ) ) {
			$sidebarLink['class'] .= ' ' . $badgeClass;
		} else {
			$sidebarLink['class'] = $badgeClass;
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
	 * @param ItemId[] $badgeIds
	 *
	 * @return string[] An associative array with the keys 'class' and 'label' with assigned
	 * string values. These fields correspond to the fields in the description array for language
	 * links used by the SkinTemplateGetLanguageLink hook and expected by the applyBadgeToLink()
	 * function.
	 */
	public function getBadgeInfo( array $badgeIds ) {
		$classes = [];
		$labels = [];

		foreach ( $badgeIds as $badgeId ) {
			$badgeSerialization = $badgeId->getSerialization();
			$classes[] = 'badge-' . Sanitizer::escapeClass( $badgeSerialization );

			// nicer classes for well known badges
			if ( isset( $this->badgeClassNames[$badgeSerialization] ) ) {
				// add class name
				$classes[] = Sanitizer::escapeClass( $this->badgeClassNames[$badgeSerialization] );

				// add label (but only if this badge is well known on this wiki)
				$label = $this->getLabel( $badgeId );

				if ( $label !== null ) {
					$labels[] = $label;
				}
			}
		}

		$info = [
			'class' => implode( ' ', $classes ),
			'label' => $this->language->commaList( $labels ),
		];

		return $info;
	}

	/**
	 * Returns the label for the given badge.
	 *
	 * @param ItemId $badgeId
	 *
	 * @return string|null
	 */
	private function getLabel( ItemId $badgeId ) {
		try {
			$term = $this->labelDescriptionLookup->getLabel( $badgeId );
		} catch ( LabelDescriptionLookupException $ex ) {
			return null;
		}

		if ( $term !== null ) {
			return $term->getText();
		}

		return null;
	}

}
