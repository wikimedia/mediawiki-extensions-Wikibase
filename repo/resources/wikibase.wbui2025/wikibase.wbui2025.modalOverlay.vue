<template>
	<teleport to="body">
		<div class="wikibase-wbui2025-modal-overlay">
			<slot></slot>
		</div>
	</teleport>
</template>

<script>
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025ModalOverlay',
	mounted() {
		document.body.classList.add( 'wikibase-wbui2025-modal-open' );
	},
	unmounted() {
		document.body.classList.remove( 'wikibase-wbui2025-modal-open' );
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

body.wikibase-wbui2025-modal-open {
	overflow: hidden;
}
</style>
