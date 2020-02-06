<template>
	<div>
		<h1>
			An error occurred
		</h1>
		<pre>{{ applicationErrorsString }}</pre>
	</div>
</template>

<script lang="ts">
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';

/**
 * A component which gets shown if no dedicated handling for the type of
 * error which happened is configured.
 */
@Component( {
} )
export default class ErrorUnknown extends mixins( StateMixin ) {
	public get applicationErrorsString(): string {
		return JSON.stringify( this.rootModule.state.applicationErrors, null, 4 );
	}
}
</script>
