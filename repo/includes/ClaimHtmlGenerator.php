<?php

namespace Wikibase;

use DataValues\DataValue;
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
	 * Returns the Html for the main Snak.
	 *
	 * @param string $formattedValue
	 * @return string
	 */
	protected function getMainSnakHtml( $formattedValue ) {
		$mainSnakHtml = wfTemplate( 'wb-snak',
			'wb-mainsnak',
			'', // Link to property. NOTE: we don't display this ever (instead, we generate it on
				// Claim group level) If this was a public function, this should be generated
				// anyhow since important when displaying a Claim on its own.
			'', // type selector, JS only
			( $formattedValue === '' ) ? '&nbsp;' : $formattedValue
		);

		return $mainSnakHtml;
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

		$mainSnakHtml = $this->getMainSnakHtml(
			$this->getFormattedSnakValue( $claim->getMainSnak() )
		);

		$rankHtml = '';

		if( is_a( $claim, 'Wikibase\Statement' ) ) {
			$serializedRank = ClaimSerializer::serializeRank( $claim->getRank() );

			$rankHtml = wfTemplate( 'wb-rankselector',
				'wb-rankselector-' . $serializedRank,
				wfMessage( 'wikibase-statementview-rank-' . $serializedRank )->text()
			);
		}

		// @todo: Use 'wb-claim' or 'wb-statement' template accordingly
		// @todo: get rid of usage of global wfTemplate function
		$claimHtml = wfTemplate( 'wb-statement',
			'', // additional classes
			$rankHtml,
			$claim->getGuid(),
			$mainSnakHtml,
			$this->getHtmlForQualifiers( $claim->getQualifiers() ), // TODO: Qualifiers
			$editSectionHtml,
			'', // TODO: References heading
			'' // TODO: References
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
			$snaksHtml = '';
			$i = 0;

			foreach( $qualifiersByProperty->getByPropertyId( $propertyId ) as $snak ) {

				$propertyKey = $propertyId->getSerialization();
				$propertyLabel = isset( $this->propertyLabels[$propertyKey] )
					? $this->propertyLabels[$propertyKey]
					: $propertyKey;
				$propertyLink = \Linker::link(
					$this->entityTitleLookup->getTitleForId( $propertyId ),
					htmlspecialchars( $propertyLabel )
				);

				$snaksHtml .= wfTemplate( 'wb-snak',
					'wb-snakview',
					// Display property link only once for snaks featuring the same property:
					$i++ === 0 ? $propertyLink : '',
					'',
					( $snak->getType() === 'value' ) ? $this->getFormattedSnakValue( $snak ) : ''
				);
			}

			$snaklistviewsHtml .= wfTemplate(
				'wb-snaklistview',
				$snaksHtml
			);
		}

		return wfTemplate( 'wb-listview',
			$snaklistviewsHtml
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
