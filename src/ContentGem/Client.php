<?php

namespace ContentGem;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Utils;

/**
 * ContentGem API Client
 * 
 * Provides methods to interact with the ContentGem API for content generation,
 * publication management, and more.
 */
class Client
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private GuzzleClient $httpClient;

    /**
     * Initialize the ContentGem client.
     * 
     * @param string $apiKey Your ContentGem API key
     * @param string $baseUrl API base URL (default: https://gemcontent.com/api/v1)
     * @param int $timeout Request timeout in seconds (default: 30)
     */
    public function __construct(string $apiKey, string $baseUrl = 'https://gemcontent.com/api/v1', int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        
        $this->httpClient = new GuzzleClient([
            'timeout' => $this->timeout,
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Make HTTP request to the API.
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array API response
     * @throws \Exception If the request fails
     */
    private function makeRequest(string $method, string $endpoint, array $options = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        try {
            $response = $this->httpClient->request($method, $url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $errorData = json_decode($response->getBody()->getContents(), true);
                throw new \Exception(
                    'API Error: ' . ($errorData['message'] ?? $e->getMessage())
                );
            }
            throw new \Exception('Request failed: ' . $e->getMessage());
        }
    }

    /**
     * Get all publications.
     * 
     * @param int $page Page number (default: 1)
     * @param int $limit Items per page (default: 10)
     * @param string|null $type Filter by type (blog, review)
     * @param string|null $status Filter by status (draft, published, archived)
     * @return array Publications response
     */
    public function getPublications(int $page = 1, int $limit = 10, ?string $type = null, ?string $status = null): array
    {
        $params = ['page' => $page, 'limit' => $limit];
        
        if ($type !== null) {
            $params['type'] = $type;
        }
        
        if ($status !== null) {
            $params['status'] = $status;
        }
        
        return $this->makeRequest('GET', '/publications', ['query' => $params]);
    }

    /**
     * Get specific publication.
     * 
     * @param string $publicationId Publication ID
     * @return array Publication data
     */
    public function getPublication(string $publicationId): array
    {
        return $this->makeRequest('GET', "/publications/{$publicationId}");
    }

    /**
     * Create new publication.
     * 
     * @param array $data Publication data
     * @return array Created publication
     */
    public function createPublication(array $data): array
    {
        return $this->makeRequest('POST', '/publications', ['json' => $data]);
    }

    /**
     * Generate new publication.
     * 
     * @param string $prompt Generation prompt
     * @param array $companyInfo Company information
     * @param array $keywords Keywords for generation
     * @return array Generation response
     */
    public function generatePublication(string $prompt, array $companyInfo = [], array $keywords = []): array
    {
        $requestData = ['prompt' => $prompt];
        
        if (!empty($companyInfo)) {
            $requestData['company_info'] = $companyInfo;
        }
        
        if (!empty($keywords)) {
            $requestData['keywords'] = $keywords;
        }
        
        return $this->makeRequest('POST', '/publications/generate', ['json' => $requestData]);
    }

    /**
     * Check generation status.
     * 
     * @param string $sessionId Generation session ID
     * @return array Generation status
     */
    public function checkGenerationStatus(string $sessionId): array
    {
        return $this->makeRequest('GET', "/publications/generation-status/{$sessionId}");
    }

    /**
     * Bulk generate multiple publications.
     * 
     * @param array $prompts Array of generation prompts
     * @param array $companyInfo Company information
     * @param array $commonSettings Common settings for all publications
     * @return array Bulk generation response
     */
    public function bulkGeneratePublications(array $prompts, array $companyInfo = [], array $commonSettings = []): array
    {
        $requestData = [
            'prompts' => $prompts,
            'settings' => [
                'company_info' => $companyInfo,
                'keywords' => $commonSettings['keywords'] ?? []
            ]
        ];
        
        return $this->makeRequest('POST', '/publications/bulk-generate', ['json' => $requestData]);
    }

    /**
     * Check bulk generation status.
     * 
     * @param string $bulkSessionId Bulk generation session ID
     * @return array Bulk generation status
     */
    public function checkBulkGenerationStatus(string $bulkSessionId): array
    {
        return $this->makeRequest('GET', "/publications/bulk-status/{$bulkSessionId}");
    }

    /**
     * Wait for generation to complete.
     * 
     * @param string $sessionId Generation session ID
     * @param int $maxAttempts Maximum polling attempts (default: 60)
     * @param int $delaySeconds Delay between attempts (default: 5)
     * @return array Final generation status
     * @throws \Exception If generation doesn't complete within max_attempts
     */
    public function waitForGeneration(string $sessionId, int $maxAttempts = 60, int $delaySeconds = 5): array
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $status = $this->checkGenerationStatus($sessionId);
            
            if ($status['success'] && $status['data']['status'] === 'completed') {
                return $status;
            }
            
            if ($status['success'] && $status['data']['status'] === 'failed') {
                throw new \Exception('Generation failed');
            }
            
            if ($attempt < $maxAttempts - 1) {
                sleep($delaySeconds);
            }
        }
        
        throw new \Exception('Generation timeout');
    }

    /**
     * Update publication.
     * 
     * @param string $publicationId Publication ID
     * @param array $data Update data
     * @return array Updated publication
     */
    public function updatePublication(string $publicationId, array $data): array
    {
        return $this->makeRequest('PUT', "/publications/{$publicationId}", ['json' => $data]);
    }

    /**
     * Delete publication.
     * 
     * @param string $publicationId Publication ID
     * @return array Deletion response
     */
    public function deletePublication(string $publicationId): array
    {
        return $this->makeRequest('DELETE', "/publications/{$publicationId}");
    }

    /**
     * Publish publication.
     * 
     * @param string $publicationId Publication ID
     * @return array Publish response
     */
    public function publishPublication(string $publicationId): array
    {
        return $this->makeRequest('POST', "/publications/{$publicationId}/publish");
    }

    /**
     * Archive publication.
     * 
     * @param string $publicationId Publication ID
     * @return array Archive response
     */
    public function archivePublication(string $publicationId): array
    {
        return $this->makeRequest('POST', "/publications/{$publicationId}/archive");
    }

    /**
     * Download publication.
     * 
     * @param string $publicationId Publication ID
     * @param string $format Download format (pdf, docx, html, markdown)
     * @return array Download response
     */
    public function downloadPublication(string $publicationId, string $format = 'pdf'): array
    {
        return $this->makeRequest('POST', "/publications/{$publicationId}/download", [
            'json' => ['format' => $format]
        ]);
    }

    /**
     * Get all images.
     * 
     * @param int $page Page number (default: 1)
     * @param int $limit Items per page (default: 10)
     * @param string|null $publicationId Filter by publication ID
     * @param string|null $search Search in prompts and section titles
     * @return array Images response
     */
    public function getImages(int $page = 1, int $limit = 10, ?string $publicationId = null, ?string $search = null): array
    {
        $params = ['page' => $page, 'limit' => $limit];
        
        if ($publicationId !== null) {
            $params['publicationId'] = $publicationId;
        }
        
        if ($search !== null) {
            $params['search'] = $search;
        }
        
        return $this->makeRequest('GET', '/images', ['query' => $params]);
    }

    /**
     * Get specific image.
     * 
     * @param string $imageId Image ID
     * @return array Image data
     */
    public function getImage(string $imageId): array
    {
        return $this->makeRequest('GET', "/images/{$imageId}");
    }

    /**
     * Upload image.
     * 
     * @param string $filePath Path to image file
     * @param string|null $publicationId Optional publication ID to associate with
     * @return array Upload response
     */
    public function uploadImage(string $filePath, ?string $publicationId = null): array
    {
        $multipart = [
            [
                'name' => 'image',
                'contents' => Utils::tryFopen($filePath, 'r'),
                'filename' => basename($filePath)
            ]
        ];
        
        if ($publicationId) {
            $multipart[] = [
                'name' => 'publicationId',
                'contents' => $publicationId
            ];
        }
        
        return $this->makeRequest('POST', '/images/upload', [
            'multipart' => $multipart,
            'headers' => ['X-API-Key' => $this->apiKey] // Remove Content-Type for multipart
        ]);
    }

    /**
     * Get company information.
     * 
     * @return array Company information
     */
    public function getCompanyInfo(): array
    {
        return $this->makeRequest('GET', '/company');
    }

    /**
     * Update company information.
     * 
     * @param array $companyData Company data to update
     * @return array Updated company information
     */
    public function updateCompanyInfo(array $companyData): array
    {
        return $this->makeRequest('PUT', '/company', ['json' => $companyData]);
    }

    /**
     * Parse company website.
     * 
     * @param string|array $urls Website URL(s) to parse
     * @return array Parsing response
     */
    public function parseCompanyWebsite($urls): array
    {
        // Convert single URL to array for consistency with API
        if (is_string($urls)) {
            $urls = [$urls];
        }
        
        return $this->makeRequest('POST', '/company/parse', [
            'json' => ['urls' => $urls]
        ]);
    }

    /**
     * Get company parsing status.
     * 
     * @return array Parsing status
     */
    public function getCompanyParsingStatus(): array
    {
        return $this->makeRequest('GET', '/company/parsing-status');
    }

    /**
     * Delete image.
     * 
     * @param string $imageId Image ID
     * @return array Deletion response
     */
    public function deleteImage(string $imageId): array
    {
        return $this->makeRequest('DELETE', "/images/{$imageId}");
    }

    /**
     * Generate image using AI.
     * 
     * @param string $prompt Image generation prompt
     * @param string $style Image style (realistic, artistic, cartoon, etc.)
     * @param string $size Image size (1024x1024, 512x512, etc.)
     * @param string|null $publicationId Optional publication ID to associate with
     * @return array Generation response
     */
    public function generateImage(string $prompt, string $style = 'realistic', string $size = '1024x1024', ?string $publicationId = null): array
    {
        $requestData = [
            'prompt' => $prompt,
            'style' => $style,
            'size' => $size
        ];
        
        if ($publicationId) {
            $requestData['publicationId'] = $publicationId;
        }
        
        return $this->makeRequest('POST', '/images/generate', ['json' => $requestData]);
    }

    /**
     * Get images for specific publication.
     * 
     * @param string $publicationId Publication ID
     * @param int $page Page number (default: 1)
     * @param int $limit Items per page (default: 10)
     * @return array Publication images
     */
    public function getPublicationImages(string $publicationId, int $page = 1, int $limit = 10): array
    {
        return $this->makeRequest('GET', "/publications/{$publicationId}/images", [
            'query' => ['page' => $page, 'limit' => $limit]
        ]);
    }

    /**
     * Update image metadata.
     * 
     * @param string $imageId Image ID
     * @param array $data Image data to update
     * @return array Updated image
     */
    public function updateImage(string $imageId, array $data): array
    {
        return $this->makeRequest('PUT', "/images/{$imageId}", ['json' => $data]);
    }

    /**
     * Check publication generation status by publication ID.
     * 
     * @param string $publicationId Publication ID
     * @return array Generation status
     */
    public function checkPublicationGenerationStatus(string $publicationId): array
    {
        return $this->makeRequest('GET', "/publications/publication-status/{$publicationId}");
    }


    /**
     * Get subscription status.
     * 
     * @return array Subscription status
     */
    public function getSubscriptionStatus(): array
    {
        return $this->makeRequest('GET', '/subscription/status');
    }

    /**
     * Get subscription limits.
     * 
     * @return array Subscription limits
     */
    public function getSubscriptionLimits(): array
    {
        return $this->makeRequest('GET', '/subscription/limits');
    }

    /**
     * Get available plans.
     * 
     * @return array Available plans
     */
    public function getSubscriptionPlans(): array
    {
        return $this->makeRequest('GET', '/subscription/plans');
    }

    /**
     * Get overview statistics.
     * 
     * @return array Statistics overview
     */
    public function getStatisticsOverview(): array
    {
        return $this->makeRequest('GET', '/statistics/overview');
    }

    /**
     * Get publication statistics.
     * 
     * @return array Publication statistics
     */
    public function getPublicationStatistics(): array
    {
        return $this->makeRequest('GET', '/statistics/publications');
    }

    /**
     * Get image statistics.
     * 
     * @return array Image statistics
     */
    public function getImageStatistics(): array
    {
        return $this->makeRequest('GET', '/statistics/images');
    }

    /**
     * Health check.
     * 
     * @return array Health status
     */
    public function healthCheck(): array
    {
        return $this->makeRequest('GET', '/health');
    }

} 