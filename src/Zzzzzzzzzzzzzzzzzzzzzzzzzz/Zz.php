<?php

namespace Zzzzzzzzzzzzzzzzzzzzzzzzzz;

use Composer\Autoload\ClassLoader;

class Zz {

	use Utility;

	private $namespace;
	private $core;
	private $override;
	private $vendor;
	private $composer_json;
	private $origin;
	private $blacklist = [];

	public function __construct () {
		$this->namespace = 'App';
		$this->core = 'Core';
		$this->override = 'App\Override';
		$this->vendor = 'vendor';
		$this->composer_json = 'composer.json';
		$this->origin = '.origin';
		$this->blacklist = [
			'ClassLoader',
		];

		spl_autoload_register( function( $class ) {
			// echo "perso - $class <br>\n";
			$this->loader( $class );
		}, false, true );
	}

	public function override ( $class ) {
		if ( $file = $this->findOverride( $class ) ) {
// echo "<br>--------- over -----------{$class} - {$file} <br>\n";
			if ( $this->copyExistingPackage( $class ) ) {
				includeFile( $file );

				return true;
			}
		}

		return false;
	}

	public function core ( $class ) {
		$override = $this->core2override( $class );

		list( $source, $destination ) = $this->infos( $override );
		$core = $this->buildCore( $destination );

		if ( $file = $this->findOverride( $override ) ) {
// echo "--------- core -----------{$class}{$file} <br>\n";

			includeFile( $destination->file );
			
			return true;
		}
	}

	public function loader ( $class ) {
		if ( $this->is_it_blacklisted( $class ) ) {
			return false;
		}

		if ( $this->is_it_already_in_app_folder( $class ) ) {
			return false;
		}

		if ( $this->is_it_core_folder( $class ) ) {
			return $this->core( $class );
		}

		return $this->override( $class );
	}

	public function findFile ( $class ) {
		$file = str_replace( [ 'App\\', '\\' ], [ '', '/' ], $class );
		$file = app_path( $file ).'.php';

		return file_exists( $file ) ? $file : false;
	}

}

function includeFile( $file ) {
	include $file;
}