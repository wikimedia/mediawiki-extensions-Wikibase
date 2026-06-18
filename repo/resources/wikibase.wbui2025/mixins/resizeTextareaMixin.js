'use strict';

const resizeTextareaMixin = {
	methods: {
		resizeTextarea() {
			this.$nextTick( () => {
				const inputElement = this.$refs.inputElement;

				if ( !inputElement || !inputElement.$refs ) {
					return;
				}

				const textarea = inputElement.$refs.textarea;

				// Do not calculate the height while the textarea is hidden,
				// e.g. inside a collapsed reference or qualifier section.
				// In that state scrollHeight can be only a few pixels.
				if ( !textarea || textarea.offsetHeight === 0 ) {
					return;
				}

				const normalHeight = textarea.offsetHeight;

				textarea.style.height = 'auto';
				textarea.style.height = `${ Math.max( textarea.scrollHeight, normalHeight ) }px`;
			} );
		}
	}
};

module.exports = resizeTextareaMixin;
