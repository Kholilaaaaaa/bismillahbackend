<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use App\Services\Exception;

class FirebaseService
{
    protected $auth;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/' . env('FIREBASE_CREDENTIALS')));
            
            $this->auth = $factory->createAuth();
        } catch (\Exception $e) {
            throw new \Exception('Firebase initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Create user in Firebase Authentication
     */
    public function createUser($email, $password, $displayName = null)
    {
        try {
            $userProperties = [
                'email' => $email,
                'password' => $password,
                'emailVerified' => false,
            ];

            if ($displayName) {
                $userProperties['displayName'] = $displayName;
            }

            $createdUser = $this->auth->createUser($userProperties);

            return [
                'success' => true,
                'uid' => $createdUser->uid,
                'email' => $createdUser->email,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update Firebase user
     */
    public function updateUser($uid, $properties)
    {
        try {
            $this->auth->updateUser($uid, $properties);
            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete Firebase user
     */
    public function deleteUser($uid)
    {
        try {
            $this->auth->deleteUser($uid);
            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Firebase ID Token
     */
    public function verifyIdToken($idToken)
    {
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            return [
                'success' => true,
                'uid' => $verifiedIdToken->claims()->get('sub'),
                'email' => $verifiedIdToken->claims()->get('email'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user by UID
     */
    public function getUser($uid)
    {
        try {
            $user = $this->auth->getUser($uid);
            return [
                'success' => true,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        try {
            $user = $this->auth->getUserByEmail($email);
            return [
                'success' => true,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create custom token for user
     */
    public function createCustomToken($uid)
    {
        try {
            $customToken = $this->auth->createCustomToken($uid);
            return [
                'success' => true,
                'token' => $customToken->toString(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}