<?php

class LadsTheme {

    private static $options = [];

    # -------------------------------------------------------------------------
    # properties (instance variables)
    # -------------------------------------------------------------------------
	
	private $twig = null;
	private $menuDict = [];

    # -------------------------------------------------------------------------
    # class methods
    # -------------------------------------------------------------------------
    
	#
	# Make globally visible so theme services are available to widgets too.
	#
	
	public static function Get() {
		if ( !array_key_exists('LadsTheme', $GLOBALS) )
			$GLOBALS['LadsTheme'] = new LadsTheme();
		
		return $GLOBALS['LadsTheme'];
	}
	
    public static function SetOptions($opts) {
        self::$options = $opts;
    }
    
    # -------------------------------------------------------------------------
    # constructor
    # -------------------------------------------------------------------------
    
    public function __construct() {
		$this->_initTwig();
		$this->_initI18n();
		$this->_initWidgets();
        
		if ( is_admin() ) {
			# So that menu is editable as admin
			$this->registerMenus([
				'menu-primary' => 'Primary Menu',
			]);	
		}
    }
    
    /* (Keep this around to remind me how to do it...)
	private function _addTwigFunctions() {
		$func = new Twig_Function('getMenuItems', function($menuSlug) {
			return $this->getMenuItems($menuSlug);
		});
		$this->twig->addFunction($func);
	}
    */
    
    #
    # Set the locale so Twig can find .mo files
    #
    
    private function _initI18n() {
        $lang = apply_filters('wpml_current_language', 'en');
        if ($lang === 'en')
            return;
        
        # Set language and locale
        $langCountry = sprintf('%s_%s', $lang, strtoupper($lang));
        putenv("LANGUAGE=$langCountry");
        $locale = $langCountry . '.UTF-8';
        setlocale(LC_ALL, $locale);
        
        # Tell PHP where .mo files are hiding
        bindtextdomain('lads', get_template_directory() . '/locale');
        bind_textdomain_codeset('lads', 'UTF-8');
        textdomain('lads');
    }
    
	private function _initTwig() {
        $twigOptions = [ 'autoescape' => false, 'cache' => false ];
        
        # If caching requested, tell Twig; it'll create the dir if necessary
        $o = self::$options;
        if ( array_key_exists('twigCacheDir', $o) && strlen($o['twigCacheDir']) )
            $twigOptions['cache'] = $o['twigCacheDir'];

		$td = get_template_directory();
		$loader = new Twig_Loader_Filesystem("$td/twig");
		$this->twig = new Twig_Environment($loader, $twigOptions);
        $this->twig->addExtension( new Twig_Extensions_Extension_I18n() );
	}
    
    # -------------------------------------------------------------------------
    # rendering (public)
    # -------------------------------------------------------------------------
    
	public function renderPage() {
        $vars = ['post' => $this->_getPost()];
        
		return $this->_renderDoc('page', $vars);
	}

	public function render404() {
		return $this->_renderDoc('404');
	}
    
	public function renderSingle() {
        $vars = [
            'post' => $this->_getPost(),
            'adjPostInfo' => $this->_getAdjPostInfo()
        ];

		return $this->_renderDoc('single', $vars);
	}
	
	public function renderIndex() {
        $vars = [
            'posts' => $this->_getPosts(),
            'paging' => $this->_getPagingInfo(),
            'index' => $this->_getIndexInfo()
        ];
        
        return $this->_renderDoc('index', $vars);
	}
	
	public function renderWidget($name, $vars = []) {
		$vars['env'] = $this->_makeEnv();
        
		return $this->twig->render("widgets/$name.twig", $vars);
	}
    
    # -------------------------------------------------------------------------
    # rendering (private)
    # -------------------------------------------------------------------------
    
	private function _renderDoc($docName, $vars = []) {
		$allvars = [
            'doc' => $this->_makeDocVars(),
			'env' => $this->_makeEnv(),
			'sidebar' => $this->_getSidebar()
		];
		$allvars = array_merge($allvars, $vars);

		return $this->twig->render("docs/$docName.twig", $allvars);
	}
	
    private function _renderLangSwitcher() {
		ob_start();
		do_action('wpml_add_language_selector');
		$html = ob_get_clean();
        
		return $html;
    }
    
    # -------------------------------------------------------------------------
    # query DB (private)
    # -------------------------------------------------------------------------
    
	private function _getPosts() {
		$posts = [];
		while ( have_posts() ) {
			the_post();
			$posts[] = $this->_getPost();
		}

		return $posts;
	}
	
	private function _getPost() {
		$wpPost = get_post();
		$post = [
			'title' => apply_filters('the_title', $wpPost->post_title),
			'content' => apply_filters('the_content', $wpPost->post_content),
			'permalink' => apply_filters('the_permalink', get_permalink($wpPost)),
			'meta' => [
				'author' => get_the_author_meta('display_name', $wpPost->post_author),
				'date' => get_the_date('', $wpPost),
			]
		];
        
        # Excerpt
        $dict = $this->_makeExcerpt($post['content']);
        $post['excerpt'] = $dict['excerpt'];
        $post['hasMore'] = $dict['hasMore'];
        
        # Use the first image (if there is one) as "featured" image on index page
        $post['featured'] = [];
        if ( preg_match('!\[lads_image\s+url="(.*?)"!', $wpPost->post_content, $toks) ) {
            $post['featured']['url'] = $this->_getThumbnailUrl($toks[1]);
        } else {
            $post['featured']['url'] = '';
        }
		
		return $post;
	}
	
    #
    # Return the corresponding thumbnail if available, otherwise return self.
    #
    
    private function _getThumbnailUrl($imageUrl) {
        global $wpdb;
        
        $thumbFn = '';
        
        # Try to find the relative path in the DB (it isn't always there)
        $imgRelPath = preg_replace("!.*/wp-content/uploads/!", '', $imageUrl);
        $sql = "SELECT pm2.meta_value FROM wp_postmeta pm1, wp_postmeta pm2" .
            " WHERE pm1.meta_key = '_wp_attached_file' AND pm1.meta_value = '$imgRelPath'" .
            " AND pm1.post_id = pm2.post_id AND pm2.meta_key = '_wp_attachment_metadata'";
        $str = $wpdb->get_var($sql);
        if ($str) {
            $dict = unserialize($str);
            if ( array_key_exists('thumbnail', $dict['sizes']) ) {
                $thumbFn = $dict['sizes']['thumbnail']['file'];
            }
        }
        
        # If not found in DB, try to find by globbing in 'uploads'
        if (!$thumbFn) {
            $thumbFn = $this->_findThumbByGlob($imgRelPath);
        }

        # If found, replace image filename in URL; else just return original URL
        if ($thumbFn) {
            $thumbUrl = preg_replace('!/[^/]+$!', "/$thumbFn", $imageUrl);
        } else {
            $thumbUrl = $imageUrl;
        }
        
        return $thumbUrl;
    }
    
    #
    # Use globbing to try to find the thumbnail for $imgRelPath
    #
    
    private function _findThumbByGlob($imgRelPath) {
        $path1 = wp_upload_dir()['basedir'] . '/' . $imgRelPath;
        $path2 = preg_replace('!\.\w+$!', '*', $path1);
        $paths = glob($path2);
        
        $thumbFn = '';
        foreach ($paths as $p) {
            if ( preg_match('!/([^/]+\-\d{2,3}x\d{2,3}\.\w+)$!', $p, $toks) ) {
                $thumbFn = $toks[1];
                break;
            }
        }
        
        return $thumbFn;
    }
    
    private function _makeExcerpt($content) {
        if ( preg_match('#(.*?)<!--more-->#s', $content, $toks) ) {
            $excerpt = $toks[1];
            $hasMore = true;
        } else {
            $excerpt = $content;
            $hasMore = false;
        }

        # Remove possible image from the excerpt; it'll be featured image
        $excerpt = preg_replace('!<figure .*?</figure>!s', '', $excerpt);
        
        # Remove trailing linebreaks
        $excerpt = preg_replace('!(<br />\s*)+$!', '', $excerpt);
        
        return ['excerpt' => $excerpt, 'hasMore' => $hasMore];
    }
    
	private function _makeEnv() {
		$env = [
			'lang' => 'en',
			'themeDirUrl' => get_template_directory_uri(),
			'uploadDirUrl' => wp_upload_dir()['baseurl'],
            'siteUrl' => get_bloginfo('url')
		];
		
		return $env;
	}
	
    private function _makeDocVars() {
        $docvars = [];
        ob_start();
        wp_head();
        $docvars['wpHead'] = ob_get_clean();
        ob_start();
        wp_footer();
        $docvars['wpFooter'] = ob_get_clean();
        $docvars['menuItems'] = $this->getMenuItems('menu-primary');
        $docvars['langSwitcher'] = $this->_renderLangSwitcher();
        
        return $docvars;
    }
    
	private function _getSidebar() {
		ob_start();
		dynamic_sidebar('sidebar');
		$foo = ob_get_contents();
		ob_end_clean();
        
		return $foo;
	}
	
	private function _getAdjPostInfo() {
		$info = [];

		# prev
		$link = get_previous_post_link();
		$url = $title = '';
		if ($link) {
			preg_match('!<a href="(.*?)".*?>(.*?)<!u', $link, $matches);
			list($all, $url, $title) = $matches;
		}
		$info['prev'] = ['url' => $url, 'title' => $title];
		
		# next
		$link = get_next_post_link();
		$url = $title = '';
		if ($link) {
			preg_match('!<a href="(.*?)".*?>(.*?)<!u', $link, $matches);
			list($all, $url, $title) = $matches;
		}
		$info['next'] = ['url' => $url, 'title' => $title];
		
		return $info;
	}
	
	private function _getPagingInfo() {
		$info = [];

		# prev
		$link = get_next_posts_link();
		$url = '';
		if ($link) {
			preg_match('!<a href="(.*?)".*?>!u', $link, $matches);
			list($all, $url) = $matches;
		}
		$info['prev'] = ['url' => $url];
		
		# next
		$link = get_previous_posts_link();
		$url = '';
		if ($link) {
			preg_match('!<a href="(.*?)".*?>!u', $link, $matches);
			list($all, $url) = $matches;
		}
		$info['next'] = ['url' => $url];
		
		return $info;
	}
	
    #
    # Study our environment to figure out what the title should be
    #
    
    private function _getIndexInfo() {
        $info = [];
        $uri = $_SERVER['REQUEST_URI'];
        
        if ( is_search() ) {
            $info['type'] = 'search';
            $info['search'] = $_GET['s'];
        } elseif ( is_year() ) {
            $info['type'] = 'archive';
            preg_match('!^(/(de|fr))?/(\d{4})!i', $uri, $toks);
            $info['year'] = $toks[3];
        } elseif ( is_month() ) {
            $info['type'] = 'archive';
            preg_match('!^(/(de|fr))?/(\d{4})/(\d{2})!i', $uri, $toks);
            $info['year'] = $toks[3];
            $dateObj = DateTime::createFromFormat('!m', $toks[4]);
            $info['month'] = $dateObj->format('F');  # month name in EN
        } else {
            $info['type'] = 'home';
        }
        
        return $info;
    }
    
    # -------------------------------------------------------------------------
    # menus
    # -------------------------------------------------------------------------
    
    #
    # Menus must be registered in order to be visible on the admin site
    #
  
    public function registerMenus($menuDict) {
      $this->menuDict = $menuDict;
      add_action('init', [$this, '_init_registerMenus']);
    }
  
	#
	# Callback during 'init' action
	#
	
    public function _init_registerMenus() {
      add_theme_support('nav-menus');
      register_nav_menus($this->menuDict);
    }
  
    #
    # Return a "jbr" (i.e. neutral) array of menu item objs
    #
  
    public function getMenuItems($menuSlug) {
      $wpItems = wp_get_nav_menu_items($menuSlug);
      $jbrItems = [];
      foreach ($wpItems as $key => $item) {
        $i = [
          'label' => $item->title,
          'url' => $item->url
        ];
        array_push($jbrItems, $i);
      }
      
      return $jbrItems;
    }

    # -------------------------------------------------------------------------
    # widgets
    # -------------------------------------------------------------------------
    
	private function _initWidgets() {
		# Register widgets
		add_action('widgets_init', function(){
            $heads = ['Faces', 'Lang', 'Twig'];  # (This should be done elsewhere...)
            foreach ($heads as $head) {
                register_widget($head . 'Widget');
            }
		});

		# Register widget area
		add_action('init', function(){
			register_sidebar([
				'name' => 'Sidebar',
				'id' => 'sidebar',
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget' => "</div>",
				'before_title' => '<h2 class="widget-title">',
				'after_title' => '</h2>',
			]);
		});
	}
    
}

#?>