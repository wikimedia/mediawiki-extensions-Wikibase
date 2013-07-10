<?php

namespace Wikibase;

use Html;
use Language;
use MWException;
use ValueFormatters\ValueFormatterFactory;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\EntityIdFormatter;

/**
 *
 * Class Description
 * To be added
 */
class ClaimHtmlGenerator {

	/**
	 * @since 0.4
	 *
	 * @var ValueFormatterFactory
	 */
	protected $valueFormatters;

	/**
	 * Constructor.
	 *
	 * @param ValueFormatterFactory $valueFormatters
	 */
	public function __construct( ValueFormatterFactory $valueFormatters ) {
		$this->valueFormatters = $valueFormatters;
	}

	/**
	 * Builds and returns the HTML representing a single WikibaseEntity's claim.
	 *
	 * @since 0.4
	 *
	 * @param EntityContent $entity the entity related to the claim
	 * @param Claim $claim the claim to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local
	 *        context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @param editSectionHtml has the html for the edit section
	 * @return string
	 *
	 * @throws MWException If a claim's value can't be displayed because the related value formatter
	 *         is not yet implemented or provided in the constructor. (Also see related todo)
	 */
	public function getHtmlForClaim(
		EntityContent $entity,
		Claim $claim,
		Language $lang = null,
		$editable = true,
		$editSectionHtml = null
	) {
		global $wgLang;

		wfProfileIn( __METHOD__ );

		$languageCode = isset( $lang ) ? $lang->getCode() : $wgLang->getCode();

		$entitiesPrefixMap = array();
		foreach ( Settings::get( 'entityPrefixes' ) as $prefix => $entityType ) {
			$entitiesPrefixMap[ $entityType ] = $prefix;
		}
		$valueFormatterOptions = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $languageCode,
			Lib\EntityIdFormatter::OPT_PREFIX_MAP => $entitiesPrefixMap
		) );

		// TODO: display a "placeholder" message for novalue/somevalue snak
		$value = '';
		if ( $claim->getMainSnak()->getType() === 'value' ) {
			$value = $claim->getMainSnak()->getDataValue();

			// TODO: Bad to have a switch for different data types here, implement a formatter!
			if( $value instanceof \DataValues\TimeValue ) {
				$value = $value->getTime() . ' (' . $value->getCalendarModel() . ')';
			} else {
				// Proper way, use value formatter:
				$valueFormatter = $this->valueFormatters->newFormatter(
					$value->getType(), $valueFormatterOptions
				);

				if( $valueFormatter !== null ) {
					$value = $valueFormatter->format( $value );
				} else {
					// If value representation is a string, just display that one as a
					// fallback for values not having a formatter implemented yet.
					$value = $value->getValue();

					if( !is_string( $value ) ) {
						// TODO: don't fail here, display a message in the UI instead
						throw new MWException( 'Displaying of values of type "'
							. $value->getType() . '" not supported yet' );
					}
				}
			}
		}

		$mainSnakHtml = wfTemplate( 'wb-snak',
			'wb-mainsnak',
			'', // Link to property. NOTE: we don't display this ever (instead, we generate it on
				// Claim group level) If this was a public function, this should be generated
				// anyhow since important when displaying a Claim on its own.
			'', // type selector, JS only
			( $value === '' ) ? '&nbsp;' : htmlspecialchars( $value )
		);

		// TODO: Use 'wb-claim' or 'wb-statement' template accordingly
		$claimHtml = wfTemplate( 'wb-statement',
			'', // additional classes
			$claim->getGuid(),
			$mainSnakHtml,
			'', // TODO: Qualifiers
			$editSectionHtml,
			'', // TODO: References heading
			'' // TODO: References
		);

		wfProfileOut( __METHOD__ );
		return $claimHtml;
	}
}