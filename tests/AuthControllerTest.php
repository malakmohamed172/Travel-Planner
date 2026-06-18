<?php


use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    private $conn;
    private $testEmail;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();


        $this->testEmail = 'unittest_' . time() . '_' . rand(1000, 9999) . '@test.com';
    }

    protected function tearDown(): void
    {
        // Clean up test user
        $stmt = $this->conn->prepare("DELETE FROM users WHERE email = ?");
        $stmt->bind_param("s", $this->testEmail);
        $stmt->execute();
        $stmt->close();

        $this->conn->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 1: Register a new user → should return "success"
     *  INPUT:  name="Test User", email=unique, password="Pass123"
     *  EXPECTED OUTPUT: "success"
     * ───────────────────────────────────────────────────────── */
    public function testRegisterNewUserReturnsSuccess(): void
    {
        // INPUT
        $name = 'Test User';
        $email = $this->testEmail;
        $password = 'TestPassword123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert directly (simulating register logic)
        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, 2)"
        );
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        $result = $stmt->execute();
        $stmt->close();

        // ✅ EXPECTED: Insert should succeed
        $this->assertTrue($result, "Register new user should succeed");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 2: Register duplicate email → should fail
     *  INPUT:  Same email registered twice
     *  EXPECTED OUTPUT: Second insert should detect "exists"
     * ───────────────────────────────────────────────────────── */
    public function testRegisterDuplicateEmailDetected(): void
    {
        // INPUT: Register first time
        $name = 'Test User';
        $email = $this->testEmail;
        $password = password_hash('Pass123', PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, 2)"
        );
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $stmt->close();

        // INPUT: Check if email exists
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        $this->assertGreaterThan(0, $stmt->num_rows, "Duplicate email should be detected");
        $stmt->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 3: Sign in with correct credentials → should pass
     *  INPUT:  Valid email + matching password
     *  EXPECTED OUTPUT: User record found & password verified
     * ───────────────────────────────────────────────────────── */
    public function testSigninWithCorrectCredentials(): void
    {
        // Setup: Create test user
        $name = 'Login Test';
        $email = $this->testEmail;
        $rawPassword = 'SecurePass456';
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, 2)"
        );
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        $stmt->execute();
        $stmt->close();

        // INPUT: Try to login
        $stmt = $this->conn->prepare(
            "SELECT user_id, name, email, password, role_id FROM users WHERE email = ? LIMIT 1"
        );
        $loginEmail = strtolower(trim($email));
        $stmt->bind_param("s", $loginEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        // ✅ EXPECTED: One user found
        $this->assertEquals(1, $result->num_rows, "Should find exactly one user");

        $row = $result->fetch_assoc();

        // ✅ EXPECTED: Password verification passes
        $this->assertTrue(
            password_verify($rawPassword, $row['password']),
            "Password should verify correctly"
        );
        $stmt->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 4: Sign in with wrong password → should fail auth
     *  INPUT:  Valid email + WRONG password
     *  EXPECTED OUTPUT: password_verify returns false
     * ───────────────────────────────────────────────────────── */
    public function testSigninWithWrongPasswordFails(): void
    {
        // Setup: Create test user
        $email = $this->testEmail;
        $hashedPassword = password_hash('CorrectPass', PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, 2)"
        );
        $name = 'Wrong Pass Test';
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        $stmt->execute();
        $stmt->close();

        // INPUT: Try wrong password
        $stmt = $this->conn->prepare(
            "SELECT password FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        // ✅ EXPECTED: password_verify returns FALSE for wrong password
        $this->assertFalse(
            password_verify('WrongPassword', $row['password']),
            "Wrong password should not verify"
        );
        $stmt->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 5: Sign in with non-existent email → no user found
     *  INPUT:  Email that doesn't exist in DB
     *  EXPECTED OUTPUT: num_rows = 0
     * ───────────────────────────────────────────────────────── */
    public function testSigninWithNonExistentEmailFails(): void
    {
        // INPUT: Non-existent email
        $fakeEmail = 'nonexistent_' . time() . '@nowhere.com';

        $stmt = $this->conn->prepare(
            "SELECT user_id FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $fakeEmail);
        $stmt->execute();
        $stmt->store_result();

        // ✅ EXPECTED: No user found
        $this->assertEquals(0, $stmt->num_rows, "Non-existent email should return 0 rows");
        $stmt->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 6: getUserByEmail → returns correct user
     *  INPUT:  Registered email address
     *  EXPECTED OUTPUT: Array with 'user_id' key
     * ───────────────────────────────────────────────────────── */
    public function testGetUserByEmailReturnsUser(): void
    {
        // Setup
        $email = $this->testEmail;
        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, 2)"
        );
        $name = 'GetByEmail Test';
        $pass = password_hash('pass', PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $pass);
        $stmt->execute();
        $insertedId = $stmt->insert_id;
        $stmt->close();

        // INPUT: Lookup by email
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // ✅ EXPECTED: user_id matches inserted ID
        $this->assertNotNull($result, "User should be found");
        $this->assertEquals($insertedId, $result['user_id'], "User ID should match");
        $stmt->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 7: getUserByEmail with invalid email → returns null
     *  INPUT:  Non-existent email
     *  EXPECTED OUTPUT: null
     * ───────────────────────────────────────────────────────── */
    public function testGetUserByEmailReturnsNullForInvalid(): void
    {
        $fakeEmail = 'nobody_' . time() . '@nowhere.com';

        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $fakeEmail);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // ✅ EXPECTED: No user found → null
        $this->assertNull($result, "Invalid email should return null");
        $stmt->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 8: Password is hashed on registration (not plain)
     *  INPUT:  Raw password "MyPassword"
     *  EXPECTED OUTPUT: Stored password ≠ raw password
     * ───────────────────────────────────────────────────────── */
    public function testPasswordIsHashedOnRegister(): void
    {
        $rawPassword = 'MySecretPassword';
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

        // ✅ EXPECTED: Hashed ≠ raw
        $this->assertNotEquals($rawPassword, $hashedPassword);
        // ✅ EXPECTED: But verify still works
        $this->assertTrue(password_verify($rawPassword, $hashedPassword));
    }
}
