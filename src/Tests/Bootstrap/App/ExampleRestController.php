<?php

namespace Symlex\Tests\Bootstrap\App;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class ExampleRestController
{
    public function cgetAction()
    {
        $result = array();

        $result[] = array('id' => 1, 'foo' => 'bar');
        $result[] = array('id' => 2, 'foo' => 'baz');

        return $result;
    }

    public function getAction($id)
    {
        $result = array('id' => $id, 'foo' => 'baz');

        return $result;
    }

    public function deleteAction($id)
    {
    }

    public function putAction($id, Request $request)
    {
        $result = array('id' => $id, 'foo' => 'bar');

        return $result;
    }

    public function postAction(Request $request)
    {
        $result = array('id' => 3, 'foo' => 'bar');

        return $result;
    }
}