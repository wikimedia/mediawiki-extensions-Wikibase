<?php

namespace Wikibase\View;

use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\ByPropertyIdGrouper;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\StatementRankSerializer;
use Wikibase\View\Template\TemplateFactory;

/**
 * Base class for generating the HTML for a Claim in Entity View.
 *
 * @todo move Statement-specific formatting elsewhere.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 * @author Pragunbhutani
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ClaimHtmlGenerator {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var SnakHtmlGenerator
	 */
	private $snakHtmlGenerator;

	/**
	 * @var string[]
	 */
	private $referenceHeadings = array();

	/**
	 * @var string[]
	 */
	private $statementRankText = array();

	/**
	 * @param TemplateFactory $templateFactory
	 * @param SnakHtmlGenerator $snakHtmlGenerator
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		SnakHtmlGenerator $snakHtmlGenerator
	) {
		$this->snakHtmlGenerator = $snakHtmlGenerator;
		$this->templateFactory = $templateFactory;
	}

	/**
	 * Builds and returns the HTML representing a single WikibaseEntity's statement.
	 *
	 * @since 0.4
	 *
	 * @param Statement $statement
	 * @param null|string $editSectionHtml has the html for the edit section
	 *
	 * @return string
	 */
	public function getHtmlForClaim( Statement $statement, $editSectionHtml = null ) {
		$mainSnakHtml = $this->snakHtmlGenerator->getSnakHtml(
			$statement->getMainSnak(),
			false
		);

		$statementRankSerializer = new StatementRankSerializer();
		$serializedRank = $statementRankSerializer->serialize( $statement->getRank() );

		// Messages: wikibase-statementview-rank-preferred, wikibase-statementview-rank-normal,
		// wikibase-statementview-rank-deprecated
		$rankHtml = $this->templateFactory->render(
			'wikibase-rankselector',
			'ui-state-disabled',
			'wikibase-rankselector-' . $serializedRank,
			$this->getStatementRankText( $serializedRank )
		);

		$referencesHeading = $this->getReferencesHeading( $statement );

		$referencesHtml = $this->getHtmlForReferences(
			$statement->getReferences()
		);

		return $this->templateFactory->render(
			'wikibase-statementview',
			$statement->getGuid(),
			$rankHtml,
			$mainSnakHtml,
			$this->getHtmlForQualifiers( $statement->getQualifiers() ),
			$editSectionHtml,
			$referencesHeading,
			$referencesHtml
		);
	}

	/**
	 * Generates and returns the HTML representing a claim's qualifiers.
	 *
	 * @param SnakList $qualifiers
	 *
	 * @return string
	 */
	protected function getHtmlForQualifiers( SnakList $qualifiers ) {
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
	 * @return string
	 */
	protected function getHtmlForReferences( ReferenceList $referenceList ) {
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
	 * @return string
	 */
	protected function getHtmlForReference( $reference ) {
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
	 * @return string
	 */
	protected function getSnaklistviewHtml( $snaks ) {
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
	 * @return string
	 */
	private function getReferencesHeading( Statement $statement ) {
		$referenceCount = count( $statement->getReferences() );

		if ( !array_key_exists( $referenceCount, $this->referenceHeadings ) ) {
			$this->referenceHeadings[ $referenceCount ] = wfMessage(
				'wikibase-ui-pendingquantitycounter-nonpending',
				wfMessage(
					'wikibase-statementview-referencesheading-pendingcountersubject'
				)->numParams( $referenceCount )->text()
			)->numParams( $referenceCount )->text();
		}

		return $this->referenceHeadings[ $referenceCount ];
	}

	/**
	 * @param string $serializedRank
	 *
	 * @return string
	 */
	private function getStatementRankText( $serializedRank ) {
		if ( !array_key_exists( $serializedRank, $this->statementRankText ) ) {
			$rankText = wfMessage( 'wikibase-statementview-rank-' . $serializedRank )->text();
			$this->statementRankText[ $serializedRank ] = $rankText;
		}

		return $this->statementRankText[ $serializedRank ];
	}

}
