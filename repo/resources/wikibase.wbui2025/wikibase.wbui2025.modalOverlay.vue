<template>
	<teleport to="#mw-teleport-target">
		<transition name="wikibase-wbui2025-modal-slide-in" appear>
			<div class="wikibase-wbui2025-modal-overlay">
				<slot></slot>
			</div>
		</transition>
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
		const target = document.getElementById( 'mw-teleport-target' );
		if ( target && target.childElementCount === 0 ) {
			document.body.classList.remove( 'wikibase-wbui2025-modal-open' );
		}
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

.wikibase-wbui2025-modal-slide-in-enter-active {
	transition-duration: @transition-duration-base;
	transition-timing-function: @transition-timing-function-user;
}

.wikibase-wbui2025-modal-slide-in-enter-from {
	left: 100%;
}

body.wikibase-wbui2025-modal-open {
	overflow: hidden;
}
</style>
