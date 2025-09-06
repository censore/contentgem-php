# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.3.0] - 2024-12-19

### Added

- ✅ **AI Image Generation** - `generateImage()` method for creating images from text prompts
- ✅ **Publication Image Management** - `getPublicationImages()` method to get images for specific publications
- ✅ **Image Metadata Updates** - `updateImage()` method to update image metadata
- ✅ **Publication Status Check** - `checkPublicationGenerationStatus()` method to check status by publication ID
- ✅ **Enhanced Documentation** - Updated README with new methods and examples

### Fixed

- ✅ **Bulk Generation API** - Fixed request structure to use proper `settings` format
- ✅ **Bulk Status Check** - Fixed endpoint to use GET method instead of POST
- ✅ **API Compatibility** - All methods now match current API specification
- ✅ **Documentation Updates** - Updated README to reflect all available methods

### Changed

- ✅ **bulkGeneratePublications()** - Updated to use proper `settings` structure with `company_info` and `keywords`
- ✅ **checkBulkGenerationStatus()** - Changed from POST to GET method with URL parameter
- ✅ **README Examples** - Updated all examples to use correct API structure

## [2.2.0] - 2024-12-19

### Fixed

- ✅ **Bulk Generation API** - Fixed request structure to use direct `company_info` and `common_settings` parameters
- ✅ **API Compatibility** - Removed non-existent `checkPublicationGenerationStatus()` method
- ✅ **Documentation Updates** - Updated README to reflect current API capabilities

### Added

- ✅ **Publication Filtering** - Added `type` and `status` parameters to `getPublications()` method
- ✅ **Image Filtering** - Added `publicationId` and `search` parameters to `getImages()` method
- ✅ **Enhanced Documentation** - Added examples for filtering publications and images

### Changed

- ✅ **bulkGeneratePublications()** - Updated request structure to match current API specification
- ✅ **getPublications()** - Now supports filtering by type (blog, review) and status (draft, published, archived)
- ✅ **getImages()** - Now supports filtering by publication ID and search terms

## [2.1.0] - 2024-12-19

### Fixed

- ✅ **Bulk Generation API** - Fixed request structure to use `settings.company_info` and `settings.common_settings`
- ✅ **Company Parsing API** - Updated to use `urls` array instead of `website_url` string
- ✅ **API Compatibility** - Removed `generateImage()` method as it's not available in current API
- ✅ **Documentation Updates** - Updated README to reflect current API capabilities

### Changed

- ✅ **parseCompanyWebsite()** - Now accepts both string and array parameters for URLs
- ✅ **bulkGeneratePublications()** - Updated request structure to match API specification

## [2.0.0] - 2024-12-19

### Added

- ✅ **AI Image Generation** - `generateImage()` method for creating images from text prompts
- ✅ **Enhanced Publication Management** - `checkPublicationGenerationStatus()` for checking status by publication ID
- ✅ **Complete API Coverage** - all current ContentGem API endpoints supported
- ✅ **Advanced Company Information** - full company data management and website parsing
- ✅ **Bulk Operations** - support for bulk content generation
- ✅ **Statistics & Analytics** - comprehensive usage statistics
- ✅ **Subscription Management** - complete subscription and plan management
- ✅ **Enhanced Documentation** - updated examples and API reference

### Changed

- ✅ **Updated Base URL** - now uses `https://gemcontent.com/api/v1`
- ✅ **Improved Error Handling** - better error messages and response handling
- ✅ **Enhanced Type Safety** - improved type hints and parameter validation
- ✅ **Modern PHP Support** - optimized for PHP 7.4+ with latest features

### Fixed

- ✅ **API Compatibility** - all methods now match current API specification
- ✅ **Request Structure** - corrected request formats and parameters
- ✅ **Response Handling** - proper handling of API response structures
- ✅ **Documentation Accuracy** - all examples tested and verified

### Security

- ✅ **API Key Security** - secure handling of API keys in headers
- ✅ **Request Validation** - proper input validation and sanitization

## [1.0.0] - 2024-01-01

### Added

- Initial release of ContentGem PHP SDK
- Basic API client functionality
- Guzzle HTTP client integration
- PSR-4 autoloading
- Basic error handling
