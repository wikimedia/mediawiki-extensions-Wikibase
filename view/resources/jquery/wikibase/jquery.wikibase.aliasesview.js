( function () {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * Displays and allows editing of `datamodel.MultiTerm` objects.
	 *
	 * @see datamodel.MultiTerm
	 * @class jQuery.wikibase.aliasesview
	 * @extends jQuery.ui.EditableTemplatedWidget
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {datamodel.MultiTerm} options.value
	 * @param {string} [options.helpMessage=mw.msg( 'wikibase-aliases-input-help-message' )]
	 */
	$.widget( 'wikibase.aliasesview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			template: 'wikibase-aliasesview',
			templateParams: [
				function () {
					return this.options.value.getTexts().length === 0 ? 'wb-empty' : '';
				}, // additional class
				'', // list items
				'', // toolbar
				function () {
					return $.util.getDirectionality( this.options.value.getLanguageCode() );
				},
				function () {
					return this.options.value.getLanguageCode();
				}
			],
			templateShortCuts: {
				$list: 'ul'
			},
			value: null,
			helpMessage: mw.msg( 'wikibase-aliases-input-help-message' )
		},

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} if a required option is not specified properly.
		 */
		_create: function () {
			if ( !( this.options.value instanceof datamodel.MultiTerm ) ) {
				throw new Error( 'Required option not specified properly' );
			}

			PARENT.prototype._create.call( this );

			if ( this.$list.children( 'li' ).length !== this.options.value.getTexts().length ) {
				this.draw();
			}

			this.$list.addClass( this.widgetFullName + '-input' );
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			if ( this.$list ) {
				this.$list.removeClass( this.widgetFullName + '-input' );
			}
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			this.$list.off( '.' + this.widgetName );

			if ( this.isInEditMode() ) {
				this._initTagadata();
			} else {
				var self = this,
					tagadata = this.$list.data( 'tagadata' );

				if ( tagadata ) {
					tagadata.destroy();
				}

				this.element.toggleClass( 'wb-empty', this.options.value.getTexts().length === 0 );

				this.$list.empty();

				this.options.value.getTexts().forEach( function ( text ) {
					self.$list.append( mw.wbTemplate( 'wikibase-aliasesview-list-item', text ) );
				} );
			}

			return $.Deferred().resolve().promise();
		},

		/**
		 * Creates and initializes the `jQuery.ui.tagadata` widget.
		 *
		 * @private
		 */
		_initTagadata: function () {
			var self = this;

			this.$list
			.tagadata( {
				placeholderText: mw.msg( 'wikibase-alias-edit-placeholder' )
			} )
			.on(
				'tagadatatagremoved.' + this.widgetName
				+ ' tagadatatagchanged.' + this.widgetName
				+ ' tagadatatagremoved.' + this.widgetName, function ( event ) {
					self._trigger( 'change' );
				}
			);

			var expansionOptions = {
				expandOnResize: false,
				comfortZone: 16, // width of .ui-icon
				maxWidth: function () {
					// TODO/FIXME: figure out why this requires at least -17, can't be because of padding + border
					// which is only 6 for both sides
					return self.$list.width() - 20;
				}
				/*
				// TODO/FIXME: both solutions are not perfect, when tag larger than available space either the
				// input will be auto-resized and not show the whole text or we still show the whole tag but it
				// will break the site layout. A solution would be replacing input with textarea.
				maxWidth: function() {
					var tagList = self._getTagadata().tagList;
					var origCssDisplay = tagList.css( 'display' );
					tagList.css( 'display', 'block' );
					var width = tagList.width();
					tagList.css( 'display', origCssDisplay );
					return width;
				}
				 */
			};

			var tagadata = this.$list.data( 'tagadata' );

			// calculate size for all input elements initially:
			tagadata.getTags().add( tagadata.getHelperTag() )
				.find( 'input' ).inputautoexpand( expansionOptions );

			// also make sure that new helper tags will calculate size correctly:
			this.$list.on( 'tagadatahelpertagadded.' + this.widgetName, function ( event, tag ) {
				$( tag ).find( 'input' ).inputautoexpand( expansionOptions );
			} );
		},

		_startEditing: function () {
			// FIXME: This could be much faster
			return this.draw();
		},

		_stopEditing: function () {
			// FIXME: This could be much faster
			return this.draw();
		},

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} when trying to set the widget's value to something other than a
		 *         `datamodel.MultiTerm` instance.
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' && !( value instanceof datamodel.MultiTerm ) ) {
				throw new Error( 'Value needs to be a datamodel.MultiTerm instance' );
			}

			var response = PARENT.prototype._setOption.call( this, key, value );

			if ( key === 'disabled' && this.isInEditMode() ) {
				this.$list.data( 'tagadata' ).option( 'disabled', value );
			}

			return response;
		},

		/**
		 * @inheritdoc
		 *
		 * @param {datamodel.MultiTerm} [value]
		 * @return {datamodel.MultiTerm|undefined}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			var tagadata = this.$list.data( 'tagadata' );

			return new datamodel.MultiTerm(
				this.options.value.getLanguageCode(),
				// tagadata.getTags() returns a jquery object of html nodes
				// eslint-disable-next-line no-jquery/no-map-util
				$.map( tagadata.getTags(), function ( tag ) {
					return tagadata.getTagLabel( $( tag ) );
				} )
			);
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			if ( this.isInEditMode() ) {
				this.$list.data( 'tagadata' ).getHelperTag().find( 'input' ).trigger( 'focus' );
			} else {
				this.element.trigger( 'focus' );
			}
		}

	} );

}() );
