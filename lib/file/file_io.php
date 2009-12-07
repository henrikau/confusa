<?php
require_once 'config.php';
require_once 'file.php';

/**
 * File_IO: Convenience class for reading and writing files on the local
 * filesystem of the server.
 */
class File_IO
{
	/**
	 * Write the supplied content to the file in $path.
	 *
	 * @param $path The path to which should be written
	 * @param $content The content which should be written
	 * @param $overwrite Determine if the existing file should be overwritten
	 * @param $create_directory Determine if the parent directory should be created
	 */
	static function writeToFile($path, $content, $overwrite = TRUE, $create_directory = FALSE)
	{

		if (file_exists(dirname($path)) === FALSE) {
			if ($create_directory) {
				if (!mkdir(dirname($path), 0755, TRUE)) {
					throw new FileException("Could not create the directory " .
											dirname($path) .
											"when trying to write to $path");
				}
			} else {
				throw new FileException("Can not write to file $path, because the " .
										"parent directory does not exist!");
			}
		}


		if ($overwrite) {
			$result = file_put_contents($path, $content);
		} else {
			$result = file_put_contents($path, $content, FILE_APPEND);
		}

		if ($result === FALSE) {
			throw new FileException("Could not write to file $path!");
		}
	}

	/**
	 * Read from the file in the supplied path and return the read content
	 *
	 * @param $path The path from which should be read
	 * @return The read content
	 */
	static function readFromFile($path)
	{
		if (!file_exists($path)) {
			throw new FileException("File in $path not found");
		}

		$content=file_get_contents($path);

		if ($content === FALSE) {
			throw new FileException("Could not read file in $path");
		}

		return $content;
	}
}

?>
