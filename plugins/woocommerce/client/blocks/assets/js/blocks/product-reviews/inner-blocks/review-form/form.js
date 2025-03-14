/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import {
	Warning,
	store as blockEditorStore,
	__experimentalGetElementClassName,
} from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

const CommentsFormPlaceholder = () => {
	const instanceId = useInstanceId( CommentsFormPlaceholder );

	return (
		<div className="comment-respond">
			<h3>{ __( 'Add a review', 'woocommerce' ) }</h3>
			<div className="wp-block-woocommerce-product-reviews__editor__form-container">
				<div className="wp-block-woocommerce-product-reviews__editor__row">
					<span>{ __( 'Your rating *', 'woocommerce' ) }</span>
					<p className="wp-block-woocommerce-product-reviews__editor__stars"></p>
				</div>
				<div className="wp-block-woocommerce-product-reviews__editor__row">
					<span>{ __( 'Your review *', 'woocommerce' ) }</span>
					<textarea />
				</div>
				<input
					type="submit"
					className="submit wp-block-button__link wp-element-button"
					value={ __( 'Submit', 'woocommerce' ) }
				/>
			</div>
		</div>
	);
};

const CommentsForm = ( { postId, postType } ) => {
	const [ commentStatus, setCommentStatus ] = useEntityProp(
		'postType',
		postType,
		'comment_status',
		postId
	);

	const isSiteEditor = postType === undefined || postId === undefined;

	const { defaultCommentStatus } = useSelect(
		( select ) =>
			select( blockEditorStore ).getSettings()
				.__experimentalDiscussionSettings
	);

	const postTypeSupportsComments = useSelect( ( select ) =>
		postType
			? !! select( coreStore ).getPostType( postType )?.supports.comments
			: false
	);

	if ( ! isSiteEditor && 'open' !== commentStatus ) {
		if ( 'closed' === commentStatus ) {
			const actions = [
				<Button
					__next40pxDefaultSize
					key="enableComments"
					onClick={ () => setCommentStatus( 'open' ) }
					variant="primary"
				>
					{ _x(
						'Enable comments',
						'action that affects the current post',
						'woocommerce'
					) }
				</Button>,
			];
			return (
				<Warning actions={ actions }>
					{ __(
						'Post Comments Form block: Comments are not enabled for this item.',
						'woocommerce'
					) }
				</Warning>
			);
		} else if ( ! postTypeSupportsComments ) {
			return (
				<Warning>
					{ sprintf(
						/* translators: 1: Post type (i.e. "post", "page") */
						__(
							'Post Comments Form block: Comments are not enabled for this post type (%s).',
							'woocommerce'
						),
						postType
					) }
				</Warning>
			);
		} else if ( 'open' !== defaultCommentStatus ) {
			return (
				<Warning>
					{ __(
						'Post Comments Form block: Comments are not enabled.',
						'woocommerce'
					) }
				</Warning>
			);
		}
	}

	return <CommentsFormPlaceholder />;
};

export default CommentsForm;
