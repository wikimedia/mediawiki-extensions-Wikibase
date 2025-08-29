<template>
	<div class="wikibase-wbui2025-modal-overlay">
		<slot></slot>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025ModalOverlay',
	mounted() {
		[ 'body', '.minerva-footer', '.minerva-header' ]
			.map( ( el ) => document.querySelector( el ) )
			.filter( ( el ) => el )
			.forEach( ( el ) => el.classList.add( 'wikibase-wbui2025-modal-open' ) );
	},
	unmounted() {
		[ 'body', '.minerva-footer', '.minerva-header' ]
			.map( ( el ) => document.querySelector( el ) )
			.filter( ( el ) => el )
			.forEach( ( el ) => el.classList.remove( 'wikibase-wbui2025-modal-open' ) );
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-modal-overlay {
	position: fixed;
	background-color: @background-color-base;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	display: flex;
	justify-content: center;
	align-items: center;
	z-index: 1;
}

.minerva-header.wikibase-wbui2025-modal-open {
	display: none;
}

.minerva-footer.wikibase-wbui2025-modal-open {
	display: none;
}

body.wikibase-wbui2025-modal-open {
	overflow: hidden;
}
</style>
