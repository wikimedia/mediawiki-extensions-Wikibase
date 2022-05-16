<template>
	<label
		class="wb-db-property-label"
		:for="htmlFor"
	><TermLabel :term="term" :in-language="$inLanguage" /></label>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';
import TermLabel from '@/presentation/components/TermLabel.vue';
import Term from '@/datamodel/Term';

export default defineComponent( {
	name: 'PropertyLabel',
	components: { TermLabel },
	props: {
		term: {
			type: Object as PropType<Term>,
			required: true,
		},
		htmlFor: {
			type: String,
			required: true,
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-property-label {
	@include inputFieldLabel();
}
</style>
