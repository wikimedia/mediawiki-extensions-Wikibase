import IndeterminateProgressBar from '@/presentation/components/IndeterminateProgressBar.vue';

export default {
	title: 'IndeterminateProgressBar',
	component: IndeterminateProgressBar,
	parameters: {
		controls: { hideNoControlsWarning: true },
	},
};

export function normal() {
	return {
		components: { IndeterminateProgressBar },
		template:
			`<div>
				<IndeterminateProgressBar />
			</div>`,
	};
}
