<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private UserRepository $users = new UserRepository())
    {
    }

    /** @return array{ok:bool, message?:string, user?:array} */
    public function register(string $name, string $email, string $password, string $confirm): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || strlen($name) > 120) {
            return ['ok' => false, 'message' => 'Please enter a valid name.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Please enter a valid email address.'];
        }
        if ($password !== $confirm) {
            return ['ok' => false, 'message' => 'Passwords do not match.'];
        }
        if (!$this->isStrongPassword($password)) {
            return ['ok' => false, 'message' => 'Password must be at least 10 characters and include a letter and a number.'];
        }
        if ($this->users->findByEmail($email) !== null) {
            return ['ok' => false, 'message' => 'An account with this email already exists.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = $this->users->create($name, $email, $hash);

        return ['ok' => true, 'user' => $user];
    }

    /** @return array{ok:bool, message?:string, user?:array} */
    public function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);

        // Constant-shape response whether or not the user exists, to avoid user enumeration.
        if ($user === null) {
            password_verify($password, '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidin');
            return ['ok' => false, 'message' => 'Incorrect email or password.'];
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return ['ok' => false, 'message' => 'Too many failed attempts. Please try again later.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->users->recordFailedLogin($user['id']);
            return ['ok' => false, 'message' => 'Incorrect email or password.'];
        }

        $this->users->resetFailedLogin($user['id']);

        return ['ok' => true, 'user' => $user];
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Za-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }
}
