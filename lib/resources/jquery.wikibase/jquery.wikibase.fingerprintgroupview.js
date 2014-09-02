/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Encapsulates a fingerprintlistview widget.
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
$.widget( 'wikibase.fingerprintgroupview', PARENT, {
	options: {
		template: 'wikibase-fingerprintgroupview',
		templateParams: [
			function() {
				return mw.msg( 'wikibase-terms' );
			},
			'' // fingerprintlistview
		],
		templateShortCuts: {
			$h: 'h2'
		},
		value: [],
		entityId: null,
		api: null
	},

	/**
	 * @type {jQuery}
	 */
	$fingerprintlistview: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if( !$.isArray( this.options.value ) || !this.options.entityId || !this.options.api ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this.element.addClass( 'wikibase-fingerprintgroupview' );

		this.$fingerprintlistview = this.element.find( '.wikibase-fingerprintlistview' );

		if( !this.$fingerprintlistview.length ) {
			this.$fingerprintlistview = $( '<table/>' ).appendTo( this.element );
		}

		this._createFingerprintlistview();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		// When destroying a widget not initialized properly, fingerprintlistview will not have been
		// created.
		if( this.$fingerprintlistview ) {
			var fingerprintlistview = this.$fingerprintlistview.data( 'fingerprintlistview' );

			if( fingerprintlistview ) {
				fingerprintlistview.destroy();
			}

			this.$fingerprintlistview.remove();
		}

		this.element.removeClass( 'wikibase-fingerprintgroupview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates and initializes the fingerprintlistview widget.
	 */
	_createFingerprintlistview: function() {
		this.$fingerprintlistview.fingerprintlistview( {
			value: this.options.value,
			entityId: this.options.entityId,
			api: this.options.api
		} );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$fingerprintlistview.data( 'fingerprintlistview' ).option( key, value );
		}

		return response;
	}
} );

}( mediaWiki, jQuery ) );
