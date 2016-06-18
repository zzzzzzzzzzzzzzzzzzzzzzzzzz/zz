<?php

namespace Zzzzzzzzzzzzzzzzzzzzzzzzzz;

trait Utility {

	public function duplicate ( $source, $destination ) {
		if ( !$source || !$destination ) {
			return false;
		}

		$from = str_replace( base_path().'/', '', $source->dir );
		$to = str_replace( base_path().'/', '', $destination->dir );

		$files = app('filesystem')->disk('root')->allFiles( $from );

		foreach ( $files as $file ) {
			$new = str_replace( $from, $to, $file );

			if ( !app('filesystem')->disk('root')->exists( $new ) ) {
				app('filesystem')->disk('root')->copy( $file, $new );

				$new = base_path( $new );
				$pathinfo = pathinfo( $new );

				if ( file_exists( $new ) && isset( $pathinfo['extension'] ) && $pathinfo['extension'] == 'php' ) {
					file_put_contents( $new,
						str_replace( 'namespace ', "namespace {$this->core}\\",
							file_get_contents( $new )
						)
					);
				}
			}
		}

		return true;
	}

	public function infos ( $class ) {
		$source = $this->getInfos( $class );

		if ( !$source ) {
			return [ false, false ];
		}

		return [ $source, $this->getInfosDestination( $source, $this->override ) ];
	}

	public function copyExistingPackage ( $class ) {
		list( $source, $destination ) = $this->infos( $class );

		return $this->duplicate( $source, $destination );
	}

	public function getInfosDestination ( $source, $destination ) {
		$destination = app_path( str_replace( $this->namespace.'\\', '', $destination ) );

		$return = (object)array_map( function ( $item ) use ( $source, $destination ) {
			return is_string( $item ) ? str_replace( $source->root, $destination, $item ) : $item;
		}, (array)$source );

		$dir = $return->dir.'/'.$this->origin;
		$file = str_replace( $return->dir, $dir, $return->file );

		$return->file = $file;
		$return->dir = $dir;

		return $return;
	}

	public function getInfos ( $class ) {
		$source = realpath( $this->getLoader()->findFile( $class ) );

		$root = base_path( $this->vendor );

		$creator = $this->explodeClass( str_replace( $root, '', $source ) )->first;
		$package = $creator.'/'.$this->explodeClass( str_replace( $root.'/'.$creator, '', $source ) )->first;

		$dir = $root.'/'.$package;
		if ( file_exists( $dir.'/'.$this->composer_json ) ) {
			$json = json_decode( file_get_contents( $dir.'/'.$this->composer_json ) );

			return (object)[
				'file' => $source,
				'dir' => $dir,
				'root' => $root,
				'creator' => $creator,
				'package' => $this->explodeClass( $package )->last,
				'class' => $class,
				'namespace' => $this->getNamespace( $class ),
				'name' => $this->getName( $class ),
				// 'json' => $json,
				// 'psr4' => $json->autoload->{'psr-4'},
			];
		}

		return false;
	}

	public function getLoader () {
		return include base_path( 'vendor/autoload.php' );
	}

	public function explodeClass ($class) {
		$class = str_replace( '/', '\\', $class );
		$all = explode( '\\', trim( $class, '\\' ) );

		$pop = $all;
		array_pop( $pop );

		$shift = $all;
		array_shift( $shift );

		$first = current( $all );
		$last = end( $all );

		return (object)[
			'first' => $first,
			'last' => $last,
			'all' => $all,
			'pop' => $pop,
			'shift' => $shift,
		];
	}

	public function getNamespace ( $class ) {
		$explode = $this->explodeClass( $class )->all;

		array_pop( $explode );

		return join( '\\', $explode );
	}

	public function getName ( $class ) {
		$explode = $this->explodeClass( $class )->all;

		return array_pop( $explode );
	}

	public function is_it_already_in_app_folder ( $class ) {
		$explode = $this->explodeClass( $class )->all;

		if ( count( $explode ) > 1 && $explode[0] == $this->namespace ) {
			return true;
		}
		
		return false;
	}

	public function is_it_core_folder ( $class ) {
		$explode = $this->explodeClass( $class )->all;

		if ( count( $explode ) > 1 && $explode[0] == $this->core ) {
			return true;
		}
		
		return false;
	}

	public function is_it_blacklisted ( $class ) {
		return in_array( $class, $this->blacklist );
	}

	public function createClass( $php ) {
		$md5 = md5( $php );

		$dir = storage_path('tmp/classes/');
		$file = $md5.'.php';

		if ( !is_writable( $dir ) ) {
			exit("<pre>\n\n\nmkdir {$dir}\nchmod 777 $dir\n\n\n</pre>");
		}

		file_put_contents( $dir.$file, "<?php\n\n".$php );

		includeFile( $dir.$file );
	}

	public function buildOverride ( $source ) {
		return "{$this->override}\\{$source->creator}\\{$source->package}\\{$source->name}";
	}

	public function buildCore ( $source ) {
		return "{$this->override}\\{$source->creator}\\{$source->package}\\{$source->name}";
	}

	public function core2override ( $class ) {
		return join( '\\', $this->explodeClass( $class )->shift );
	}

	public function findOverride ( $class ) {
		$source = $this->getInfos( $class );

		if ( !$source ) {
			return false;
		}

		$override = $this->buildOverride( $source );

		return $this->findFile( $override );
	}

}