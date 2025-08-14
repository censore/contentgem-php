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
     * @param string $baseUrl API base URL (default: https://your-domain.com/api/v1)
     * @param int $timeout Request timeout in seconds (default: 30)
     */
    public function __construct(string $apiKey, string $baseUrl = 'https://your-domain.com/api/v1', int $timeout = 30)
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
     * @return array Publications response
     */
    public function getPublications(int $page = 1, int $limit = 10): array
    {
        $params = ['page' => $page, 'limit' => $limit];
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
     * @return array Images response
     */
    public function getImages(int $page = 1, int $limit = 10): array
    {
        $params = ['page' => $page, 'limit' => $limit];
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
     * Generate AI image.
     * 
     * @param string $prompt Image generation prompt
     * @param string $style Image style
     * @param string $size Image size
     * @return array Generation response
     */
    public function generateImage(string $prompt, string $style = 'realistic', string $size = '1024x1024'): array
    {
        $data = ['prompt' => $prompt, 'style' => $style, 'size' => $size];
        return $this->makeRequest('POST', '/images/generate', ['json' => $data]);
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
     * Bulk generate multiple publications.
     * 
     * @param array $prompts Array of prompts
     * @param array $companyInfo Company information
     * @param array $commonSettings Common settings for all publications
     * @return array Bulk generation response
     */
    public function bulkGeneratePublications(array $prompts, array $companyInfo = [], array $commonSettings = []): array
    {
        $data = [
            'prompts' => $prompts,
            'company_info' => array_merge([
                'name' => 'My Company',
                'description' => 'Technology company',
                'industry' => 'Technology',
                'target_audience' => 'Developers'
            ], $companyInfo),
            'common_settings' => array_merge([
                'length' => 'medium',
                'style' => 'educational',
                'include_examples' => true
            ], $commonSettings)
        ];
        
        return $this->makeRequest('POST', '/publications/bulk-generate', ['json' => $data]);
    }

    /**
     * Check bulk generation status.
     * 
     * @param string $bulkSessionId Bulk session ID
     * @return array Bulk generation status
     */
    public function checkBulkGenerationStatus(string $bulkSessionId): array
    {
        $data = ['bulk_session_id' => $bulkSessionId];
        return $this->makeRequest('POST', '/publications/bulk-status', ['json' => $data]);
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
     * @return array Update response
     */
    public function updateCompanyInfo(array $companyData): array
    {
        return $this->makeRequest('PUT', '/company', ['json' => $companyData]);
    }

    /**
     * Parse company website.
     * 
     * @param string $websiteUrl Website URL to parse
     * @return array Parsing response
     */
    public function parseCompanyWebsite(string $websiteUrl): array
    {
        $data = ['website_url' => $websiteUrl];
        return $this->makeRequest('POST', '/company/parse', ['json' => $data]);
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
     * Wait for bulk generation to complete.
     * 
     * @param string $bulkSessionId Bulk session ID
     * @param int $maxAttempts Maximum number of attempts
     * @param int $delaySeconds Delay between attempts in seconds
     * @return array Final bulk generation status
     * @throws \Exception If generation times out or fails
     */
    public function waitForBulkGeneration(string $bulkSessionId, int $maxAttempts = 120, int $delaySeconds = 10): array
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $status = $this->checkBulkGenerationStatus($bulkSessionId);
            
            if ($status['success'] && isset($status['data']['status']) && $status['data']['status'] === 'completed') {
                return $status;
            }
            
            if ($status['success'] && isset($status['data']['status']) && $status['data']['status'] === 'failed') {
                throw new \Exception('Bulk generation failed');
            }
            
            if ($attempt < $maxAttempts - 1) {
                sleep($delaySeconds);
            }
        }
        
        throw new \Exception('Bulk generation timeout');
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