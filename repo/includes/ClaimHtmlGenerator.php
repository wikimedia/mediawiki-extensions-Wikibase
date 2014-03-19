<?php

namespace Wikibase;

use Wikibase\Lib\FormattingException;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\SnakFormatter;

/**
 * Base class for generating the HTML for a Claim in Entity View.
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
	 * @since 0.4
	 *
	 * @var SnakFormatter
	 */
	protected $snakFormatter;

	/**
	 * @since 0.5
	 *
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * Array of property labels indexed by serialized property ids
	 * @since 0.5
	 *
	 * @var string[]
	 */
	protected $propertyLabels;

	/**
	 * Constructor.
	 *
	 * @param SnakFormatter $snakFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param string[] $propertyLabels
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		EntityTitleLookup $entityTitleLookup,
		$propertyLabels = array()
	) {
		$this->snakFormatter = $snakFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->propertyLabels = $propertyLabels;
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
	public function getHtmlForClaim( Claim $claim, $editSectionHtml = null ) {
		wfProfileIn( __METHOD__ );

		$mainSnakHtml = $this->getSnakHtml( $claim->getMainSnak(), false, true );

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

			$referencesHtml = $this->getHtmlForReferences( $claim->getReferences() );
		}

		// @todo: Use 'wb-claim' or 'wb-statement' template accordingly
		// @todo: get rid of usage of global wfTemplate function
		$claimHtml = wfTemplate( 'wb-statement',
			'', // additional classes
			$rankHtml,
			$claim->getGuid(),
			$mainSnakHtml,
			$this->getHtmlForQualifiers( $claim->getQualifiers() ),
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
	 * @return string
	 */
	protected function getHtmlForQualifiers( Snaks $qualifiers ) {
		$qualifiersByProperty = new ByPropertyIdArray( $qualifiers );
		$qualifiersByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $qualifiersByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$qualifiersByProperty->getByPropertyId( $propertyId )
			);
		}

		return wfTemplate( 'wb-listview',
			$snaklistviewsHtml
		);
	}

	/**
	 * Generates the HTML for a ReferenceList object.
	 *
	 * @param ReferenceList $referenceList
	 * @return string
	 */
	protected function getHtmlForReferences( ReferenceList $referenceList ) {
		$referencesHtml = '';

		foreach( $referenceList as $reference ) {
			$referencesHtml .= $this->getHtmlForReference( $reference );
		}

		if( $referencesHtml !== '' ) {
			$referencesHtml = wfTemplate( 'wb-listview',
				$referencesHtml
			);
		}

		return $referencesHtml;
	}

	/**
	 * Generates the HTML for a Reference object.
	 *
	 * @param Reference $reference
	 * @return string
	 */
	protected function getHtmlForReference( $reference ) {
		$referenceSnaksByProperty = new ByPropertyIdArray( $reference->getSnaks() );
		$referenceSnaksByProperty->buildIndex();

		$snaklistviewsHtml = '';

		foreach( $referenceSnaksByProperty->getPropertyIds() as $propertyId ) {
			$snaklistviewsHtml .= $this->getSnaklistviewHtml(
				$referenceSnaksByProperty->getByPropertyId( $propertyId )
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
	 * @return string
	 */
	protected function getSnaklistviewHtml( $snaks ) {
		$snaksHtml = '';
		$i = 0;

		foreach( $snaks as $snak ) {
			$snaksHtml .= $this->getSnakHtml( $snak, ( $i++ === 0 ), false );
		}

		return wfTemplate(
			'wb-snaklistview',
			$snaksHtml
		);
	}

	/**
	 * Generates the HTML for a single snak.
	 *
	 * @param Snak $snak
	 * @param boolean $showPropertyLink
	 * @param boolean $showNonValueSnakContents Whether to render snak content for snaks
	 *																					which are not value snaks
	 * @return string
	 */
	protected function getSnakHtml( $snak, $showPropertyLink = false, $showNonValueSnakContents = false ) {
		$propertyLink = '';

		if( $showPropertyLink ) {
			$propertyId = $snak->getPropertyId();
			$propertyKey = $propertyId->getSerialization();
			$propertyLabel = isset( $this->propertyLabels[$propertyKey] )
				? $this->propertyLabels[$propertyKey]
				: $propertyKey;
			$propertyLink = \Linker::link(
				$this->entityTitleLookup->getTitleForId( $propertyId ),
				htmlspecialchars( $propertyLabel )
			);
		}

		$formattedValue = '';
		if( $showNonValueSnakContents || $snak->getType() === 'value' ) {
			$formattedValue = $this->getFormattedSnakValue( $snak );
		}

		if( $formattedValue === '' ) {
			$formattedValue = '&nbsp;';
		}

		return wfTemplate( 'wb-snak',
			// Display property link only once for snaks featuring the same property:
			$propertyLink,
			$formattedValue
		);
	}

	/**
	 * @param Snak $snak
	 * @return string
	 */
	protected function getFormattedSnakValue( $snak ) {
		try {
			return $this->snakFormatter->formatSnak( $snak );
		} catch ( FormattingException $ex ) {
			return '?'; // XXX: perhaps show error message?
		} catch ( PropertyNotFoundException $ex ) {
			return '?'; // XXX: perhaps show error message?
		}
	}
}
