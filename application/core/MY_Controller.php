<?php

class MY_Controller extends CI_Controller
{
	public function __construct()
    {
        parent::__construct();

        define('CAPTCHA_DOMAIN', 'yourname.org');
        ini_set("session.cookie_domain", '.' . CAPTCHA_DOMAIN);
        
        // Sign up for API key here 
        // https://www.google.com/recaptcha/admin#list
        define('CAPTCHA_SECRET_KEY', '6LfH_R5TAABAAPQJM27zAn6ChPLle2Sna24o-hZf');
        define('CAPTCHA_SITE_KEY', '6LfH_R8TAAAABJTo21zGBRaZnpJDqKDJ4c5kgsZF');


        $this->load->helper('cookie');
        
        // $this->load->library('session');
        // If you want to load session library, must before Anti-Scraping library because of session_start() issue.
        $this->load->library('AntiScraping');
        
        // PSR-4 autoloader, it will automaticlly load ReCaptcha when it needs.
        require_once APPPATH .'third_party/autoload.php';

        $this->_AntiScraping();
    }

    public function _AntiScraping()
    {
        // Install SQL table for Anti-Scraping, after installed, please remove this line.
        // storage engine: memory, innodb, myisam
        
        //$this->antiscraping->install('innodb'); 

        /* for test
        
        $this->antiscraping->user_ip_address = '66.249.92.125';
        $this->antiscraping->user_ip_resolve = 'rate-limited-proxy-66-249-92-125.google.com';
        $this->antiscraping->user_agent = 'Mediapartners-Google';

        */
        
        $this->antiscraping->debug(); // debug information will be displayed in page source (view HTML).

        $anti_scraping_result = $this->antiscraping->run();

        if ( $anti_scraping_result == 'deny' )
        {
            if ($this->input->post('g-recaptcha-response'))
            {
                $remoteIp           = $this->input->ip_address();
                $gRecaptchaResponse = $this->input->post('g-recaptcha-response');

                $recaptcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET_KEY);
                $resp = $recaptcha->verify($gRecaptchaResponse, $remoteIp);

                if ($resp->isSuccess())
                {
                    $this->antiscraping->delete_ip_rule();
                }
                else
                {
                    $this->_ReCaptcha();
                }
            }
            else
            {
                $this->_ReCaptcha();
            }
        }
    }

    public function _ReCaptcha()
    {
        $data = array();

        $data['title'] = 'Please solve Captcha';
        $data['heading'] = 'Something went wrong';
        $data['message'] = 'Please complete the CAPTCHA to confirm you are a human.';
        $data['lang']    = 'en';

        $data['captcha_site_key'] = CAPTCHA_SITE_KEY;

        ob_start();

        $output = $this->load->view('captcha', $data, true);
        echo $output;
        $buffer = ob_get_contents();
        ob_end_clean();
        
        echo $buffer;
        exit;
    }
}
