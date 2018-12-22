<?php
namespace Interfaces;

interface AuthInterface {

    public function login($u);

    public function logout($id);

    public function get($id);

    public function user();
}

