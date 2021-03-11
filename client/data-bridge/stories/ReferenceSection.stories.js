import ReferenceSection from '@/presentation/components/ReferenceSection';
import useStore from './useStore';

export default {
	title: 'ReferenceSection',
	component: ReferenceSection,
	decorators: [
		useStore( {
			renderedTargetReferences: [
				'<span><a href="https://example.com" target="_blank">title</a>. foo.</span>',
				'<span>bar. baz.</span>',
			],
		} ),
	],
};

export function normal() {
	return {
		components: { ReferenceSection },
		template:
			'<ReferenceSection />',
	};
}
