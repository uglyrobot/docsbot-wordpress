<?php

$root = '/wordpress/wp-content/plugins/docsbot-ai';
$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);
$failed = false;

foreach ( $files as $file ) {
	if ( 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	try {
		token_get_all( file_get_contents( $file->getPathname() ), TOKEN_PARSE );
		echo "OK {$file->getPathname()}\n";
	} catch ( ParseError $error ) {
		$failed = true;
		fwrite( STDERR, "ERROR {$file->getPathname()}: {$error->getMessage()}\n" );
	}
}

exit( $failed ? 1 : 0 );
