# Developer Hooks

WPRuby Delivery Estimates for WooCommerce exposes filters so other
plugins can extend or override its behaviour without editing core files.

All hooks use the `eddd_` prefix.

## Filters

### `eddd_default_settings`
Filter the default settings before they are merged with stored values.

```php
add_filter( 'eddd_default_settings', function ( array $defaults ) {
    $defaults['transit_max'] = 5;
    return $defaults;
} );
```

### `eddd_calculated_estimate`
Filter the final `Estimate` object after calculation.

```php
add_filter( 'eddd_calculated_estimate', function ( $estimate, $settings ) {
    return $estimate;
}, 10, 2 );
```

### `eddd_message`
Filter the formatted display message before output.

```php
add_filter( 'eddd_message', function ( $message, $template, $estimate, $context, $replacements ) {
    return $message;
}, 10, 5 );
```

### `eddd_range_separator`
Filter the separator placed between the two dates of a range (default `" – "`).

```php
add_filter( 'eddd_range_separator', function ( $separator ) {
    return ' to ';
} );
```

### `eddd_product_message_template`
Filter the product-page message template before placeholders are replaced.

```php
add_filter( 'eddd_product_message_template', function ( $template, $product ) {
    return $template;
}, 10, 2 );
```

### `eddd_product_html`
Filter the rendered product-page estimate HTML.

```php
add_filter( 'eddd_product_html', function ( $html, $product, $message ) {
    return $html;
}, 10, 3 );
```

## Template placeholders

`{earliest_date}`, `{latest_date}`, `{processing_days}`,
`{min_transit_days}`, `{max_transit_days}`, `{delivery_range}`, `{cutoff_time}`
