<?php

namespace Symlex\Router;

/**
 * An interceptor can be used in addition to routers for performing HTTP
 * authentication or intercepting certain requests (e.g. blocking or
 * redirecting requests coming from specified IP ranges)
 *
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class HttpInterceptor
{
    /**
     * Perform digest HTTP authentication
     *
     * Note: HTTP authentication is disabled, if application
     * runs in command-line (CLI) mode
     *
     * @param string $realm
     * @param array $users username => password
     */
    public function digestAuth($realm, array $users)
    {
        if ($this->isCLIRequest()) {
            return;
        }

        if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
            $this->sendAuthenticateHeader($realm);

            exit();
        }

        if (!($data = $this->httpDigestParse($_SERVER['PHP_AUTH_DIGEST'])) || !isset($users[$data['username']])) {
            $this->sendAuthenticateHeader($realm);

            exit();
        }

        $A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
        $A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);

        $valid_response = md5($A1 . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);

        if ($data['response'] != $valid_response) {
            $this->sendAuthenticateHeader($realm);

            exit();
        }
    }

    /**
     * Perform digest HTTP authentication, if SSL is used to access the site (checks HTTP header)
     *
     * @param string $realm
     * @param array $users username => password
     */
    public function sslDigestAuth($realm, array $users)
    {
        if ($this->isSSLRequest()) {
            $this->digestAuth($realm, $users);
        }
    }

    /**
     * Checks, if application runs in command line (CLI) mode
     *
     * @return bool
     */
    protected function isCLIRequest()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Checks, if application is requested via secure SSL
     *
     * @return bool
     */
    protected function isSSLRequest()
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS'])) {
                return true;
            }

            if ('1' == $_SERVER['HTTPS']) {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }

        return false;
    }

    /**
     * Helper method for digestAuth()
     *
     * @param string $txt
     * @return array|bool
     */
    protected function httpDigestParse($txt)
    {
        $needed_parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
        $data = array();
        $keys = implode('|', array_keys($needed_parts));

        preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[3] ? $m[3] : $m[4];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
    }

    /**
     * Send HTTP auth header
     *
     * @param $realm
     */
    protected function sendAuthenticateHeader($realm)
    {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Digest realm="' . $realm .
            '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) . '"');
    }
}