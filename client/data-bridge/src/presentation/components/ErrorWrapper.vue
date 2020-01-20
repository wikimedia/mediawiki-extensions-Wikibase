<template>
	<section class="wb-db-error">
		<ErrorPermission
			v-if="permissionErrors.length"
			:permission-errors="permissionErrors"
		/>
		<ErrorUnknown v-else />
	</section>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { State } from 'vuex-class';
import { MissingPermissionsError, PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import ApplicationError from '@/definitions/ApplicationError';

@Component( {
	components: { ErrorPermission, ErrorUnknown },
} )
export default class ErrorWrapper extends Vue {
	@State( 'applicationErrors' )
	public applicationErrors!: ApplicationError[];

	public get permissionErrors(): MissingPermissionsError[] {
		return this.applicationErrors.filter( this.isPermissionError );
	}

	private isPermissionError( error: ApplicationError ): error is MissingPermissionsError {
		return Object.values( PageNotEditable ).includes( error.type );
	}
}
</script>

<style lang="scss">
.wb-db-error {
	padding: $padding-panel-form;
}
</style>
