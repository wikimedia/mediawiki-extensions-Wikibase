import { storiesOf } from '@storybook/vue';
import ReferenceSection from '@/presentation/components/ReferenceSection';
import useStore from './useStore';

storiesOf( 'ReferenceSection', module )
	.addParameters( { component: ReferenceSection } )
	.addDecorator( useStore( {
		renderedTargetReferences: [
			'<span><a href="https://example.com" target="_blank">title</a>. foo.</span>',
			'<span>bar. baz.</span>',
		],
	} ) )
	.add( 'default', () => ( {
		components: { ReferenceSection },
		template:
			'<ReferenceSection />',
	} ) );
