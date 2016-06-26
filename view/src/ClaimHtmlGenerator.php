<?php

namespace Wikibase\View;

use ValueFormatters\NumberLocalizer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\ByPropertyIdGrouper;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\Template\TemplateFactory;

/**
 * Base class for generating the HTML for a Claim in Entity View.
 *
 * @todo move Statement-specific formatting elsewhere.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Pragunbhutani
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ClaimHtmlGenerator {

	private static $rankNames = [
		Statement::RANK_DEPRECATED => 'deprecated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred'
	];

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var SnakHtmlGenerator
	 */
	private $snakHtmlGenerator;

	/**
	 * @var NumberLocalizer
	 */
	private $numberLocalizer;

	/**
	 * @var string[]
	 */
	private $referenceHeadings = array();

	/**
	 * @var string[]
	 */
	private $statementRankSelector = array();

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param SnakHtmlGenerator $snakHtmlGenerator
	 * @param NumberLocalizer $numberLocalizer
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		SnakHtmlGenerator $snakHtmlGenerator,
		NumberLocalizer $numberLocalizer,
		LocalizedTextProvider $textProvider
	) {
		$this->snakHtmlGenerator = $snakHtmlGenerator;
		$this->templateFactory = $templateFactory;
		$this->numberLocalizer = $numberLocalizer;
		$this->textProvider = $textProvider;
	}

	/**
	 * Builds and returns the HTML representing a single WikibaseEntity's statement.
	 *
	 * @since 0.4
	 *
	 * @param Statement $statement
	 * @param null|string $editSectionHtml has the html for the edit section
	 *
	 * @return string HTML
	 */
	public function getHtmlForClaim( Statement $statement, $editSectionHtml = null ) {
		$mainSnakHtml = $this->snakHtmlGenerator->getSnakHtml(
			$statement->getMainSnak(),
			false
		);

		$rankHtml = $this->getRankSelector( $statement->getRank() );

		$referencesHeadingHtml = $this->getReferencesHeading( $statement );

		$references = $statement->getReferences();
		$referencesHtml = $this->getHtmlForReferences( $references );

		return $this->templateFactory->render(
			'wikibase-statementview',
			$statement->getGuid(),
			self::$rankNames[ $statement->getRank() ],
			$rankHtml,
			$mainSnakHtml,
			$this->getHtmlForQualifiers( $statement->getQualifiers() ),
			$editSectionHtml,
			$referencesHeadingHtml,
			$referencesHtml,
			count( $references ) ? 'wikibase-initially-collapsed' : ''
		);
	}

	/**
	 * Generates and returns the HTML representing a claim's qualifiers.
	 *
	 * @param SnakList $qualifiers
	 *
	 * @return string HTML
	 */
	private function getHtmlForQualifiers( SnakList $qualifiers ) {
		$qualifiersByProperty = new ByPropertyIdGrouper( $qualifiers );

		$snaklistviewsHtml = '';

		foreach ( $qualifiersByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$qualifiersByProperty->getByPropertyId( $propertyId )
			);
		}

		return $this->wrapInListview( $snaklistviewsHtml );
	}

	/**
	 * Generates the HTML for a ReferenceList object.
	 *
	 * @param ReferenceList $referenceList
	 *
	 * @return string HTML
	 */
	private function getHtmlForReferences( ReferenceList $referenceList ) {
		$referencesHtml = '';

		foreach ( $referenceList as $reference ) {
			$referencesHtml .= $this->getHtmlForReference( $reference );
		}

		return $this->wrapInListview( $referencesHtml );
	}

	private function wrapInListview( $listviewContent ) {
		if ( $listviewContent !== '' ) {
			return $this->templateFactory->render( 'wikibase-listview', $listviewContent );
		} else {
			return '';
		}
	}

	/**
	 * Generates the HTML for a Reference object.
	 *
	 * @param Reference $reference
	 *
	 * @return string HTML
	 */
	private function getHtmlForReference( Reference $reference ) {
		$snaks = $reference->getSnaks();

		$referenceSnaksByProperty = new ByPropertyIdGrouper( $snaks );

		$snaklistviewsHtml = '';

		foreach ( $referenceSnaksByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$referenceSnaksByProperty->getByPropertyId( $propertyId )
			);
		}

		return $this->templateFactory->render(
			'wikibase-referenceview',
			'wikibase-referenceview-' . $reference->getHash(),
			$snaklistviewsHtml
		);
	}

	/**
	 * Generates the HTML for a list of snaks.
	 *
	 * @param Snak[] $snaks
	 *
	 * @return string HTML
	 */
	private function getSnaklistviewHtml( array $snaks ) {
		$snaksHtml = '';
		$i = 0;

		foreach ( $snaks as $snak ) {
			$snaksHtml .= $this->snakHtmlGenerator->getSnakHtml( $snak, ( $i++ === 0 ) );
		}

		return $this->templateFactory->render( 'wikibase-snaklistview', $snaksHtml );
	}

	/**
	 * @param Statement $statement
	 *
	 * @return string HTML
	 */
	private function getReferencesHeading( Statement $statement ) {
		$referenceCount = count( $statement->getReferences() );

		if ( !array_key_exists( $referenceCount, $this->referenceHeadings ) ) {
			$this->referenceHeadings[ $referenceCount ] = htmlspecialchars( $this->textProvider->get(
				'wikibase-statementview-references-counter',
				[
					$this->numberLocalizer->localizeNumber( $referenceCount ),
					$this->numberLocalizer->localizeNumber( 0 ),
					'',
					''
				]
			) );
		}

		return $this->referenceHeadings[ $referenceCount ];
	}

	/**
	 * @param int $rank
	 *
	 * @return string HTML
	 */
	private function getRankSelector( $rank ) {
		if ( !array_key_exists( $rank, $this->statementRankSelector ) ) {
			$rankName = self::$rankNames[ $rank ];

			// Messages: wikibase-statementview-rank-preferred, wikibase-statementview-rank-normal,
			// wikibase-statementview-rank-deprecated
			$rankSelector = $this->templateFactory->render(
				'wikibase-rankselector',
				'ui-state-disabled',
				'wikibase-rankselector-' . $rankName,
				htmlspecialchars( $this->textProvider->get( 'wikibase-statementview-rank-' . $rankName ) )
			);

			$this->statementRankSelector[ $rank ] = $rankSelector;
		}
		return $this->statementRankSelector[ $rank ];
	}
}
