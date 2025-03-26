/**
 * External dependencies
 */
import { Meta, StoryObj } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Skeleton, SkeletonProps } from '../';

export default {
	title: 'Base Components/Skeleton/Base',
	component: Skeleton,
	argTypes: {
		width: { control: 'text' },
		height: { control: 'text' },
		borderRadius: { control: 'text' },
		className: { control: 'text' },
		tag: {
			control: { type: 'select' },
			options: [ 'div' ],
		},
	},
	parameters: {
		docs: {
			description: {
				component:
					'Base skeletons provide foundational building blocks for all skeleton components.',
			},
		},
	},
} as Meta< SkeletonProps >;

const Template = ( args: SkeletonProps ) => <Skeleton { ...args } />;

export const Default: StoryObj< SkeletonProps > = {
	render: Template,
	args: {},
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with the default args.',
			},
		},
	},
};

export const CustomHeight: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		height: '2.5em',
	},
	storyName: 'Custom height',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with a custom height.',
			},
		},
	},
};

export const CustomWidth: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		width: '50%',
	},
	storyName: 'Custom width',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with a custom width.',
			},
		},
	},
};

export const NoBorderRadius: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		borderRadius: '0',
	},
	storyName: 'No border radius',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component without a border radius.',
			},
		},
	},
};
