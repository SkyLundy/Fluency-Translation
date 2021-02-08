# Fluency for ProcessWire Changelog

## 0.2.4 2021-02-08
### Alpha version change, upgrade recommended for all users
- Updated js that handles CKEditor field translation and population. Fixes an issue
  where some fields may not received translated content and other fields may not
  allow for translating content until the page/fields are saved at least once.
- Learned that a space before end of sentence punctuation is a thing in French
  and troubleshooting the module after first noticing that was a waste of time.
  Not relative to module update, just complaining.

## 0.2.3 2020-12-19
### Alpha version change, *Critical upate*, upgrade recommended for all users
- Critical update to AJAX calls. Previously, calls to the module were made using
  a GET request which ran into URL length problems on large bodies of text,
  notably in CKEditor content. Admin AJAX calls are now made using POST.
- Using Fluency now requires the `fluency-translate` permission which allows for
  more granular control over the use of a paid service. All users that will use
  Fluency must be given this permission after upgrading to this version
- Made all Fluency methods public. Translation, usage, and DeepL supported
  languages can now be called directly from the module. Now matches capabilities
  of using the DeepL class directly. Full documentation in README.md
- Added a 5th parameter to Fluency->translate() method that allows for full API
  usage without restrictions. Takes an array with key/value API parameters. Full
  documentation for Fluency API usage added to README.md
- All AJAX calls made in ProcessWire admin now return consistently structured
  object that contain proper HTTP codes to support future full error handling
- Fluency source is now hosted at Github to adhere to ProcessWire module
  directory standards
- Removed bd() debugging function left in master as oversight.
- Internal code cleanup/refactoriing.
- Reformatted CHANGELOG.md

## 0.2.2
### Alpha version change, upgrade recommended
- Added ability for page-edit roles to use translation
- Updated README.md to include inline CKEditor as not yet supported (is on the roadmap)

## 0.2.1
### Alpha version change, Critical update, Upgrade recommended
- Fixed critical failing issue in Chrome where text containing newlines were
  rejected on reason of security.[Now in compliance with this feature](https://www.chromestatus.com/feature/5735596811091968)
- Added the ability to configure globally ignored strings that will not be
  translated. Adding words/phrases in the module's configuration page will have
  them ignored and always remain in the original langauge.
- Refactored API key validity storage. Module checks and stores whether API key
  is valid
- Updated README.md with known Grammarly plugin conflict

## 0.2.0 2020-11-06
### Alpha version change, Critical update, Upgrade recommended
This fixes an issue that can cause the translator to not function and display an
error. Also significant updates to the UI. All users should update.
- Translator tool can now be used if API key is present & valid but no languages
  are configured
- Translator tool now has click to copy ability
- UI properly shows triggers/messaging when some languges configured vs. all
  languages configured
- UI doesn't initialize fields if no languages are configured. No messages, no
  triggers
- Reduced number of assets loaded in Admin with combined CSS files
- Updated README to reflect availability of translator tool under instructions
- Module no longer initializes if on login screen
- Added license

## 0.1.1 2020-11-04
### Minor version, Upgrade recommended
- Various bugfixes and improvements
- Contains better user-facing admin UI when module is not yet configured
- Fixed issued where textarea fields did not get a translation trigger on the
  'Translate File' page
- Code refactored to have assets delivered to page depending on the page/context

## 0.1.0
### 2020-11-01 Initial release
- Alpha release
