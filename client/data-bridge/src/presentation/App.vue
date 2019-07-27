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
import ApplicationStatus from '@/definitions/ApplicationStatus';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
	NS_ENTITY,
} from '@/store/namespaces';
import {
	ENTITY_ID,
} from '@/store/entity/getterTypes';
import { Getter, namespace } from 'vuex-class';

@Component( {
	components: {
		DataPlaceholder,
	},
} )
export default class App extends Vue {
	@Getter( 'applicationStatus' )
	public applicationStatus!: ApplicationStatus;

	@namespace( NS_ENTITY ).Getter( ENTITY_ID )
	public entityId!: string;

	@Getter( 'editFlow' )
	public editFlow!: string;

	@Getter( 'targetProperty' )
	public propertyId!: string;

	public created() {
		this.$store.dispatch( BRIDGE_INIT );
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
