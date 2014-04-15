<?php

namespace Wikibase;

use Wikibase\Lib\Serializers\ClaimSerializer;
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
 * @author Katie Filbert < aude.wiki@gmail.com >
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
	 * @param array[] $propertyInfo
	 * @param null|string $editSectionHtml has the html for the edit section
	 *
	 * @return string
	 */
	public function getHtmlForClaim( Claim $claim, array $propertyInfo, $editSectionHtml = null ) {
		wfProfileIn( __METHOD__ );

		$mainSnakHtml = $this->snakHtmlGenerator->getSnakHtml(
			$claim->getMainSnak(),
			$propertyInfo,
			false
		);

		if( !is_a( $claim, 'Wikibase\Statement' ) ) {
			$claimHtml = wfTemplate( 'wb-claim',
				$claim->getGuid(),
				$mainSnakHtml,
				$this->getHtmlForQualifiers( $claim->getQualifiers(), $propertyInfo ),
				$editSectionHtml
			);
		} else {
			/** @var \Wikibase\Statement $claim */
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
				$propertyInfo
			);

			$claimHtml = wfTemplate( 'wb-statement',
				$rankHtml,
				$claim->getGuid(),
				$mainSnakHtml,
				$this->getHtmlForQualifiers( $claim->getQualifiers(), $propertyInfo ),
				$editSectionHtml,
				$referencesHeading,
				$referencesHtml
			);
		}

		wfProfileOut( __METHOD__ );
		return $claimHtml;
	}

	/**
	 * Generates and returns the HTML representing a claim's qualifiers.
	 *
	 * @param Snaks $qualifiers
	 * @param array[] $propertyInfo
	 *
	 * @return string
	 */
	protected function getHtmlForQualifiers( Snaks $qualifiers, array $propertyInfo ) {
		$qualifiersByProperty = new ByPropertyIdArray( $qualifiers );
		$qualifiersByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $qualifiersByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$qualifiersByProperty->getByPropertyId( $propertyId ),
				$propertyInfo
			);
		}

		return $this->wrapInListview( $snaklistviewsHtml );
	}

	/**
	 * Generates the HTML for a ReferenceList object.
	 *
	 * @param ReferenceList $referenceList
	 * @param array[] $propertyInfo
	 *
	 * @return string
	 */
	protected function getHtmlForReferences( ReferenceList $referenceList, array $propertyInfo ) {
		$referencesHtml = '';

		foreach( $referenceList as $reference ) {
			$referencesHtml .= $this->getHtmlForReference( $reference, $propertyInfo );
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
	 * @param array[] $propertyInfo
	 *
	 * @return string
	 */
	protected function getHtmlForReference( $reference, array $propertyInfo ) {
		$referenceSnaksByProperty = new ByPropertyIdArray( $reference->getSnaks() );
		$referenceSnaksByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $referenceSnaksByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$referenceSnaksByProperty->getByPropertyId( $propertyId ),
				$propertyInfo
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
	 * @param array[] $propertyInfo
	 *
	 * @return string
	 */
	protected function getSnaklistviewHtml( $snaks, array $propertyInfo ) {
		$snaksHtml = '';
		$i = 0;

		foreach( $snaks as $snak ) {
			$snaksHtml .= $this->snakHtmlGenerator->getSnakHtml( $snak, $propertyInfo, ( $i++ === 0 ) );
		}

		return wfTemplate(
			'wb-snaklistview',
			$snaksHtml
		);
	}

}
