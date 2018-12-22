<?php
namespace Core;

class VCode {

    public $font = 'purisa_bo.ttf';

    public $font_size = 23;

    public $length = 5;

    public $width = 182;

    public $height = 64;

    public $red   = [145, 196];

    public $green = [190, 196];

    public $blue = [228, 248];

    public $fake = true;

    public $transp = true;

    public $noise_color = [128, 196];

    public $code_color = [12, 160];
    
    public $codestr       = '0123456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxy';

    public $codestr_len = 0;

    public $verify_code = '';

    public $point_number = 50;

    public $arc_number = 8;

    public $point = false;

    public $fake_diff_num = -11;

    public $img = false;



    public function __construct($options = []) {
        $this->codestr_len = strlen($this->codestr);
    }

    public function getCode() {
        return $this->verify_code;
    }

    public function genCode() {
        $this->verify_code = '';

        $ind = 0;
        for($i=0;$i<$this->length;$i++){
            $ind = mt_rand(0,$this->codestr_len-1);
            $this->verify_code .= $this->codestr[$ind];
        }

        return $this->verify_code;
    }

    public function genFakeCode() {
        $save_code = $this->verify_code;
        $fake_code = $this->genCode();
        $this->verify_code = $save_code;
        return $fake_code;
    }


    public function genCodeImage() {

        $this->genCode();
    
        $this->img = imagecreatetruecolor($this->width,$this->height);
        
        $red = mt_rand($this->red[0], $this->red[1]);
        
        $green = mt_rand($this->green[0], $this->green[1]);

        $blue = mt_rand($this->blue[0], $this->blue[1]);

        $color = imagecolorallocate($this->img, $red, $green, $blue);

        
        imagefill($this->img,0,0,$color);
        if ($this->transp) {
            $this->fake_diff_num = 248;
            $red = $green = $blue = 0;
            imagecolortransparent($this->img, $color);

            $this->code_color[0] += 45;
            $this->code_color[1] += 45;
        }

        imagesetthickness($this->img, 2);


        for($i=0; $i<$this->arc_number; $i++) {
            $color = imagecolorexact(
                                $this->img,
                                mt_rand($this->noise_color[0],$this->noise_color[1]),
                                mt_rand($this->noise_color[0],$this->noise_color[1]),
                                mt_rand($this->noise_color[0],$this->noise_color[1])
                            );

            imagearc($this->img,
                mt_rand(5, $this->width - 10)+i,
                mt_rand(5, $this->height - 10),
                mt_rand(30, 100),
                30,
                0,mt_rand(10,360),
                $color
            );
        }

        if ($this->point) {
            for($i=0;$i<$this->point_number;$i++){
                $color = imagecolorexact($this->img,
                            mt_rand($this->noise_color[0],$this->noise_color[1]),
                            mt_rand($this->noise_color[0],$this->noise_color[1]),
                            mt_rand($this->noise_color[0],$this->noise_color[1])
                        );
                imagesetpixel($this->img,
                    mt_rand(5, $this->width - 5),
                    mt_rand(5, $this->height - 5),
                    $color
                );
            }
        }

        $code_color = imagecolorexact($this->img,
                            mt_rand($this->code_color[0], $this->code_color[1]),
                            mt_rand($this->code_color[0], $this->code_color[1]), 
                            mt_rand($this->code_color[0], $this->code_color[1]) 
                        );

        $font = CONFIG_PATH . '/' . $this->font;

        if ($this->fake) {
            $fake_code = $this->genFakeCode();
            $fake_color = imagecolorexact($this->img, 
                $red + $this->fake_diff_num,
                $green + $this->fake_diff_num,
                $blue + $this->fake_diff_num
            );

            imagettftext($this->img, 
                $this->font_size - 1,
                mt_rand(-15,15),39,33,
                $fake_color,
                $font,
                $fake_code
            );
        }
        
        imagettftext($this->img,
            $this->font_size,
            mt_rand(-6,6),
            28,
            30,
            $code_color,
            $font,
            $this->verify_code
        );

    }

    public function codeImage() {

        $this->genCodeImage();
        imagepng($this->img);
        imagedestroy($this->img);
        $this->img = false;
    }

    public function clearCodeImage() {
        imagedestroy($this->img);
        $this->img = false;
    }

    public function __destruct() {
        if ($this->img) {
            imagedestroy($this->img);
        }
    }

}

