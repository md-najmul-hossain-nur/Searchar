<?php
declare(strict_types=1);

function loadFacebookPageConfig(): array
{
    $config = [
        'page_id' => '',
        'access_token' => '',
        'api_version' => 'v18.0',
    ];

    $envPath = __DIR__ . '/../Fb Post/.env';
    if (is_file($envPath) && is_readable($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $value = trim($value, "\"'");

                if ($key === 'PAGE_ID') {
                    $config['page_id'] = $value;
                } elseif ($key === 'PAGE_ACCESS_TOKEN') {
                    $config['access_token'] = $value;
                } elseif ($key === 'FACEBOOK_API_VERSION' && $value !== '') {
                    $config['api_version'] = $value;
                }
            }
        }
    }

    return $config;
}

function resolveFacebookMediaUpload(array $postRow): array
{
    $mediaType = (string)($postRow['media_type'] ?? '');
    $mediaPath = (string)($postRow['media_path'] ?? '');
    $mediaJson = (string)($postRow['media_json'] ?? '');

    if ($mediaType === 'video' && $mediaPath !== '') {
        $absolute = realpath(__DIR__ . '/../' . ltrim($mediaPath, '/\\'));
        return $absolute && is_file($absolute) ? ['type' => 'video', 'path' => $absolute] : [];
    }

    if ($mediaType === 'image') {
        $candidatePath = $mediaPath;
        if ($mediaJson !== '') {
            $decoded = json_decode($mediaJson, true);
            if (is_array($decoded) && !empty($decoded)) {
                $candidatePath = (string)$decoded[0];
            }
        }

        if ($candidatePath !== '') {
            $absolute = realpath(__DIR__ . '/../' . ltrim($candidatePath, '/\\'));
            return $absolute && is_file($absolute) ? ['type' => 'image', 'path' => $absolute] : [];
        }
    }

    return [];
}

function publishPostToFacebook(array $postRow, array $facebookConfig): array
{
    if (empty($facebookConfig['page_id']) || empty($facebookConfig['access_token'])) {
        return [
            'attempted' => false,
            'shared' => false,
            'skipped' => true,
            'message' => 'Facebook page configuration is missing.',
        ];
    }

    $message = trim((string)($postRow['text'] ?? ''));
    $footer = "\n\nPublished by SearcharPageAPI";
    $mediaUpload = resolveFacebookMediaUpload($postRow);

    $endpoint = 'feed';
    $payload = [
        'access_token' => $facebookConfig['access_token'],
    ];

    if ($mediaUpload === []) {
        if ($message === '') {
            $message = 'New post from Searchar';
        }
        $payload['message'] = $message . $footer;
    } elseif ($mediaUpload['type'] === 'image') {
        $endpoint = 'photos';
        $payload['caption'] = $message . $footer;
        $payload['source'] = function_exists('curl_file_create')
            ? curl_file_create($mediaUpload['path'])
            : new CURLFile($mediaUpload['path']);
    } elseif ($mediaUpload['type'] === 'video') {
        $endpoint = 'videos';
        $payload['description'] = $message . $footer;
        $payload['source'] = function_exists('curl_file_create')
            ? curl_file_create($mediaUpload['path'])
            : new CURLFile($mediaUpload['path']);
    }

    $url = sprintf(
        'https://graph.facebook.com/%s/%s/%s',
        rawurlencode((string)$facebookConfig['api_version']),
        rawurlencode((string)$facebookConfig['page_id']),
        $endpoint
    );

    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL extension is required for Facebook sharing.');
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Unable to initialize Facebook request.');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $rawResponse = curl_exec($curl);
    if ($rawResponse === false) {
        $curlError = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException('Facebook request failed: ' . $curlError);
    }

    $statusCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decoded = json_decode((string)$rawResponse, true);
    if ($statusCode < 200 || $statusCode >= 300) {
        $errorMessage = is_array($decoded) && isset($decoded['error'])
            ? (string)($decoded['error']['message'] ?? 'Unknown Facebook error')
            : (string)$rawResponse;
        throw new RuntimeException('Facebook publish failed: ' . $errorMessage);
    }

    return [
        'attempted' => true,
        'shared' => true,
        'post_id' => $decoded['id'] ?? null,
        'endpoint' => $endpoint,
        'response' => is_array($decoded) ? $decoded : null,
    ];
}