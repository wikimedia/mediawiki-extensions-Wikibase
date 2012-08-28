/**
 * JavaScript for 'Wikibase' edit form for an items site links
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 * @author H. Snater
 */
"use strict";

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the site links of an item.
 */
window.wikibase.ui.SiteLinksEditTool = function( subject ) {
	if( typeof subject != 'undefined' ) {
		this._init( subject );
	}
};
window.wikibase.ui.SiteLinksEditTool.prototype = new window.wikibase.ui.PropertyEditTool();
$.extend( window.wikibase.ui.SiteLinksEditTool.prototype, {
	/**
	 * Shared prototype for editable site links managed by an instance of this. This allows to
	 * directly manipulate some of all of the values properties as long as they are not overwritten
	 * explicitly.
	 * @see getEditableValuePrototype()
	 * @var Function constructor for window.wikibase.ui.PropertyEditTool.EditableSiteLink
	 *      basically.
	 */
	_editableValuesProto: null,

	/**
	 * @see wikibase.ui.PropertyEditTool._init
	 *
	 * @param jQuery subject
	 */
	_init: function( subject ) {
		// add colspan+1 because of toolbar td's:
		var th = subject.find( 'th' ).first();

		$( '<th/>', {
			'class': this.UI_CLASS + '-counterinheading'
		} )
		.append( $( '<span/>', {
			'class': this.UI_CLASS + '-counter'
		} ) )
		.appendTo( th.parent() );

		window.wikibase.ui.PropertyEditTool.prototype._init.call( this, subject );

		//th.attr( 'colspan', parseInt( th.attr( 'colspan' ) ) + 1 );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._initToolbar
	 */
	_initToolbar: function() {
		window.wikibase.ui.PropertyEditTool.prototype._initToolbar.call( this );
		// change message appearing when all language links are represented within the list
		this._toolbar.lblFull.setContent( '&nbsp;- ' + mw.msg( 'wikibase-sitelinksedittool-full' ) );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._buildSingleValueToolbar
	 *
	 * @return wikibase.ui.Toolbar
	 */
	_buildSingleValueToolbar: function( editableValue ) {
		var toolbar = window.wikibase.ui.PropertyEditTool.prototype._buildSingleValueToolbar.call( this, editableValue );
		toolbar.editGroup.tooltipAnchor.getTooltip().setGravity( 'se' );
		return toolbar;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getToolbarParent
	 *
	 * @return jQuery
	 */
	_getToolbarParent: function() {
		// take content (table), put it into a div and also add the toolbar into the div
		this.__toolbarParent = this.__toolbarParent || $(
			'<td/>'
		)
		.addClass( 'wb-sitelinks-toolbar' )
		.appendTo(
			$( '<tr/>' ).append(
				$( '<td/>', {
					colspan: '3',
					'class': 'wb-empty'
				} ) // empty cell moving the "add" button to the action column
			)
			.appendTo(
				$( '<tfoot/>' ).appendTo( this._subject )
			)
		);
		return this.__toolbarParent;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._initEditToolForValues
	 */
	_initEditToolForValues: function() {
		window.wikibase.ui.PropertyEditTool.prototype._initEditToolForValues.call( this );

		// make sure selecting a language in EditableSiteLink only offers languages not yet chosen
		this.getEditableValuePrototype().prototype.ignoredSiteLinks = this.getRepresentedSites();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._initSingleValue
	 *
	 * @param jQuery valueElem
	 * @return wikibase.ui.PropertyEditTool.EditableValue the initialized value
	 */
	_initSingleValue: function( valueElem ) {
		var editableSiteLink = window.wikibase.ui.PropertyEditTool.prototype._initSingleValue.call( this, valueElem );

		var valElem = editableSiteLink._subject;

		valElem.on( 'mouseenter', function(){
			valElem.addClass( editableSiteLink.UI_CLASS + '-inhover' );
		} );
		valElem.on( 'mouseleave', function(){
			valElem.removeClass( editableSiteLink.UI_CLASS + '-inhover' );
			// NOTE: in case still in edit mode, we NEVER hide it via some css rules
		} );

		return editableSiteLink;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.enterNewValue
	 *
	 * @param value Object optional, initial value
	 * @return newValue wikibase.ui.PropertyEditTool.EditableValue
	 */
	enterNewValue: function( value ) {
		var newValue = wikibase.ui.PropertyEditTool.prototype.enterNewValue.call( this, value );

		// this would be the first site link -> attach column headers
		if ( this._editableValues.length === 1 ) {
			$( 'table.wb-sitelinks thead' ).append(
				$( '<tr/>' )
					.addClass( 'wb-sitelinks-columnheaders' )
					.append(
					$( '<th/>' )
						.addClass( 'wb-sitelinks-sitename' )
						.text( mw.message( 'wikibase-sitelinks-sitename-columnheading' ).escaped() )
				)
					.append(
					$( '<th/>' )
						.addClass( 'wb-sitelinks-siteid' )
						.text( mw.message( 'wikibase-sitelinks-siteid-columnheading' ).escaped() )
				)
					.append(
					$( '<th/>' )
						.addClass( 'wb-sitelinks-link' )
						.text( mw.message( 'wikibase-sitelinks-link-columnheading' ).escaped() )
				)
					.append( $( '<th/>' ).addClass( 'wb-sitelinks-sitename' ) )
			);
		}

		return newValue;
	},

	/**
	 * Full when all languages are represented within
	 * @see wikibase.ui.PropertyEditTool.isFull
	 *
	 * @return bool
	 */
	isFull: function() {
		var allSites = window.wikibase.getSites();
		var usedSites = this.getRepresentedSites();

		// FIXME: in case we have sites in the DB which were removed at some point, this check will
		//        return true for isFull() too early! Make an array diff instead!
		return usedSites.length >= Object.keys( allSites ).length;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getValueElems
	 *
	 * @return jQuery[]
	 */
	_getValueElems: function() {
		// select all rows but not heading!
		return this._subject.find( 'tr:not(:has(th))' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._newEmptyValueDOM
	 *
	 * @return jQuery
	 */
	_newEmptyValueDOM: function() {
		return $( '<tr/>' )
			.append( $( '<td colspan="2" class="wb-sitelinks-sitename"/>' ) )
			.append( $( '<td class="wb-sitelinks-link"/>' ) );
	},

	/**
	 * @see window.wikibase.ui.PropertyEditTool.getEditableValuePrototype
	 */
	getEditableValuePrototype: function() {
		// TODO: this system might be useful in other prototypes based on PropertyEditTool, implement
		//       this in PropertyEditTool directly perhaps?
		if( this._editableValuesProto !== null ) {
			return this._editableValuesProto;
		}
		var basePrototype = window.wikibase.ui.PropertyEditTool.EditableSiteLink;

		this._editableValuesProto = function() { basePrototype.apply( this, arguments ); };
		this._editableValuesProto.prototype = new basePrototype();
		this._editableValuesProto.prototype.ignoredSiteLinks = this.getRepresentedSites();

		return this._editableValuesProto;
	},

	/**
	 * @see window.wikibase.ui.PropertyEditTool._editableValueHandler_onAfterRemove
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue
	 */
	_editableValueHandler_onAfterRemove: function( editableValue ) {
		window.wikibase.ui.PropertyEditTool.prototype._editableValueHandler_onAfterRemove.call( this, editableValue );

		// remove only site used by removed site link from list of ignored sites:
		var removedSite = editableValue.siteIdInterface.getSelectedSite();
		if( removedSite !== null ) {
			var index = $.inArray( removedSite, this._editableValuesProto.prototype.ignoredSiteLinks );
			if( index > -1 ) {
				// remove site link from ignored site links shared by all values managed by this:
				this._editableValuesProto.prototype.ignoredSiteLinks.splice( index, 1 );

				var pendingValues = this.getPendingValues();
				for( var i in pendingValues ) {
					// re-init site link list for value in edit mode currently
					pendingValues[ i ].siteIdInterface._initSiteList();
				}
			}
		}
		if ( this._editableValues.length === 0 ) { // no more site links -> remove column headers
			$( 'table.wb-sitelinks thead' ).children( 'tr' ).last().remove();
		}
	},

	/**
	 * @see window.wikibase.ui.PropertyEditTool._newValueHandler_onAfterStopEditing
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue newValue
	 * @param bool save
	 * @param bool wasPending
	 */
	_newValueHandler_onAfterStopEditing: function( newValue, save, wasPending ) {
		window.wikibase.ui.PropertyEditTool.prototype._newValueHandler_onAfterStopEditing.call( this, newValue, save );
		if( save ) {
			// add chosen site to list of sites which can not be chosen by other editable site links
			var addedSite = newValue.siteIdInterface.getSelectedSite();
			this._editableValuesProto.prototype.ignoredSiteLinks.push( addedSite );
		}
	},

	/**
	 * Returns a list of sites already represented with a value.
	 *
	 * @return wikibase.Site[]
	 */
	getRepresentedSites: function() {
		var sites = [];

		for( var i in this._editableValues ) {
			var editableSiteLink = this._editableValues[ i ];
			var site = editableSiteLink.siteIdInterface.getSelectedSite();
			if( site !== null ) {
				sites.push( site );
			}
		}
		return sites;
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * @see window.wikibase.ui.PropertyEditTool.prototype.allowsMultipleValues
	 */
	allowsMultipleValues: true
} );

/**
 * Returns the basic DOM structure sufficient for a new wikibase.ui.SiteLinksEditTool
 *
 * @return jQuery
 */
window.wikibase.ui.SiteLinksEditTool.getEmptyStructure = function() {
	return $(
			'<table class="wb-sitelinks" cellspacing="0">' +
				'<colgroup>' +
					'<col class="wb-sitelinks-sitename" />' +
					'<col class="wb-sitelinks-siteid" />' +
					'<col class="wb-sitelinks-link" />' +
					'<col class="wb-ui-propertyedittool-editablevalue-toolbarparent" />' +
				'</colgroup>' +
				'<thead><th colspan="3"><h3>' +
					mw.message( 'wikibase-sitelinks' ).escaped() +
				'</h3></th></thead>' +
				'<tbody></tbody>' +
			'</table>'
	);
};
