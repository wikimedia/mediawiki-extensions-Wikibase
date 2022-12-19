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
 * Backend renderer that generates the HTML representation of a statement for use in an entity view.
 *
 * @license GPL-2.0-or-later
 */
class StatementHtmlGenerator {

	private const RANK_NAMES = [
		Statement::RANK_DEPRECATED => 'deprecated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred',
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
	private $referenceHeadings = [];

	/**
	 * @var string[]
	 */
	private $statementRankSelector = [];

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

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
	 * @param Statement $statement
	 * @param string $editSectionHtml has the html for the edit section
	 *
	 * @return string HTML
	 */
	public function getHtmlForStatement( Statement $statement, $editSectionHtml ) {
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
			self::RANK_NAMES[ $statement->getRank() ],
			$rankHtml,
			$mainSnakHtml,
			$this->getHtmlForQualifiers( $statement->getQualifiers() ),
			$editSectionHtml,
			$referencesHeadingHtml,
			$referencesHtml,
			$references->isEmpty() ? '' : 'wikibase-initially-collapsed'
		);
	}

	/**
	 * @param SnakList $qualifiers
	 *
	 * @return string HTML
	 * @suppress PhanTypeMismatchArgument
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
	 * @param Reference $reference
	 *
	 * @return string HTML
	 * @suppress PhanTypeMismatchArgument
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
			$this->referenceHeadings[ $referenceCount ] = $this->textProvider->getEscaped(
				'wikibase-statementview-references-counter',
				[
					$this->numberLocalizer->localizeNumber( $referenceCount ),
				]
			);
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
			$rankName = self::RANK_NAMES[ $rank ];

			// Messages: wikibase-statementview-rank-preferred, wikibase-statementview-rank-normal,
			// wikibase-statementview-rank-deprecated
			$rankSelector = $this->templateFactory->render(
				'wikibase-rankselector',
				'ui-state-disabled',
				'wikibase-rankselector-' . $rankName,
				$this->textProvider->getEscaped( 'wikibase-statementview-rank-' . $rankName )
			);

			$this->statementRankSelector[ $rank ] = $rankSelector;
		}
		return $this->statementRankSelector[ $rank ];
	}

}
