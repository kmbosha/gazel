<?php
declare(strict_types=1);

/**
 * Simple price monitor script.
 *
 * Configure the $products array with the URLs and XPath selectors for
 * the price elements you want to monitor. The script fetches each page,
 * extracts the price, and renders an HTML table.
 */

$products = [
    [
        'name' => 'Example Domain',
        'url' => 'https://example.com/',
        'price_selector' => '//h1',
    ],
];

/**
 * Fetch the raw HTML for a given URL.
 */
function fetchHtml(string $url): string
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'GazellePriceMonitor/1.0 (+https://github.com/WhatCD/Gazelle)',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $html = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($html === false) {
        throw new RuntimeException(sprintf('Failed to fetch %s: %s', $url, $error));
    }

    if ($statusCode >= 400) {
        throw new RuntimeException(sprintf('Unexpected status code %d when fetching %s', $statusCode, $url));
    }

    return $html;
}

/**
 * Extract a text value from HTML using an XPath selector.
 */
function extractValue(string $html, string $xpathSelector): ?string
{
    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    if (!$loaded) {
        return null;
    }

    $xpath = new DOMXPath($dom);
    $nodeList = $xpath->query($xpathSelector);

    if ($nodeList === false || $nodeList->length === 0) {
        return null;
    }

    $text = trim($nodeList->item(0)->textContent ?? '');

    return $text !== '' ? $text : null;
}

$results = [];
foreach ($products as $product) {
    $name = $product['name'] ?? $product['url'];
    $url = $product['url'];
    $selector = $product['price_selector'];

    try {
        $html = fetchHtml($url);
        $price = extractValue($html, $selector);
    } catch (Throwable $exception) {
        $price = null;
        $results[] = [
            'name' => $name,
            'url' => $url,
            'price' => 'Error: ' . $exception->getMessage(),
            'checkedAt' => new DateTimeImmutable(),
        ];
        continue;
    }

    $results[] = [
        'name' => $name,
        'url' => $url,
        'price' => $price ?? 'Not found',
        'checkedAt' => new DateTimeImmutable(),
    ];
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Price Monitor</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        table { border-collapse: collapse; width: 100%; max-width: 960px; }
        th, td { border: 1px solid #ccc; padding: 0.75rem; text-align: left; }
        th { background-color: #f5f5f5; }
        caption { font-size: 1.5rem; margin-bottom: 1rem; }
        tbody tr:nth-child(even) { background-color: #fafafa; }
    </style>
</head>
<body>
    <table>
        <caption>Monitored Prices</caption>
        <thead>
            <tr>
                <th scope="col">Product</th>
                <th scope="col">Current Value</th>
                <th scope="col">Checked At</th>
                <th scope="col">Source</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$row['price'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['checkedAt']->format(DateTimeInterface::ATOM), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><a href="<?= htmlspecialchars($row['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Visit</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
