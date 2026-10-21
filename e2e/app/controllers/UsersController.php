<?php

namespace E2e\Controllers;

use Phalcon\Mvc\Controller;

class UsersController extends Controller
{
    public function indexAction()
    {
        return $this->inertia->render('Users/Index', ['users' => array_values($this->users())]);
    }

    public function createAction()
    {
        return $this->inertia->render('Users/Create');
    }

    public function storeAction()
    {
        $validation = class_exists('Phalcon\Filter\Validation') ? 'Phalcon\Filter\Validation' : 'Phalcon\Validation';
        $stringLength = $validation.'\Validator\StringLength';
        $validation = new $validation;
        $validation->add('name', new $stringLength(['min' => 2, 'messageMinimum' => 'The name must have at least 2 characters.']));
        $name = $this->inertia->validate($validation, ['name' => (string) $this->inertia->input('name', 'trim')])['name'];

        $users = $this->users();
        $id = max(array_keys($users)) + 1;
        $users[$id] = ['id' => $id, 'name' => $name];
        $this->session->set('users', $users);
        $this->inertia->flash('success', 'User created.');

        return $this->response->redirect('users/'.$id);
    }

    public function showAction($id)
    {
        $users = $this->users();
        if (! isset($users[$id])) {
            return $this->inertia->render('Error', ['status' => 404])->setStatusCode(404, 'Not Found');
        }

        return $this->inertia->render('Users/Show', ['user' => $users[$id]]);
    }

    public function updateAction($id)
    {
        $users = $this->users();
        $users[$id]['name'] = (string) $this->inertia->input('name', 'trim');
        $this->session->set('users', $users);
        $this->inertia->flash('success', 'User updated.');

        return $this->response->redirect('users/'.$id);
    }

    private function users()
    {
        return $this->session->get('users', [
            1 => ['id' => 1, 'name' => 'Ada Lovelace'],
            2 => ['id' => 2, 'name' => 'Linus Torvalds'],
        ]);
    }
}
