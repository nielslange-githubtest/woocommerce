/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

type Context = {
	context: { postId: string; postType: string };
};

export type ProductAttributesEditProps = BlockEditProps< object > & Context;
