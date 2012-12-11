/**
 * Auto-Suggester widget wrapping jquery.ui.suggester in a more elegant way
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * jquery.ui.autosuggester may take a container (e.g. a div) which will be used as a wrapper for a
 * jquery.ui.suggester enhanced input box overlaying another input box used for displaying the
 * auto-completed suggestion in a more compatible and elegant way.
 * Suggester options or a pre-initialized input element should be provided.
 *
 * @example $( 'div' ).autosuggester( { suggester: { source: ['a', 'b', 'c'] } } );
 * @desc Creates a simple autosuggester.
 *
 * @option inputElement {jQuery} (optional) A pre-initialized input element which will be
 *         initialized a suggester widget upon.
 *         Default value: null (will initialize an input automatically)
 *
 * @option name {String} (optional) Name attribute of the input element.
 *         Default value: null (not setting name attribute)
 *
 * @option suggester {Object} (optional) Options to be passed to the jquery.ui.suggester widget.
 *         Default value: null (default jquery.ui.suggester options will be applied)
 *
 * @dependency jquery.ui.suggester
 */
( function( $, undefined ) {
	'use strict';

	$.widget( 'ui.autosuggester', {

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			inputElement: null,
			name: null,
			suggester: null
		},

		/**
		 * Actual input element
		 * @type {jQuery}
		 */
		$input: null,

		/**
		 * Suggestion element that underlays the input element with an auto-completed string
		 * obtained through the suggestion list
		 * @type {jQuery}
		 */
		$suggestion: null,

		/**
		 * Dummy node used for detecting a string's direction.
		 */
		$dirDetector: null,

		/**
		 * @see ui.widget._create
		 *
		 * @throws {Error} Suggester options have been provided.
		 */
		_create: function() {
			if ( this.options.suggester === null ) {
				throw new Error( 'jquery.ui.autosuggester: Suggester options need to be provided.' );
			}

			var self = this;

			this.$input = ( this.options.inputElement !== null ) ?
				this.options.inputElement
				: $( '<input/>' );

			this.$input
			.addClass( 'ui-autosuggester-input' )
			.suggester( this.options.suggester );

			var suggester = this.$input.data( 'suggester' );

			// disable suggester's native auto-completing
			suggester.autocompleteString = function( incomplete, complete ) {};

			// store suggester reference for easy external access
			this.element.data( 'suggester', suggester );

			if ( this.options.name !== null ) {
				this.$input.attr( 'name', this.options.name );
			}

			this.$suggestion = $( '<input/>' )
			.addClass( 'ui-autosuggester-suggestion' )
			.prop( 'disabled', true ); // prevent tabbing

			this.element
			.addClass( 'ui-autosuggester' )
			.append( this.$suggestion )
			.append( this.$input );

			// set language and direction attributes
			this.updateLanguage();

			this.$input.on( 'suggesteropen.' + this.widgetName, function( event ) {
				// Only auto-complete if the string has the language direction of the input box
				// since the characters would not overlay properly (e.g. typing "DV" for hebrew
				// Wikipedia would return "DVD" as a suggestion. While the string actually has a
				// ltr direction, the input box is assigned rtl. The strings would be right-aligned
				// causing misalignment of the suggestion.
				var item = suggester.menu.element.children().first().data( 'item.autocomplete' );
				if ( self.detectDir( item.label ) === self.$input.attr( 'dir' ) ) {
					self.suggest( item.label, {} );
				} else {
					self.suggest( item.label, { replaceOnly: true } );
				}
			} );

			this.$input.on( 'keyup.' + this.widgetName, function( event ) {
				if ( event.keyCode === $.ui.keyCode.ENTER || event.keyCode === $.ui.keyCode.RIGHT ) {
					// The suggestion might be empty (e.g. if language directions of input box and
					// suggestions mismatch)
					if ( self.$suggestion.val() !== '' ) {
						self.$input.val( self.$suggestion.val() );
					}
				}
			} );

			this.$input.on( 'blur.' + this.widgetName, function( event ) {
				if ( self.$suggestion.val().indexOf( self.$input.val() ) !== -1 ) {
					self.$input.val( self.$suggestion.val() );
				}
			} );

			this.$input.eachchange( function( event, oldValue ) {
				var item = suggester.menu.element.children().first().data( 'item.autocomplete' ),
					inputValue = self.$input.val();

				// Some of the following lines have the same effect but are not merged by intention
				// for the purpose of better documentation.
				if (
					self.$suggestion.val() !== ''
					&& self.detectDir( self.$suggestion.val() ) !== self.$input.attr( 'dir' )
				) {
					// Never show auto-complete suggestion when language directions of suggestion
					// and input box mismatch since it would cause misalignment of the characters.
					self.$suggestion.val( '' );
				} else if ( inputValue === '' ) {
					// When clearing the input box, immediately clear the suggestion to not
					// interfere with a placeholder in the input element (autocomplete delay would
					// cause placeholder overlaying the last suggestion)
					self.$suggestion.val( '' );
				} else if (
					self.$suggestion.val().toLowerCase().indexOf( inputValue.toLowerCase() ) === 0
				) {
					// When "overtyping" a suggestion, immediately adjust the letter case to not end
					// up with misalignment
					self.suggest( self.$suggestion.val(), {} );
				} else if (
					suggester.menu.element.children().length
					&& self.detectDir( item.label ) !== self.$input.attr( 'dir' )
					&& item.label.toLowerCase().indexOf( inputValue.toLowerCase() ) !== -1
				) {
					// Since suggestion should not be filled when language directions differ
					// (because it would cause misalignment of input value and auto-completed
					// suggestion), only the letter case has to be adjusted according to the first
					// menu item
					self.suggest( item.label, { replaceOnly: true } );
				} else {
					// There may be text in the input box, but it does not validate against the
					// first suggestion: Just clear the suggestion. When the autocomplete has
					// finished searching for suggestions, the suggestion box will be refilled
					// anyway.
					self.$suggestion.val( '' );
				}
			} );

			suggester.element.on( 'suggesterselect', function( event, ui ) {
				self.$suggestion.val( ui.item.label );
			} );

			suggester.menu.element.on( 'menufocus.' + this.widgetName, function( event, ui ) {
				var label = ui.item.data( 'item.autocomplete' ).label;
				// When focusing a menu item, only show the suggestion when language direction of
				// suggestion and input box match to not cause misalignment. Alternatives would be
				// replacing the input box with the complete suggestion or hiding the current term.
				if ( self.detectDir( label ) === self.$input.attr( 'dir' ) ) {
					// Need to force setting the suggestion since there might be suggestions not
					// starting with the same character(s) (e.g. language scripts)
					self.suggest( label, { force: true } );
				}
			} );

			suggester.menu.element.on( 'menublur.' + this.widgetName, function( event ) {
				if (
					self.$suggestion.val().indexOf( suggester.term ) !== 0
					&& suggester.term !== suggester.menu.active.data( 'item.autocomplete' ).label
				) {
					// The suggestions in the menu might have a different script. Consequently, the
					// suggestion box has to be reset when the suggestion value (the last hovered
					// item when blurring the suggestions list) does not match to the actual search
					// term previously entered.
					self.$suggestion.val( '' );
				} else if ( suggester.menu.element.children().length ) {
					// Search might return similar unicode characters (like Ş for S) which would
					// not validate against the first list item when blurring the menu
					var suggestion = '';
					$.each( suggester.menu.element.children(), function( i, listItem ) {
						var label = $( listItem ).data( 'item.autocomplete' ).label;
						if (
							label.toLowerCase().indexOf(
								self.$input.val().toLowerCase()
							) === 0
						) {
							suggestion = label;
							return false;
						}
					} );
					if ( self.detectDir( suggestion ) !== self.$input.attr( 'dir' ) ) {
						self.suggest( suggestion, { replaceOnly: true, force: true } );
					} else {
						self.suggest( suggestion, { force: true } );
					}
				}
			} );

		},

		/**
		 * Sets/gets the input box value.
		 *
		 * @param {String} [value] Value to set
		 * @return {String} Current (new) value
		 */
		value: function( value ) {
			if ( value !== undefined ) {
				this.$input.val( value );
			}
			return this.$input.val();
		},

		/**
		 * Suggests a given string (includes checking if it is a valid suggestion).
		 *
		 * @param {String} suggestion
		 * @param {Object} options
		 */
		suggest: function( suggestion, options ) {
			var self = this;

			if (
				options.force
				|| suggestion.toLowerCase().indexOf( this.$input.val().toLowerCase() ) !== -1
			) {
				if ( !options.replaceOnly ) {
					self.$suggestion.val( suggestion );
				}

				// reset the current input string to the one returned as suggestion (since letter
				// case might differ)
				self.$input.val( suggestion.substr( 0, self.$input.val().length ) );
			}
		},

		/**
		 * Sets language and direction of input and suggestion element according to the element the
		 * widget is initialized upon and orientates the input element to the right in an rtl
		 * or to the left in an ltr context.
		 */
		updateLanguage: function() {
			var dir = this.element.attr( 'dir' ),
				lang = this.element.attr( 'lang' );

			if ( dir === undefined && document.documentElement.dir === 'rtl' ) {
				dir = 'rtl';
			}
			if ( dir === undefined ) {
				dir = 'auto';
			}

			this.$input.attr( 'dir', dir );
			this.$suggestion.attr( 'dir', dir );

			this.$input.data( 'suggester' )._updateDirection();
			this.$input.data( 'suggester' ).menu.element.attr( 'dir', dir );

			if ( dir === 'rtl' ) {
				this.$input.css( 'right', '0' );
				this.$suggestion.css( 'float', 'right' );
			} else {
				this.$input.css( 'left', '0' );
				this.$suggestion.css( 'float', 'left' );
			}

			if ( lang !== undefined ) {
				this.$input.attr( 'lang', lang );
				this.$input.data( 'suggester' ).menu.element.attr( 'lang', lang );
				this.$suggestion.attr( 'lang', lang );
			}
		},

		/**
		 * Detects the language direction in which a given string would be rendered in the browser.
		 *
		 * @param {String} string
		 * @return {String}
		 */
		detectDir: function( string ) {
			var dir = 'ltr';

			if ( this.$dirDetector === null ) {
				this.$dirDetector = $( '<div style="position: absolute; top: -9999px;'
					+ ' left: -9999px; visibility: hidden; width: auto; height: auto;" />' );
				this.$dirDetector.appendTo( 'body' );
			}

			this.$dirDetector
				.append( $( '<span/>' ).text( string ) )
				.append( $( '<span/>' ).text( string ) );

			if (
				this.$dirDetector.children( 'span' ).first().offset().left
					> this.$dirDetector.children( 'span' ).last().offset().left
			) {
				dir = 'rtl';
			}
			this.$dirDetector.empty();

			return dir;
		},

		/**
		 * Disables input.
		 * @see ui.suggester.disable
		 */
		disable: function() {
			this.$input.data( 'suggester' ).disable();
			this.element.addClass( 'ui-autosuggester-disabled' );
		},

		/**
		 * Enables input.
		 * @see ui.suggester.enable
		 */
		enable: function() {
			this.$input.data( 'suggester' ).enable();
			this.element.removeClass( 'ui-autosuggester-disabled' );
		},

		/**
		 * @see ui.widget.destroy
		 */
		destroy: function() {
			if ( this.$dirDetector !== null ) {
				this.$dirDetector.remove();
			}
			this.$input.off( '.' + this.widgetName );
			this.$input.data( 'suggester' ).destroy();
			this.element.children().remove();
			this.element.removeClass( 'ui-autosuggester' );
			this.element.removeClass( 'ui-autosuggester-disabled' );
			$.Widget.prototype.destroy.call( this );
		}

	} );

} )( jQuery );
