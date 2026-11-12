# Changelog

## 0.0.1 - 2026-11-12

- Initial release, extracted from `flobbos/laravel-cm`.
- MJML workshop with a remote compiler and an optional local `mjml` binary.
- `newsletter_image()` and the model file URL use the slugged public path compile writes.
- Install does not overwrite files that are already published.
- Template and layout names are slugged. Names that contain `..` or a slash are rejected.
- Layout files are renamed only when the filename starts with the layout name.
- Generator commands exit non-zero when they refuse to run.
- The compiler token is read from `NEWSLETTER_TEMPLATES_API_TOKEN`.

