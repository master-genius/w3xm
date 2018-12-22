<?php
namespace Core;

class View {

    public function pageData($vfile, $vars = []) {
    
        $path_file = VIEW_PATH . '/' . $vfile;
        if (file_exists($path_file)) {
            ob_start();
            if (!empty($vars)){
                extract($vars);
            }
            include($path_file);
            $page_data = ob_get_contents();
            ob_clean();
            return $page_data;
        } else {
            return 'Not found';
        }
    }

    public function render($vfile, $vars=[]) {
        exit($this->pageData($vfile, $vars));
    }

    public function page($vfile, $vars = []) {
        return $this->pageData($vfile, $vars);
    }

}

