<template>
	<span
		class="wb-db-term-label"
		v-inlanguage="term.language"
	>{{ term.value }}</span>
</template>

<script lang="ts">
import Vue, { PropType } from 'vue';
import Term from '@/datamodel/Term';

/**
 * A label for a Term in a certain language.
 */
export default Vue.extend( {
	name: 'TermLabel',
	props: {
		term: {
			type: Object as PropType<Term>,
			required: true,
		},
	},
} );
</script>

<style lang="scss">
.wb-db-term-label {
	@include hyphens();
}
</style>
