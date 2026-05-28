<?php
// ============================================================
//  classes/User.php
//
//  Handles all database operations related to the users table.
//  Extends BaseModel so it inherits getAll(), delete(), find().
// ============================================================

class User extends BaseModel {
    protected $table = "users";

    // Find a user by username (used for login)
    public function findByUsername($username) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    // Update profile fields for a given user ID
    public function update($id, $data) {
        // already handled by BaseModel's update()
        return parent::update($id, $data);
    }

    // Create a new user record
    public function create($data) {
        return $this->add($data);
    }
}
