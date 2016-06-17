<?php

namespace Zzzzzzzzzzzzzzzzzzzzzzzzzz;

use Composer\Autoload\ClassLoader;

class Zz {

	private $namespace;
	private $core;
	private $override;

	private $map = [];

	public function __construct () {
		$this->namespace = 'App';
		$this->core = 'Core';
		$this->override = 'App\Override\\';

		spl_autoload_register( function( $class ) {
			echo "perso - $class <br>";
			$this->loader( $class );
		}, false, true );
	}

	public function loader ( $class ) {
		if ( $this->is_it_classLoader( $class ) ) {
			return false;
		}

		if ( $this->is_it_already_in_app_folder( $class ) ) {
			return false;
		}

		if ( $this->is_it_core_folder( $class ) ) {
			return $this->core( $class );
		}

		$this->override( $class );

		return true;
	}

	public function override ( $class ) {
		$override = "{$this->override}{$class}";

		$this->map[$class] = $override;

        if ( $file = $this->findFile( $override ) ) {
            includeFile( $file );

            return true;
        }
    }

	public function core ( $class ) {
		$class = str_replace( $this->core.'\\', '', $class );

		$namespace = $this->getNamespace( $class );
		$name = $this->getName( $class );

		$php = "
namespace {$this->core}\\{$namespace};

use \\{$class} as Origine;

class {$name} extends Origine {}";
		
		$loader = new ClassLoader;
        $loader->loadClass( $class );

		// eval( $php );
		echo( "\n"."\n"."\n".$php."\n"."\n"."\n" );
		
		return true;
    }

    public function getNamespace ( $class ) {
    	$explode = explode( '\\', $class );

    	array_pop( $explode );

    	return join( '\\', $explode );
    }

    public function getName ( $class ) {
    	$explode = explode( '\\', $class );

    	return array_pop( $explode );
    }

	public function findFile ( $class ) {
		$file = str_replace( [ 'App\\', '\\' ], [ '', '/' ], $class );
		$file = app_path( $file ).'.php';

        return file_exists( $file ) ? $file : false;
    }

	public function is_it_already_in_app_folder ( $class ) {
		$explode = explode( '\\', $class );

		if ( count( $explode ) > 1 && $explode[0] == $this->namespace ) {
			return true;
		}
		
		return false;
	}

	public function is_it_core_folder ( $class ) {
		$explode = explode( '\\', $class );

		if ( count( $explode ) > 1 && $explode[0] == $this->core ) {
			return true;
		}
		
		return false;
	}

	public function is_it_classLoader ( $class ) {
		return $class == 'ClassLoader';
	}

}

function includeFile( $file ) {
    include $file;
}