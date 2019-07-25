<template>
	<div id="data-bridge-app" class="wb-db-app">
		<DataPlaceholder
			:entity-id="entityId"
			:property-id="propertyId"
			:edit-flow="editFlow"
		/>
	</div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import DataPlaceholder from '@/presentation/components/DataPlaceholder.vue';

@Component( {
	components: {
		DataPlaceholder,
	},
} )
export default class App extends Vue {

	data() {
		return {
			entityId: null,
			propertyId: null,
			editFlow: null,
		};
	}
}
</script>

<style scoped>
.wb-db-app {
	width: 458px;
	height: 448px;
	font-family: 'Avenir', 'Helvetica', 'Arial', sans-serif;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	text-align: center;
	color: #2c3e50;
}

@media screen and ( max-width: 499px ) {
	.wb-db-app {
		width: 100%;
		height: 100%;
	}
}
</style>
