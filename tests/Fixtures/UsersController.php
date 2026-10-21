<?php

namespace PhalconInertia\Tests\Fixtures;

use Phalcon\Mvc\Controller;

class UsersController extends Controller
{
    public static $updated = false;

    public static $stored;

    public function indexAction()
    {
        return $this->inertia->render('Users/Index', ['users' => [['id' => 1]]]);
    }

    public function storeAction()
    {
        self::$stored = $this->inertia->validate(self::validation());

        return $this->response->redirect('users/index');
    }

    public function updateAction()
    {
        self::$updated = true;

        return $this->response->redirect('users/index');
    }

    private static function validation()
    {
        $validation = class_exists('Phalcon\Filter\Validation') ? 'Phalcon\Filter\Validation' : 'Phalcon\Validation';
        $presenceOf = $validation.'\Validator\PresenceOf';
        $validation = new $validation;
        $validation->add('name', new $presenceOf(['message' => 'The name is required.']));

        return $validation;
    }
}
