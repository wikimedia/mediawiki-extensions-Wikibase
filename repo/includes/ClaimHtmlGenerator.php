<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Lib\FormattingException;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\SnakHtmlGenerator;

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
 * @author Katie Filbert < aude.wiki@gmail.com>
 */
class ClaimHtmlGenerator {

	/**
	 * @var SnakHtmlGenerator
	 */
	protected $snakHtmlGenerator;

	/**
	 * @since 0.5
	 *
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @param SnakHtmlGenerator $snakHtmlGenerator
	 * @param EntityTitleLookup $entityTitleLookup
	 */
	public function __construct(
		SnakHtmlGenerator $snakHtmlGenerator,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->snakHtmlGenerator = $snakHtmlGenerator;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * Builds and returns the HTML representing a single WikibaseEntity's claim.
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim the claim to render
	 * @param null|string $editSectionHtml has the html for the edit section
	 *
	 * @return string
	 */
	public function getHtmlForClaim( Claim $claim, $propertyLabels, $editSectionHtml = null ) {
		wfProfileIn( __METHOD__ );

		$mainSnakHtml = $this->snakHtmlGenerator->getSnakHtml(
			$claim->getMainSnak(),
			$propertyLabels,
			false
		);

		$rankHtml = '';
		$referencesHeading = '';
		$referencesHtml = '';

		if( is_a( $claim, 'Wikibase\Statement' ) ) {
			$serializedRank = ClaimSerializer::serializeRank( $claim->getRank() );

			// Messages: wikibase-statementview-rank-preferred, wikibase-statementview-rank-normal,
			// wikibase-statementview-rank-deprecated
			$rankHtml = wfTemplate( 'wb-rankselector',
				'ui-state-disabled',
				'wb-rankselector-' . $serializedRank,
				wfMessage( 'wikibase-statementview-rank-' . $serializedRank )->text()
			);

			$referenceList = $claim->getReferences();
			$referencesHeading = wfMessage(
				'wikibase-ui-pendingquantitycounter-nonpending',
				wfMessage(
					'wikibase-statementview-referencesheading-pendingcountersubject',
					count( $referenceList )
				)->text(),
				count( $referenceList )
			)->text();

			$referencesHtml = $this->getHtmlForReferences(
				$claim->getReferences(),
				$propertyLabels
			);
		}

		// @todo: Use 'wb-claim' or 'wb-statement' template accordingly
		// @todo: get rid of usage of global wfTemplate function
		$claimHtml = wfTemplate( 'wb-statement',
			$rankHtml,
			$claim->getGuid(),
			$mainSnakHtml,
			$this->getHtmlForQualifiers( $claim->getQualifiers(), $propertyLabels ),
			$editSectionHtml,
			$referencesHeading,
			$referencesHtml
		);

		wfProfileOut( __METHOD__ );
		return $claimHtml;
	}

	/**
	 * Generates and returns the HTML representing a claim's qualifiers.
	 *
	 * @param Snaks $qualifiers
	 * @param string[] $propertyLabels
	 *
	 * @return string
	 */
	protected function getHtmlForQualifiers( Snaks $qualifiers, array $propertyLabels ) {
		$qualifiersByProperty = new ByPropertyIdArray( $qualifiers );
		$qualifiersByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $qualifiersByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$qualifiersByProperty->getByPropertyId( $propertyId ),
				$propertyLabels
			);
		}

		return $this->wrapInListview( $snaklistviewsHtml );
	}

	/**
	 * Generates the HTML for a ReferenceList object.
	 *
	 * @param ReferenceList $referenceList
	 * @param string[] $propertyLabels
	 *
	 * @return string
	 */
	protected function getHtmlForReferences( ReferenceList $referenceList, array $propertyLabels ) {
		$referencesHtml = '';

		foreach( $referenceList as $reference ) {
			$referencesHtml .= $this->getHtmlForReference( $reference, $propertyLabels );
		}

		return $this->wrapInListview( $referencesHtml );
	}

	private function wrapInListview( $listviewContent ) {
		if( $listviewContent !== '' ) {
			return wfTemplate( 'wb-listview', $listviewContent );
		} else {
			return '';
		}
	}

	/**
	 * Generates the HTML for a Reference object.
	 *
	 * @param Reference $reference
	 * @param string[] $propertyLabels
	 *
	 * @return string
	 */
	protected function getHtmlForReference( $reference, array $propertyLabels ) {
		$referenceSnaksByProperty = new ByPropertyIdArray( $reference->getSnaks() );
		$referenceSnaksByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $referenceSnaksByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$referenceSnaksByProperty->getByPropertyId( $propertyId ),
				$propertyLabels
			);
		}

		return wfTemplate( 'wb-referenceview',
			'wb-referenceview-' . $reference->getHash(),
			$snaklistviewsHtml
		);
	}

	/**
	 * Generates the HTML for a list of snaks.
	 *
	 * @param Snak[] $snaks
	 * @param string[] $propertyLabels
	 *
	 * @return string
	 */
	protected function getSnaklistviewHtml( $snaks, array $propertyLabels ) {
		$snaksHtml = '';
		$i = 0;

		foreach( $snaks as $snak ) {
			$snaksHtml .= $this->snakHtmlGenerator->getSnakHtml( $snak, $propertyLabels, ( $i++ === 0 ) );
		}

		return wfTemplate(
			'wb-snaklistview',
			$snaksHtml
		);
	}

}
