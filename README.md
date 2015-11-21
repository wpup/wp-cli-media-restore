# wp-cli-media-restore

[![Build Status](https://travis-ci.org/frozzare/wp-cli-media-restore.svg)](https://travis-ci.org/frozzare/wp-cli-media-restore)

Restore media attachments using WP CLI. Works with custom content directories.

## Installation

Require this file in your global config file or add it to your project.

Example of `~/.wp-cli/config.yml`:
```yaml
require:
	- /path/to/wp-cli-media-restore/src/class-media-restore-command.php
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

#### `[--url]`
The URL to the uploads directory, not including any date based folder structure.

## Examples

```
wp media restore --uploads_url=http://www.bedrock.com/app/uploads/
```
