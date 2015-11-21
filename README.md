# wp-cli-download-attachments

[![Build Status](https://travis-ci.org/frozzare/wp-cli-download-attachments.svg)](https://travis-ci.org/frozzare/wp-cli-download-attachments)

> WIP

Download attachments using WP CLI.

## Installation

Require this file in your global config file or add it to your project.

Example of `~/.wp-cli/config.yml`:
```yaml
require:
	- /path/to/wp-cli-download-attachments/src/class-download-attachments-command.php
```

For other methods, please refer to WP-CLI's [Community Packages](https://github.com/wp-cli/wp-cli/wiki/Community-Packages) wiki.

## Config

You can add the path to the `phpcs` bin to use in WP CLI's config file and/or the standard that should be used.

Example of `~/.wp-cli/config.yml`:

```yaml
download_attachments:
  url: http://www.bedrock.com/app/uploads/
```

## Options

#### `[--generate_thumbs=false]`
Set this optional parameter if you want to (re)generate all the different image sizes. Defaults to not generating thumbnails.

#### `[--url]`
The URL to the uploads directory, not including any date based folder structure.

## Examples

```
wp download-attachments run --url=http://www.bedrock.com/app/uploads/
```
