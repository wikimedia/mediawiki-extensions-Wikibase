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
 * @param {wikibase.RepoApi} api
 */
wb.initTermBox = function( entity, api ) {
	mw.hook( 'wikibase.domready' ).add( function() {
		var $fingerprintgroupview = $( '.wikibase-fingerprintgroupview' ),
			userSpecifiedLanguages = mw.config.get( 'wbUserSpecifiedLanguages' ),
			hasSpecifiedLanguages = userSpecifiedLanguages && userSpecifiedLanguages.length,
			isUlsDefined = mw.uls !== undefined
				&& $.uls !== undefined
				&& $.uls.data !== undefined,
			languageCodes = [];

		// Skip if having no extra languages is what the user wants
		if( !$fingerprintgroupview.length && !hasSpecifiedLanguages && isUlsDefined ) {
			// No term box present; Ask ULS to provide languages and generate plain HTML
			languageCodes = mw.uls.getFrequentLanguageList().slice( 1, 4 );

			if( !languageCodes.length ) {
				return;
			}

			var $precedingNode = $( '#toc' );

			if( $precedingNode.length ) {
				updateToc( $precedingNode );
			} else {
				$precedingNode = $( '.wikibase-aliasesview' );
			}

			$fingerprintgroupview = $( '<div/>' ).appendTo( $precedingNode );

		} else {
			// Scrape language codes of existing DOM structure:
			// TODO: Find more sane way to figure out language code.
			$fingerprintgroupview.find( '.wikibase-fingerprintview' ).each( function() {
				$.each( $( this ).attr( 'class' ).split( ' ' ), function( i, cssClass ) {
					if( cssClass.indexOf( 'wikibase-fingerprintview-' ) === 0 ) {
						languageCodes.push( cssClass.replace( /wikibase-fingerprintview-/, '' ) );
						return false;
					}
				} );
			} );
		}

		var value = [];
		for( var i = 0; i < languageCodes.length; i++ ) {
			value.push( {
				language: languageCodes[i],
				label: entity.getLabel( languageCodes[i] ) || null,
				description: entity.getDescription( languageCodes[i] ) || null
			} );
		}

		$fingerprintgroupview.fingerprintgroupview( {
			value: value,
			entityId: entity.getId(),
			api: api
		} );
	} );
};

/**
 * @param {jQuery} $toc
 */
function updateToc( $toc ) {
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
}

} )( jQuery, mediaWiki, wikibase );
