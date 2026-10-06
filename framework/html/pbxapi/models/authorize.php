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
  $Id: authorize.php, Tue 04 Sep 2018 09:55:56 AM EDT, nicolas@issabel.com
*/

use Firebase\JWT\JWT;

class authorize {

    protected $whitelist = array( '127.0.0.1', '::1');

    function authorized($f3) {

        $headers = $f3->get('HEADERS');

        if(!$f3->exists('HEADERS.Authorization')) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Unauthorized', true, 403);
            die();
        }

        // Expect exactly "Bearer <token>". Splitting on a single space broke on a
        // missing token, on extra spaces and on other schemes, leaving $jwt
        // undefined or empty and turning a 403 into a 500.
        $parts = preg_split('/\s+/', trim($headers['Authorization']));
        if (count($parts) !== 2 || strcasecmp($parts[0], 'Bearer') !== 0 || $parts[1] === '') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Unauthorized', true, 403);
            die();
        }
        $jwt = $parts[1];

        $key = $f3->get('JWT_KEY');

        // Do not inherit a loosened expiry window from elsewhere in the request.
        JWT::$leeway = 0;

        try {
            $data = JWT::decode($jwt, $key, array('HS256'));
        }catch(Exception $e) {

            // An expired token is rejected like any other invalid one. It used
            // to be re-decoded with a 720000 second (200 hour) leeway, which
            // defeated the one hour expiry outright.
            if($e->getMessage()=="Expired token") {
                header('Content-Type: application/json');
                echo "{\"status\": \"expired\"}";
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Unauthorized', true, 403);
            }
            die();
        }

        // Token ok, then just return
        return;

        /*
        if($f3->get('DOAUTH')==false) {
            return;
        }

        if(in_array($_SERVER['REMOTE_ADDR'], $this->whitelist)){
            // always accept from localhost
            return;
        }
        */
    }
}
