/**
 * jQuery UI extension 'tag-a-data'
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * @version 0.1
 *
 * 'tag-a-data' is based on the original jQuery extension 'tag-it' v2.0 (06/2011) by
 *   Levy Carneiro Jr.
 *   Martin Rehfeld
 *   Tobias Schmidt
 *   Skylar Challand
 *   Alex Ehlke
 * See http://aehlke.github.com/tag-it/ for details.
 *
 * Copyright 2011, Levy Carneiro Jr.
 * Released under the MIT license.
 * http://aehlke.github.com/tag-it/LICENSE
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence MIT license
 * @author Daniel Werner
 */

(function( $ ) {

	$.widget( 'ui.tagadata', {
		options: {
			itemName: 'item',
			fieldName: 'tags',
			availableTags: [],
			tagSource: null,

			/**
			 * If set to true, hitting backspace will not delete the last tag immediately but highlight it first.
			 * @var Boolean
			 */
			removeConfirmation: false,

			/**
			 * If true, tags with the same text but different capitalization can be inserted.
			 * @var Boolean
			 */
			caseSensitive: true,

			/**
			 * Text used as placeholder in the input field if no text has been typed yet.
			 * @var String
			 */
			placeholderText: null,

			/**
			 * Whether  to animate tag removals or not.
			 * @var Boolean
			 */
			animate: true,

			/**
			 * Optionally set a tabindex attribute on the input that gets created for 'tag-a-data'.
			 * @var Number
			 */
			tabIndex: null,

			/**
			 * if true this creates a new tag if text was inserted and the user leaves the input field.
			 * @var Boolean
			 */
			createOnBlur: false,

			/**
			 * Keys which - when pressed in the input area - will trigger the current
			 * input to be added as tag. $.ui.keyCode members can be used for convenience.
			 * @var Number[]
			 */
			triggerKeys: [
				$.ui.keyCode.ENTER
			],

			// Event callbacks.
			onTagAdded: null,
			onBeforeTagRemoved: null,
			onTagRemoved: null,
			onTagClicked: null
		},


		_create: function() {
			// for handling static scoping inside callbacks
			var that = this;

			this.tagList = this.element.find( 'ul, ol' ).andSelf().last();

			this._tagInput = $( '<input type="text" />' ).addClass( 'ui-widget-content' );
			if( this.options.tabIndex ) {
				this._tagInput.attr( 'tabindex', this.options.tabIndex );
			}
			if( this.options.placeholderText ) {
				this._tagInput.attr( 'placeholder', this.options.placeholderText );
			}

			this.options.tagSource = this.options.tagSource || function( search, showChoices ) {
				var filter = search.term.toLowerCase();
				var choices = $.grep( this.options.availableTags, function( element ) {
					// Only match autocomplete options that begin with the search term.
					// (Case insensitive.)
					return (element.toLowerCase().indexOf( filter ) === 0);
				} );
				showChoices( this._subtractArray( choices, this.assignedTags() ) );
			};

			// Bind tagSource callback functions to this context.
			if( $.isFunction( this.options.tagSource ) ) {
				this.options.tagSource = $.proxy( this.options.tagSource, this );
			}

			this.tagList
			.addClass( 'tagadata' )
			.addClass( 'ui-widget ui-widget-content ui-corner-all' )
			// Create the input field.
			.append( $( '<li class="tagadata-new"></li>' ).append( this._tagInput ) )
			.on( 'click.tagadata', function( e ) {
				var target = $( e.target );
				if( target.hasClass( 'tagadata-label' ) ) {
					that._trigger( 'onTagClicked', e, target.closest( '.tagadata-choice' ) );
				} else {
					// Sets the focus() to the input field, if the user
					// clicks anywhere inside the UL. This is needed
					// because the input field needs to be of a small size.
					that._tagInput.focus();
				}
			} );

			// Add existing tags from the list, if any.
			this.tagList.children( 'li' ).each( function() {
				if( !$( this ).hasClass( 'tagadata-new' ) ) {
					that.createTag( $( this ).html(), $( this ).attr( 'class' ) );
					$( this ).remove();
				}
			} );

			// Events.
			this._tagInput
			.keydown(function( event ) {
				// Backspace is not detected within a keypress, so it must use keydown.
				if( event.which == $.ui.keyCode.BACKSPACE && that._tagInput.val() === '' ) {
					var tag = that._lastTag();
					if( !that.options.removeConfirmation || tag.hasClass( 'remove' ) ) {
						// When backspace is pressed, the last tag is deleted.
						that.removeTag( tag );
					} else if( that.options.removeConfirmation ) {
						tag.addClass( 'remove ui-state-highlight' );
					}
				} else if( that.options.removeConfirmation ) {
					that._lastTag().removeClass( 'remove ui-state-highlight' );
				}

				// check whether key for insertion was triggered
				if( that._tagInput.val() !== '' && $.inArray( event.which, that.options.triggerKeys ) > -1 ) {
					event.preventDefault();
					that.createTag( that._tagInput.val() );

					// The autocomplete doesn't close automatically when TAB is pressed.
					// So let's ensure that it closes.
					that._tagInput.autocomplete( 'close' );
				}
			} ).blur( function( e ) {
				if( that.options.createOnBlur ) {
					// Create a tag when the element loses focus (unless it's empty).
					that.createTag( that._tagInput.val() );
				}
			} );


			// Autocomplete.
			if( this.options.availableTags || this.options.tagSource ) {
				this._tagInput.autocomplete( {
					source: this.options.tagSource,
					select: function( event, ui ) {
						// Delete the last tag if we autocomplete something despite the input being empty
						// This happens because the input's blur event causes the tag to be created when
						// the user clicks an autocomplete item. I don't know how to lock my screen.
						// The only artifact of this is that while the user holds down the mouse button
						// on the selected autocomplete item, a tag is shown with the pre-autocompleted text,
						// and is changed to the autocompleted text upon mouseup.
						if( that._tagInput.val() === '' ) {
							that.removeTag( that._lastTag(), false );
						}
						that.createTag( ui.item.value );
						// Preventing the tag input to be updated with the chosen value.
						return false;
					}
				} );
			}
		},

		_lastTag: function() {
			return this.tagList.children( '.tagadata-choice:last' );
		},

		/**
		 * Returns the labels of all tags currently assigned.
		 *
		 * @return String[]
		 */
		assignedTags: function() {
			// Returns an array of tag string values
			var that = this;
			var tags = [];

			this.tagList.children( '.tagadata-choice' ).each( function() {
				// check if already removed but still assigned till animations end. if so, don't add tag!
				if( !$( this ).hasClass( 'tagadata-choice-removed' ) ) {
					tags.push( that.tagLabel( this ) );
				}
			} );

			return tags;
		},

		_subtractArray: function( a1, a2 ) {
			var result = [];
			for( var i = 0; i < a1.length; i++ ) {
				if( $.inArray( a1[i], a2 ) === -1 ) {
					result.push( a1[i] );
				}
			}
			return result;
		},

		/**
		 * Returns the label of a tag represented by a DOM node.
		 *
		 * @param jQuery tag
		 * @return string
		 */
		tagLabel: function( tag ) {
			// Returns the tag's string label.
			return $( tag ).children( 'input' ).val();
		},

		/**
		 * Returns a tags element by its label. If the tag is not in the list, null will be returned.
		 *
		 * @param string label
		 * @return jQuery|null
		 */
		getTag: function( label ) {
			var self = this;
			var result = null;
			this.tagList.children( '.tagadata-choice' ).each( function( i ) {
				if( self._formatLabel( label ) === self._formatLabel( self.tagLabel( this ) ) ) {
					result = $( this );
					return false;
				}
			} );
			return result;
		},

		/**
		 * Returns whether the tag with an given label is present within the list of tags already
		 *
		 * @param string label
		 * @return Boolean
		 */
		hasTag: function( label ) {
			return this.getTag( label ) !== null;
		},

		_formatLabel: function( str ) {
			str = $.trim( str );
			if( this.options.caseSensitive ) {
				return str;
			}
			return str.toLowerCase();
		},

		/**
		 * This will add a new tag to the list of tags. If the tag exists in the list already, false will be returned,
		 * otherwise the newly assigned tag.
		 *
		 * @param String value
		 * @param String additionalClass
		 * @return jQuery|false
		 */
		createTag: function( value, additionalClass ) {
			var that = this;
			var tag;

			// Automatically trims the value of leading and trailing whitespace.
			value = this._formatLabel( value );

			if( this.hasTag( value ) || value === '' ) {
				tag = this.getTag( value );
				if( tag !== null ) {
					// tag in list already, don't add it twice
					this._tagInput.val( '' );
					// highlight tag visually so the user knows the tag is in the list already
					// switch to highlighted class...
					tag.switchClass( '', 'tagadata-choice-existing ui-state-highlight', 150, 'linear', function() {
						// ... and remove it again (also remove 'remove' class to avoid confusio
						tag.switchClass( 'tagadata-choice-existing ui-state-highlight remove', '', 750, 'linear' );
					} );
				}
				return false;
			}

			var label = $( this.options.onTagClicked ? '<a class="tagadata-label"></a>' : '<span class="tagadata-label"></span>' ).text( value );

			// Create tag.
			tag = $( '<li></li>' )
				.addClass( 'tagadata-choice ui-widget-content ui-state-default ui-corner-all' )
				.addClass( additionalClass )
				.append( label );

			// Button for removing the tag.
			var removeTagIcon = $( '<span></span>' )
				.addClass( 'ui-icon ui-icon-close' );
			var removeTag = $( '<a><span class="text-icon">\xd7</span></a>' )// \xd7 is an X
				.addClass( 'tagadata-close' )
				.append( removeTagIcon )
				.click( function( e ) {
					// Removes a tag when the little 'x' is clicked.
					that.removeTag( tag );
				} );
			tag.append( removeTag );

			// each tag has a hidden input field inline.
			var escapedValue = label.html();
			tag.append( '<input type="hidden" style="display:none;" value="' + escapedValue + '" name="' + this.options.itemName + '[' + this.options.fieldName + '][]" />' );

			// Cleaning the input.
			this._tagInput.val( '' );

			// insert tag
			this._tagInput.parent().before( tag );

			this._trigger( 'onTagAdded', null, tag );

			return tag;
		},

		removeTag: function( tag, animate ) {
			animate = animate || this.options.animate;

			tag = $( tag );

			this._trigger( 'onBeforeTagRemoved', null, tag );

			// Animate the removal.
			if( animate ) {
				tag.addClass( 'tagadata-choice-removed' );
				tag.fadeOut( 'fast' ).hide( 'blind', {direction: 'horizontal'}, 'fast',function() {
					tag.remove(); //TODO/FIXME: danwe: This won't work for some reason, callback not called, fadeOut not happening!
				} ).dequeue();
			} else {
				tag.remove();
			}

			this._trigger( 'onTagRemoved', null, tag );
			return true;
		},

		removeAll: function() {
			// Removes all tags.
			var that = this;
			this.tagList.children( '.tagadata-choice' ).each( function( index, tag ) {
				that.removeTag( tag, false );
			} );
		},

		/**
		 * Destroys the element and only leaves the original ul element (including all new elements)
		 */
		destroy: function() {
			var that = this;

			this.tagList
			.removeClass( 'tagadata ui-widget ui-widget-content ui-corner-all' )
			.off( 'click.tagadata' )
			.children( '.tagadata-new' ).remove();

			this.tagList.children( 'li' ).each( function() {
				var tag = $( this );
				var text = that.tagLabel( tag );
				tag
				.removeClass( 'tagadata-choice tagadata-choice-removed ui-widget-content ui-state-default ui-corner-all ui-state-highlight remove' )
				.empty()
				.text( text ); // also removes all the helper stuff within
			} );

			return $.Widget.prototype.destroy.call( this );
		}

	} );

})( jQuery );
