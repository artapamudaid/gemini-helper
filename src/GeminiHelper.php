<?php

namespace Artapamudaid\GeminiHelper;

use Exception;

class GeminiHelper {
    
    private $apiKey;
    private $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=";

    public function __construct($apiKey) {
        if (empty($apiKey)) {
            throw new Exception("Gemini API Key tidak boleh kosong.");
        }
        $this->apiKey = $apiKey;
        $this->apiUrl .= $apiKey;
    }



    public function convertToHtml($text) {
        // Mengonversi **teks** dan __teks__ menjadi <strong>
        $text = preg_replace('/\*\*(.*?)\*\*|__(.*?)__/', '<strong>$1$2</strong>', $text);

        // Mengonversi *teks* dan _teks_ menjadi <em>
        $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)|(?<!\_)\_(?!\_)(.*?)(?<!\_)\_(?!\_)/', '<em>$1$2</em>', $text);

        // Mengonversi ### Judul menjadi <h3>
        $text = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $text);

        // Mengonversi ## Judul menjadi <h2>
        $text = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $text);

        // Mengonversi list item dengan "-" atau "*" menjadi <ul> dan <li>
        $text = preg_replace('/^\s*[-*] (.*)$/m', '<li>$1</li>', $text);
        if (strpos($text, '<li>') !== false) {
            $text = '<ul>' . $text . '</ul>';
            $text = str_replace('</ul>' . "\n" . '<ul>', '', $text); // Gabungkan jika ada dua list
        }
        
        // Mengonversi baris baru menjadi <br>
        $text = nl2br($text);
        
        // Mengonversi teks biasa menjadi <p> dan memastikan tag list tidak berada di dalam tag p
        $text = str_replace('<br />', '', $text);
        $text = '<p>' . $text . '</p>';
        $text = preg_replace('/<p>(.*?)<\/p>/s', '<p>$1</p>', $text);
        $text = str_replace(['<p><ul>', '</ul></p>'], ['<ul>', '</ul>'], $text);
        $text = str_replace(['<p><h3>', '</h3></p>'], ['<h3>', '</h3>'], $text);
        $text = str_replace(['<p><h2>', '</h2></p>'], ['<h2>', '</h2>'], $text);
        
        return $text;
    }

    /**
     * Metode internal untuk mengirim permintaan ke Gemini API.
     * @param array $contents Payload untuk permintaan API.
     * @return array|null Hasil respons dari API.
     */
    private function sendRequest($contents) {
        $ch = curl_init($this->apiUrl);
        
        $payload = json_encode(['contents' => $contents]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return ['error' => $error];
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode !== 200) {
            $error = isset($responseData['error']['message']) ? $responseData['error']['message'] : "Unknown API Error";
            return ['error' => "API returned HTTP $httpCode: $error"];
        }

        return $responseData;
    }

    /**
     * Membuat teks/jawaban dari prompt sederhana.
     * @param string $prompt Teks prompt yang ingin dikirim.
     * @return string|null Jawaban dari Gemini atau pesan error.
     */
    public function generateText($prompt) {
        $contents = [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ];
        
        $response = $this->sendRequest($contents);

        if (isset($response['error'])) {
            return $response['error'];
        }

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? "Tidak dapat menghasilkan teks.";
    }

    /**
     * Meringkas dokumen PDF.
     * @param string $pdfFilePath Path ke file PDF lokal.
     * @param string $prompt Teks prompt untuk ringkasan.
     * @return string|null Ringkasan teks atau pesan error.
     */
    public function summarizePdf($pdfFilePath, $prompt = "Tolong ringkas dokumen PDF ini.") {
        if (!file_exists($pdfFilePath)) {
            return "File PDF tidak ditemukan: $pdfFilePath";
        }
        
        $pdfContent = file_get_contents($pdfFilePath);
        $base64Pdf = base64_encode($pdfContent);

        $contents = [
            [
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inlineData' => [
                            'mimeType' => 'application/pdf',
                            'data' => $base64Pdf,
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->sendRequest($contents);

        if (isset($response['error'])) {
            return $response['error'];
        }

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? "Tidak dapat membuat ringkasan.";
    }

    /**
     * Membuat caption untuk gambar.
     * @param string $imageFilePath Path ke file gambar lokal.
     * @param string $prompt Teks prompt untuk caption.
     * @return string|null Caption gambar atau pesan error.
     */
    public function captionImage($imageFilePath, $prompt = "Tolong buatkan caption singkat dan menarik untuk gambar ini.") {
        if (!file_exists($imageFilePath)) {
            return "File gambar tidak ditemukan: $imageFilePath";
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imageFilePath);
        finfo_close($finfo);
        
        $imageData = file_get_contents($imageFilePath);
        $base64Image = base64_encode($imageData);

        $contents = [
            [
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64Image,
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->sendRequest($contents);

        if (isset($response['error'])) {
            return $response['error'];
        }
        
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? "Tidak dapat membuat caption.";
    }
}