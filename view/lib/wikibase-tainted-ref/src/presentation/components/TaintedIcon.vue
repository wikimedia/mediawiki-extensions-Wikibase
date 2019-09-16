<template>
	<div class="wb-tr-tainted-icon" title="This statement has some potential issues">
	</div>
</template>

<script>
	export default {
		name: 'TaintedIcon'
	};
</script>

<style lang="scss">
	.wb-tr-tainted-icon {
		min-width: 20px;
		min-height: 30px;
		background-color: transparent;
		background-image: $svg-tainted-icon;
		background-position: top left;
		background-size: 20px 30px;
		background-repeat: no-repeat;
	}
</style>
