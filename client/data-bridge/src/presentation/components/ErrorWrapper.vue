<template>
	<section class="wb-db-error">
		<ErrorPermission
			v-if="permissionErrors.length"
			:permission-errors="permissionErrors"
		/>
		<ErrorUnsupportedDatatype
			v-else-if="unsupportedDatatypeError !== null"
			:data-type="unsupportedDatatypeError.info.unsupportedDatatype"
		/>
		<ErrorDeprecatedStatement
			v-else-if="statementValueIsDeprecated"
		/>
		<ErrorAmbiguousStatement
			v-else-if="statementIsAmbiguous"
		/>
		<ErrorUnsupportedSnakType
			v-else-if="unsupportedSnakTypeError !== null"
			:snak-type="unsupportedSnakTypeError.info.snakType"
		/>
		<ErrorSaving
			v-else-if="isGenericSavingError"
		/>
		<ErrorSavingAssertUser
			v-else-if="isAssertUserFailedError"
			:login-url="loginUrl"
		/>
		<ErrorSavingEditConflict
			v-else-if="isEditConflictError"
			@reload="reload"
		/>
		<ErrorUnknown
			v-else
			@relaunch="relaunch"
		/>
	</section>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import ErrorUnsupportedSnakType from '@/presentation/components/ErrorUnsupportedSnakType.vue';
import { MissingPermissionsError, PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import StateMixin from '@/presentation/StateMixin';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import ErrorDeprecatedStatement from '@/presentation/components/ErrorDeprecatedStatement.vue';
import ErrorAmbiguousStatement from '@/presentation/components/ErrorAmbiguousStatement.vue';
import ErrorSaving from '@/presentation/components/ErrorSaving.vue';
import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser.vue';
import ErrorSavingEditConflict from '@/presentation/components/ErrorSavingEditConflict.vue';
import ApplicationError, {
	ErrorTypes,
	UnsupportedDatatypeError,
	UnsupportedSnakTypeError,
} from '@/definitions/ApplicationError';

function isPermissionError( error: ApplicationError ): error is MissingPermissionsError {
	return ( Object.values( PageNotEditable ) as string[] ).includes( error.type );
}

export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ErrorWrapper',
	components: {
		ErrorUnsupportedSnakType,
		ErrorPermission,
		ErrorUnknown,
		ErrorUnsupportedDatatype,
		ErrorDeprecatedStatement,
		ErrorAmbiguousStatement,
		ErrorSaving,
		ErrorSavingAssertUser,
		ErrorSavingEditConflict,
	},
	emits: [ 'reload', 'relaunch' ],
	computed: {
		applicationErrors(): ApplicationError[] {
			return this.rootModule.state.applicationErrors;
		},
		permissionErrors(): MissingPermissionsError[] {
			return this.applicationErrors.filter( isPermissionError );
		},
		unsupportedDatatypeError(): UnsupportedDatatypeError | null {
			for ( const applicationError of this.applicationErrors ) {
				if ( applicationError.type === ErrorTypes.UNSUPPORTED_DATATYPE ) {
					return applicationError;
				}
			}
			return null;
		},
		statementValueIsDeprecated(): boolean {
			return this.applicationErrors.some(
				( applicationError: ApplicationError ) =>
					applicationError.type === ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT,
			);
		},
		statementIsAmbiguous(): boolean {
			return this.applicationErrors.some(
				( applicationError: ApplicationError ) =>
					applicationError.type === ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT,
			);
		},
		unsupportedSnakTypeError(): UnsupportedSnakTypeError | null {
			for ( const applicationError of this.applicationErrors ) {
				if ( applicationError.type === ErrorTypes.UNSUPPORTED_SNAK_TYPE ) {
					return applicationError;
				}
			}
			return null;
		},
		isGenericSavingError(): boolean {
			return this.rootModule.getters.isGenericSavingError;
		},
		isAssertUserFailedError(): boolean {
			return this.rootModule.getters.isAssertUserFailedError;
		},
		isEditConflictError(): boolean {
			return this.rootModule.getters.isEditConflictError;
		},
		loginUrl(): string {
			return this.$clientRouter.getPageUrl(
				'Special:UserLogin',
				{
					warning: this.$messages.KEYS.LOGIN_WARNING,
				},
			);
		},
	},
	methods: {
		relaunch(): void {
			/**
			 * An event fired when it is time to relaunch the bridge (usually bubbled from a child component)
			 * @type {Event}
			 */
			this.$emit( 'relaunch' );
		},
		reload(): void {
			/**
			 * An event fired when the user requested to reload the whole page (usually bubbled from a child component)
			 * @type {Event}
			 */
			this.$emit( 'reload' );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-error {
	padding: $padding-panel-form;
}
</style>
