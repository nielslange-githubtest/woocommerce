---
post_title: Checkout - How to create a custom inner block within the Checkout block
menu_title: Extending Checkout with custom inner blocks
tags: how-to
---

## Motivation

The Checkout block offers a standardised checkout experience, but not all hooks and filters that are available in the classic checkout work with it, due to the different architecture of the Checkout block. To extend the Checkout block, developers can create custom inner blocks. This guide outlines the key implementation steps based on the [Build a "Not at Home" Shipping Extension in the WooCommerce Checkout Block](https://github.com/woocommerce/wceu23-shipping-workshop-final/tree/main) repo. It covers block registration, checkout block injection, UI integration, and Store API handling.

### Prerequisites

-   WordPress development environment
-   WooCommerce installed and activated
-   Basic knowledge of JavaScript and [React](https://react.dev/learn)
-   Code editor, e.g. [Visual Studio Code](https://code.visualstudio.com/) or [Cursor](https://www.cursor.com/)
-   [Node](https://nodejs.org/en) or [NVM](https://github.com/nvm-sh/nvm) installed

## Block registration

💡 The block registration process involves defining the block metadata, registering the block with `registerBlockType`, and setting up the parent block relationship using the `parent` properties.

### Define the block metadata in `block.json`

For our custom inner block, we first need to create a `block.json` file to configure the block's name, title, category, scripts, styles, and attributes. This metadata is also used to register the block and render it in the editor. In the example repo, the `block.json` file can be found in [src/js/shipping-workshop-block/block.json](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/main/src/js/shipping-workshop-block/block.json). The documentation for the `block.json` file can be found on [developer.wordpress.org](https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-json/).

### Register the block with `registerBlockType`

Next, we need to register the block using `registerBlockType`. This takes place in [src/js/shipping-workshop-block/index.js](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/main/src/js/shipping-workshop-block/index.js):

```js
registerBlockType( metadata, {
	icon: {
		src: <Icon icon={ box } />,
	},
	edit: Edit,
	save: Save,
} );
```

The documentation on how to register a block can be found on [developer.wordpress.org](https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/).

### Set up the parent block relationship using the `parent` property

To ensure that the custom inner block appears at the desired section within the checkout form, we need to define a parent block relationship. This is done by setting the `parent` property in the block metadata. The parent block is the block that contains the custom inner block. In the example repo, the parent block is `'woocommerce/checkout-shipping-methods-block'`. The corresponding definition can be found [here](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/1d65e685fb40c207e8e06d6c066b890f527276c5/src/js/shipping-workshop-block/block.json#L14):

```json
"parent": [ "woocommerce/checkout-shipping-methods-block" ],
```

We can find the parent block names in the `block.json` file of each [inner block](https://github.com/woocommerce/woocommerce/tree/9c70772b63643c1156e77fd571f0284cfea74f6f/plugins/woocommerce/client/blocks/assets/js/blocks/checkout/inner-blocks). The following list shows a few examples:

| Parent block name                                | Placement of custom inner block     |
| ------------------------------------------------ | ----------------------------------- |
| `woocommerce/checkout-contact-information-block` | Below the contact information block |
| `woocommerce/checkout-shipping-address-block`    | Below the shipping address block    |
| `woocommerce/checkout-billing-address-block`     | Below the billing address block     |
| `woocommerce/checkout-order-note-block`          | Below the order note block          |

The documentation for the `parent` property can be found on [developer.wordpress.org](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#parent).

### Implement the frontend and editor components

Now that we've defined the block metadata and registered the block, we need to implement the frontend and editor components. The frontend component is handled in [src/js/shipping-workshop-block/block.js](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/main/src/js/shipping-workshop-block/block.js) while the editor component is handled in [src/js/shipping-workshop-block/edit.js](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/main/src/js/shipping-workshop-block/edit.js).

## Checkout block injection & UI Integration

💡 The checkout block injection and UI integration process involves registering the custom inner block within the Checkout block, managing its local state and connecting it to the checkout lifecycle.

### Register the block with `registerCheckoutBlock`

Now that we've created our custom inner block, we need to register it within the Checkout block. This is done using `registerCheckoutBlock()` from the `@woocommerce/blocks-checkout` package. We are doing this in [src/js/shipping-workshop-block/frontend.js](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/main/src/js/shipping-workshop-block/frontend.js):

```js
registerCheckoutBlock( {
	metadata,
	component: Block,
} );
```

The documentation for `registerCheckoutBlock()` can be found on [github.com](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/packages/checkout/blocks-registry/README.md#registercheckoutblock-options-).

### Control the block visibility and placement

The visibility and placement is controlled by the parent block. For example, if a custom inner block has the parent property `woocommerce/checkout-shipping-address-block`, but the shopper selected local pickup, then the shipping address block will not be visible. As a result of that, the custom inner block will not be visible either.

### Manage the local block state using `useState`

To manage the local block state, we can use the `useState` hook. This allows us to store and update the user input within the block. In our case, we are storing the selected option, e.g. `Try again another day`, and the user input if the shopper selected the option `Other`. This takes place in [the following section](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/1d65e685fb40c207e8e06d6c066b890f527276c5/src/js/shipping-workshop-block/block.js#L58-L62):

```js
const [
	selectedAlternateShippingInstruction,
	setSelectedAlternateShippingInstruction,
] = useState( 'try-again' );
const [ otherShippingValue, setOtherShippingValue ] = useState( '' );
```

The documentation for `useState` can be found on [react.dev](https://react.dev/docs/hooks-state).

### Connect to the checkout lifecycle

We also want to react to certain actions, for example when the shopper selects the option `Other`, which then displays a text field, but does not provide any input. In this case, we want to inform the shopper about the missing input. We are doing this in [src/js/shipping-workshop-block/block.js](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/1d65e685fb40c207e8e06d6c066b890f527276c5/src/js/shipping-workshop-block/block.js#L148-L153):

```js
setValidationErrors( {
	[ validationErrorId ]: {
		message: __( 'Please add some text', 'shipping-workshop' ),
		hidden: ! hasInteractedWithOtherInput,
	},
} );
```

If the shopper provides a text or selects another option, we want to clear the validation error. This also takes place in [src/js/shipping-workshop-block/block.js](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/1d65e685fb40c207e8e06d6c066b890f527276c5/src/js/shipping-workshop-block/block.js#L139-L147):

```js
if (
	selectedAlternateShippingInstruction !== 'other' ||
	otherShippingValue !== ''
) {
	if ( validationError ) {
		clearValidationError( validationErrorId );
	}
	return;
}
```

## Store API and Data Flow

### Extend the Store API using `register_endpoint_data`

Next, we register a new endpoint for our data by calling `register_endpoint_data()`. We're doing this in [shipping-workshop-extend-store-endpoint.php](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/1d65e685fb40c207e8e06d6c066b890f527276c5/shipping-workshop-extend-store-endpoint.php#L37-L47):

```php
if ( is_callable( [ self::$extend, 'register_endpoint_data' ] ) ) {
  self::$extend->register_endpoint_data(
    [
      'endpoint'        => CheckoutSchema::IDENTIFIER,
      'namespace'       => self::IDENTIFIER,
      'schema_callback' => [ 'Shipping_Workshop_Extend_Store_Endpoint', 'extend_checkout_schema' ],
      'schema_type'     => ARRAY_A,
    ]
  );
}
```

Once we've registered the new endpoint, we can extend the schema. This also takes place in [shipping-workshop-extend-store-endpoint.php](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/1d65e685fb40c207e8e06d6c066b890f527276c5/shipping-workshop-extend-store-endpoint.php#L55-L82):

```php
public static function extend_checkout_schema() {

  return [
    'otherShippingValue'           => [
      'description' => 'Other text for shipping instructions',
      'type'        => 'string',
      'context'     => [ 'view', 'edit' ],
      'readonly'    => true,
      'optional'    => true,
      'arg_options' => [
        'validate_callback' => function( $value ) {
          return is_string( $value );
        },
      ],
    ],
    'alternateShippingInstruction' => [
      'description' => 'Alternative shipping instructions for the courier',
      'type'        => 'string',
      'context'     => [ 'view', 'edit' ],
      'readonly'    => true,
      'arg_options' => [
        'validate_callback' => function( $value ) {
          return is_string( $value );
        },
      ],
    ],
  ];
}
```

### Submit the data using `setExtensionData`

When a user interacts with the custom inner block, we can then submit the data using `setExtensionData()`. This takes place in [src/js/shipping-workshop-block/block.js](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/1d65e685fb40c207e8e06d6c066b890f527276c5/src/js/shipping-workshop-block/block.js#L81-L86):

```js
setExtensionData(
  'shipping-workshop',
  'alternateShippingInstruction',
  selectedAlternateShippingInstruction
);
}, [ setExtensionData, selectedAlternateShippingInstruction ] );
```

### Perform server-side validation and sanitization

> TODO: Write about server-side validation and sanitization according to the defined schema.

### Process and persist the data using `woocommerce_store_api_checkout_update_order_from_request`

> TODO: Write about processing and persisting the data using `woocommerce_store_api_checkout_update_order_from_request`.

### Saving and displaying the data of the custom block

> TODO: Write about saving and displaying the data of the custom block on the order confirmation page, on the admin order page and in the order email.
