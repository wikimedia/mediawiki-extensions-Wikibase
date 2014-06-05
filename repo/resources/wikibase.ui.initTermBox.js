/**
 * Term box initialisation.
 * The term box displays label and description in languages other than the user language.
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author: H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	mw.hook( 'wikibase.domready' ).add( function() {
		var termsValueTools = [],
			$termBoxRows = $( 'tr.wb-terms-label, tr.wb-terms-description' ),
			userSpecifiedLanguages = mw.config.get( 'wbUserSpecifiedLanguages' ),
			hasSpecifiedLanguages = userSpecifiedLanguages && userSpecifiedLanguages.length,
			isUlsDefined = mw.uls !== undefined && $.uls !== undefined && $.uls.data !== undefined;

		// Skip if having no extra languages is what the user wants
		if( !$termBoxRows.length && !hasSpecifiedLanguages && isUlsDefined ) {
			// No term box present; Ask ULS to provide languages and generate plain HTML
			var languageCodes = mw.uls.getFrequentLanguageList(),
				title = new mw.Title(
					mw.config.get( 'wgTitle' ),
					mw.config.get( 'wgNamespaceNumber' )
				);

			if( !languageCodes.length ) {
				return;
			}

			var $sectionHeading = addTermBoxSection();
			$sectionHeading.after( renderTermBox( title, wb.entity, languageCodes.slice( 1, 4 ) ) );

			$termBoxRows = $( 'tr.wb-terms-label, tr.wb-terms-description' );
		}

		$termBoxRows.each( function() {
			var $termsRow = $( this ),
				editTool = wb.ui.PropertyEditTool[
					$termsRow.hasClass( 'wb-terms-label' ) ? 'EditableLabel' : 'EditableDescription'
				],
				$toolbar = mw.template( 'wikibase-toolbar', '', '' ).toolbar(),
				toolbar = $toolbar.data( 'toolbar' ),
				$editGroup = mw.template( 'wikibase-toolbareditgroup', '', '' ).toolbareditgroup();

			toolbar.addElement( $editGroup );

			// TODO: EditableLabel should not assume that this is set
			toolbar.$editGroup = $editGroup;

			termsValueTools.push( editTool.newFromDom( $termsRow, {}, toolbar ) );
		} );

		$( wb )
		.on( 'startItemPageEditMode', function( event, origin ) {
			// Disable language terms table's editable value or mark it as the active one if it is
			// the one being edited by the user and therefore the origin of the event
			$.each( termsValueTools, function( i, termValueTool ) {
				if(
					!( origin instanceof wb.ui.PropertyEditTool.EditableValue )
					|| origin.getSubject() !== termValueTool.getSubject()
				) {
					termValueTool.disable();
				} else if( origin && origin.getSubject() === termValueTool.getSubject() ) {
					$( 'table.wb-terms' ).addClass( 'wb-edit' );
				}
			} );
		} )
		.on( 'stopItemPageEditMode', function( event, origin ) {
			$( 'table.wb-terms' ).removeClass( 'wb-edit' );
			$.each( termsValueTools, function( i, termValueTool ) {
				termValueTool.enable();
			} );
		} );

	} );

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
	 * @param {mediaWiki.Title} title
	 * @param {wikibase.Entity} entity
	 * @param {string[]} languageCodes
	 * @return {jQuery|undefined}
	 */
	function renderTermBox( title, entity, languageCodes ) {
		if( languageCodes === undefined ) {
			return;
		}
		var labels = entity.getLabels(),
			descriptions = entity.getDescriptions(),
			$tbody = $( '<tbody>' );

		for( var i = 0; i < languageCodes.length; i++ ) {
			var languageCode = languageCodes[i],
				alternatingClass = i % 2 ? 'even' : 'uneven';

			$tbody.append( mw.template( 'wb-term',
				languageCode,
				alternatingClass,
				$.uls.data.getAutonym( languageCode ),
				labels.hasOwnProperty( languageCode ) ? labels[languageCode] : '',
				descriptions.hasOwnProperty( languageCode ) ? descriptions[languageCode] : '',
				'',
				'',
				'',
				'',
				title.getUrl( { setlang: languageCode } )
			) );
		}

		return mw.template( 'wb-terms-table', $tbody );
	}


} )( jQuery, mediaWiki, wikibase );
