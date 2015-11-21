# wp-cli-media-restore

[![Build Status](https://travis-ci.org/frozzare/wp-cli-media-restore.svg)](https://travis-ci.org/frozzare/wp-cli-media-restore)

Restore media attachments using WP CLI. Based on [download missing attachments](https://github.com/cftp/wp-cli-download-missing-attachments) with support for custom content directory and configuration in WP CLI config file.

## Installation

```
composer require frozzare/wp-cli-media-restore
```

For other methods, please refer to WP-CLI's [Community Packages](https://github.com/wp-cli/wp-cli/wiki/Community-Packages) wiki.

## Config

Example of `~/.wp-cli/config.yml`:

```yaml
media:
	restore:
		generate: false
		uploads_url: http://www.bedrock.com/app/uploads/
```

## Options

#### `[--generate=false]`
Set this optional parameter if you want to (re)generate all the different image sizes. Defaults to not generating thumbnails.

#### `--url`
The URL to the uploads directory, not including any date based folder structure.

## Examples

```
wp media restore --uploads_url=http://www.bedrock.com/app/uploads/
```

# License

MIT © [Fredrik Forsmo](https://github.com/frozzare)
