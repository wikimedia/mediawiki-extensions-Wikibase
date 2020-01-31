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
import Component, { mixins } from 'vue-class-component';
import { MissingPermissionsError, PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import StateMixin from '@/presentation/StateMixin';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import ApplicationError from '@/definitions/ApplicationError';

@Component( {
	components: { ErrorPermission, ErrorUnknown },
} )
export default class ErrorWrapper extends mixins( StateMixin ) {
	public get permissionErrors(): MissingPermissionsError[] {
		return this.rootModule.state.applicationErrors
			.filter( this.isPermissionError );
	}

	private isPermissionError( error: ApplicationError ): error is MissingPermissionsError {
		return ( Object.values( PageNotEditable ) as string[] ).includes( error.type );
	}
}
</script>

<style lang="scss">
.wb-db-error {
	padding: $padding-panel-form;
}
</style>
