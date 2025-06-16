# Laravel Stock Image

**Laravel Stock Image** is a pluggable image search API tool that allows you to query stock images from various providers like **Unsplash**, **Pexels**, **Pixabay**, and also generate placeholder images dynamically.

---

## 🚀 Features

- Supports multiple providers: Unsplash, Pexels, Pixabay, Placeholder
- Custom API keys per request or config
- Smart keyword fallback mechanism
- Random image selection and provider rotation
- Easy Laravel integration with route and controller
- Fully testable and extendable architecture

---

## 📦 Installation

```bash
composer require apsonex/laravel-stock-image
```

If you're using Laravel 10+, package auto-discovery will register everything automatically.

---

## ⚙️ Configuration

Publish the configuration file if needed:

```bash
php artisan vendor:publish --tag=laravel-stock-image-config
```

This will create `config/stock-image.php`:

```php
return [
    'route' => [
        'enable'     => true,
        'path'       => 'api/ai/tools/stock-images/search',
        'middleware' => [
            // middlewares
        ],
    ],
];
```

---

## 🔌 Routing

The package automatically registers this route when enabled in the config:

```
POST /stock-image-search
```

### Payload Parameters

| Key                     | Type      | Description |
|------------------------|-----------|-------------|
| `keywords`             | `string`  | Comma-separated keywords to search images (required) |
| `random_result`        | `boolean` | Whether to return a random image from results |
| `random_provider`      | `boolean` | Whether to pick a random provider |
| `page`                 | `int`     | Which page to fetch from provider |
| `cache`                | `boolean` | Whether to cache the result (default: false) |
| `result_limit`         | `int`     | Limit number of images result |
| `provider_api_keys`    | `array`   | Optional - Override API keys per provider (optional) |
| `placeholder_size`     | `string`  | Used only when fallback placeholder is returned e.g. `600x400` |
| `placeholder_text`     | `string`  | Text to show in placeholder image e.g. `Sample Text` |
| `placeholder_text_color`| `string` | Color for placeholder text e.g. `#000000`|
| `placeholder_bg_color` | `string`  | Background color for placeholder e.g. `#cccccc`|

---

### ✅ Example Request (JSON)

`POST` to `/api/ai/tools/stock-images/search`

```json
// result
{
  "keywords": "mountain, beach",
  "random_result": true,
  "random_provider": false,
  "page": 1,
  "cache": false,
  "result_limit": 10,
  "provider_api_keys": {
    "pexels": "<PEXELS_API_KEY>",
    "unsplash": "<UNSPLASH_API_KEY>",
    "pixabay": "<PIXABAY_API_KEY>"
  },
  "placeholder_text": "Placeholder Image",
  "placeholder_size": "600x400",
  "placeholder_bg_color": "cccccc",
  "placeholder_text_color": "333333"
}
```

---

## 🔄 Sample JSON Response

```json
{
  "items": [
    {
      "image_url": "https://example.com/image.jpg",
      "image_description": "A stunning mountain landscape",
      "source": "unsplash"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 30,
    "count": 1
  },
  "status": "success"
}
```

---

## 🧪 Testing

You can run tests using Pest:

```bash
./vendor/bin/pest
```

Make sure to mock external APIs to avoid hitting rate limits.

---

## 🧠 License

MIT © [Apsonex](https://apsonex.com)
