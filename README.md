# Site Downloader

**Warning: this is ancient crap.** This is a PHP class for downloading a web page along with its assets (stylesheets, scripts, images) that was written around 2010, for a project that no longer exists, with hardcoded URLs and `die('error')` error handling. It is abandonware. Do not use it for anything serious. Kept here for nostalgic / archaeological purposes only.

## PHP versions

- **Minimum: PHP 5.2.0** - uses named regex subpatterns `(?<name>...)` (added in PHP 5.2.0), `file_put_contents`, `str_ireplace` and `__construct`.
- **Maximum: PHP 5.6.x** - written for the PHP 5 branch (`var` properties, zero type safety). It might limp along on PHP 7.x with warnings; it is untested there and definitely not supported on PHP 8+.

## Notes

- Requires the cURL extension (falls back to `file_get_contents` / raw sockets, because it was that kind of era).
- At runtime the code creates a `cookie` file (cURL cookie jar) next to the script. It was once accidentally committed to this repo along with its contents; the file has been scrubbed from the entire git history and is now gitignored.
