# ContentGem PHP SDK

Official PHP SDK for the ContentGem API.

## Installation

```bash
composer require contentgem/php-sdk
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use ContentGem\Client;

// Initialize client
$client = new Client(
    'cg_your_api_key_here',
    'https://your-domain.com/api/v1' // optional
);

// Generate content
$result = $client->generatePublication(
    'Write about AI in business',
    [
        'name' => 'TechCorp',
        'description' => 'Leading technology solutions provider',
        'industry' => 'Technology'
    ]
);

if ($result['success']) {
    $sessionId = $result['data']['sessionId'];
    
    // Wait for generation to complete
    $status = $client->waitForGeneration($sessionId);
    
    if ($status['success'] && $status['data']['status'] === 'completed') {
        echo "Generated content: " . $status['data']['content'];
    }
}
```

## Features

- ✅ **Full PHP 7.4+ support** with type hints
- ✅ **All API endpoints** covered
- ✅ **Error handling** with detailed error messages
- ✅ **Guzzle HTTP client** for reliable requests
- ✅ **Automatic polling** with `waitForGeneration()`
- ✅ **PSR-4 autoloading** compliant
- ✅ **Composer** ready
- ✅ **Bulk generation** support for multiple publications
- ✅ **Company management** with website parsing
- ✅ **Real-time status tracking** for all operations

## API Reference

### Constructor

```php
new Client($apiKey, $baseUrl = 'https://your-domain.com/api/v1', $timeout = 30)
```

**Parameters:**
- `$apiKey` (string): Your ContentGem API key
- `$baseUrl` (string, optional): API base URL
- `$timeout` (int, optional): Request timeout in seconds

### Publications

```php
// Get all publications
$publications = $client->getPublications($page, $limit);

// Get specific publication
$publication = $client->getPublication($publicationId);

// Create publication
$newPub = $client->createPublication($data);

// Generate content
$result = $client->generatePublication(
    $prompt,
    $companyInfo,
    $keywords
);

// Check generation status
$status = $client->checkGenerationStatus($sessionId);

// Wait for generation to complete
$completed = $client->waitForGeneration($sessionId);

// Update publication
$client->updatePublication($publicationId, $data);

// Delete publication
$client->deletePublication($publicationId);

// Publish/Archive
$client->publishPublication($publicationId);
$client->archivePublication($publicationId);

// Download
$client->downloadPublication($publicationId, 'pdf');
```

### Bulk Generation

```php
// Bulk generate multiple publications
$bulkResult = $client->bulkGeneratePublications(
    [
        'Write about AI in business',
        'Explain machine learning basics',
        'Discuss the future of automation'
    ],
    [
        'name' => 'TechCorp',
        'description' => 'Technology company'
    ],
    [
        'length' => 'medium',
        'style' => 'educational'
    ]
);

// Check bulk generation status
$bulkStatus = $client->checkBulkGenerationStatus($bulkSessionId);

// Wait for bulk generation to complete
$completedBulk = $client->waitForBulkGeneration($bulkSessionId);
```

### Company Management

```php
// Get company information
$companyInfo = $client->getCompanyInfo();

// Update company information
$client->updateCompanyInfo([
    'name' => 'Updated Company Name',
    'description' => 'Updated description',
    'website' => 'https://example.com',
    'contact_email' => 'contact@example.com'
]);

// Parse company website
$parsingResult = $client->parseCompanyWebsite('https://example.com');

// Get parsing status
$parsingStatus = $client->getCompanyParsingStatus();
```

### Images

```php
// Get all images
$images = $client->getImages($page, $limit);

// Get specific image
$image = $client->getImage($imageId);

// Upload image
$uploaded = $client->uploadImage($filePath, $publicationId);

// Generate AI image
$generated = $client->generateImage($prompt, $style, $size);

// Delete image
$client->deleteImage($imageId);
```

### Subscription & Statistics

```php
// Subscription
$status = $client->getSubscriptionStatus();
$limits = $client->getSubscriptionLimits();
$plans = $client->getSubscriptionPlans();

// Statistics
$overview = $client->getStatisticsOverview();
$pubStats = $client->getPublicationStatistics();
$imgStats = $client->getImageStatistics();
```

## Error Handling

```php
try {
    $result = $client->generatePublication('Test prompt');
    
    if ($result['success']) {
        echo "Success: " . json_encode($result['data']);
    } else {
        echo "API Error: " . $result['error'] . " - " . $result['message'];
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Advanced Usage

### Custom Company Information

```php
$companyInfo = [
    'name' => 'My Company',
    'description' => 'Technology company',
    'industry' => 'Technology',
    'target_audience' => 'Developers',
    'content_preferences' => [
        'length' => 'medium',
        'style' => 'educational',
        'include_examples' => true,
        'include_statistics' => true,
        'include_images' => true
    ],
    'tone' => 'professional'
];

$result = $client->generatePublication(
    'Write about AI in business',
    $companyInfo,
    ['AI', 'business', 'automation']
);
```

### Image Upload

```php
// Upload image
$uploadResult = $client->uploadImage('/path/to/image.jpg', $publicationId);

if ($uploadResult['success']) {
    echo "Image uploaded: " . $uploadResult['data']['image']['url'];
}
```

### Generation Polling

```php
// Start generation
$result = $client->generatePublication('Write about AI');

if ($result['success']) {
    $sessionId = $result['data']['sessionId'];
    
    // Poll for completion
    $status = $client->waitForGeneration($sessionId, 60, 5); // 60 attempts, 5s delay
    
    if ($status['success'] && $status['data']['status'] === 'completed') {
        echo "Content: " . $status['data']['content'];
    }
}
```

## Development

```bash
# Clone repository
git clone https://github.com/contentgem/contentgem-php.git
cd contentgem-php

# Install dependencies
composer install

# Install development dependencies
composer install --dev

# Run tests
composer test

# Code style check
composer cs-check

# Code style fix
composer cs-fix

# Static analysis
composer stan
```

## Requirements

- PHP >= 7.4
- Guzzle HTTP >= 7.0
- JSON extension

## License

MIT License 