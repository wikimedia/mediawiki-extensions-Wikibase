<?php

namespace Wikibase;

use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snaks;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\SnakHtmlGenerator;

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
	 * @param array[] $entityInfo
	 * @param null|string $editSectionHtml has the html for the edit section
	 *
	 * @return string
	 */
	public function getHtmlForClaim( Claim $claim, array $entityInfo, $editSectionHtml = null ) {
		wfProfileIn( __METHOD__ );

		$mainSnakHtml = $this->snakHtmlGenerator->getSnakHtml(
			$claim->getMainSnak(),
			$entityInfo,
			false
		);

		if ( !( $claim instanceof Statement ) ) {
			$claimHtml = wfTemplate( 'wb-claim',
				$claim->getGuid(),
				$mainSnakHtml,
				$this->getHtmlForQualifiers( $claim->getQualifiers(), $entityInfo ),
				$editSectionHtml
			);
		} else {
			/** @var Statement $claim */
			$serializedRank = ClaimSerializer::serializeRank( $claim->getRank() );

			// Messages: wikibase-statementview-rank-preferred, wikibase-statementview-rank-normal,
			// wikibase-statementview-rank-deprecated
			$rankHtml = wfTemplate( 'wb-rankselector',
				'ui-state-disabled',
				'wb-rankselector-' . $serializedRank,
				wfMessage( 'wikibase-statementview-rank-' . $serializedRank )->text()
			);

			$referenceCount = count( $claim->getReferences() );

			$referencesHeading = wfMessage(
				'wikibase-ui-pendingquantitycounter-nonpending',
				wfMessage(
					'wikibase-statementview-referencesheading-pendingcountersubject'
				)->numParams( $referenceCount )->text()
			)->numParams( $referenceCount )->text();

			$referencesHtml = $this->getHtmlForReferences(
				$claim->getReferences(),
				$entityInfo
			);

			$claimHtml = wfTemplate( 'wb-statement',
				$rankHtml,
				wfTemplate( 'wb-claim',
					$claim->getGuid(),
					$mainSnakHtml,
					$this->getHtmlForQualifiers( $claim->getQualifiers(), $entityInfo ),
					''
				),
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
	 * @param array[] $entityInfo
	 *
	 * @return string
	 */
	protected function getHtmlForQualifiers( Snaks $qualifiers, array $entityInfo ) {
		$qualifiersByProperty = new ByPropertyIdArray( iterator_to_array( $qualifiers ) );
		$qualifiersByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $qualifiersByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$qualifiersByProperty->getByPropertyId( $propertyId ),
				$entityInfo
			);
		}

		return $this->wrapInListview( $snaklistviewsHtml );
	}

	/**
	 * Generates the HTML for a ReferenceList object.
	 *
	 * @param ReferenceList $referenceList
	 * @param array[] $entityInfo
	 *
	 * @return string
	 */
	protected function getHtmlForReferences( ReferenceList $referenceList, array $entityInfo ) {
		$referencesHtml = '';

		foreach( $referenceList as $reference ) {
			$referencesHtml .= $this->getHtmlForReference( $reference, $entityInfo );
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
	 * @param array[] $entityInfo
	 *
	 * @return string
	 */
	protected function getHtmlForReference( $reference, array $entityInfo ) {
		$snaks = $reference->getSnaks();

		$referenceSnaksByProperty = new ByPropertyIdArray( iterator_to_array( $snaks ) );
		$referenceSnaksByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $referenceSnaksByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$referenceSnaksByProperty->getByPropertyId( $propertyId ),
				$entityInfo
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
	 * @param array[] $entityInfo
	 *
	 * @return string
	 */
	protected function getSnaklistviewHtml( $snaks, array $entityInfo ) {
		$snaksHtml = '';
		$i = 0;

		foreach( $snaks as $snak ) {
			$snaksHtml .= $this->snakHtmlGenerator->getSnakHtml( $snak, $entityInfo, ( $i++ === 0 ) );
		}

		return wfTemplate(
			'wb-snaklistview',
			$snaksHtml
		);
	}

}
