<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2018 Issabel Foundation                                |
  +----------------------------------------------------------------------+
  | This program is free software: you can redistribute it and/or modify |
  | it under the terms of the GNU General Public License as published by |
  | the Free Software Foundation, either version 3 of the License, or    |
  | (at your option) any later version.                                  |
  |                                                                      |
  | This program is distributed in the hope that it will be useful,      |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
  | GNU General Public License for more details.                         |
  |                                                                      |
  | You should have received a copy of the GNU General Public License    |
  | along with this program.  If not, see <http://www.gnu.org/licenses/> |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is Issabel LLC            |
  +----------------------------------------------------------------------+
  $Id: jwtauth.php, Wed 18 May 2022 08:17:16 AM EDT, nicolas@issabel.com
*/

use Firebase\JWT\JWT;

class jwtauth {

    //protected $data;

    //protected $db;

    protected $pwd;

    function __construct($f3) {

        // $this->db  = new DB\SQL( 'sqlite:/var/www/db/acl.db' );

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');

        /*
        try {
            $this->data = new DB\SQL\Mapper($this->db,'acl-user');
        } catch(Exception $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }
        */

        if(is_file("/etc/issabel.conf")) {
            $data      = parse_conf("/etc/issabel.conf");
            if (isset($data['amiadminpwd'])) {
                $this->pwd = $data['amiadminpwd'];
            }
        }
        // Leaving $this->pwd NULL would make the password check in post() pass
        // for any request that omits the password field, so fail closed instead.
        if ($this->pwd === NULL || $this->pwd === '') {
            throw new RuntimeException('Missing or empty amiadminpwd in /etc/issabel.conf');
        }

    }

    function parse_conf($file) {
        $result = array();
        $lines = file($file);
        foreach($lines as $line) {
            $partes = preg_split("/=/",$line);
            $result[trim($partes[0])]=trim($partes[1]);
        }
        return $result;
    }

    // JWT::decode() returns stdClass, not array, and so does every nested
    // object, so the identity has to be normalised before it is reused.
    private function tokenIdentity($payload) {
        if (!isset($payload->data))
            return array();
        $data = $payload->data;
        if (is_array($data))
            return $data;
        if (is_object($data))
            return get_object_vars($data);
        return array();
    }

    function get($f3) {
        //
        // Retrieve access and refresh tokens from PHP session, or refresh tokens if authenticate/refresh is called
        //
        session_name("issabelSession");
        session_start();

        if($f3->get('PARAMS.id')=='') {
            // returns tokens from php session
            header('Content-Type: application/json');
            if($_SESSION['access_token']<>'') {
                $jwt        = $_SESSION['access_token'];
                $jwtrefresh = $_SESSION['refresh_token'];
                echo "{\"access_token\":\"$jwt\",\"refresh_token\":\"$jwtrefresh\",\"token_type\":\"Bearer\",\"status\":\"authorized\"}";
            } else {
                echo "{\"status\":\"unauthorized\"}";
            }
            die();

        } else {
            //
            // try to refresh tokens
            //
            $key = $f3->get('JWT_KEY');

            // Never let a previous call in this request loosen expiry checks.
            JWT::$leeway = 0;

            //
            // The refresh token is the authentication factor here, so it has to be
            // validated strictly: any failure rejects the request. Previously only
            // "Expired token" was handled and every other error fell through,
            // which made this check skippable by sending any garbage token.
            //
            $rnt = $f3->get('GET.refresh_token');
            if (empty($rnt)) {
                header('Content-Type: application/json');
                echo "{\"status\":\"unauthorized\"}";
                die();
            }

            try {

                $refresh = JWT::decode($rnt, $key, array('HS256'));

            }catch(Exception $e) {

                header('Content-Type: application/json');
                if ($e->getMessage() == 'Expired token') {
                    echo "{\"status\":\"expired\",\"type\":\"refresh\"}";
                } else {
                    echo "{\"status\":\"unauthorized\"}";
                }
                die();
            }

            //
            // Refresh token is valid, so mint a new pair. The identity comes from
            // the refresh token itself. Tokens issued before this fix carry no
            // identity, so fall back to the access token, also validated with no
            // leeway.
            //
            $identity = $this->tokenIdentity($refresh);

            if (!$identity) {
                $jwt = $f3->get('GET.access_token');
                if (empty($jwt)) {
                    header('Content-Type: application/json');
                    echo "{\"status\":\"unauthorized\"}";
                    die();
                }
                try {
                    $access = JWT::decode($jwt, $key, array('HS256'));
                }catch(Exception $e) {
                    header('Content-Type: application/json');
                    echo "{\"status\":\"unauthorized\"}";
                    die();
                }
                $identity = $this->tokenIdentity($access);
            }

            //
            // Generate a new access token with a new expiration. The refresh
            // token outlives it by a factor of 24 and now carries the identity
            // too, so later refreshes need nothing else.
            //
            $time = time();
            $exp  = $f3->get('JWT_EXPIRES');

            $token = array(
                'iat'  => $time,
                'exp'  => $time + $exp,
                'data' => $identity
            );

            $tokenrefresh = array(
                'iat'  => $time,
                'exp'  => $time + ( $exp * 24 ),
                'data' => $identity
            );

            $jwt        = JWT::encode($token, $key);
            $jwtrefresh = JWT::encode($tokenrefresh, $key);

            header('Content-Type: application/json');
            echo "{\"access_token\":\"$jwt\",\"expires_in\":$exp,\"refresh_token\":\"$jwtrefresh\",\"token_type\":\"Bearer\",\"status\":\"authorized\"}";
            die();
        }
    }

    function put($f3) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
        die();
    }

    function post($f3) {
        //
        // authenticate user and return a set of access and refresh tokens
        //
        $input = json_decode($f3->get('BODY'),true);
        $user = isset($input['user'])?$input['user']:$f3->get('POST.user');
        if(!isset($user)) {
            $user = isset($input['username'])?$input['username']:$f3->get('POST.username');
        }
        $password    = isset($input['password'])?$input['password']: $f3->get('POST.password');

        //  $result = $this->db->exec('SELECT * FROM acl_user WHERE name = :name AND md5_password = :md5password',array(':name'=>$user,':md5password'=>$md5password));
        //  if($this->db->count() > 0) {

        // hash_equals() instead of == so a magic-hash password such as
        // "0e123456..." cannot be matched with a loose "0".
        if($user==='admin' && is_string($password) && hash_equals($this->pwd,$password)) {

            $time = time();
            $key  = $f3->get('JWT_KEY');
            $exp  = $f3->get('JWT_EXPIRES');

            $token = array(
                'iat' => $time,
                'exp' => $time + $exp,
                'data' => [
                    'name' => $user
                ]
            );

            // The refresh token carries the identity too, so a refresh needs
            // nothing but a valid refresh token.
            $tokenrefresh = array(
                'iat'  => $time,
                'exp'  => $time + ( $exp * 24 ),
                'data' => [
                    'name' => $user
                ]
            );

            $jwt = JWT::encode($token, $key);
            $jwtrefresh = JWT::encode($tokenrefresh, $key);

            header('Content-Type: application/json');
            echo "{\"access_token\":\"$jwt\",\"expires_in\":$exp,\"refresh_token\":\"$jwtrefresh\",\"token_type\":\"Bearer\"}";

            die();

        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
            die();
        }

    }
}
