<template>
	<label
		class="wb-db-property-label"
		:for="htmlFor"
	><TermLabel :term="term" /></label>
</template>

<script lang="ts">
import Vue, { PropType } from 'vue';
import TermLabel from '@/presentation/components/TermLabel.vue';
import Term from '@/datamodel/Term';

export default Vue.extend( {
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
} );
</script>

<style lang="scss">
.wb-db-property-label {
	@include inputFieldLabel();
}
</style>
