<?php
namespace Simply_Static;

use Cloudflare\Zone;

class Wrapup_Task extends Task {

	/**
	 * @var string
	 */
	protected static $task_name = 'wrapup';

	public function perform() {
		if ( $this->options->get( 'delete_temp_files' ) === '1' ) {
			Util::debug_log( "Deleting temporary files" );
			$this->save_status_message( __( 'Wrapping up', 'simply-static' ) );
			$deleted_successfully = $this->delete_temp_static_files();
			$this->clearCloudflare();
		} else {
			Util::debug_log( "Keeping temporary files" );
		}


        update_option('simply-static-publish-state','ok');

		return true;
	}

	public function clearCloudflare() {
        $cloudflareEmail = $this->options->get('cloudflare_email');
        $cloudflareDomain = $this->options->get('cloudflare_domain');
        $cloudflareKey = $this->options->get('cloudflare_key');

        error_log("[Publish] Cloudflare: $cloudflareDomain");

        if (!empty($cloudflareEmail) && !empty($cloudflareDomain) && !empty($cloudflareKey)) {
            $zone = new Zone($cloudflareEmail, $cloudflareKey);
            $results = $zone->zones();

            $id = null;
            if ($results->result && is_array($results->result)) {
                foreach($results->result as $zone) {
                    error_log("[Publish] Found ".count($results->result)." zones.");
                    if ($zone->name == $cloudflareDomain) {
                        $id = $zone->id;
                        error_log("[Publish] Found zone $id");
                        break;
                    }
                }
            } else {
                error_log('[Publish] NO RESULTS '.json_encode($results));
            }

            if (!$id) {
                error_log('[Publish] Cloudflare Error: Invalid domain.'.json_encode($results));
            } else {
                error_log("[Publish] Purging cache for $cloudflareDomain ($id)");
                $cache = new Zone\Cache($cloudflareEmail, $cloudflareKey);
                $result=$cache->purge($id, true);
                if ($result->success != 1) {
                    $error=$result->errors[0];
                    error_log("[Publish] Cloudflare Error: {$error->message}");
                } else {
                    error_log("[Publish] Cache purged: $cloudflareDomain ($id)");
                }
            }
        }
    }

	/**
	 * Delete temporary, generated static files
	 * @return true|\WP_Error True on success, WP_Error otherwise
	 */
	public function delete_temp_static_files() {
        $workDir = trim($this->options->get_local_dir());
        $currentDir = $this->options->get_current_dir();

        $pathParts = explode(DIRECTORY_SEPARATOR, trim($workDir, DIRECTORY_SEPARATOR));
        $name  = array_pop($pathParts);

        $baseDir = DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $pathParts);

        chdir($baseDir);
        unlink($currentDir);
	    symLink($name, 'current');

        delete_transient('static_export_dir');

	    $archive_dir = $this->options->get_archive_dir();

		if ( file_exists( $archive_dir ) ) {
			$directory_iterator = new \RecursiveDirectoryIterator( $archive_dir, \FilesystemIterator::SKIP_DOTS );
			$recursive_iterator = new \RecursiveIteratorIterator( $directory_iterator, \RecursiveIteratorIterator::CHILD_FIRST );

			// recurse through the entire directory and delete all files / subdirectories
			foreach ( $recursive_iterator as $item ) {
				$success = $item->isDir() ? rmdir( $item ) : unlink( $item );
				if ( ! $success ) {
					$message = sprintf( __( "Could not delete temporary file or directory: %s", 'simply-static' ), $item );
					$this->save_status_message( $message );
					return true;
				}
			}

			// must make sure to delete the original directory at the end
			$success = rmdir( $archive_dir );
			if ( ! $success ) {
				$message = sprintf( __( "Could not delete temporary file or directory: %s", 'simply-static' ), $archive_dir );
				$this->save_status_message( $message );
				return true;
			}
		}

		return true;
	}
}
