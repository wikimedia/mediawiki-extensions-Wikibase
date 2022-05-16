<template>
	<span
		class="wb-db-term-label"
		v-bind="inLanguage(term.language)"
	>{{ term.value }}</span>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';
import Term from '@/datamodel/Term';

/**
 * A label for a Term in a certain language.
 */
export default defineComponent( {
	name: 'TermLabel',
	props: {
		term: {
			type: Object as PropType<Term>,
			required: true,
		},
		inLanguage: {
			type: Function as PropType<( langCode: string ) => { lang: string; dir: string; }>,
			required: true,
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-term-label {
	@include hyphens();
}
</style>
