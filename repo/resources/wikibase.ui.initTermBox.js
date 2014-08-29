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
wb.ui.initTermBox = function( entity, api ) {
	mw.hook( 'wikibase.domready' ).add( function() {
		var $termBoxRows = $( 'tr.wb-terms-label, tr.wb-terms-description' ),
			userSpecifiedLanguages = mw.config.get( 'wbUserSpecifiedLanguages' ),
			hasSpecifiedLanguages = userSpecifiedLanguages && userSpecifiedLanguages.length,
			isUlsDefined = mw.uls !== undefined
				&& $.uls !== undefined
				&& $.uls.data !== undefined;

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
			$sectionHeading.after(
				renderTermBox( title, entity, languageCodes.slice( 1, 4 ) )
			);

			$termBoxRows = $( 'tr.wb-terms-label, tr.wb-terms-description' );
		}

		$termBoxRows.each( function() {
			var $termsRow = $( this ),
				languageCode;

			// TODO: Find more sane way to figure out language code.
			$.each( $termsRow.attr( 'class' ).split( ' ' ), function( i, cssClass ) {
				if(
					cssClass.indexOf( 'wb-terms-' ) === 0
					&& cssClass.indexOf( 'wb-terms-label' ) === -1
					&& cssClass.indexOf( 'wb-terms-description' ) === -1
				) {
					languageCode =  cssClass.replace( /wb-terms-/, '' );
					return false;
				}
			} );

			if( $termsRow.hasClass( 'wb-terms-label' ) ) {
				$termsRow.children( 'td' ).eq( 1 ).labelview( {
					value: {
						language: languageCode,
						label: entity.getLabel( languageCode )
					},
					helpMessage: mw.msg(
						'wikibase-label-input-help-message',
						wb.getLanguageNameByCode( languageCode )
					),
					entityId: entity.getId(),
					api: api
				} );

				return true;
			}

			$termsRow.children( 'td' ).first().descriptionview( {
				value: {
					language: languageCode,
					description: entity.getDescription( languageCode )
				},
				helpMessage: mw.msg(
					'wikibase-description-input-help-message',
					wb.getLanguageNameByCode( languageCode )
				),
				entityId: entity.getId(),
				api: api
			} );

		} );

		$( wb )
		.on( 'startItemPageEditMode', function( event, origin ) {
			$termBoxRows.find( ':wikibase-labelview, :wikibase-descriptionview' )
			.not( origin )
			.each( function() {
				( $( this ).data( 'labelview' ) || $( this ).data( 'descriptionview' ) )
					.disable();
				$( this ).data( 'edittoolbar' ).toolbar.disable();
			} );
		} )
		.on( 'stopItemPageEditMode', function( event, origin ) {
			$termBoxRows.find( ':wikibase-labelview' ).each( function() {
				var labelview = $( this ).data( 'labelview' );

				if( labelview.value().label ) {
					var toolbar = $( this ).data( 'edittoolbar' ).toolbar,
						btnEdit = toolbar.editGroup.getButton( 'edit' ).data( 'toolbarbutton' );

					$( this ).data( 'edittoolbar' ).toolbar.enable();

					// FIXME: Get rid of StatableObject making things complicated
					btnEdit.setState( btnEdit.STATE.ENABLED );
				}
				labelview.enable();
			} );

			$termBoxRows.find( ':wikibase-descriptionview' ).each( function() {
				var descriptionview = $( this ).data( 'descriptionview' );

				if( descriptionview.value().description ) {
					var toolbar = $( this ).data( 'edittoolbar' ).toolbar,
						btnEdit = toolbar.editGroup.getButton( 'edit' ).data( 'toolbarbutton' );

					$( this ).data( 'edittoolbar' ).toolbar.enable();

					// FIXME: Get rid of StatableObject making things complicated
					btnEdit.setState( btnEdit.STATE.ENABLED );
				}
				descriptionview.enable();
			} );
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
 * @param {mediaWiki.Title} title
 * @param {wikibase.datamodel.Entity} entity
 * @param {string[]} languageCodes
 * @return {jQuery|undefined}
 */
function renderTermBox( title, entity, languageCodes ) {
	if( languageCodes === undefined ) {
		return;
	}
	var labels = entity.getLabels(),
		descriptions = entity.getDescriptions(),
		$tbody = $();

	for( var i = 0; i < languageCodes.length; i++ ) {
		var languageCode = languageCodes[i];

		$tbody = $tbody.add( mw.template( 'wb-term',
			languageCode,
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

// TODO: Merge with native labelview/descriptionview toolbar definiton
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'terms-labelview',
	selector: '.wb-terms-label',
	events: {
		labelviewcreate: function( event, toolbarcontroller ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' );

			$labelview.edittoolbar( {
				$container: $labelview.next(),
				interactionWidgetName: $.wikibase.labelview.prototype.widgetName,
				enableRemove: false
			} );

			$labelview.on( 'keyup', function( event ) {
				if( labelview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					labelview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					labelview.stopEditing( false );
				}
			} );

			if( !labelview.value().label ) {
				labelview.startEditing();
			}
		},
		'labelviewchange labelviewafterstartediting': function( event ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				toolbar = $labelview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = labelview.isValid() && !labelview.isInitialValue(),
				$btnCancel = toolbar.editGroup.getButton( 'cancel' ),
				btnCancel = $btnCancel.data( 'toolbarbutton' ),
				currentLabel = labelview.value().label,
				disableCancel = !currentLabel && labelview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
			btnCancel[disableCancel ? 'disable' : 'enable']();
		},
		labelviewafterstopediting: function( event, dropValue ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' );

			if( !labelview.value().label ) {
				labelview.startEditing();
			}
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
			var $labelview = $( event.target ).closest( ':wikibase-edittoolbar' ),
				labelview = $labelview.data( 'labelview' );

			if( !labelview ) {
				return;
			}

			labelview.focus();
		}
	}
} );
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'terms-descriptionview',
	selector: '.wb-terms-description',
	events: {
		descriptionviewcreate: function( event, toolbarcontroller ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			$descriptionview.edittoolbar( {
				$container: $descriptionview.next(),
				interactionWidgetName: $.wikibase.descriptionview.prototype.widgetName,
				enableRemove: false
			} );

			$descriptionview.on( 'keyup', function( event ) {
				if( descriptionview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					descriptionview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					descriptionview.stopEditing( false );
				}
			} );

			if( !descriptionview.value().description ) {
				descriptionview.startEditing();
			}
		},
		'descriptionviewchange descriptionviewafterstartediting': function( event ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				toolbar = $descriptionview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = descriptionview.isValid() && !descriptionview.isInitialValue(),
				$btnCancel = toolbar.editGroup.getButton( 'cancel' ),
				btnCancel = $btnCancel.data( 'toolbarbutton' ),
				currentDescription = descriptionview.value().description,
				disableCancel = !currentDescription && descriptionview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
			btnCancel[disableCancel ? 'disable' : 'enable']();
		},
		descriptionviewafterstopediting: function( event, dropValue ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			if( !descriptionview.value().description ) {
				descriptionview.startEditing();
			}
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
			var $descriptionview = $( event.target ).closest( ':wikibase-edittoolbar' ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			if( !descriptionview ) {
				return;
			}

			descriptionview.focus();
		}
	}
} );

} )( jQuery, mediaWiki, wikibase );
