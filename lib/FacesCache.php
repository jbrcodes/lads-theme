<?php

#
# File: themes/lads/lib/FacesCache.php
# Provide an abstraction for getting random "faces" URLs
#

class FacesCache {

	# --------------------------------------------------------------------------
	# Class Methods (public)
	# --------------------------------------------------------------------------

    public static function GetRandomUrl() {
		$up = wp_upload_dir();
        $blob = get_option('lads_FacesCache');
        if ($blob) {
            $fnArr = unserialize($blob);
        } else {
            $imgDirPath = $up['basedir'] . '/faces';
            $fnArr = FacesCache::_LoadFileNames($imgDirPath);
            $blob = serialize($fnArr);
            add_option('lads_FacesCache', $blob);
        }     
        
        $i = mt_rand(0, (count($fnArr)-1));  # get random index
        $imgUrl = sprintf('%s/faces/%s', $up['baseurl'], $fnArr[$i]);
        
        return $imgUrl;
    }
    
    #
    # Empty the cache.
    #
    
    public static function Empty() {
        delete_option('lads_FacesCache');
    }
    
	# --------------------------------------------------------------------------
	# Class Methods (private)
	# --------------------------------------------------------------------------

    #
    # Return an array of image filenames located in $dirPath
    #
    
	private static function _LoadFileNames($dirPath) {
		$fnArr = scandir($dirPath);
        $imgFnArr = [];
        foreach ($fnArr as $fn) {
            if ( preg_match('!^\w+\.(gif|jpg|jpeg|png)$!', $fn) )
                $imgFnArr[] = $fn;
        }
        
        return $imgFnArr;
	}

}

#?>