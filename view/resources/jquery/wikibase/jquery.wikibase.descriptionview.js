/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * Manages a description.
	 *
	 * @extends jQuery.ui.EditableTemplatedWidget
	 *
	 * @option {datamodel.Term} value
	 *
	 * @option {string} [inputNodeName='TEXTAREA']
	 *         Should either be 'TEXTAREA' or 'INPUT'.
	 */
	$.widget( 'wikibase.descriptionview', PARENT, {
		/**
		 * @see jQuery.ui.TemplatedWidget.options
		 */
		options: {
			template: 'wikibase-descriptionview',
			templateParams: [
				'', // additional class
				'', // text
				'' // toolbar
			],
			templateShortCuts: {
				$text: '.wikibase-descriptionview-text'
			},
			value: null,
			inputNodeName: 'TEXTAREA'
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._create
		 *
		 * @throws {Error} if required parameters are not specified properly.
		 */
		_create: function () {
			if ( !( this.options.value instanceof datamodel.Term )
				|| this.options.inputNodeName !== 'INPUT' && this.options.inputNodeName !== 'TEXTAREA'
			) {
				throw new Error( 'Required parameter(s) missing' );
			}

			var self = this;

			this.element
				.on(
					'descriptionviewafterstartediting.' + this.widgetName
					+ ' eachchange.' + this.widgetName,
					function ( event ) {
						if ( self.value().getText() === '' ) {
							// Since the widget shall not be in view mode when there is no value, triggering
							// the event without a proper value is only done when creating the widget. Disabling
							// other edit buttons shall be avoided.
							// TODO: Move logic to a sensible place.
							self.element.addClass( 'wb-empty' );
							return;
						}

						self.element.removeClass( 'wb-empty' );
					}
				);

			PARENT.prototype._create.call( this );

			if ( this.$text.text() === '' ) {
				this.draw();
			}
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			if ( this.isInEditMode() ) {
				var self = this;

				this.element.one( this.widgetEventPrefix + 'afterstopediting', function ( event ) {
					PARENT.prototype.destroy.call( self );
				} );

				this.stopEditing( true );
			} else {
				PARENT.prototype.destroy.call( this );
			}
		},

		/**
		 * Main draw routine.
		 */
		draw: function () {
			var done = $.Deferred().resolve().promise();
			var self = this,
				languageCode = this.options.value.getLanguageCode(),
				descriptionText = this.options.value.getText();

			if ( descriptionText === '' ) {
				descriptionText = null;
			}

			this.element[ descriptionText ? 'removeClass' : 'addClass' ]( 'wb-empty' );

			if ( !this.isInEditMode() && !descriptionText ) {
				this.$text.text( mw.msg( 'wikibase-description-empty' ) );
				// Apply lang and dir of UI language
				// instead language of that row
				var userLanguage = mw.config.get( 'wgUserLanguage' );
				this.element
				.attr( 'lang', userLanguage )
				.attr( 'dir', $.util.getDirectionality( userLanguage ) );
				return done;
			}

			this.element
			.attr( 'lang', languageCode )
			.attr( 'dir', $.util.getDirectionality( languageCode ) );

			if ( !this.isInEditMode() ) {
				this.$text.text( descriptionText );
				return done;
			}

			var $input = $( document.createElement( this.options.inputNodeName ) );

			$input
			.addClass( this.widgetFullName + '-input' )
			// TODO: Inject correct placeholder via options
			.attr( 'placeholder', mw.msg(
				'wikibase-description-edit-placeholder-language-aware',
				wb.getLanguageNameByCode( languageCode )
			) )
			.attr( 'lang', languageCode )
			.attr( 'dir', $.util.getDirectionality( languageCode ) )
			.on( 'keydown.' + this.widgetName, function ( event ) {
				if ( event.keyCode === $.ui.keyCode.ENTER ) {
					event.preventDefault();
				}
			} )
			.on( 'eachchange.' + this.widgetName, function ( event ) {
				self._trigger( 'change' );
			} );

			if ( descriptionText ) {
				$input.val( descriptionText );
			}

			if ( $.fn.inputautoexpand ) {
				$input.inputautoexpand( {
					expandHeight: true,
					suppressNewLine: true
				} );
			}

			this.$text.empty().append( $input );
			return done;
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
		 * @see jQuery.ui.TemplatedWidget._setOption
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' && !( value instanceof datamodel.Term ) ) {
				throw new Error( 'Value needs to be a datamodel.Term instance' );
			}

			var response = PARENT.prototype._setOption.call( this, key, value );

			if ( key === 'disabled' && this.isInEditMode() ) {
				this.$text.children( '.' + this.widgetFullName + '-input' ).prop( 'disabled', value );
			}

			return response;
		},

		/**
		 * Gets/Sets the widget's value.
		 *
		 * @param {datamodel.Term} [value]
		 * @return {datamodel.Term|undefined}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			return new datamodel.Term(
				this.options.value.getLanguageCode(),
				this.$text.children( '.' + this.widgetFullName + '-input' ).val().trim()
			);
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.focus
		 */
		focus: function () {
			if ( this.isInEditMode() ) {
				this.$text.children( '.' + this.widgetFullName + '-input' ).trigger( 'focus' );
			} else {
				this.element.trigger( 'focus' );
			}
		}

	} );

}( wikibase ) );
