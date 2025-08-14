<?php

namespace ContentGem\Tests;

use ContentGem\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private Client $client;
    private array $container = [];
    private MockHandler $mock;

    protected function setUp(): void
    {
        $this->mock = new MockHandler();
        $handlerStack = HandlerStack::create($this->mock);
        
        // Add history middleware to capture requests
        $history = Middleware::history($this->container);
        $handlerStack->push($history);

        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        
        $this->client = new Client(
            'cg_test_api_key_123',
            'https://api.test.com/v1',
            30
        );

        // Use reflection to set the mocked Guzzle client
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->client, $guzzleClient);
    }

    public function testConstructorWithDefaultValues()
    {
        $client = new Client('test_key');
        
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorWithCustomValues()
    {
        $client = new Client('test_key', 'https://custom.com/api', 60);
        
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGeneratePublicationSuccess()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'publicationId' => 'pub_123',
                'sessionId' => 'sess_456',
                'status' => 'generating'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->generatePublication(
            'Write about AI in business',
            [
                'name' => 'Test Company',
                'description' => 'Test description'
            ]
        );

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/publications/generate', $request->getUri()->getPath());
        $this->assertEquals('cg_test_api_key_123', $request->getHeaderLine('X-API-Key'));
    }

    public function testGeneratePublicationWithError()
    {
        $mockError = [
            'success' => false,
            'error' => 'INVALID_PROMPT',
            'message' => 'Prompt is too short'
        ];

        $this->mock->append(
            new Response(400, [], json_encode($mockError))
        );

        $result = $this->client->generatePublication('AI');

        $this->assertEquals($mockError, $result);
    }

    public function testCheckGenerationStatus()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'publicationId' => 'pub_123',
                'sessionId' => 'sess_456',
                'status' => 'completed',
                'content' => 'Generated content here...'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->checkGenerationStatus('sess_456');

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/publications/generation-status/sess_456', $request->getUri()->getPath());
    }

    public function testGetPublications()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'publications' => [
                    [
                        'id' => 'pub_1',
                        'title' => 'Test Publication',
                        'content' => 'Test content',
                        'type' => 'blog',
                        'status' => 'published',
                        'contentLength' => 100,
                        'imagesCount' => 0,
                        'createdAt' => '2024-01-01T00:00:00Z',
                        'updatedAt' => '2024-01-01T00:00:00Z'
                    ]
                ],
                'pagination' => [
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'totalItems' => 1,
                    'itemsPerPage' => 10
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->getPublications(1, 10);

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('page=1&limit=10', $request->getUri()->getQuery());
    }

    public function testWaitForGenerationSuccess()
    {
        $generatingResponse = [
            'success' => true,
            'data' => [
                'publicationId' => 'pub_123',
                'sessionId' => 'sess_456',
                'status' => 'generating'
            ]
        ];

        $completedResponse = [
            'success' => true,
            'data' => [
                'publicationId' => 'pub_123',
                'sessionId' => 'sess_456',
                'status' => 'completed',
                'content' => 'Generated content here...'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($generatingResponse)),
            new Response(200, [], json_encode($completedResponse))
        );

        $result = $this->client->waitForGeneration('sess_456', 2, 0.1);

        $this->assertEquals($completedResponse, $result);
        $this->assertCount(2, $this->container);
    }

    public function testWaitForGenerationFailure()
    {
        $failedResponse = [
            'success' => true,
            'data' => [
                'publicationId' => 'pub_123',
                'sessionId' => 'sess_456',
                'status' => 'failed'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($failedResponse))
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Generation failed');

        $this->client->waitForGeneration('sess_456', 1);
    }

    public function testWaitForGenerationTimeout()
    {
        $generatingResponse = [
            'success' => true,
            'data' => [
                'publicationId' => 'pub_123',
                'sessionId' => 'sess_456',
                'status' => 'generating'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($generatingResponse))
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Generation timeout');

        $this->client->waitForGeneration('sess_456', 1);
    }

    public function testUploadImage()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'image' => [
                    'id' => 'img_123',
                    'filename' => 'test.jpg',
                    'originalName' => 'test.jpg',
                    'mimeType' => 'image/jpeg',
                    'size' => 1024,
                    'url' => 'https://example.com/test.jpg',
                    'createdAt' => '2024-01-01T00:00:00Z'
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test image content');

        $result = $this->client->uploadImage($tempFile);

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/images/upload', $request->getUri()->getPath());

        // Clean up
        unlink($tempFile);
    }

    public function testGetSubscriptionStatus()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'subscription' => [
                    'planName' => 'Pro',
                    'planSlug' => 'pro',
                    'price' => 29.99,
                    'currency' => 'USD',
                    'interval' => 'month',
                    'postsPerMonth' => 100,
                    'postsUsed' => 25,
                    'postsRemaining' => 75,
                    'status' => 'active',
                    'currentPeriodStart' => '2024-01-01T00:00:00Z',
                    'currentPeriodEnd' => '2024-02-01T00:00:00Z',
                    'cancelAtPeriodEnd' => false,
                    'features' => []
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->getSubscriptionStatus();

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/subscription/status', $request->getUri()->getPath());
    }

    public function testNetworkError()
    {
        $this->mock->append(
            new \GuzzleHttp\Exception\ConnectException(
                'Network error',
                new \GuzzleHttp\Psr7\Request('POST', '/test')
            )
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Request failed: Network error');

        $this->client->generatePublication('Write about AI');
    }

    public function testCreatePublication()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'publication' => [
                    'id' => 'pub_123',
                    'title' => 'Test Publication',
                    'content' => 'Test content'
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $data = [
            'title' => 'Test Publication',
            'content' => 'Test content'
        ];

        $result = $this->client->createPublication($data);

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/publications', $request->getUri()->getPath());
    }

    public function testUpdatePublication()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'publication' => [
                    'id' => 'pub_123',
                    'title' => 'Updated Publication',
                    'content' => 'Updated content'
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $data = [
            'title' => 'Updated Publication',
            'content' => 'Updated content'
        ];

        $result = $this->client->updatePublication('pub_123', $data);

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/publications/pub_123', $request->getUri()->getPath());
    }

    public function testDeletePublication()
    {
        $mockResponse = [
            'success' => true,
            'message' => 'Publication deleted successfully'
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->deletePublication('pub_123');

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('/publications/pub_123', $request->getUri()->getPath());
    }

    public function testPublishPublication()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'publication' => [
                    'id' => 'pub_123',
                    'status' => 'published'
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->publishPublication('pub_123');

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/publications/pub_123/publish', $request->getUri()->getPath());
    }

    public function testGetImages()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'images' => [
                    [
                        'id' => 'img_123',
                        'filename' => 'test.jpg',
                        'originalName' => 'test.jpg',
                        'mimeType' => 'image/jpeg',
                        'size' => 1024,
                        'url' => 'https://example.com/test.jpg',
                        'createdAt' => '2024-01-01T00:00:00Z'
                    ]
                ],
                'pagination' => [
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'totalItems' => 1,
                    'itemsPerPage' => 10
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->getImages(1, 10);

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('page=1&limit=10', $request->getUri()->getQuery());
    }

    public function testGenerateImage()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'image' => [
                    'id' => 'img_123',
                    'filename' => 'generated.jpg',
                    'url' => 'https://example.com/generated.jpg'
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->generateImage('A beautiful landscape', 'realistic', '1024x1024');

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/images/generate', $request->getUri()->getPath());
    }

    public function testHealthCheck()
    {
        $mockResponse = [
            'success' => true,
            'message' => 'Service is healthy',
            'timestamp' => '2024-01-01T00:00:00Z',
            'version' => '1.0.0'
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->healthCheck();

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/health', $request->getUri()->getPath());
    }

    public function testBulkGeneratePublicationsSuccess()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'bulk_session_id' => 'bulk_sess_123',
                'total_prompts' => 3,
                'status' => 'processing',
                'publications' => [
                    ['id' => 'pub_1', 'prompt' => 'Write about AI', 'status' => 'pending'],
                    ['id' => 'pub_2', 'prompt' => 'Explain ML', 'status' => 'pending'],
                    ['id' => 'pub_3', 'prompt' => 'Discuss automation', 'status' => 'pending']
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $prompts = [
            'Write about AI in business',
            'Explain machine learning basics',
            'Discuss the future of automation'
        ];

        $companyInfo = [
            'name' => 'Test Company',
            'description' => 'Test description'
        ];

        $commonSettings = [
            'length' => 'medium',
            'style' => 'educational'
        ];

        $result = $this->client->bulkGeneratePublications($prompts, $companyInfo, $commonSettings);

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/publications/bulk-generate', $request->getUri()->getPath());
    }

    public function testCheckBulkGenerationStatus()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'bulk_session_id' => 'bulk_sess_123',
                'total_prompts' => 3,
                'completed_prompts' => 2,
                'failed_prompts' => 0,
                'status' => 'processing',
                'publications' => [
                    ['id' => 'pub_1', 'title' => 'AI Article', 'prompt' => 'Write about AI', 'status' => 'completed', 'content' => 'Generated content...'],
                    ['id' => 'pub_2', 'title' => 'ML Article', 'prompt' => 'Explain ML', 'status' => 'completed', 'content' => 'Generated content...'],
                    ['id' => 'pub_3', 'title' => 'Automation Article', 'prompt' => 'Discuss automation', 'status' => 'generating']
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->checkBulkGenerationStatus('bulk_sess_123');

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/publications/bulk-status', $request->getUri()->getPath());
    }

    public function testWaitForBulkGenerationSuccess()
    {
        $processingResponse = [
            'success' => true,
            'data' => [
                'bulk_session_id' => 'bulk_sess_123',
                'status' => 'processing'
            ]
        ];

        $completedResponse = [
            'success' => true,
            'data' => [
                'bulk_session_id' => 'bulk_sess_123',
                'status' => 'completed'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($processingResponse)),
            new Response(200, [], json_encode($completedResponse))
        );

        $result = $this->client->waitForBulkGeneration('bulk_sess_123', 2, 0.1);

        $this->assertEquals($completedResponse, $result);
        $this->assertCount(2, $this->container);
    }

    public function testWaitForBulkGenerationFailure()
    {
        $failedResponse = [
            'success' => true,
            'data' => [
                'bulk_session_id' => 'bulk_sess_123',
                'status' => 'failed'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($failedResponse))
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bulk generation failed');

        $this->client->waitForBulkGeneration('bulk_sess_123', 1, 0.1);
    }

    public function testGetCompanyInfo()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'company' => [
                    'name' => 'Test Company',
                    'description' => 'Test company description',
                    'industry' => 'Technology',
                    'website' => 'https://testcompany.com',
                    'contact_email' => 'contact@testcompany.com',
                    'target_audience' => 'Developers'
                ],
                'last_updated' => '2024-01-01T00:00:00Z'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->getCompanyInfo();

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/company', $request->getUri()->getPath());
    }

    public function testUpdateCompanyInfo()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'company' => [
                    'name' => 'Updated Company',
                    'description' => 'Updated description',
                    'industry' => 'Technology',
                    'website' => 'https://updatedcompany.com'
                ],
                'last_updated' => '2024-01-01T00:00:00Z'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $companyData = [
            'name' => 'Updated Company',
            'description' => 'Updated description',
            'website' => 'https://updatedcompany.com'
        ];

        $result = $this->client->updateCompanyInfo($companyData);

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/company', $request->getUri()->getPath());
    }

    public function testParseCompanyWebsite()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'parsing_session_id' => 'parse_sess_123',
                'status' => 'processing',
                'message' => 'Parsing started'
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->parseCompanyWebsite('https://example.com');

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/company/parse', $request->getUri()->getPath());
    }

    public function testGetCompanyParsingStatus()
    {
        $mockResponse = [
            'success' => true,
            'data' => [
                'parsing_session_id' => 'parse_sess_123',
                'status' => 'completed',
                'progress' => 100,
                'extracted_data' => [
                    'name' => 'Example Company',
                    'description' => 'Company description from website',
                    'website' => 'https://example.com'
                ]
            ]
        ];

        $this->mock->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->client->getCompanyParsingStatus();

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(1, $this->container);

        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/company/parsing-status', $request->getUri()->getPath());
    }
} 