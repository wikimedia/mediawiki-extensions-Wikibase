/**
* jQuery UI Tag-it, modified version for 'Wikibase' extension for 'MediaWiki'
*
* @version v2.1.0wb (5/2012) extended for 'Wikibase' extension for 'MediaWiki'
*
* Copyright 2011, Levy Carneiro Jr.
* Released under the MIT license.
* http://aehlke.github.com/tag-it/LICENSE
*
* Homepage:
*   http://aehlke.github.com/tag-it/
*
* Authors:
*   Levy Carneiro Jr.
*   Martin Rehfeld
*   Tobias Schmidt
*   Skylar Challand
*   Alex Ehlke
*   Daniel Werner < daniel.werner@wikimedia.de >
*
* Maintainer:
*   Alex Ehlke - Twitter: @aehlke
*
* Dependencies:
*   jQuery v1.4+
*   jQuery UI v1.8+
*/
(function($) {

    $.widget('ui.tagit', {
        options: {
            itemName          : 'item',
            fieldName         : 'tags',
            availableTags     : [],
            tagSource         : null,

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
			 * The below options are for using a single field instead of several for our form values.
			 * When enabled, will use a single hidden field for the form, rather than one per tag. It will delimit tags
			 * in the field with singleFieldDelimiter.
			 *
			 * The easiest way to use singleField is to just instantiate tag-it on an INPUT element, in which case
			 * singleField is automatically set to true, and singleFieldNode is set to that element. This way, you don't
			 * need to fiddle with these options.
			 *
			 * @var Boolean
			 */
            singleField: false,

            singleFieldDelimiter: ',',

			/**
			 * Set this to an input DOM node to use an existing form field. Any text in it will be erased on init. But
			 * it will be populated with the text of tags as they are created, delimited by singleFieldDelimiter.
			 * If this is not set, we create an input node for it, with the name given in settings.fieldName,
			 * ignoring settings.itemName.
			 */
            singleFieldNode: null,

            /**
			 * Optionally set a tabindex attribute on the input that gets created for tag-it.
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
			onTagAdded        : null,
			onBeforeTagRemoved: null,
			onTagRemoved      : null,
            onTagClicked      : null
        },


        _create: function() {
            // for handling static scoping inside callbacks
            var that = this;

            // There are 2 kinds of DOM nodes this widget can be instantiated on:
            //     1. UL, OL, or some element containing either of these.
            //     2. INPUT, in which case 'singleField' is overridden to true,
            //        a UL is created and the INPUT is hidden.
            if (this.element.is('input')) {
                this.tagList = $('<ul></ul>').insertAfter(this.element);
                this.options.singleField = true;
                this.options.singleFieldNode = this.element;
                this.element.css('display', 'none');
            } else {
                this.tagList = this.element.find('ul, ol').andSelf().last();
            }

            this._tagInput = $('<input type="text" />').addClass('ui-widget-content');
            if (this.options.tabIndex) {
                this._tagInput.attr('tabindex', this.options.tabIndex);
            }
            if (this.options.placeholderText) {
                this._tagInput.attr('placeholder', this.options.placeholderText);
            }

            this.options.tagSource = this.options.tagSource || function(search, showChoices) {
                var filter = search.term.toLowerCase();
                var choices = $.grep(this.options.availableTags, function(element) {
                    // Only match autocomplete options that begin with the search term.
                    // (Case insensitive.)
                    return (element.toLowerCase().indexOf(filter) === 0);
                });
                showChoices(this._subtractArray(choices, this.assignedTags()));
            };

            // Bind tagSource callback functions to this context.
            if ($.isFunction(this.options.tagSource)) {
                this.options.tagSource = $.proxy(this.options.tagSource, this);
            }

            this.tagList
                .addClass('tagit')
                .addClass('ui-widget ui-widget-content ui-corner-all')
                // Create the input field.
                .append($('<li class="tagit-new"></li>').append(this._tagInput))
                .on('click.tagit', function(e) {
                    var target = $(e.target);
                    if (target.hasClass('tagit-label')) {
                        that._trigger('onTagClicked', e, target.closest('.tagit-choice'));
                    } else {
                        // Sets the focus() to the input field, if the user
                        // clicks anywhere inside the UL. This is needed
                        // because the input field needs to be of a small size.
                        that._tagInput.focus();
                    }
                });

            // Add existing tags from the list, if any.
            this.tagList.children('li').each(function() {
                if (!$(this).hasClass('tagit-new')) {
                    that.createTag($(this).html(), $(this).attr('class'));
                    $(this).remove();
                }
            });

            // Single field support.
            if (this.options.singleField) {
                if (this.options.singleFieldNode) {
                    // Add existing tags from the input field.
                    var node = $(this.options.singleFieldNode);
                    var tags = node.val().split(this.options.singleFieldDelimiter);
                    node.val('');
                    $.each(tags, function(index, tag) {
                        that.createTag(tag);
                    });
                } else {
                    // Create our single field input after our list.
                    this.options.singleFieldNode = this.tagList.after('<input type="hidden" style="display:none;" value="" name="' + this.options.fieldName + '" />');
                }
            }

            // Events.
            this._tagInput
                .keydown(function(event) {
                    // Backspace is not detected within a keypress, so it must use keydown.
                    if (event.which == $.ui.keyCode.BACKSPACE && that._tagInput.val() === '') {
                        var tag = that._lastTag();
                        if (!that.options.removeConfirmation || tag.hasClass('remove')) {
                            // When backspace is pressed, the last tag is deleted.
                            that.removeTag(tag);
                        } else if (that.options.removeConfirmation) {
                            tag.addClass('remove ui-state-highlight');
                        }
                    } else if (that.options.removeConfirmation) {
                        that._lastTag().removeClass('remove ui-state-highlight');
                    }

                    // check whether key for insertion was triggered
                    if( that._tagInput.val() !== '' && $.inArray( event.which, that.options.triggerKeys ) > -1 ) {
                        event.preventDefault();
                        that.createTag( that._tagInput.val() );

                        // The autocomplete doesn't close automatically when TAB is pressed.
                        // So let's ensure that it closes.
                        that._tagInput.autocomplete('close');
                    }
                }).blur(function(e){
					if( that.options.createOnBlur ) {
						// Create a tag when the element loses focus (unless it's empty).
						that.createTag( that._tagInput.val() );
					}
                });
                

            // Autocomplete.
            if (this.options.availableTags || this.options.tagSource) {
                this._tagInput.autocomplete({
                    source: this.options.tagSource,
                    select: function(event, ui) {
                        // Delete the last tag if we autocomplete something despite the input being empty
                        // This happens because the input's blur event causes the tag to be created when
                        // the user clicks an autocomplete item. I don't know how to lock my screen.
                        // The only artifact of this is that while the user holds down the mouse button
                        // on the selected autocomplete item, a tag is shown with the pre-autocompleted text,
                        // and is changed to the autocompleted text upon mouseup.
                        if (that._tagInput.val() === '') {
                            that.removeTag(that._lastTag(), false);
                        }
                        that.createTag(ui.item.value);
                        // Preventing the tag input to be updated with the chosen value.
                        return false;
                    }
                });
            }
        },

        _lastTag: function() {
            return this.tagList.children('.tagit-choice:last');
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
            if (this.options.singleField) {
                tags = $(this.options.singleFieldNode).val().split(this.options.singleFieldDelimiter);
                if (tags[0] === '') {
                    tags = [];
                }
            } else {
                this.tagList.children('.tagit-choice').each( function() {
					// check if already removed but still assigned till animations end. if so, don't add tag!
					if( ! $( this ).hasClass( 'tagit-choice-removed' ) ) {
                    	tags.push( that.tagLabel( this ) );
					}
                } );
            }
            return tags;
        },

        _updateSingleTagsField: function(tags) {
            // Takes a list of tag string values, updates this.options.singleFieldNode.val to the tags delimited by this.options.singleFieldDelimiter
            $(this.options.singleFieldNode).val(tags.join(this.options.singleFieldDelimiter));
        },

        _subtractArray: function(a1, a2) {
            var result = [];
            for (var i = 0; i < a1.length; i++) {
                if ($.inArray(a1[i], a2) == -1) {
                    result.push(a1[i]);
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
        tagLabel: function(tag) {
            // Returns the tag's string label.
            if (this.options.singleField) {
                return $(tag).children('.tagit-label').text();
            } else {
                return $(tag).children('input').val();
            }
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
			this.tagList.children( '.tagit-choice' ).each( function( i ) {
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

        _formatLabel: function(str) {
			str = $.trim( str );
            if (this.options.caseSensitive) {
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
            // Automatically trims the value of leading and trailing whitespace.
            value = this._formatLabel( value );

            if( this.hasTag( value ) || value === '' ) {
				var tag = this.getTag( value );
				if( tag !== null ) {
					// tag in list already, don't add it twice
					this._tagInput.val( '' );
					// highlight tag visually so the user knows the tag is in the list already
					// switch to highlighted class...
					tag.switchClass( '', 'tagit-choice-existing ui-state-highlight', 150, 'linear', function() {
						// ... and remove it again (also remove 'remove' class to avoid confusio
						tag.switchClass( 'tagit-choice-existing ui-state-highlight remove', '', 750, 'linear' );
					} );
				}
				return false;
            }

            var label = $(this.options.onTagClicked ? '<a class="tagit-label"></a>' : '<span class="tagit-label"></span>').text(value);

            // Create tag.
            var tag = $('<li></li>')
                .addClass('tagit-choice ui-widget-content ui-state-default ui-corner-all')
                .addClass(additionalClass)
                .append(label);

            // Button for removing the tag.
            var removeTagIcon = $('<span></span>')
                .addClass('ui-icon ui-icon-close');
            var removeTag = $('<a><span class="text-icon">\xd7</span></a>') // \xd7 is an X
                .addClass('tagit-close')
                .append(removeTagIcon)
                .click(function(e) {
                    // Removes a tag when the little 'x' is clicked.
                    that.removeTag(tag);
                });
            tag.append(removeTag);

            // Unless options.singleField is set, each tag has a hidden input field inline.
            if (this.options.singleField) {
                var tags = this.assignedTags();
                tags.push(value);
                this._updateSingleTagsField(tags);
            } else {
                var escapedValue = label.html();
                tag.append('<input type="hidden" style="display:none;" value="' + escapedValue + '" name="' + this.options.itemName + '[' + this.options.fieldName + '][]" />');
            }

            // Cleaning the input.
            this._tagInput.val('');

            // insert tag
            this._tagInput.parent().before(tag);

			this._trigger( 'onTagAdded', null, tag );

			return tag;
        },
        
        removeTag: function(tag, animate) {
            animate = animate || this.options.animate;

            tag = $(tag);

            this._trigger( 'onBeforeTagRemoved', null, tag );

            if (this.options.singleField) {
                var tags = this.assignedTags();
                var removedTagLabel = this.tagLabel(tag);
                tags = $.grep(tags, function(el){
                    return el != removedTagLabel;
                });
                this._updateSingleTagsField(tags);
            }

            // Animate the removal.
            if (animate) {
				tag.addClass( 'tagit-choice-removed' );
                tag.fadeOut('fast').hide('blind', {direction: 'horizontal'}, 'fast', function(){
					tag.remove(); //TODO/FIXME: danwe: This won't work for some reason, callback not called, fadeOut not happening!
                }).dequeue();
            } else {
				tag.remove();
            }

			this._trigger( 'onTagRemoved', null, tag );
			return true;
        },

        removeAll: function() {
            // Removes all tags.
            var that = this;
            this.tagList.children('.tagit-choice').each(function(index, tag) {
                that.removeTag(tag, false);
            });
        },

		/**
		 * Destroys the element and only leaves the original ul element (including all new elements)
		 *
		 * @todo test this also with 'input' element variation of tagit
		 */
		destroy: function() {
			var that = this;

			this.tagList
				.removeClass('tagit ui-widget ui-widget-content ui-corner-all')
				.off( 'click.tagit' )
				.children( '.tagit-new' ).remove();

			this.tagList.children( 'li' ).each( function() {
				var tag = $( this );
				var text = that.tagLabel( tag );
				tag
					.removeClass('tagit-choice tagit-choice-removed ui-widget-content ui-state-default ui-corner-all ui-state-highlight remove')
					.empty()
					.text( text ); // also removes all the helper stuff within
			} );

			return $.Widget.prototype.destroy.call( this );
		}

    });

})(jQuery);


