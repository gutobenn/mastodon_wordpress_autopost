<?php
/**
 * Intellectual Property of #Mastodon
 * 
 * @copyright (c) 2017, #Mastodon
 * @author V.A. (Victor) Angelier <victor@thecodingcompany.se>
 * @version 1.0
 * @license http://www.apache.org/licenses/GPL-compatibility.html GPL
 * 
 */
namespace theCodingCompany;

/**
 * HTTP Request object finetune for High Security
 */
final class HttpRequest
{
    /**
     * Holds our base path. In most cases this is just /, but it can be /api for example
     * @var string
     */
    private static $base_path = "/";

    /**
     * Base URL without leading /
     * @var string
     */
    private static $base_url = "";
    
    /**
     * Holds our instance
     * @var array
     */
    private static $instance = array();
    
    /**
     * Enable debugging
     * @var bool
     */
    private static $debug = false;

    /**
     * Construct new HttpRequest
     * @param string $base_url Base url like http://api.website.com without leading /
     * @param string $base_path Base path like, / or /api
     */
    protected function __construct($base_url = "", $base_path = "/") {            
        self::$base_path = $base_path;
        self::$base_url = $base_url;
    }
    protected function __clone(){}
    protected function __wakeup(){}
    
    /**
     * Singleton design pattern
     * @param string $base_url The full FQDN url. http://api.domainname.com
     * @param string $base_path The endpoint. We start at /
     * @return HttpRequest instance
     */
    public static function Instance($base_url = "", $base_path = "/"){
        $cls = get_called_class();
        if(!isset(self::$instance[$cls])){
            self::$instance[$cls] = new HttpRequest($base_url, $base_path);
        }
        return self::$instance[$cls];
    }

    /**
     * HTTP POST request
     * @param string $path
     * @param array $parameters
     * @param array $headers
     * @return bool
     */
    public static function Post($path = "", $parameters = array(), $headers = array()){
        //Sen the request and return response
        $post_data = json_encode($parameters);
        return self::http_request(
            "POST", 
            self::$base_url.self::$base_path.$path, 
            $headers,
            $post_data
        );
    }

    /**
     * HTTP GET request
     * @param string $path
     * @param array $parameters
     * @param array $headers
     * @return bool
     */
    public static function Get($path = "", $parameters = array(), $headers = array()){
        //Sen the request and return response

        return self::http_request(
            "GET", 
            self::$base_url.self::$base_path.$path, 
            $headers,
            $parameters
        );
    }

    /**
    * Buikd the HTTP request
    * @param string $method  GET|POST
    * @param string $url
    * @param array $headers
    * @param array $parameters
    * @return boolean
    */
    
   private static function http_request($method = "GET", $url = "", $headers = array(), $parameters = array()) {


         //Check if we have parameters to post
        if (count($parameters) > 0 && is_array($parameters)) {
            $content = "";
            foreach($parameters as $k => $v) {
                $content .= "&".urlencode($k)."=" . urlencode($v);
            }

            // Strip first & sign
            $content = substr($content, 1);

            // If the method is get, append to the URL            
            if ($method == "GET") {
                $url .= "?" . $content;
                $body = "";
            }
            // Otherwise, post in the content
            else {
                $body = $content;
            }
        }
        elseif ($parameters) {
            $body = $parameters;
        }


        $args = array(
            'method'  => $method,
            'headers' => $headers,
            'body' => $body,    
            'timeout' => '30',
            'httpversion' => '1.1'
        );


        $response = wp_remote_request( $url, $args );
        $body = wp_remote_retrieve_body($response);


        if (is_wp_error($response)) {
            $error = "<pre>" . print_r(error_get_last(), true) . "</pre>";
            $error .= "<pre>Error Found ( ".$response->get_error_message()." )</pre>";
            
            if(self::$debug){ print_r($error); }
            return $error;
        }
        else{
            //Debug the response/url
            self::debug_response($url, null);
            
            if (($json = \json_decode($body, true)) !== NULL) {
                return $json;
            }
            else {
                return $body;
            }
        }

    }
    
    /**
     * Debug method for response analyzing
     * @param string $url
     * @param resource $context
     */
    private static function debug_response($url, $context){
        //Get and debug headers
        if(self::$debug){
            //Get meta data for debugging
            $fp = @fopen($url, "r", false, $context);
            if($fp){
                $req_headers = stream_get_meta_data($fp);
                if(isset($req_headers["wrapper_data"])){
                    echo "<pre>".print_r($req_headers["wrapper_data"], true)."</pre>";
                }else{
                    echo "<pre>".print_r($req_headers, true)."</pre>";
                }
            }
            echo "<pre>Check host alive headers<br/>\r\n";
            $headers = @get_headers($url);
            if($headers !== FALSE){
                print_r($headers);
            }
            echo "</pre>";
        }
    }
}
