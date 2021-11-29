# `Fluency::translatableLanguages()`
This gets all of the languages that can be translated from and to via DeepL

```php
// Method:
$modules->get('fluency')->translatableLanguages();

```

```php
// Output:
stdClass Object
(
  [source] => Array
    (
    [0] => stdClass Object
        (
            [language] => BG
            [name] => Bulgarian
        )

    [1] => stdClass Object
        (
            [language] => ZH
            [name] => Chinese
        )

    [2] => stdClass Object
        (
            [language] => CS
            [name] => Czech
        )

    [3] => stdClass Object
        (
            [language] => DA
            [name] => Danish
        )

    // ...

    [23] => stdClass Object
        (
            [language] => SV
            [name] => Swedish
        )

  )

  [target] => Array
    (
        [0] => stdClass Object
            (
                [language] => BG
                [name] => Bulgarian
                [supports_formality] => false
            )

        [1] => stdClass Object
            (
                [language] => ZH
                [name] => Chinese
                [supports_formality] => false
            )

        [2] => stdClass Object
            (
                [language] => CS
                [name] => Czech
                [supports_formality] => false
            )

        [3] => stdClass Object
            (
                [language] => DA
                [name] => Danish
                [supports_formality] => false
            )

        // ...

        [25] => stdClass Object
            (
                [language] => SV
                [name] => Swedish
                [supports_formality] => false
            )

    )

)

```

```json
// JSON Encoded
{
  "source": [
    {
      "language": "BG",
      "name": "Bulgarian"
    },
    {
      "language": "ZH",
      "name": "Chinese"
    },
    {
      "language": "CS",
      "name": "Czech"
    },
    {
      "language": "DA",
      "name": "Danish"
    },
    // ...
    {
      "language": "SV",
      "name": "Swedish"
    }
  ],
  "target": [
    {
      "language": "BG",
      "name": "Bulgarian",
      "supports_formality": false
    },
    {
      "language": "ZH",
      "name": "Chinese",
      "supports_formality": false
    },
    {
      "language": "CS",
      "name": "Czech",
      "supports_formality": false
    },
    {
      "language": "DA",
      "name": "Danish",
      "supports_formality": false
    },
    // ...
    {
      "language": "SV",
      "name": "Swedish",
      "supports_formality": false
    }
  ]
}
```
