<?php

// Only run through WP CLI.
if ( ! defined( 'WP_CLI' ) ) {
    return;
}

/**
 * Download Attachments command for WP CLI.
 */
class Download_Attachments_Command extends WP_CLI_Command {

    /**
     * The default options for this command.
     *
     * @var array
     */
    private $default_assoc_args = [
        'generate_thumbs' => false,
        'uploads_url'     => ''
    ];

    /**
     * Get config value from download attachments config.
     *
     * @param  string $key
     *
     * @return mixed
     */
    private function get_config_value( $key ) {
        $config = WP_CLI::get_configurator()->to_array();

        if ( count( $config ) === 1 || ! isset( $config[1]['download_attachments'] ) ) {
            return;
        }

        if ( ! isset( $config[1]['download_attachments'][$key] ) ) {
            return;
        }

        return $config[1]['download_attachments'][$key];
    }

    /**
     * Download attachments with WP CLI.
     *
     * ### Config
     *
     * Example of `~/.wp-cli/config.yml`:
     *
     *     download_attachments:
     *       generate_thumbs: false
     *       url: http://www.bedrock.com/app/uploads/
     *
     * ### Options
     *
     * #### `[--generate_thumbs=false]`
     * Set this optional parameter if you want to (re)generate all the different image sizes. Defaults to not generating thumbnails.
     *
     * #### `--url`
     * The URL to the uploads directory, not including any date based folder structure.
     *
     * ### Examples
     *
     *     wp download-attachments run --url=http://www.bedrock.com/app/uploads/
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function run( array $args = [], array $assoc_args = [] ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Merge default arguments.
        $assoc_args = array_merge( $this->default_assoc_args, $assoc_args );

        // Get generate thumbnails value from WP CLI config or cmd argument.
        $generate_thumbs = $this->get_config_value( 'generate_thumbs' ) !== null
            ? $this->get_config_value( 'generate_thumbs' )
            : $assoc_args['generate_thumbs'];

        // Get url base value from WP CLI config or cmd argument.
        $url_base = $this->get_config_value( 'uploads_url' ) !== null
            ? $this->get_config_value( 'uploads_url' )
            : $assoc_args['uploads_url'];

        // Don't continue without a url.
        if ( empty( $url_base ) ) {
            WP_CLI::error( 'Missing url' );
        }

        $attachments     = $this->get_attachments();
        $content_dir     = CONTENT_DIR;
        $url_base        = trailingslashit( $url_base );
        $dir             = wp_upload_dir();
        $base_dir        = trailingslashit( $dir['basedir'] );
        $results         = [
            'attachments' => count( $attachments ),
            'exists'      => 0,
            'downloaded'  => 0,
            'failed'      => 0,
            'generated'   => 0
        ];

        // Output information about what the CLI command is doing.
        WP_CLI::line( sprintf( 'Downloading %d attachments.', $results['attachments'] ) );

        // Create a progress bar.
        $progress = new \cli\progress\Bar( 'Progress',  $results[ 'attachments' ] );

        try {
            foreach ( $attachments as $id ) {
                $progress->tick();

                $attachment    = get_post( $id );
                $attached_file = get_post_meta( $id, '_wp_attached_file', true );

                if ( ! empty( $attached_file ) ) {
                    $guid       = $attachment->guid;
                    $scheme     = parse_url( $url_base, PHP_URL_SCHEME );
                    $domain     = $scheme . '://' . parse_url( $url_base, PHP_URL_HOST );
                    $remote_url = $domain . parse_url( $guid, PHP_URL_PATH );
                    $response   = wp_remote_head( $remote_url );

                    if ( is_wp_error( $response ) ) {
                        $warnings[] = sprintf( 'Could not retrieve remote file for attachment ID %d, HTTP error "%s"', $id, $response->get_error_message() );
                    } else if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
                        $warnings[] = sprintf( 'Could not retrieve remote file for attachment ID %d, HTTP response code %d', $id, wp_remote_retrieve_response_code( $response ) );
                        continue;
                    }

                    if ( strpos( $attached_file, $domain . '/uploads' ) !== false ) {
                        $attached_file = ltrim( str_replace( $domain . '/uploads', '', $attached_file ), '/' );
                    } else {
                        $attached_file = ltrim( str_replace( $url_base, '', $attached_file ), '/' );
                    }

                    $local_path = str_replace( '/' . $content_dir . '/uploads/', '', $base_dir ) . $attached_file;
                    update_post_meta( $id, '_wp_attached_file', $attached_file );
                } else {
                    $remote_url = $url_base . $attached_file;
                    $local_path = $base_dir . $attached_file;
                }

                // Check if the file already exists
                if ( file_exists( $local_path ) ) {
                    $results['exists']++;
                    continue;
                }

                // Create directory if it don't exists.
                wp_mkdir_p( dirname( $local_path ) );

                // Download attachment.
                $response = wp_safe_remote_get( $remote_url, [
                    'timeout' => 300,
                    'stream' => true,
                    'filename' => $local_path
                ] );

                // If'ts a error, add a warning and a failed file.
                if ( is_wp_error( $response ) ) {
                    if ( file_exists( $local_path ) ) {
                        unlink( $local_path );
                    }

                    $warnings[] = sprintf( 'Could not download %s, got error: %s', $remote_url, $response->get_error_message() );
                    $results['failed']++;
                    continue;
                } else if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
                    $warnings[] = sprintf( 'Could not retrieve remote file for attachment ID %d, HTTP response code %d', $id, wp_remote_retrieve_response_code( $response ) );
                    continue;
                }

                // Generate thumbs if enabled and the attachment is a image.
                if ( $generate_thumbs && $attachment && 'attachment' === $attachment->post_type && 'image/' === substr( $attachment->post_type, 0, 6 ) ) {
                    @set_time_limit( 900 );
                    update_post_meta( $id, '_wp_attachment_metadata', wp_generate_attachment_metadata( $id, $local_path ) );

                    if ( is_wp_error( $metadata ) ) {
                        $warnings[] = sprintf( 'Error generating image thumbnails for attachment ID %d: %s', $id, $metadata->get_error_message() );
                    } else if ( empty( $metadata ) ) {
                        $warnings[] = sprintf( 'Unknown error generating image thumbnails for attachment ID %d', $id );
                    } else {
                        $results['generated']++;
                    }
                }

                $results['downloaded']++;
            }
        } catch ( Exception $e ) {
            WP_CLI::error( $e->getMessage() );
        }

        $progress->finish();

        foreach ( $warnings as $warning ) {
            WP_CLI::warning( $warning );
        }

        $lines = [];
        foreach ( $results as $name => $count ) {
            $lines[] = (object) ['Item' => $name, 'Count' => $count];
        }

        \WP_CLI\Utils\format_items( 'table', $lines, ['Item', 'Count'] );
    }

    /**
     * Get attachments.
     *
     * @return array
     */
    protected function get_attachments() {
        return ( new \WP_Query( [
            'post_type'   => 'attachment',
            'post_status' => 'any',
            'nopaging'    => true,
            'fields'      => 'ids',
            'order'       => 'DESC',
            'orderby'     => 'date'
        ] ) )->get_posts();
    }

}

\WP_CLI::add_command( 'download-attachments', 'Download_Attachments_Command' );
