<?php defined('SYSPATH') or die('No direct script access.');

/**
 *  Dodano metodę   ->body_append($content);
 */


class Response extends Kohana_Response
{
    public function body_append($content = NULL)
    {
        if ($content === NULL)
            return $this->_body;

        $this->_body .= (string) "\n".$content;
        return $this;
    }
        
    public function body_prepend($content = NULL)
    {
        if($content===NULL)
            return $this->_body;

        $this->_body = (string) $content."\n".$this->_body;
        return $this;
    }
        
    /**
     * Alternatywa dla send_file().
     * Różnica polega na tym, że dla istniejących plików rozszerzenie jest brane z
     * nazwy określonej przez $download, a nie (jak w oryginale) przez $filename oraz istnieje możliwość
     * zdefiniowania sposobu wysłania pliku za pomocą opcji 'stream'.
     * $options['stream'] = TRUE  ->  Wyślij jako strumień;
     * $options['stream'] = FALSE ->  Wyślij jako plik;
     * Reszta parametrów analogicznie do send_file();
     */
     public function stream_file($filename, $download = NULL, array $options = NULL)
    {
        if ( ! empty($options['mime_type']))
        {
            // The mime-type has been manually set
            $mime = $options['mime_type'];
        }

        if ($filename === TRUE)
        {
            if (empty($download))
            {
                throw new Kohana_Exception('Download name must be provided for streaming files');
            }

            // Temporary files will automatically be deleted
            $options['delete'] = FALSE;

            if ( ! isset($mime))
            {
                // Guess the mime using the file extension
                $mime = File::mime_by_ext(strtolower(pathinfo($download, PATHINFO_EXTENSION)));
            }

            // Force the data to be rendered if
            $file_data = (string) $this->_body;

            // Get the content size
            $size = strlen($file_data);

            // Create a temporary file to hold the current response
            $file = tmpfile();

            // Write the current response into the file
            fwrite($file, $file_data);

            // File data is no longer needed
            unset($file_data);
        }
        else
        {
            // Get the complete file path
            $filename = realpath($filename);

            if (empty($download))
            {
                // Use the file name as the download file name
                $download = pathinfo($filename, PATHINFO_BASENAME);
            }

            // Get the file size
            $size = filesize($filename);

            if ( ! isset($mime))
            {
                // Guess the mime using the file extension
                $mime = File::mime_by_ext(strtolower(pathinfo($download, PATHINFO_EXTENSION)));
            }
          
            if ( ! $mime) 
            {
                throw new Kohana_Exception('Invalid File Format (unknown MIME type).');
            }
            // Open the file for reading
            $file = fopen($filename, 'rb');
        }

        if ( ! is_resource($file))
        {
            throw new Kohana_Exception('Could not read file to send: :file', array(
                ':file' => $download,
            ));
        }

        // Inline or download?
        $disposition = empty($options['inline']) ? 'attachment' : 'inline';

        // Calculate byte range to download.
        list($start, $end) = $this->_calculate_byte_range($size);

        if ( ! empty($options['resumable']))
        {
            if ($start > 0 OR $end < ($size - 1))
            {
                // Partial Content
                $this->_status = 206;
            }

            // Range of bytes being sent
            $this->_header['content-range'] = 'bytes '.$start.'-'.$end.'/'.$size;
            $this->_header['accept-ranges'] = 'bytes';
        }

        // Set the headers for a download
        if( ! isset($options['stream']) or ! $options['stream'])
        {
            $this->_header['content-disposition'] = $disposition.'; filename="'.$download.'"';
        }
        $this->_header['content-type']        = $mime;
        $this->_header['content-length']      = (string) (($end - $start) + 1);


        if (Request::user_agent('browser') === 'Internet Explorer')
        {
            // Naturally, IE does not act like a real browser...
            if (Request::$initial->secure())
            {
                // http://support.microsoft.com/kb/316431
                $this->_header['pragma'] = $this->_header['cache-control'] = 'public';
            }

            if (version_compare(Request::user_agent('version'), '8.0', '>='))
            {
                // http://ajaxian.com/archives/ie-8-security
                $this->_header['x-content-type-options'] = 'nosniff';
            }
        }
        
        // Send all headers now
        $this->send_headers();

        while (ob_get_level())
        {
            // Flush all output buffers
            ob_end_flush();
        }

        // Manually stop execution
        ignore_user_abort(TRUE);

        if ( ! Kohana::$safe_mode)
        {
            // Keep the script running forever
            set_time_limit(0);
        }

        // Send data in 16kb blocks
        $block = 1024 * 16;

        fseek($file, $start);

        while ( ! feof($file) AND ($pos = ftell($file)) <= $end)
        {
            if (connection_aborted())
                break;

            if ($pos + $block > $end)
            {
                // Don't read past the buffer.
                $block = $end - $pos + 1;
            }

            // Output a block of the file
            echo fread($file, $block);

            // Send the data now
            flush();
        }

        // Close the file
        fclose($file);

        if ( ! empty($options['delete']))
        {
            try
            {
                // Attempt to remove the file
                unlink($filename);
            }
            catch (Exception $e)
            {
                // Create a text version of the exception
                $error = Kohana_Exception::text($e);

                if (is_object(Kohana::$log))
                {
                    // Add this exception to the log
                    Kohana::$log->add(Log::ERROR, $error);

                    // Make sure the logs are written
                    Kohana::$log->write();
                }

                // Do NOT display the exception, it will corrupt the output!
            }
        }

        // Stop execution
        exit;
    }
}