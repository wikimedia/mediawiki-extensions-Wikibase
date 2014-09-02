/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Displays multiple fingerprints (see jQuery.wikibase.fingerprintview).
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {Object[]} value
 *         Object representing the widget's value.
 *         Structure: [
 *           { language: <{string]>, label: <{string|null}>, description: <{string|null}> } [, ...]
 *         ]
 *
 * @options {string} entityId
 *
 * @option {wikibase.RepoApi} api
 */
$.widget( 'wikibase.fingerprintlistview', PARENT, {
	options: {
		template: 'wikibase-fingerprintlistview',
		templateParams: [
			'' // tbodys
		],
		templateShortCuts: {},
		value: [],
		entityId: null,
		api: null
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if( !$.isArray( this.options.value ) || !this.options.entityId || !this.options.api ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._createListView();

		this.element.addClass( 'wikibase-fingerprintlistview' );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		// When destroying a widget not initialized properly, listview will not have been created.
		var listview = this.element.data( 'listview' );

		if( listview ) {
			listview.destroy();
		}

		this.element.removeClass( 'wikibase-fingerprintlistview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates the listview widget managing the fingerprintview widgets
	 */
	_createListView: function() {
		var self = this;

		this.element
		.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.fingerprintview,
				listItemWidgetValueAccessor: 'value',
				newItemOptionsFn: function( value ) {
					return {
						value: value,
						entityId: self.options.entityId,
						api: self.options.api,
						helpMessage: mw.msg(
							'wikibase-fingerprintview-input-help-message',
							wb.getLanguageNameByCode( value.language )
						)
					};
				}
			} ),
			value: self.options.value || null,
			listItemNodeName: 'TBODY'
		} );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.element.data( 'listview' ).option( key, value );
		}

		return response;
	}
} );

}( mediaWiki, wikibase, jQuery ) );
