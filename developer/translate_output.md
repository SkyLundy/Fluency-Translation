# `Fluency::translate()`
Executes a translation

```php
// Method:
$modules->get('fluency')->translate(
  'EN',
  'Hello, have you met the developer of Fluency? His name is Sky and he is an avid indoorsman, Linux user, and does full stack development in California.',
  'DE',
  [
    'addParams' => ['preserve_formatting' => true],
    'ignoredStrings' => ['Sky']
  ],
);
```

```php
// Output
stdClass Object (
  [data] => stdClass Object (
    [translations] => Array (
      [0] => stdClass Object (
        [detected_source_language] => EN
        [text] => Hallo, haben Sie den Entwickler von Fluency schon kennengelernt? Sein Name ist Sky und er ist ein begeisterter indoorsman, Linux-Nutzer, und macht Full-Stack-Entwicklung in Kalifornien.
      )
    )
  )
  [httpStatus] => 200
)
```

```json
// JSON encoded
{
  "data": {
    "translations": [
      {
        "detected_source_language": "EN",
        "text": "Hallo, haben Sie den Entwickler von Fluency schon kennengelernt? Sein Name ist Sky und er ist ein begeisterter Indoor-Sportler, Linux-Nutzer und macht Full-Stack-Entwicklung in Kalifornien."
      }
    ]
  },
  "httpStatus": 200 // This is the status from the DeepL API passed through for UI error handling
}
```