<template>
	<div class="wb-tr-app">
		<TaintedIcon />
	</div>
</template>

<script lang="ts">
import {
	Component,
	Vue,
} from 'vue-property-decorator';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
	@Component( {
		components: {
			TaintedIcon,
		},
	} )
export default class App extends Vue {}
</script>
