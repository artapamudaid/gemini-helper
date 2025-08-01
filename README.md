# Gemini Helper for PHP

A simple and easy-to-use PHP helper class to interact with the Google Gemini API. This package supports text generation, PDF summarization, and image captioning using the `gemini-1.5-flash` model.

### Installation

```bash
composer require artapamudaid/gemini-helper
```

### Usage

```php
<?php
require 'vendor/autoload.php';

use YourUsername\GeminiHelper\GeminiHelper;

$apiKey = 'YOUR_API_KEY';

try {
    $gemini = new GeminiHelper($apiKey);

    // Generate Text
    $result = $gemini->generateText('Tuliskan 3 tips untuk belajar PHP.');
    echo $result;

    // Summarize a PDF
    $summary = $gemini->summarizePdf('path/to/document.pdf');
    echo $summary;

    // Caption an Image
    $caption = $gemini->captionImage('path/to/image.jpg');
    echo $caption;

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```