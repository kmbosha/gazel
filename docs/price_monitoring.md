# Price Monitoring Script

The `price_monitor.php` script provides a minimal example of how to fetch prices
from product pages and render the result inside an HTML table. This file is not
part of Gazelle's core application – it is provided as a simple utility that can
be deployed alongside the rest of the site when you need to monitor external
prices.

## Configuration

Open `price_monitor.php` and edit the `$products` array near the top of the
file. Each product entry accepts the following keys:

| Key              | Description |
| ---------------- | ----------- |
| `name`           | Friendly label that is shown in the table. |
| `url`            | The page that will be fetched. |
| `price_selector` | XPath selector that resolves to the HTML element containing the price. |

You can add as many products as you like by appending new items to the array.

## How it works

1. The script fetches the HTML for each product URL using cURL.
2. It parses the markup with `DOMDocument` and `DOMXPath`.
3. The price text is extracted with the provided XPath selector.
4. Results are rendered in an HTML table with the retrieval timestamp.

## Error handling

If the script cannot fetch or parse the target page, the error message is shown
in the `Current Value` column so you can react accordingly.

## Tips

* Many storefronts load prices dynamically with JavaScript. For those pages you
  may need to use an API instead of scraping the HTML.
* Use your own user agent string if you need to identify yourself to the remote
  service.
* Respect the terms of service of the sites you monitor and avoid requesting the
  data too frequently.
