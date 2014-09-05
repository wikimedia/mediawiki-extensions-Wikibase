/**
 * @licence GNU GPL v2+
 *
 * @author: H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
'use strict';

/**
 * Term box initialization.
 * The term box displays label and description in languages other than the user language.
 * @since 0.5
 *
 * @param {wikibase.datamodel.Entity} entity
 * @param {wikibase.AbstractedRepoApi} api
 */
wb.initTermBox = function( entity, api ) {
	mw.hook( 'wikibase.domready' ).add( function() {
		var $fingerprintview = $( '.wikibase-fingerprintview' ),
			userSpecifiedLanguages = mw.config.get( 'wbUserSpecifiedLanguages' ),
			hasSpecifiedLanguages = userSpecifiedLanguages && userSpecifiedLanguages.length,
			isUlsDefined = mw.uls !== undefined
				&& $.uls !== undefined
				&& $.uls.data !== undefined;

		// Skip if having no extra languages is what the user wants
		if( !$fingerprintview.length && !hasSpecifiedLanguages && isUlsDefined ) {
			// No term box present; Ask ULS to provide languages and generate plain HTML
			var languageCodes = mw.uls.getFrequentLanguageList();

			if( !languageCodes.length ) {
				return;
			}

			var $sectionHeading = addTermBoxSection(),
				$table = mw.template( 'wikibase-fingerprintlistview', '' );

			$sectionHeading.after( $table );

			for( var i = 1; i < languageCodes.length && i < 5; i++ ) {
				var languageCode = languageCodes[i];

				initFingerprintview(
					$( '<tbody/>' ).appendTo( $table ), languageCode, entity, api
				);
			}

			return;
		}

		$fingerprintview.each( function() {
			var $singleFingerprintview = $( this ),
				languageCode;

			// TODO: Find more sane way to figure out language code.
			$.each( $singleFingerprintview.attr( 'class' ).split( ' ' ), function( i, cssClass ) {
				if( cssClass.indexOf( 'wikibase-fingerprintview-' ) === 0 ) {
					languageCode =  cssClass.replace( /wikibase-fingerprintview-/, '' );
					return false;
				}
			} );

			initFingerprintview( $singleFingerprintview, languageCode, entity, api );
		} );
	} );
};

/**
 * @return {jQuery}
 */
function addTermBoxSection() {
	var $sectionHeading = mw.template( 'wb-terms-heading', mw.msg( 'wikibase-terms' ) ),
		$toc = $( '#toc' ),
		$precedingNode;

	if( $toc.length ) {
		$toc
		.children( 'ul' ).prepend(
			$( '<li>' )
			.addClass( 'toclevel-1' )
			.append(
				$( '<a>' )
				.attr( 'href', '#wb-terms' )
				.text( mw.msg( 'wikibase-terms' ) )
			)
		)
		.find( 'li' ).each( function( i, li ) {
			$( li )
			.removeClass( 'tocsection-' + i )
			.addClass( 'tocsection-' + ( i + 1 ) );
		} );

		$precedingNode = $toc;
	} else {
		$precedingNode = $( '.wb-aliases' );
	}

	$precedingNode.after( $sectionHeading );

	return $sectionHeading;
}

/**
 * @param {jQuery} $node
 * @param {string} languageCode
 * @param {wikibase.datamodel.Entity} entity
 * @param {wikibase.RepoApi} api
 * @return {jQuery}
 */
function initFingerprintview( $node, languageCode, entity, api ) {
	return $node.fingerprintview( {
		value: {
			language: languageCode,
			label: entity.getLabel( languageCode ) || null,
			description: entity.getDescription( languageCode ) || null
		},
		entityId: entity.getId(),
		api: api,
		helpMessage: mw.msg(
			'wikibase-fingerprintview-input-help-message',
			wb.getLanguageNameByCode( languageCode )
		)
	} );
}

} )( jQuery, mediaWiki, wikibase );
