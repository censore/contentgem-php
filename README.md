# ContentGem PHP SDK

Official PHP SDK for the ContentGem API - a powerful content generation platform using AI.

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
    'https://gemcontent.com/api/v1' // optional
);

// Generate content
$result = $client->generatePublication(
    'Write about AI in business',
    [
        'name' => 'TechCorp',
        'description' => 'Leading technology solutions provider',
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
    ],
    ['AI', 'business', 'automation'] // keywords
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
- ✅ **Complete API coverage** - all ContentGem API endpoints
- ✅ **AI Content Generation** - blog posts, reviews, and articles
- ✅ **AI Image Generation** - generate images using AI
- ✅ **Image Management** - upload, update, and manage images
- ✅ **Bulk Operations** - generate multiple publications at once
- ✅ **Company Information** - manage and parse company data
- ✅ **Subscription Management** - check plans, limits, and usage
- ✅ **Statistics & Analytics** - detailed usage statistics
- ✅ **Error handling** with detailed error messages
- ✅ **Guzzle HTTP client** for reliable requests
- ✅ **Automatic polling** with `waitForGeneration()`
- ✅ **PSR-4 autoloading** compliant
- ✅ **Composer** ready

## API Reference

### Constructor

```php
new Client($apiKey, $baseUrl = 'https://gemcontent.com/api/v1', $timeout = 30)
```

**Parameters:**

- `$apiKey` (string): Your ContentGem API key
- `$baseUrl` (string, optional): API base URL
- `$timeout` (int, optional): Request timeout in seconds

### Publications

```php
// Get all publications with filtering
$publications = $client->getPublications($page, $limit, $type, $status);

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

// Check publication generation status by publication ID
$pubStatus = $client->checkPublicationGenerationStatus($publicationId);

// Bulk generate multiple publications
$bulkResult = $client->bulkGeneratePublications(
    ['Write about AI', 'Explain machine learning', 'Discuss automation'],
    $companyInfo,
    ['keywords' => ['AI', 'technology', 'automation']]
);

// Check bulk generation status
$bulkStatus = $client->checkBulkGenerationStatus($bulkSessionId);

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

### Images

```php
// Get all images with filtering
$images = $client->getImages($page, $limit, $publicationId, $search);

// Get specific image
$image = $client->getImage($imageId);

// Upload image
$uploaded = $client->uploadImage($filePath, $publicationId);

// Generate image using AI
$generated = $client->generateImage('A beautiful landscape', 'realistic', '1024x1024');

// Get images for specific publication
$publicationImages = $client->getPublicationImages($publicationId);

// Update image metadata
$client->updateImage($imageId, ['title' => 'New title']);

// Delete image
$client->deleteImage($imageId);
```

### Company

```php
// Get company information
$company = $client->getCompanyInfo();

// Update company information
$client->updateCompanyInfo([
    'name' => 'Updated Company Name',
    'description' => 'Updated description',
    'industry' => 'Technology',
    'website' => 'https://example.com'
]);

// Parse company website
$parseResult = $client->parseCompanyWebsite('https://example.com');
// Or parse multiple URLs
$parseResult = $client->parseCompanyWebsite(['https://example.com', 'https://company.com']);

// Get parsing status
$parseStatus = $client->getCompanyParsingStatus();
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

### Filtering Publications

```php
// Get only published blog posts
$publishedBlogs = $client->getPublications(1, 10, 'blog', 'published');

// Get all draft publications
$drafts = $client->getPublications(1, 20, null, 'draft');

// Get all review publications
$reviews = $client->getPublications(1, 10, 'review', null);
```

### Filtering Images

```php
// Get images for specific publication
$publicationImages = $client->getImages(1, 20, $publicationId);

// Search images by prompt or section title
$searchResults = $client->getImages(1, 10, null, 'AI technology');

// Get images with all filters
$filteredImages = $client->getImages(1, 10, $publicationId, 'business');
```

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

### Image Management

```php
// Upload single image
$uploadResult = $client->uploadImage('/path/to/image.jpg');

if ($uploadResult['success']) {
    echo "Image uploaded: " . $uploadResult['data']['image']['url'];
}

// Upload image with publication association
$uploadResult = $client->uploadImage('/path/to/image.jpg', $publicationId);

// Generate image using AI
$generateResult = $client->generateImage(
    'A modern office building with glass facade',
    'realistic',
    '1024x1024',
    $publicationId
);

if ($generateResult['success']) {
    echo "Image generated: " . $generateResult['data']['image']['url'];
}

// Get images for specific publication
$publicationImages = $client->getPublicationImages($publicationId);

// Update image metadata
$updateResult = $client->updateImage($imageId, [
    'title' => 'Updated image title',
    'description' => 'Updated description'
]);
```

### Bulk Generation

```php
// Bulk generate multiple publications
$prompts = [
    'Write about AI in business',
    'Explain machine learning basics',
    'Discuss the future of automation'
];

$companyInfo = [
    'name' => 'TechCorp',
    'description' => 'Technology company',
    'industry' => 'Technology',
    'target_audience' => 'Developers'
];

$commonSettings = [
    'keywords' => ['AI', 'technology', 'automation']
];

$bulkResult = $client->bulkGeneratePublications($prompts, $companyInfo, $commonSettings);

if ($bulkResult['success']) {
    $bulkSessionId = $bulkResult['data']['bulk_session_id'];

    // Check bulk status
    $bulkStatus = $client->checkBulkGenerationStatus($bulkSessionId);

    if ($bulkStatus['success']) {
        foreach ($bulkStatus['data']['publications'] as $publication) {
            echo "Publication: " . $publication['title'] . "\n";
        }
    }
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

## API Endpoints Coverage

The PHP SDK provides complete coverage of the ContentGem API:

### ✅ Publications

- `GET /publications` - List all publications
- `GET /publications/:id` - Get specific publication
- `POST /publications` - Create new publication
- `POST /publications/generate` - Generate content with AI
- `POST /publications/bulk-generate` - Bulk content generation
- `GET /publications/generation-status/:sessionId` - Check generation status
- `GET /publications/publication-status/:id` - Check publication generation status
- `GET /publications/bulk-status/:bulkSessionId` - Check bulk generation status
- `PUT /publications/:id` - Update publication
- `DELETE /publications/:id` - Delete publication
- `POST /publications/:id/publish` - Publish publication
- `POST /publications/:id/archive` - Archive publication
- `POST /publications/:id/download` - Download publication

### ✅ Images

- `GET /images` - List all images
- `GET /images/:id` - Get specific image
- `POST /images/upload` - Upload image
- `POST /images/generate` - Generate image using AI
- `GET /publications/:id/images` - Get images for specific publication
- `PUT /images/:id` - Update image metadata
- `DELETE /images/:id` - Delete image

### ✅ Company

- `GET /company` - Get company information
- `PUT /company` - Update company information
- `POST /company/parse` - Parse company website
- `GET /company/parsing-status` - Get parsing status

### ✅ Subscription & Statistics

- `GET /subscription/status` - Get subscription status
- `GET /subscription/plans` - Get available plans
- `GET /subscription/limits` - Get API limits
- `GET /statistics/overview` - Get overview statistics
- `GET /statistics/publications` - Get publication statistics
- `GET /statistics/images` - Get image statistics

### ✅ Health Check

- `GET /health` - API health check

## Requirements

- PHP >= 7.4
- Guzzle HTTP >= 7.0
- JSON extension

## License

MIT License

## Support

- 📧 Email: support@contentgem.com
- 📖 Documentation: https://docs.contentgem.com/api
- 🐛 Issues: https://github.com/contentgem/contentgem-php/issues
- 💬 Community: https://github.com/contentgem/contentgem-php/discussions
