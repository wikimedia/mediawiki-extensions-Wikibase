<template>
	<div class="wb-ui-indeterminate-progress-bar__box">
		<div
			class="wb-ui-indeterminate-progress-bar__bar"
			aria-busy="true"
			aria-live="polite"
		/>
	</div>
</template>
<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent( {
	name: 'IndeterminateProgressBar',
	compatConfig: { MODE: 3 },
} );
</script>
<style lang="scss">
.wb-ui-indeterminate-progress-bar__box {
	position: absolute;
	height: 20px;
	width: 90%;
	left: 5%;
	top: 50%;
	transform: translate( 0%, -50% );
	border: 1px solid #a2a9b1;
	box-shadow: 0 2px 0 0 rgba( 0, 0, 0, 0.15 );
	box-sizing: border-box;
	border-radius: 2px;
	overflow: hidden;
	background-color: #fff;
}

.wb-ui-indeterminate-progress-bar__bar {
	animation: wb-ui-indeterminate-progress-bar__bar--slide 2s infinite linear;
	background-color: $color-primary;
	width: 40%;
	height: 100%;
	display: block;
}

@keyframes wb-ui-indeterminate-progress-bar__bar--slide {
	from {
		transform: translate( -100% );
	}

	to {
		transform: translate( 350% );
	}
}
</style>
