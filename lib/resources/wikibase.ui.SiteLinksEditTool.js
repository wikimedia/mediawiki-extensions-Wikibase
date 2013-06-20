/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater <mediawiki@snater.com>
 */
( function( mw, wb, $ ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.PropertyEditTool;

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the site links of an item.
 * wb.ui.SiteLinksEditTool.getEmptyStructure() can be used to get an empty DOM structure which can
 * be used as the SiteLinkEditTool's subject.
 *
 * @example http://pastebin.com/u7yLnJNg
 *
 * @constructor
 * @see wb.ui.PropertyEditTool
 * @since 0.1
 */
wb.ui.SiteLinksEditTool = wb.utilities.inherit( PARENT, {
	/**
	 * Shared prototype for editable site links managed by an instance of this. This allows to
	 * directly manipulate some of all of the values properties as long as they are not overwritten
	 * explicitly.
	 * @see getEditableValuePrototype()
	 * @var Function constructor for wb.ui.PropertyEditTool.EditableSiteLink
	 *      basically.
	 */
	_editableValuesProto: null,

	/**
	 * @see wb.ui.PropertyEditTool._init
	 */
	_init: function( subject, options ) {
		var self = this;

		// setting default options
		this._options = $.extend( {}, PARENT.prototype._options, {
			/**
			 * @see wikibase.ui.PropertyEditTool.allowsMultipleValues
			 */
			allowsMultipleValues: true,

			/**
			 * @see wikibase.ui.PropertyEditTool.fullListMessage
			 */
			fullListMessage: mw.msg( 'wikibase-sitelinksedittool-full' ),

			/**
			 * Should contain all the sites that can be used in creating site links
			 * @type wb.Site[]|Object
			 */
			allowedSites: []
		} );

		PARENT.prototype._init.call( this, subject, options );

		// TODO: Just introduce a SiteList prototype which will make all of the (even incomplete)
		//  validation here pointless, allowing to get rid of some complexity here.
		var allowedSites = this.getOption( 'allowedSites' );

		if( !$.isArray( allowedSites ) && !$.isPlainObject( allowedSites ) ) {
			throw new Error( '"allowedSites" option has to be an array or plain object' );
		}
		$.each( allowedSites, function( i, site ) {
			if( !( site instanceof wb.Site ) ) {
				throw new Error( '"allowedSites" option has to be a set of wb.Site objects' );
			}
		} );

		this._fixateTableLayout();

		// re-assign alternating table row colours when sorting the table
		this._subject.on( 'sortEnd.tablesorter', function( event ) {
			self.refreshView(); // will re-assign even/uneven classes
		} );

		if ( this._subject.children( 'thead' ).children().length > 0 ) {
			// initially sort on the site id column
			this._subject.tablesorter( { sortList: [{1:'asc'}] } );
		}
	},

	/**
	 * @see wb.ui.PropertyEditTool._buildSingleValueToolbar
	 *
	 * @param {Object} [options]
	 * @return {wb.ui.Toolbar}
	 */
	_buildSingleValueToolbar: function( options ) {
		var toolbar = PARENT.prototype._buildSingleValueToolbar.call( this, options );
		toolbar.editGroup.tooltipAnchor.getTooltip().setGravity( 'se' );
		return toolbar;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getToolbarParent
	 *
	 * @return jQuery
	 */
	_getToolbarParent: function() {
		return this._subject.children( 'tfoot' ).find( '.wb-editsection' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._initEditToolForValues
	 */
	_initEditToolForValues: function() {
		PARENT.prototype._initEditToolForValues.call( this );
	},

	/**
	 * @see wb.ui.PropertyEditTool._initSingleValue
	 *
	 * @param {jQuery} valueElem
	 * @param {Object} [options]
	 * @return {wb.ui.PropertyEditTool.EditableValue} the initialized value
	 */
	_initSingleValue: function( valueElem, options ) {
		var editableSiteLink = PARENT.prototype._initSingleValue.call( this, valueElem, options );

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
	 * @see wb.ui.PropertyEditTool.enterNewValue
	 *
	 * @param {Object} [value] initial value
	 * @return {wb.ui.PropertyEditTool.EditableValue}
	 */
	enterNewValue: function( value ) {
		var $siteLinksTable = this._subject;

		// Attach the headers before initializing the new value in order to not have the license
		// information tooltip appear in the wrong spot. (The new value's table row would be shifted
		// after adding the headers.)
		if ( this._editableValues.length === 0 ) {
			// This would be the first site link -> attach column headers.
			$siteLinksTable.children( 'thead' ).append(
				mw.template( 'wb-sitelinks-thead',
					mw.message( 'wikibase-sitelinks-sitename-columnheading' ).escaped(),
					mw.message( 'wikibase-sitelinks-siteid-columnheading' ).escaped(),
					mw.message( 'wikibase-sitelinks-link-columnheading' ).escaped()
				)
			);
		}

		var newValue = wb.ui.PropertyEditTool.prototype.enterNewValue.call(
			this, value, { prepend: true, displayRemoveButton: false }
		);

		$( newValue ).on( 'afterStopEditing', function( event, save, wasPending ) {
			if ( save ) {
				// move appended site link into the table body to have it included in sorting
				this.getSubject().appendTo( $siteLinksTable.children( 'tbody' ) );

				// init tablesorter if it has not been initialised yet (no site link existed
				// previous to adding the just added site link)
				if ( $siteLinksTable.data( 'tablesorter' ) === undefined ) {
					$siteLinksTable.tablesorter();
				} else {
					// reset sorting having the sort order appear undefined when appending a new
					// site link to the bottom of the table
					$siteLinksTable.data( 'tablesorter' ).sort( [] );
				}
			}
		} );

		return newValue;
	},

	/**
	 * Determines if all sites are in use.
	 * @see wikibase.ui.PropertyEditTool.isFull
	 *
	 * @return {Boolean}
	 */
	isFull: function() {
		return this.getUnusedAllowedSiteIds().length === 0;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getValuesParent
	 */
	_getValuesParent: function() {
		// The input form for new values is appended to the table footer in order to leave its
		// position unaffected when the table is sorted while adding the new value. As soon as the
		// new value is saved, it will be moved into the table body.
		return this._subject.children( 'tfoot' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getValueElems
	 *
	 * @return jQuery[]
	 */
	_getValueElems: function() {
		// select all rows but neither heading nor footer!
		return this._subject.find( 'tbody tr' );
	},

	/**
	 * @see wb.ui.PropertyEditTool._newEmptyValueDOM
	 *
	 * @return {jQuery}
	 */
	_newEmptyValueDOM: function() {
		return mw.template( 'wb-sitelink-new' );
	},

	/**
	 * @see wb.ui.PropertyEditTool.getEditableValuePrototype
	 */
	getEditableValuePrototype: function() {
		if( this._editableValuesProto !== null ) {
			return this._editableValuesProto;
		}
		// inherits from the actual prototype so all EditableSiteLink instances coming from the
		// same SiteLinksEditTool will know about the site links already set. This is done by all
		// of them sharing the 'ignoredSiteLinks' option via their prototype.
		// TODO: this could be considered a hack. Also, probably won't work with the new options system
		var BaseProto = wb.ui.PropertyEditTool.EditableSiteLink,
			EnhancedProto = function() { BaseProto.apply( this, arguments ); };

		EnhancedProto.prototype = new BaseProto();

		// Make sure selecting a language in EditableSiteLink only offers sites not yet chosen
		// and sites this edit tool is actually responsible for (e.g. because they are in a certain
		// group).
		var ignoredSiteIds = [],
			unusedAllowedSiteIds = this.getUnusedAllowedSiteIds();

		// TODO: very ugly to access global set of all sites here. Also kind of ugly to do this by
		//  abusing the prototype field of the EditableValue instances here. Instead, the
		//  "ignoredSiteLinks" thing (which is even named wrong) should go away, instead we should
		//  have "allowedSites" options all the way to the SiteIdInterface.
		$.each( wb.getSites(), function( siteId, site ) {
			if( $.inArray( siteId, unusedAllowedSiteIds ) === -1 ) {
				ignoredSiteIds.push( siteId );
			}
		} );
		EnhancedProto.prototype.ignoredSiteLinks = ignoredSiteIds;

		// don't forget to forward factory function
		EnhancedProto.newFromDom = function( subject, options, toolbar ) {
			// make sure factory creates an instance of EnhancedProto (3rd parameter)
			return BaseProto.newFromDom.call( this, subject, options, toolbar, EnhancedProto  );
		};

		this._editableValuesProto = EnhancedProto;
		return EnhancedProto;
	},

	/**
	 * @see wb.ui.PropertyEditTool._editableValueHandler_onAfterRemove
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue
	 */
	_editableValueHandler_onAfterRemove: function( editableValue ) {
		PARENT.prototype._editableValueHandler_onAfterRemove.call( this, editableValue );

		// remove only site used by removed site link from list of ignored sites:
		var removedSiteId = editableValue.siteIdInterface.getSelectedSiteId();
		if( removedSiteId !== null ) {
			var index = $.inArray( removedSiteId, this._editableValuesProto.prototype.ignoredSiteLinks );
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

		// no more site links
		if ( this._editableValues.length === 0 ) {
			// remove column headers
			this._subject.children( 'thead' ).children( 'tr' ).last().remove();

			// destroy tablesorter
			this._subject.removeData( 'tablesorter' );
		}
	},

	/**
	 * @see wb.ui.PropertyEditTool._newValueHandler_onAfterStopEditing
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue newValue
	 * @param bool save
	 * @param bool wasPending
	 */
	_newValueHandler_onAfterStopEditing: function( newValue, save, wasPending ) {
		PARENT.prototype._newValueHandler_onAfterStopEditing.call( this, newValue, save );
		if( save ) {
			// add chosen site to list of sites which can not be chosen by other editable site links
			var addedSiteId = newValue.siteIdInterface.getSelectedSiteId();
			this._editableValuesProto.prototype.ignoredSiteLinks.push( addedSiteId );
		}
	},

	/**
	 * Fixates the table layout preventing layout jitters when toggling edit mode.
	 */
	_fixateTableLayout: function() {
		var self = this,
			sites = wb.getSites(),
			$rulerTh = $( '<div/>' ).addClass( 'wb-ruler' ),
			$rulerTd = $( '<div/>' ).addClass( 'wb-ruler' ),
			$siteIdColumns,
			maxSiteIdWidth = 0,
			columnWidths = 0;

		// Measure the maximum width the site id column (which is set to not wrap) may have by
		// inserting all the site ids and the contents of the corresponding table header cell into
		// dummy rows/columns to be sure to measure with the correct styling (font size etc.).

		// Inserting the table header cell contents directly instead of querying for
		// th.wb-sitelinks-siteid since the table header does not exist when there are no site links
		$rulerTh.append( mw.msg( 'wikibase-sitelinks-siteid-columnheading' ) );
		this._subject.children( 'thead' ).append(
			mw.template( 'wb-sitelinks-thead', '', $rulerTh, '' ).addClass( 'wb-ruler' )
		);

		$.each( sites, function( i, site ) {
			$rulerTd.append( site.getLanguageCode() ).append( $( '<br/>' ) );
		} );
		this._subject.children( 'tbody' ).append(
			mw.template( 'wb-sitelink', '', 'wb-ruler', '', $rulerTd, '', '', $( '<td/>'), '' )
		);

		$siteIdColumns = this._subject.find( 'tr.wb-ruler .wb-sitelinks-siteid' );

		$siteIdColumns.each( function( i ) {
			var rulerWidth = $( this ).children( '.wb-ruler' ).width();
			// header needs to add space that tablesorter will acquire by adding additional padding
			if ( this.nodeName === 'TH' ) {
				rulerWidth += 11;
			}
			if ( rulerWidth  > maxSiteIdWidth ) {
				maxSiteIdWidth = rulerWidth;
			}
		} );

		// Set css adjusting the liquid table layout's column width:
		// Since we do not want the ruler to take up a lot of space, it originally needs to have set
		// its position to absolute. This, however, prevents the table cell from scaling to the
		// correct width of the ruler.
		$siteIdColumns.children( '.wb-ruler' )
		.empty()
		.width( maxSiteIdWidth )
		.css( 'position', 'static' )
		.css( 'float', 'left' );

		/**
		 * Applies the dummy row cells' widths to the colgroup.
		 *
		 * @param {String} widthModel
		 */
		function setWidths( widthModel ) {
			columnWidths = 0;
			self._subject.children( 'colgroup' ).children().each( function( i ) {
				var $td = self._subject.children( 'tbody' ).find( 'tr' ).last().children().eq( i ),
					width = ( widthModel === 'outer' ) ? $td.outerWidth() : $td.width();
				$( this ).width( width );
				columnWidths += width;
			} );
		}

		setWidths( 'outer' );
		this._subject.css( 'tableLayout', 'fixed' );

		// If the fixed layout width does not match the width the table had before, the layout model
		// has to be different. Since IE will add padding to the width specified in the column
		// definitions, their widths have to be set to the dummy row columns' inner widths.
		if ( columnWidths !== this._subject.width() ) {
			this._subject.css( 'tableLayout', 'auto' );
			setWidths( 'inner' );
			this._subject.css( 'tableLayout', 'fixed' );
		}

		// finally, remove the dummy row
		this._subject.find( '.wb-ruler' ).remove();
	},

	/**
	 * Returns a list of site IDs already used in a site link of this SiteLinkEditTool.
	 *
	 * @return {String[]} Array of site IDs
	 */
	getRepresentedSiteIds: function() {
		var sites = [];

		for( var i in this._editableValues ) {
			var editableSiteLink = this._editableValues[i];
			var site = editableSiteLink.siteIdInterface.getOption( 'siteId' );
			if( site !== null ) {
				sites.push( site );
			}
		}
		return sites;
	},

	/**
	 * Returns a list of site IDs which can be used but have not yet been used in a site link of
	 * this SiteLinkEditTool.
	 *
	 * @since 0.4
	 *
	 * @return {String[]} Array of site IDs
	 */
	getUnusedAllowedSiteIds: function() {
		var allowedSites = this.getOption( 'allowedSites' ), // can be object or array
			representedSiteIds = this.getRepresentedSiteIds(),
			unusedAllowedSiteIds = [];

		for( var i in allowedSites ) {
			var allowedSiteId = allowedSites[i].getId();

			if( $.inArray( allowedSiteId, representedSiteIds ) === -1 ) {
				unusedAllowedSiteIds.push( allowedSiteId );
			}
		}
		return unusedAllowedSiteIds;
	}
} );

/**
 * Returns the basic DOM structure sufficient for a new wikibase.ui.SiteLinksEditTool.
 *
 * @return {jQuery}
 */
wb.ui.SiteLinksEditTool.getEmptyStructure = function() {
	var $table = mw.template( 'wb-sitelinks-table', [
		'', // table header, will be inserted by Widget anyhow!
		'', // actual site-link rows; none initially!
		mw.template( 'wb-sitelinks-tfoot', [
			'',
			mw.template( 'wb-editsection', 'td', '' )
		] )
	] );
	$table.find( 'col' ).last().addClass( 'wb-ui-propertyedittool-editablevalue-toolbarparent' );
	return $table;
};

} )( mediaWiki, wikibase, jQuery );
